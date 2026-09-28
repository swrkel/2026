<?php

namespace Modules\Purchase\Http\Controllers\Entry;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Purchase\Http\Requests\StorePurchaseEntryRequest;
use Modules\Purchase\Services\Entry\PurchaseEntryCreateService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseEntryCreateController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function create(PurchaseEntryCreateService $service): View
    {
        abort_unless($this->access->canCreate(), 403, 'Unauthorized action.');

        return view('purchase::entries.create', $service->formData());
    }

    public function store(StorePurchaseEntryRequest $request, PurchaseEntryCreateService $service): JsonResponse|RedirectResponse
    {
        try {
            $result = $service->store($request->validated(), $request);
            $message = 'Purchase entry ' . $result['invoice_no'] . ' saved successfully.';
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

            return back()->withInput()->withErrors(['purchase' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Standalone Purchase entry save failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'business_id' => session('user.business_id'),
                'user_id' => auth()->id(),
            ]);

            $message = config('app.debug') ? $e->getMessage() : 'Something went wrong while saving the purchase entry.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->withInput()->withErrors(['purchase' => $message]);
        }
    }

    protected function redirectForAction(string $action, int $transactionId): string
    {
        return match ($action) {
            'new' => route('purchase.entries.create'),
            'view' => $this->access->canPrint()
                ? route('purchase.entries.print', ['id' => $transactionId])
                : route('purchase.entries.show', ['id' => $transactionId]),
            default => route('purchase.entries.index', ['just_saved_purchase_id' => $transactionId]),
        };
    }
}
