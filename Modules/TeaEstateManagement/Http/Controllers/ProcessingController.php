<?php

namespace Modules\TeaEstateManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TeaEstateManagement\Services\AuditService;
use Modules\TeaEstateManagement\Services\InventoryService;
use Modules\TeaEstateManagement\Services\NumberingService;
use Modules\TeaEstateManagement\Services\TeaFinanceBridgeService;

class ProcessingController extends BaseTeaController
{
    public function index(TeaFinanceBridgeService $finance)
    {
        $d=$this->common(); $b=$this->businessId();
        $d['batches']=$d['installed']?$this->scopeLocations(DB::table('tea_processing_batches as b')->leftJoin('tea_factories as f','f.id','=','b.factory_id')->where('b.business_id',$b),'b.location_id')->select('b.*','f.name as factory_name')->orderByDesc('b.process_date')->orderByDesc('b.id')->limit(250)->get():collect();
        $d['factories']=$d['installed']?$this->scopeLocations(DB::table('tea_factories')->where('business_id',$b))->where('status','active')->get():collect();
        $d['lots']=$d['installed']?$this->scopeLocations(DB::table('tea_inventory_lots')->where('business_id',$b))->where('item_type','green_leaf')->where('status','available')->where('current_qty_kg','>',0)->get():collect();
        $d['grades']=$d['installed']?DB::table('tea_grades')->where('business_id',$b)->where('status','active')->pluck('name','id'):collect();
        $d['stages']=$d['installed']?DB::table('tea_processing_stages')->where('business_id',$b)->where('status','active')->orderBy('sequence_no')->get():collect();
        $d['financeAccounts']=$finance->accountOptions();
        return view('teaestate::processing.index',$d);
    }

    public function store(Request $r, NumberingService $numbers, InventoryService $inventory, AuditService $audit)
    {
        $r->validate(['location_id'=>'nullable','factory_id'=>'required|integer','inventory_lot_id'=>'required|integer','input_qty_kg'=>'required|numeric|min:0.001','process_date'=>'required|date']);
        $loc=$this->locations->resolveRequired($r->location_id); $b=$this->businessId();
        $factory=DB::table('tea_factories')->where('business_id',$b)->where('location_id',$loc)->where('id',$r->factory_id)->where('status','active')->first();
        abort_if(!$factory,422,'Invalid factory for this Location.');

        [, $no] = DB::transaction(function () use ($r,$numbers,$inventory,$audit,$b,$loc,$factory) {
            $no=$numbers->next('processing_batch','TEA-BAT');
            $id=DB::table('tea_processing_batches')->insertGetId([
                'business_id'=>$b,'location_id'=>$loc,'factory_id'=>$factory->id,'batch_no'=>$no,'process_date'=>$r->process_date,
                'input_qty_kg'=>(float)$r->input_qty_kg,'status'=>'in_progress','notes'=>$r->notes,'created_by'=>$this->context->userId(),
                'created_at'=>now(),'updated_at'=>now()
            ]);
            $issue=$inventory->issue((int)$r->inventory_lot_id,$loc,(float)$r->input_qty_kg,'processing_batch',$id,$no);
            DB::table('tea_batch_inputs')->insert([
                'business_id'=>$b,'processing_batch_id'=>$id,'inventory_lot_id'=>$r->inventory_lot_id,'source_type'=>'inventory_lot',
                'source_id'=>$r->inventory_lot_id,'qty_kg'=>(float)$r->input_qty_kg,'unit_cost'=>$issue['unit_cost'],'created_at'=>now(),'updated_at'=>now()
            ]);
            $audit->log('create','processing_batch',$id);
            return [$id,$no];
        });
        return back()->with('tea_success','Processing batch '.$no.' opened.');
    }

