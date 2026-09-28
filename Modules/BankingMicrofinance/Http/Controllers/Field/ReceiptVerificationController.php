<?php

namespace Modules\BankingMicrofinance\Http\Controllers\Field;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BankingMicrofinance\Entities\FieldReceipt;
use Modules\BankingMicrofinance\Services\ReceiptVerificationService;

class ReceiptVerificationController extends Controller
{
    public function index() { return view('bankingmicrofinance::field.receipt_verification.index', ['rows' => FieldReceipt::latest()->paginate(20)]); }
    public function verify(FieldReceipt $receipt, ReceiptVerificationService $service) { $service->verify($receipt, auth()->id() ?? 0); return back()->with('status', 'Receipt verified.'); }
    public function flag(Request $request, FieldReceipt $receipt, ReceiptVerificationService $service) { $service->flag($receipt, auth()->id() ?? 0, $request->input('note')); return back()->with('status', 'Receipt flagged.'); }
}
