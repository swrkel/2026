<?php
namespace Modules\TeaEstateManagement\Http\Controllers;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseTeaController
{
    public function index()
    {
        $d=$this->common(); $d['metrics']=['estates'=>0,'green_leaf'=>0,'made_tea'=>0,'sales'=>0,'finance_pending'=>0];
        if($d['installed']){
            $b=$this->businessId();
            $d['metrics']['estates']=$this->scopeLocations(DB::table('tea_estates')->where('business_id',$b))->count();
            $d['metrics']['green_leaf']=(float)$this->scopeLocations(DB::table('tea_inventory_lots')->where('business_id',$b))->where('item_type','green_leaf')->sum('current_qty_kg');
            $d['metrics']['made_tea']=(float)$this->scopeLocations(DB::table('tea_inventory_lots')->where('business_id',$b))->whereIn('item_type',['made_tea','packed_tea'])->sum('current_qty_kg');
            $d['metrics']['sales']=(float)$this->scopeLocations(DB::table('tea_sales')->where('business_id',$b))->sum('total_amount');
            $d['metrics']['finance_pending']=DB::table('tea_finance_events')->where('business_id',$b)->whereIn('status',['pending','error'])->count();
        }
        return view('teaestate::dashboard',$d);
    }
}
