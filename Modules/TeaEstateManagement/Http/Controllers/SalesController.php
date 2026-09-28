<?php

namespace Modules\TeaEstateManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TeaEstateManagement\Services\AuditService;
use Modules\TeaEstateManagement\Services\InventoryService;
use Modules\TeaEstateManagement\Services\NumberingService;
use Modules\TeaEstateManagement\Services\TeaFinanceBridgeService;

class SalesController extends BaseTeaController
{
    public function index(TeaFinanceBridgeService $finance)
    {
        $d = $this->common(); $b = $this->businessId();
        $d['sales'] = $d['installed'] ? $this->scopeLocations(DB::table('tea_sales as s')->leftJoin('tea_parties as b','b.id','=','s.buyer_id')->where('s.business_id',$b),'s.location_id')->select('s.*','b.name as buyer_name')->orderByDesc('s.sale_date')->orderByDesc('s.id')->limit(300)->get() : collect();
        $d['buyers'] = $d['installed'] ? DB::table('tea_parties')->where('business_id',$b)->whereIn('party_type',['buyer','both'])->where('status','active')->pluck('name','id') : collect();
        $d['lots'] = $d['installed'] ? $this->scopeLocations(DB::table('tea_inventory_lots as l')->leftJoin('tea_grades as g','g.id','=','l.grade_id')->where('l.business_id',$b),'l.location_id')->whereIn('l.item_type',['made_tea','packed_tea'])->where('l.status','available')->where('l.current_qty_kg','>',0)->select('l.*','g.name as grade_name')->get() : collect();
        $d['financeAccounts'] = $finance->accountOptions();
        return view('teaestate::sales.index',$d);
    }

    public function store(Request $r, NumberingService $numbers, InventoryService $inventory, TeaFinanceBridgeService $finance, AuditService $audit)
    {
        $r->validate(['location_id'=>'nullable','sale_date'=>'required|date','buyer_id'=>'required|integer','inventory_lot_id'=>'required|integer','qty_kg'=>'required|numeric|min:0.001','unit_price'=>'required|numeric|min:0']);
        $loc = $this->locations->resolveRequired($r->location_id); $b = $this->businessId();
        abort_if(!DB::table('tea_parties')->where('business_id',$b)->where('id',$r->buyer_id)->whereIn('party_type',['buyer','both'])->where('status','active')->exists(),422,'Invalid buyer.');
        $qty=(float)$r->qty_kg; $price=(float)$r->unit_price; $subtotal=$qty*$price; $discount=(float)($r->discount_amount??0); $tax=(float)($r->tax_amount??0);
        abort_if($discount > $subtotal + 0.005, 422, 'Discount cannot exceed sale subtotal.');
        $total=max(0,$subtotal-$discount+$tax);

        [, $no] = DB::transaction(function () use ($r,$numbers,$inventory,$finance,$audit,$b,$loc,$qty,$price,$subtotal,$discount,$tax,$total) {
            $no=$numbers->next('sale','TEA-SAL');
            $id=DB::table('tea_sales')->insertGetId([
                'business_id'=>$b,'location_id'=>$loc,'sale_no'=>$no,'sale_date'=>$r->sale_date,'buyer_id'=>$r->buyer_id,
                'invoice_ref'=>$r->invoice_ref,'currency_code'=>$r->currency_code?:'LKR','total_qty_kg'=>$qty,'subtotal'=>$subtotal,
                'discount_amount'=>$discount,'tax_amount'=>$tax,'total_amount'=>$total,'payment_status'=>'unpaid','status'=>'posted',
                'notes'=>$r->notes,'created_by'=>$this->context->userId(),'created_at'=>now(),'updated_at'=>now()
            ]);
            $issue=$inventory->issue((int)$r->inventory_lot_id,$loc,$qty,'tea_sale',$id,$no);
            $lot=DB::table('tea_inventory_lots')->where('business_id',$b)->where('location_id',$loc)->where('id',$r->inventory_lot_id)->first();
            abort_if(!$lot,422,'Invalid tea inventory lot.');
            DB::table('tea_sale_lines')->insert([
                'business_id'=>$b,'sale_id'=>$id,'inventory_lot_id'=>$r->inventory_lot_id,'grade_id'=>$lot->grade_id,
                'description'=>$r->description,'qty_kg'=>$qty,'unit_price'=>$price,'amount'=>$subtotal,'created_at'=>now(),'updated_at'=>now()
            ]);
            $finance->queue('tea_sale','tea_sale',$id,$loc,$no,$r->sale_date,$total,['tax_amount'=>$tax,'discount_amount'=>$discount]);
            $finance->queue('tea_cogs','tea_sale',$id,$loc,$no.'/COGS',$r->sale_date,$issue['cost'],['qty_kg'=>$qty]);
            $audit->log('create','sale',$id);
            return [$id,$no];
        });

        return back()->with('tea_success','Tea sale '.$no.' recorded.');
    }

    public function receive(Request $r, TeaFinanceBridgeService $finance, AuditService $audit)
    {
        $r->validate(['sale_id'=>'required|integer','amount'=>'required|numeric|min:0.01','received_on'=>'required|date','method'=>'required|string|max:30']);
        $b=$this->businessId(); $sale=DB::table('tea_sales')->where('business_id',$b)->where('id',$r->sale_id)->first(); abort_if(!$sale,404);
        $this->locations->resolveRequired($sale->location_id);
        if($finance->available()) abort_if(!$finance->isValidAccount((int)$r->finance_account_id),422,'Please select a valid Finance Cash/Bank Account.');

        DB::transaction(function () use ($r,$finance,$audit,$b,$sale) {
            $locked=DB::table('tea_sales')->where('business_id',$b)->where('id',$sale->id)->lockForUpdate()->first(); abort_if(!$locked,404);
            $received=(float)DB::table('tea_sale_receipts')->where('business_id',$b)->where('sale_id',$locked->id)->sum('amount');
            $due=max(0,(float)$locked->total_amount-$received); $amt=(float)$r->amount; abort_if($amt>$due+0.005,422,'Receipt exceeds amount due.');
            $id=DB::table('tea_sale_receipts')->insertGetId([
                'business_id'=>$b,'location_id'=>$locked->location_id,'sale_id'=>$locked->id,'received_on'=>$r->received_on,
                'method'=>$r->method,'reference_no'=>$r->reference_no,'amount'=>$amt,'finance_account_id'=>$r->finance_account_id,
                'note'=>$r->note,'created_by'=>$this->context->userId(),'created_at'=>now(),'updated_at'=>now()
            ]);
            $new=$received+$amt; $status=$new+0.005>=(float)$locked->total_amount?'paid':($new>0?'partial':'unpaid');
            DB::table('tea_sales')->where('id',$locked->id)->update(['payment_status'=>$status,'updated_at'=>now()]);
            $finance->queue('sale_receipt','sale_receipt',$id,(int)$locked->location_id,$locked->sale_no.'/RCT-'.$id,$r->received_on,$amt,['finance_account_id'=>(int)$r->finance_account_id,'sale_id'=>(int)$locked->id]);
            $audit->log('create','sale_receipt',$id);
        });
        return back()->with('tea_success','Tea sale receipt recorded.');
    }
}
