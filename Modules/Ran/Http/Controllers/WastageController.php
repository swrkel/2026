<?php
namespace Modules\Ran\Http\Controllers;
use Illuminate\Http\Request;use Modules\Ran\Entities\ProductionOrder;use Modules\Ran\Services\ProductionService;
class WastageController extends RanController {public function store(Request $request,ProductionOrder $production,ProductionService $service){$data=$request->validate(['entry_date'=>'required|date','entry_type'=>'required|in:wastage,recovery,scrap','item_id'=>'nullable|integer','material_issue_line_id'=>'nullable|integer','weight'=>'required|numeric|min:0','fine_weight'=>'nullable|numeric|min:0','value'=>'nullable|numeric|min:0','reason'=>'nullable|string|max:2000']);$service->wastage($production,$data);return back()->with('status','Wastage/recovery entry saved.');}}
