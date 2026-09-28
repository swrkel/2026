<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Models\{RiceProduct,FinishedStockMovement};

class FinishedStockController extends BaseController
{
    public function index(Request $request)
    {
        $businessId=$this->bid();
        $query=RiceProduct::forBusiness($businessId)->select(['id','business_id','code','name','rice_type','current_qty']);
        $activity=FinishedStockMovement::forBusiness($businessId)->select('product_id')->distinct();
        $this->listTools()->applyDate($activity,$request,$businessId,'movement_date');
        $query->whereIn('id',$activity);
        $this->listTools()->applySearch($query,$request,['code','name','rice_type','current_qty']);
        $products=$query->orderBy('name')->paginate($this->listPerPage($request,25))->appends($request->query());
        return view('RiceMill::stock.finished',compact('products'));
    }

    public function ledger(Request $request,$id)
    {
        $b=$this->bid();
        $p=RiceProduct::forBusiness($b)->findOrFail($id);
        $rows=FinishedStockMovement::forBusiness($b)
            ->where('product_id',$id)
            ->orderBy('id')
            ->select(['id','business_id','movement_date','movement_type','quantity','signed_quantity','reference_type','reference_id','note']);
        $this->applyListFilters($rows,$request,['movement_type','reference_type','reference_id','note','quantity','signed_quantity'],'movement_date');
        $rows=$rows->paginate($this->listPerPage($request,50))->appends($request->query());
        return view('RiceMill::stock.finished-ledger',compact('p','rows'));
    }

    public function adjust(Request $r,$id)
    {
        $d=$r->validate(['direction'=>'required|in:in,out','quantity'=>'required|numeric|min:0.001','note'=>'required|max:500']);
        $b=$this->bid();
        DB::transaction(function() use($b,$id,$d){
            $p=RiceProduct::forBusiness($b)->lockForUpdate()->findOrFail($id);
            $sign=$d['direction']==='in'?1:-1;
            $new=(float)$p->current_qty+$sign*(float)$d['quantity'];
            if($new<-0.0005) throw new \RuntimeException('Insufficient finished rice stock.');
            $p->update(['current_qty'=>$new]);
            FinishedStockMovement::create([
                'business_id'=>$b,'product_id'=>$p->id,'movement_date'=>today(),
                'movement_type'=>$sign>0?'adjustment_in':'adjustment_out','quantity'=>$d['quantity'],
                'signed_quantity'=>$sign*(float)$d['quantity'],'note'=>$d['note'],'created_by'=>$this->uid()
            ]);
        });
        return back()->with('status','Finished stock adjusted.');
    }
}
