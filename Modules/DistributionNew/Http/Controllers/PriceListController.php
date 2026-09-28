<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewPriceList;

class PriceListController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $records = DisnewPriceList::where('business_id', $businessId)->latest('id')->paginate(25);
        return view('distributionnew::price_lists.index', compact('records'));
    }
    public function create()
    {
        return view('distributionnew::price_lists.create');
    }
    public function store(Request $request)
    {
        $data = $request->except(['_token']);
        $data['business_id'] = $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        DisnewPriceList::create($data);
        return redirect()->route('distribution-new.price_lists.index')->with('status', ['success' => 1, 'msg' => __('distributionnew::lang.saved_successfully')]);
    }
    public function edit($id, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $record = DisnewPriceList::where('business_id', $businessId)->findOrFail($id);
        return view('distributionnew::price_lists.edit', compact('record'));
    }
    public function update($id, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $record = DisnewPriceList::where('business_id', $businessId)->findOrFail($id);
        $data = $request->except(['_token','_method']); $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distribution-new.price_lists.index')->with('status', ['success' => 1, 'msg' => __('distributionnew::lang.updated_successfully')]);
    }
}