    public function stage(Request $r, TeaFinanceBridgeService $finance, AuditService $audit)
    {
        $r->validate(['batch_id'=>'required|integer','stage_id'=>'required|integer','cost_amount'=>'nullable|numeric|min:0']);
        $b=$this->businessId();
        $batch=DB::table('tea_processing_batches')->where('business_id',$b)->where('id',$r->batch_id)->first();
        abort_if(!$batch||$batch->status!=='in_progress',422,'Batch is not open.');
        $this->locations->resolveRequired($batch->location_id);
        abort_if(!DB::table('tea_processing_stages')->where('business_id',$b)->where('id',$r->stage_id)->where('status','active')->exists(),422,'Invalid processing stage.');
        $cost=(float)($r->cost_amount??0);
        if($cost>0 && $finance->available()) abort_if(!$finance->isValidAccount((int)$r->finance_account_id),422,'Please select a valid Finance Cash/Bank Account for the processing cost.');

        DB::transaction(function () use ($r,$finance,$audit,$b,$batch,$cost) {
            $locked=DB::table('tea_processing_batches')->where('business_id',$b)->where('id',$batch->id)->lockForUpdate()->first();
            abort_if(!$locked||$locked->status!=='in_progress',422,'Batch is not open.');
            $id=DB::table('tea_processing_stage_entries')->insertGetId([
                'business_id'=>$b,'location_id'=>$locked->location_id,'processing_batch_id'=>$locked->id,'stage_id'=>$r->stage_id,
                'sequence_no'=>(int)($r->sequence_no??1),'started_at'=>$r->started_at,'completed_at'=>$r->completed_at,
                'input_qty_kg'=>$r->input_qty_kg,'output_qty_kg'=>$r->output_qty_kg,'temperature_c'=>$r->temperature_c,
                'moisture_percent'=>$r->moisture_percent,'cost_amount'=>$cost,'finance_account_id'=>$r->finance_account_id,
                'operator_user_id'=>$this->context->userId(),'notes'=>$r->notes,'created_at'=>now(),'updated_at'=>now()
            ]);
            if($cost>0) $finance->queue('processing_cost','processing_stage',$id,(int)$locked->location_id,$locked->batch_no.'/STG-'.$id,$r->completed_at?:($r->started_at?:now()),$cost,['finance_account_id'=>(int)$r->finance_account_id,'batch_id'=>(int)$locked->id]);
            $audit->log('create','processing_stage_entry',$id);
        });
        return back()->with('tea_success','Processing stage recorded.');
    }

    public function finalize(Request $r, InventoryService $inventory, TeaFinanceBridgeService $finance, AuditService $audit)
    {
        $r->validate(['batch_id'=>'required|integer','grade_id'=>'required|integer','output_qty_kg'=>'required|numeric|min:0.001']);
        $b=$this->businessId();
        abort_if(!DB::table('tea_grades')->where('business_id',$b)->where('id',$r->grade_id)->where('status','active')->exists(),422,'Invalid tea grade.');

        $batchNo = DB::transaction(function () use ($r,$inventory,$finance,$audit,$b) {
            $batch=DB::table('tea_processing_batches')->where('business_id',$b)->where('id',$r->batch_id)->lockForUpdate()->first();
            abort_if(!$batch||$batch->status!=='in_progress',422,'Batch is not open.');
            $this->locations->resolveRequired($batch->location_id);

            $leafCost=(float)DB::table('tea_batch_inputs')->where('business_id',$b)->where('processing_batch_id',$batch->id)->selectRaw('SUM(qty_kg * unit_cost) as total')->value('total');
            $processingCost=(float)DB::table('tea_processing_stage_entries')->where('business_id',$b)->where('processing_batch_id',$batch->id)->sum('cost_amount');
            $totalCost=$leafCost+$processingCost;
            $out=(float)$r->output_qty_kg;
            abort_if($out>(float)$batch->input_qty_kg+0.005,422,'Output cannot exceed input quantity.');
            $unit=$out>0?$totalCost/$out:0;
            $lot=$inventory->createLot((int)$batch->location_id,'made_tea',$out,$unit,[
                'grade_id'=>(int)$r->grade_id,'processing_batch_id'=>(int)$batch->id,'source_type'=>'processing_batch','source_id'=>(int)$batch->id,'notes'=>$batch->batch_no
            ]);
            DB::table('tea_batch_outputs')->insert([
                'business_id'=>$b,'location_id'=>$batch->location_id,'processing_batch_id'=>$batch->id,'grade_id'=>$r->grade_id,
                'item_type'=>'made_tea','qty_kg'=>$out,'unit_cost'=>$unit,'inventory_lot_id'=>$lot,'notes'=>$r->notes,'created_at'=>now(),'updated_at'=>now()
            ]);
            $loss=max(0,(float)$batch->input_qty_kg-$out);
            $yield=(float)$batch->input_qty_kg>0?($out/(float)$batch->input_qty_kg)*100:0;
            DB::table('tea_processing_batches')->where('id',$batch->id)->update([
                'output_qty_kg'=>$out,'process_loss_qty_kg'=>$loss,'yield_percent'=>$yield,'status'=>'completed','completed_at'=>now(),'updated_at'=>now()
            ]);
            $finance->queue('processing_transfer','processing_batch',(int)$batch->id,(int)$batch->location_id,$batch->batch_no,$batch->process_date,$totalCost,[
                'leaf_cost'=>$leafCost,'processing_cost'=>$processingCost,'output_qty_kg'=>$out,'loss_qty_kg'=>$loss
            ]);
            $audit->log('finalize','processing_batch',(int)$batch->id);
            return $batch->batch_no;
        });
        return back()->with('tea_success','Batch '.$batchNo.' finalized and made-tea inventory created.');
    }
}
