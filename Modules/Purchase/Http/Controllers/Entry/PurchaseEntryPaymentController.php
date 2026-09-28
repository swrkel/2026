<?php

namespace Modules\Purchase\Http\Controllers\Entry;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Purchase\Services\Entry\PurchaseEntryAddPaymentService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseEntryPaymentController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function create(int $id, PurchaseEntryAddPaymentService $service): View|RedirectResponse
    {
        abort_unless($this->access->canAddPayments(), 403, 'Unauthorized action.');

        try {
            return view('purchase::entries.add_payment', $service->formData($id));
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('purchase.entries.index')->with('status', [
                'success' => false,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request, int $id, PurchaseEntryAddPaymentService $service): RedirectResponse
    {
        abort_unless($this->access->canAddPayments(), 403, 'Unauthorized action.');

        $data = $request->validate([
            'method' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'account_id' => ['required', 'integer', 'min:1'],
            'paid_on' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'note' => ['nullable', 'string', 'max:1000'],
            'cheque_number' => ['nullable', 'string', 'max:191'],
            'cheque_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:191'],
            'bank_account_number' => ['nullable', 'string', 'max:191'],
            'transfer_date' => ['nullable', 'date'],
            'card_transaction_number' => ['nullable', 'string', 'max:191'],
            'card_number' => ['nullable', 'string', 'max:191'],
            'card_type' => ['nullable', 'string', 'max:100'],
            'card_holder_name' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            $result = $service->store($id, $data);

            return redirect()->route('purchase.entries.index')->with('status', [
                'success' => true,
                'msg' => sprintf(
                    'Payment %s saved successfully. Payment status: %s. Remaining due: %.2f.',
                    $result['payment_ref_no'] ?? '',
                    ucfirst($result['payment_status']),
                    $result['due_total']
                ),
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['payment' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Standalone Purchase add payment failed', [
                'transaction_id' => $id,
                'business_id' => session('user.business_id'),
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->withInput()->withErrors([
                'payment' => config('app.debug') ? $e->getMessage() : 'Unable to save the purchase payment.',
            ]);
        }
    }
}
