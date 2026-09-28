<?php

namespace Modules\BankingMicrofinance\Http\Controllers\Field;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BankingMicrofinance\Entities\FieldCashHandover;
use Modules\BankingMicrofinance\Services\CashHandoverService;

class CashHandoverController extends Controller
{
    public function index() { return view('bankingmicrofinance::field.cash_handovers.index', ['rows' => FieldCashHandover::latest()->paginate(20)]); }
    public function create() { return view('bankingmicrofinance::field.cash_handovers.form'); }
    public function store(Request $request, CashHandoverService $service) { $data = $request->all(); $data['variance_amount'] = $service->calculateVariance((float)($data['declared_amount'] ?? 0), (float)($data['received_amount'] ?? 0)); FieldCashHandover::create($data); return redirect()->route('bkg.mfi.field.cash-handovers.index')->with('status', 'Cash handover saved.'); }
    public function edit($id) { $row = FieldCashHandover::findOrFail($id); return view('bankingmicrofinance::field.cash_handovers.form', compact('row')); }
    public function update(Request $request, $id, CashHandoverService $service) { $row = FieldCashHandover::findOrFail($id); $data = $request->all(); $data['variance_amount'] = $service->calculateVariance((float)($data['declared_amount'] ?? 0), (float)($data['received_amount'] ?? 0)); $row->update($data); return back()->with('status', 'Cash handover updated.'); }
    public function destroy($id) { FieldCashHandover::findOrFail($id)->delete(); return back()->with('status', 'Deleted.'); }
    public function approve(FieldCashHandover $handover, CashHandoverService $service) { $service->approve($handover, auth()->id() ?? 0); return back()->with('status', 'Handover approved.'); }
}
