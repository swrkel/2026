<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewSalesTarget;

class SalesTargetController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $records = DisnewSalesTarget::where('business_id', $businessId)->latest('id')->paginate(25);
        return view('distributionnew::sales_targets.index', compact('records'));
    }
    public function create()
    {
        return view('distributionnew::sales_targets.create');
    }
    public function store(Request $request)
    {
        $data = $request->except(['_token']);
        $data['business_id'] = $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        DisnewSalesTarget::create($data);
        return redirect()->route('distribution-new.sales_targets.index')->with('status', ['success' => 1, 'msg' => __('distributionnew::lang.saved_successfully')]);
    }
    public function edit($id, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $record = DisnewSalesTarget::where('business_id', $businessId)->findOrFail($id);
        return view('distributionnew::sales_targets.edit', compact('record'));
    }
    public function update($id, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $record = DisnewSalesTarget::where('business_id', $businessId)->findOrFail($id);
        $data = $request->except(['_token','_method']); $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distribution-new.sales_targets.index')->with('status', ['success' => 1, 'msg' => __('distributionnew::lang.updated_successfully')]);
    }
}
