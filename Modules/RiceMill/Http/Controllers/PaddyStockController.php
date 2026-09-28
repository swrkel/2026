<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Modules\RiceMill\Models\{PaddyLot,PaddyStockMovement};
use Modules\RiceMill\Services\PaddyStockService;
use Modules\RiceMill\Services\TenantContext;

class PaddyStockController extends BaseController
{
    public function __construct(TenantContext $context,private PaddyStockService $stock){parent::__construct($context);}

    public function index(Request $request)
    {
        $b = $this->bid();
        $query = PaddyLot::forBusiness($b)
            ->leftJoin('rcm_paddy_varieties as pv', function ($join) use ($b) {
                $join->on('pv.id', '=', 'rcm_paddy_lots.paddy_variety_id')
                    ->where('pv.business_id', '=', $b);
            })
            ->where('rcm_paddy_lots.balance_qty','>',0)
            ->select([
                'rcm_paddy_lots.id','rcm_paddy_lots.business_id','rcm_paddy_lots.lot_no',
                'rcm_paddy_lots.received_date','rcm_paddy_lots.paddy_variety_id',
                'rcm_paddy_lots.quality_grade','rcm_paddy_lots.original_qty','rcm_paddy_lots.balance_qty',
                'pv.code as paddy_code','pv.name as paddy_name',
            ]);
        $this->applyListFilters($query,$request,[
            'rcm_paddy_lots.lot_no','pv.code','pv.name','rcm_paddy_lots.quality_grade',
            'rcm_paddy_lots.original_qty','rcm_paddy_lots.balance_qty'
        ],'rcm_paddy_lots.received_date');
        $lots=$query->orderBy('rcm_paddy_lots.received_date')->paginate($this->listPerPage($request,25))->appends($request->query());
        return view('RiceMill::stock.paddy',compact('lots'));
    }

    public function ledger(Request $request,$id)
    {
        $b=$this->bid();
        $lot=PaddyLot::forBusiness($b)->findOrFail($id);
        $rows=PaddyStockMovement::forBusiness($b)
            ->where('paddy_lot_id',$id)
            ->orderBy('id')
            ->select(['id','business_id','movement_date','movement_type','quantity','signed_quantity','reference_type','reference_id','note']);
        $this->applyListFilters($rows,$request,['movement_type','reference_type','reference_id','note','quantity','signed_quantity'],'movement_date');
        $rows=$rows->paginate($this->listPerPage($request,50))->appends($request->query());
        return view('RiceMill::stock.paddy-ledger',compact('lot','rows'));
    }

    public function adjust(Request $r,$id)
    {
        $d=$r->validate(['direction'=>'required|in:in,out','quantity'=>'required|numeric|min:0.001','note'=>'required|max:500']);
        $type=$d['direction']==='in'?'adjustment_in':'adjustment_out';
        $this->stock->move($this->bid(),(int)$id,$type,(float)$d['quantity'],['note'=>$d['note'],'created_by'=>$this->uid()]);
        return back()->with('status','Paddy stock adjusted.');
    }
}
