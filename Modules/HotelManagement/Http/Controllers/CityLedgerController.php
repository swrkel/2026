<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\CityLedgerService;

class CityLedgerController extends Controller
{
    protected CityLedgerService $service;

    public function __construct(CityLedgerService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $cityLedger = $this->service->dashboard($request->all());
        return view('hotelmanagement::city_ledger.index', compact('cityLedger'));
    }

    public function account(Request $request)
    {
        $data = $request->validate([
            'account_code' => 'required|string|max:60',
            'account_name' => 'required|string|max:180',
            'account_type' => 'required|string|max:40',
            'contact_person' => 'nullable|string|max:160',
            'mobile' => 'nullable|string|max:60',
            'email' => 'nullable|email|max:160',
            'credit_limit' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0|max:365',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveAccount($data, optional($request->user())->id);
        return redirect()->route('hotel-management.city-ledger.index')->with('status', 'City Ledger account saved successfully.');
    }

    public function invoice(Request $request)
    {
        $data = $request->validate([
            'ledger_account_id' => 'required|integer|min:1',
            'folio_no' => 'nullable|string|max:80',
            'guest_name' => 'nullable|string|max:180',
            'invoice_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'invoice_amount' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->createInvoice($data, optional($request->user())->id);
        return redirect()->route('hotel-management.city-ledger.index')->with('status', 'City Ledger invoice posted successfully.');
    }

    public function receipt(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|integer|min:1',
            'receipt_date' => 'nullable|date',
            'payment_method' => 'required|string|max:40',
            'reference_no' => 'nullable|string|max:120',
            'receipt_amount' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->recordReceipt($data, optional($request->user())->id);
        return redirect()->route('hotel-management.city-ledger.index')->with('status', 'City Ledger receipt recorded successfully.');
    }

    public function adjustment(Request $request)
    {
        $data = $request->validate([
            'ledger_account_id' => 'required|integer|min:1',
            'adjustment_date' => 'nullable|date',
            'adjustment_type' => 'required|string|max:20',
            'adjustment_amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:1000',
        ]);
        $this->service->adjustment($data, optional($request->user())->id);
        return redirect()->route('hotel-management.city-ledger.index')->with('status', 'City Ledger adjustment saved successfully.');
    }
}
