<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ExpensesNew\Entities\CostCenter;
use Modules\ExpensesNew\Utils\BusinessScope;

class CostCenterController extends Controller
{
    public function index(){ $rows = CostCenter::where('business_id', BusinessScope::businessId())->latest('id')->paginate(25); return view('expensesnew::cost_centers.index', compact('rows')); }
    public function store(Request $request){ CostCenter::updateOrCreate(['id'=>$request->id], ['business_id'=>BusinessScope::businessId(),'location_id'=>$request->location_id,'code'=>$request->code,'name'=>$request->name,'description'=>$request->description,'is_active'=>$request->boolean('is_active', true),'created_by'=>auth()->id()]); return back()->with('status', __('expensesnew::lang.saved_successfully')); }
    public function destroy($id){ CostCenter::where('business_id', BusinessScope::businessId())->findOrFail($id)->delete(); return back()->with('status', __('expensesnew::lang.deleted_successfully')); }
}
