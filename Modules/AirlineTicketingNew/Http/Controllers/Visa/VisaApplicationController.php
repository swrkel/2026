<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Visa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\VisaApplication;
use Modules\AirlineTicketingNew\Services\Visa\VisaApplicationService;

class VisaApplicationController extends Controller
{
    public function index()
    {
        $records = VisaApplication::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::visa.applications.index', compact('records'));
    }

    public function store(Request $request, VisaApplicationService $service)
    {
        $data = $request->validate([
            'passenger_id' => ['required','integer'],
            'country_code' => ['required','string','size:2'],
            'visa_type' => ['required','string','max:80'],
            'embassy_name' => ['nullable','string','max:150'],
            'appointment_at' => ['nullable','date'],
            'submission_date' => ['nullable','date'],
            'expected_completion_date' => ['nullable','date'],
            'visa_fee' => ['nullable','numeric','min:0'],
            'service_fee' => ['nullable','numeric','min:0'],
            'currency_code' => ['required','string','size:3'],
            'remarks' => ['nullable','string'],
            'checklist' => ['nullable','array'],
            'checklist.*.item_name' => ['required','string','max:190'],
        ]);

        $header = collect($data)->except('checklist')->merge([
            'business_id' => (int)session('business.id'),
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
        ])->all();

        $service->create($header, $data['checklist'] ?? []);

        return back()->with('status', ['success' => 1, 'msg' => 'Visa application created successfully.']);
    }
}
