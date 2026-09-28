<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceEstimate;
use Modules\AutoService\Entities\AutoServiceVehicle;
use Modules\AutoService\Services\AutoServiceEstimateService;
use Modules\AutoService\Services\AutoServiceEstimateToJobService;
use Modules\AutoService\Services\ProductPartsAdapter;
use Modules\AutoService\Services\SharedCustomerAdapter;

class EstimateController extends AutoServiceBaseController
{
    public function index()
    {
        $q = AutoServiceEstimate::query();
        if ($this->businessId()) $q->where('business_id', $this->businessId());
        return view('autoservice::estimates.index', ['estimates' => $q->orderByDesc('id')->paginate(25)]);
    }

    public function create()
    {
        return view('autoservice::estimates.form', $this->formData(new AutoServiceEstimate()));
    }

    public function store(Request $request)
    {
        app(AutoServiceEstimateService::class)->saveEstimate($this->estimateData($request), $request->input('lines', []));
        return redirect()->route('autoservice.estimates.index')->with('status','Estimate saved successfully.');
    }

    public function edit($id)
    {
        return view('autoservice::estimates.form', $this->formData(AutoServiceEstimate::with('lines')->findOrFail($id)));
    }

    public function update(Request $request, $id)
    {
        $data = $this->estimateData($request); $data['id'] = $id;
        app(AutoServiceEstimateService::class)->saveEstimate($data, $request->input('lines', []));
        return redirect()->route('autoservice.estimates.index')->with('status','Estimate updated successfully.');
    }

    public function show($id)
    {
        return view('autoservice::estimates.show', ['estimate' => AutoServiceEstimate::with('lines')->findOrFail($id)]);
    }

    public function approve($id)
    {
        $estimate = AutoServiceEstimate::findOrFail($id);
        $estimate->status = 'approved';
        $estimate->approved_at = now();
        $estimate->approved_by = auth()->id();
        $estimate->save();
        return back()->with('status','Estimate approved successfully.');
    }


    public function convertToJob($id)
    {
        $job = app(AutoServiceEstimateToJobService::class)->convert((int) $id, auth()->id());
        return redirect()->route('autoservice.jobs.show', $job->id)->with('status', 'Estimate converted to job card successfully.');
    }

    private function formData($estimate)
    {
        return [
            'estimate' => $estimate,
            'vehicles' => AutoServiceVehicle::where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get(),
            'customers' => app(SharedCustomerAdapter::class)->list($this->businessId()),
            'products' => app(ProductPartsAdapter::class)->list($this->businessId()),
        ];
    }

    private function estimateData(Request $request)
    {
        $data = $request->only(['contact_id','vehicle_id','job_id','estimate_date','valid_until','status','customer_complaint','advisor_notes','discount_amount','tax_amount']);
        $data['business_id'] = $this->businessId();
        $data['location_id'] = $this->locationId();
        return $data;
    }
}
