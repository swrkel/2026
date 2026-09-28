<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Settlements;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\SupplierSettlement;
use Modules\AirlineTicketingNew\Services\Settlements\SupplierSettlementService;

class SupplierSettlementController extends Controller
{
    public function index()
    {
        $records = SupplierSettlement::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::settlements.suppliers.index', compact('records'));
    }

    public function store(Request $request, SupplierSettlementService $service)
    {
        $data = $request->validate([
            'supplier_id' => ['required','integer'],
            'settlement_date' => ['required','date'],
            'period_from' => ['required','date'],
            'period_to' => ['required','date','after_or_equal:period_from'],
            'currency_code' => ['required','string','size:3'],
            'other_deduction' => ['nullable','numeric','min:0'],
            'remarks' => ['nullable','string'],
            'lines' => ['required','array','min:1'],
            'lines.*.ticket_id' => ['required','integer'],
            'lines.*.ticket_no' => ['required','string','max:30'],
            'lines.*.supplier_cost' => ['required','numeric','min:0'],
            'lines.*.commission_amount' => ['nullable','numeric','min:0'],
            'lines.*.tax_amount' => ['nullable','numeric','min:0'],
        ]);

        $header = collect($data)->except('lines')->merge([
            'business_id' => (int) session('business.id'),
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
        ])->all();

        $service->create($header, $data['lines']);

        return back()->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::largeparcel.settlement_created')]);
    }
}
