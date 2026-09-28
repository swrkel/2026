<?php

namespace Modules\DistributionNew\Http\Controllers\CustomerPortal;

use Illuminate\Routing\Controller;

class CustomerDeliveryTrackingController extends Controller
{
    public function index()
    {
        return view('distributionnew::customer_portal.deliveries.index');
    }

    public function create()
    {
        return view('distributionnew::customer_portal.forms.create');
    }

    public function store()
    {
        return back()->with('status', __('distributionnew::customer_portal.saved'));
    }

    public function show($id)
    {
        return view('distributionnew::customer_portal.forms.show', compact('id'));
    }

    public function download($invoice)
    {
        abort(404, 'Invoice download adapter to be connected with Distribution New invoice PDF service.');
    }
}
