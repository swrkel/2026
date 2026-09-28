<?php

namespace Modules\MyHealthMembers\Http\Controllers\Billing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthBillingInvoice;
use Modules\MyHealthMembers\Services\MyHealthBillingService;
use Modules\MyHealthMembers\Services\MyHealthPaymentNumberService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthBillingPaymentController extends Controller
{
    public function create(MyHealthBillingInvoice $invoice, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_billing'), 403);
        $invoice->load('member');
        return view('myhealthmembers::billing.payments.create', compact('invoice'));
    }

    public function store(Request $request, MyHealthBillingInvoice $invoice, MyHealthPaymentNumberService $numberService, MyHealthBillingService $billingService, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_billing'), 403);
        $data = $request->validate([
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'remarks' => ['nullable', 'string'],
        ]);

        DB::connection(config('myhealthmembers.central_connection'))->transaction(function () use ($invoice, $data, $numberService, $billingService) {
            $invoice->payments()->create([
                'payment_no' => $numberService->nextNumber(),
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'payment_method' => $data['payment_method'],
                'amount' => (float) $data['amount'],
                'reference_no' => $data['reference_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);
            $billingService->refreshInvoiceTotals($invoice);
        });

        return redirect()->route('myhealth.billing.invoices.show', $invoice)->with('status', __('myhealthmembers::lang.billing_payment_saved'));
    }
}
