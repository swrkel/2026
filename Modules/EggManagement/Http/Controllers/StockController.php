<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Models\StockLot;use Modules\EggManagement\Models\Grade;
class StockController extends BaseController
{
    public function index(Request $r){$q=$this->scope(StockLot::query())->where('available_pieces','>',0);if($r->filled('grade_id'))$q->where('grade_id',$r->grade_id);return view('egg::stock.index',['rows'=>$q->latest('collection_date')->paginate(100),'grades'=>$this->scope(Grade::query())->where('active',1)->orderBy('sort_order')->get()]);}
}
