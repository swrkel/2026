<?php

namespace Modules\Purchase\Http\Controllers\Order;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Purchase\Http\Requests\StorePurchaseEntryRequest;
use Modules\Purchase\Services\Order\PurchaseOrderCreateService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseOrderCreateController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function create(PurchaseOrderCreateService $service): View
    {
        abort_unless($this->access->canCreate(), 403, 'Unauthorized action.');

        return view('purchase::orders.create', $service->formData());
    }

    public function store(
        StorePurchaseEntryRequest $request,
        PurchaseOrderCreateService $service
    ): JsonResponse|RedirectResponse {
        try {
            $result = $service->store($request->validated(), $request);
            $message = 'Purchase order ' . $result['invoice_no'] . ' saved successfully.';
            $redirect = $this->redirectForAction(
                (string) $request->input('save_action', 'list'),
                (int) $result['transaction_id']
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => $redirect,
                    'transaction' => $result,
                ]);
            }

            return redirect($redirect)->with('status', ['success' => 1, 'msg' => $message]);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['purchase_order' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Standalone Purchase order save failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'business_id' => session('user.business_id'),
                'user_id' => auth()->id(),
            ]);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'Something went wrong while saving the purchase order.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->withInput()->withErrors(['purchase_order' => $message]);
        }
    }

    protected function redirectForAction(string $action, int $transactionId): string
    {
        return match ($action) {
            'new' => route('purchase.orders.create'),
            'view' => route('purchase.orders.show', ['id' => $transactionId]),
            default => route('purchase.orders.index', ['just_saved_purchase_order_id' => $transactionId]),
        };
    }
}
