<?php
namespace Modules\TeaEstateManagement\Http\Controllers;
use Illuminate\Support\Facades\DB;
class InventoryController extends BaseTeaController
{
 public function index(){ $d=$this->common();$b=$this->businessId();$d['lots']=$d['installed']?$this->scopeLocations(DB::table('tea_inventory_lots as l')->leftJoin('tea_grades as g','g.id','=','l.grade_id')->where('l.business_id',$b),'l.location_id')->select('l.*','g.name as grade_name')->orderByDesc('l.id')->limit(500)->get():collect();$d['movements']=$d['installed']?$this->scopeLocations(DB::table('tea_stock_movements')->where('business_id',$b))->orderByDesc('movement_date')->limit(300)->get():collect();return view('teaestate::inventory.index',$d);}
}
