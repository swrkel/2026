<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Models\DisnewCollection;
use Modules\DistributionNew\Services\Collections\DisnewCollectionService;

class CollectionController extends Controller
{
    public function index(Request $request, DisnewCollectionService $service)
    {
        $businessId = (int) session('business.id');
        $collections = $service->listForBusiness($businessId, $request->all())->paginate(25);
        return view('distributionnew::collections.index', compact('collections'));
    }

    public function create(){ return view('distributionnew::collections.create'); }

    public function store(Request $request, DisnewCollectionService $service)
    {
        $data = $request->validate([
            'business_location_id'=>'nullable|integer','sales_rep_id'=>'nullable|integer','customer_id'=>'nullable|integer',
            'sales_order_id'=>'nullable|integer','sales_invoice_id'=>'nullable|integer','collection_date'=>'required|date',
            'payment_method'=>'required|string|max:50','reference_no'=>'nullable|string|max:100','amount'=>'required|numeric|min:0','note'=>'nullable|string'
        ]);
        $data['business_id'] = (int) session('business.id');
        $data['created_by'] = auth()->id();
        $service->store($data);
        return redirect()->route('distribution-new.collections.index')->with('status', __('distributionnew::messages.saved_successfully'));
    }

    public function confirm(DisnewCollection $collection, DisnewCollectionService $service)
    {
        $service->confirm($collection, (int) auth()->id());
        return back()->with('status', __('distributionnew::messages.confirmed_successfully'));
    }
}
