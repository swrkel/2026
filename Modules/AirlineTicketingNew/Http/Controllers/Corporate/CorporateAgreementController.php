<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Corporate;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\CorporateAgreement;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class CorporateAgreementController extends Controller
{
    public function index()
    {
        $records = CorporateAgreement::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::corporate.agreements.index', compact('records'));
    }

    public function store(Request $request, DocumentNumberService $numbers)
    {
        $data = $request->validate([
            'corporate_customer_id' => ['required','integer'],
            'effective_from' => ['required','date'],
            'effective_to' => ['nullable','date','after_or_equal:effective_from'],
            'credit_limit' => ['required','numeric','min:0'],
            'credit_days' => ['required','integer','min:0'],
            'currency_code' => ['required','string','size:3'],
            'discount_type' => ['nullable','in:percentage,fixed'],
            'discount_value' => ['nullable','numeric','min:0'],
            'status' => ['required','in:draft,active,inactive,expired'],
            'remarks' => ['nullable','string'],
        ]);

        $businessId = (int)session('business.id');

        CorporateAgreement::query()->create(array_merge($data, [
            'business_id' => $businessId,
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
            'agreement_no' => $numbers->next($businessId, null, null, 'corporate_agreement', 'ATCA'),
        ]));

        return back()->with('status', ['success' => 1, 'msg' => 'Corporate agreement saved successfully.']);
    }
}
