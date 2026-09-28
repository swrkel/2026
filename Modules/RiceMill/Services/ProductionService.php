<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Models\{ProductionBatch,RiceProduct};

class ProductionService
{
    public function __construct(private NumberSeriesService $numbers,private PaddyStockService $paddyStock,private FinanceAccountPostingService $financeAccounts) {}

    public function create(int $businessId,int $userId,array $data): ProductionBatch
    {
        return ProductionBatch::create(array_merge($data,[
            'business_id'=>$businessId,
            'batch_no'=>$this->numbers->next($businessId,'production','RCB-'),
            'status'=>'draft',
            'created_by'=>$userId,
        ]));
    }

    public function complete(int $businessId,int $id,int $userId,array $inputs,array $outputs,array $costs=[],array $batchData=[]): ProductionBatch
    {
        return DB::transaction(function() use($businessId,$id,$userId,$inputs,$outputs,$costs,$batchData){
            $batch=ProductionBatch::forBusiness($businessId)->lockForUpdate()->findOrFail($id);
            if(!in_array($batch->status,['draft','in_progress'],true)) {
                throw new \RuntimeException('This production batch cannot be completed.');
            }

            if ($batchData) {
                $batch->update([
                    'location_id' => $batchData['location_id'] ?? null,
                    'store_id' => $batchData['store_id'] ?? null,
                    'mill_id' => $batchData['mill_id'] ?? null,
                    'started_at' => $batchData['started_at'] ?? null,
                    'note' => $batchData['note'] ?? null,
                ]);
            }

            $now=now();
            $today=$now->toDateString();
            $totalInput=0.0;
            $riceOutput=0.0;
            $totalOutput=0.0;
            $materialCost=0.0;

            // Load source rates for every selected lot with one query instead of
            // one purchase-line join per input row.
            $lotIds=array_values(array_unique(array_map(static fn($in)=>(int)$in['paddy_lot_id'],$inputs)));
            $rateRows=DB::table('rcm_paddy_lots as l')
                ->leftJoin('rcm_paddy_receipts as r','r.id','=','l.receipt_id')
                ->leftJoin('rcm_paddy_purchase_lines as pl',function($j){
                    $j->on('pl.purchase_id','=','r.purchase_id')
                      ->on('pl.paddy_variety_id','=','l.paddy_variety_id');
                })
                ->where('l.business_id',$businessId)
                ->whereIn('l.id',$lotIds)
                ->orderBy('l.id')
                ->orderBy('pl.id')
                ->get(['l.id as lot_id','pl.unit_rate']);
            $rates=[];
            foreach($rateRows as $rateRow){
                $lotId=(int)$rateRow->lot_id;
                if(!array_key_exists($lotId,$rates)){
                    $rates[$lotId]=(float)($rateRow->unit_rate??0);
                }
            }

            $inputRows=[];
            $stockMoves=[];
            foreach($inputs as $in){
                $lotId=(int)$in['paddy_lot_id'];
                $qty=(float)$in['quantity'];
                $totalInput+=$qty;
                $rate=(float)($rates[$lotId]??0);
                $materialCost+=$qty*$rate;
                $inputRows[]=[
                    'business_id'=>$businessId,
                    'production_batch_id'=>$batch->id,
                    'paddy_lot_id'=>$lotId,
                    'quantity'=>$qty,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ];
                $stockMoves[]=[
                    'lot_id'=>$lotId,
                    'type'=>'milling_issue',
                    'qty'=>$qty,
                    'meta'=>[
                        'location_id'=>$batch->location_id,
                        'store_id'=>$batch->store_id,
                        'reference_type'=>'production_batch',
                        'reference_id'=>$batch->id,
                        'created_by'=>$userId,
                        'movement_date'=>$today,
                    ],
                ];
            }
            if($inputRows){
                DB::table('rcm_production_inputs')->insert($inputRows);
            }
            $this->paddyStock->moveMany($businessId,$stockMoves);

            // Prepare outputs and aggregate Rice quantities per product. Products
            // are then locked in one query and movements are inserted in batches.
            $outputRows=[];
            $riceQtyByProduct=[];
            $byproductRows=[];
            foreach($outputs as $out){
                $qty=(float)$out['quantity'];
                if($qty<=0) continue;
                $totalOutput+=$qty;
                $kind=(string)$out['output_type'];
                $productId=!empty($out['product_id'])?(int)$out['product_id']:null;
                $outputRows[]=[
                    'business_id'=>$businessId,
                    'production_batch_id'=>$batch->id,
                    'output_type'=>$kind,
                    'product_id'=>$productId,
                    'quantity'=>$qty,
                    'unit_cost'=>0,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ];
                if($kind==='rice'){
                    abort_unless($productId,422,'Please select a Rice Product for every Rice output.');
                    $riceOutput+=$qty;
                    $riceQtyByProduct[$productId]=($riceQtyByProduct[$productId]??0)+$qty;
                }else{
                    $byproductRows[]=[
                        'business_id'=>$businessId,
                        'location_id'=>$batch->location_id,
                        'store_id'=>$batch->store_id,
                        'byproduct_type'=>$kind,
                        'movement_date'=>$today,
                        'movement_type'=>'production',
                        'quantity'=>$qty,
                        'signed_quantity'=>$qty,
                        'reference_type'=>'production_batch',
                        'reference_id'=>$batch->id,
                        'note'=>null,
                        'created_by'=>$userId,
                        'created_at'=>$now,
                        'updated_at'=>$now,
                    ];
                }
            }
            if($outputRows){
                DB::table('rcm_production_outputs')->insert($outputRows);
            }

            $finishedRows=[];
            if($riceQtyByProduct){
                $productIds=array_keys($riceQtyByProduct);
                $products=RiceProduct::forBusiness($businessId)
                    ->whereIn('id',$productIds)
                    ->where('active',1)
                    ->lockForUpdate()
                    ->get(['id','business_id','name','current_qty'])
                    ->keyBy('id');
                abort_unless($products->count()===count($productIds),422,'One of the selected Rice Products is not available.');

                foreach($riceQtyByProduct as $productId=>$qty){
                    $product=$products->get($productId);
                    $product->update(['current_qty'=>(float)$product->current_qty+$qty]);
                    $finishedRows[]=[
                        'business_id'=>$businessId,
                        'location_id'=>$batch->location_id,
                        'store_id'=>$batch->store_id,
                        'product_id'=>$product->id,
                        'movement_date'=>$today,
                        'movement_type'=>'production',
                        'quantity'=>$qty,
                        'signed_quantity'=>$qty,
                        'reference_type'=>'production_batch',
                        'reference_id'=>$batch->id,
                        'note'=>null,
                        'created_by'=>$userId,
                        'created_at'=>$now,
                        'updated_at'=>$now,
                    ];
                }
            }
            if($finishedRows){
                DB::table('rcm_finished_stock_movements')->insert($finishedRows);
            }
            if($byproductRows){
                DB::table('rcm_byproduct_movements')->insert($byproductRows);
            }

            $costTotal=$materialCost;
            $costRows=[];
            if($materialCost>0){
                $costRows[]=[
                    'business_id'=>$businessId,
                    'production_batch_id'=>$batch->id,
                    'cost_date'=>$today,
                    'cost_type'=>'Paddy Material',
                    'amount'=>$materialCost,
                    'note'=>'Calculated from source paddy purchase rate',
                    'created_by'=>$userId,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ];
            }
            foreach($costs as $c){
                $a=(float)($c['amount']??0);
                if($a<=0) continue;
                $costTotal+=$a;
                $costRows[]=[
                    'business_id'=>$businessId,
                    'production_batch_id'=>$batch->id,
                    'cost_date'=>$today,
                    'cost_type'=>(string)$c['cost_type'],
                    'amount'=>$a,
                    'note'=>$c['note']??null,
                    'created_by'=>$userId,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ];
            }
            if($costRows){
                DB::table('rcm_cost_entries')->insert($costRows);
            }

            $loss=max(0,$totalInput-$totalOutput);
            $yield=$totalInput>0?($riceOutput/$totalInput*100):0;
            $unitCost=$riceOutput>0?$costTotal/$riceOutput:0;
            if($riceOutput>0){
                DB::table('rcm_production_outputs')
                    ->where('business_id',$businessId)
                    ->where('production_batch_id',$batch->id)
                    ->where('output_type','rice')
                    ->update(['unit_cost'=>$unitCost,'updated_at'=>$now]);
            }

            $batch->update([
                'status'=>'completed',
                'input_qty'=>$totalInput,
                'rice_output_qty'=>$riceOutput,
                'total_output_qty'=>$totalOutput,
                'process_loss_qty'=>$loss,
                'rice_yield_percent'=>$yield,
                'production_cost'=>$costTotal,
                'cost_per_kg'=>$unitCost,
                'completed_at'=>$now,
                'completed_by'=>$userId,
            ]);

            $batch = $batch->fresh();
            $this->financeAccounts->syncProduction($businessId,$userId,$batch);
            return $batch;
        });
    }
}
