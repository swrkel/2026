<?php

namespace Modules\Purchase\Http\Controllers\Entry;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Purchase\Http\Requests\UpdatePurchaseEntryRequest;
use Modules\Purchase\Services\Entry\PurchaseEntryEditService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseEntryEditController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function edit(int $id, PurchaseEntryEditService $service): View
    {
        abort_unless($this->access->canEdit(), 403, 'Unauthorized action.');

        return view('purchase::entries.create', $service->formData($id));
    }

    public function update(
        UpdatePurchaseEntryRequest $request,
        int $id,
        PurchaseEntryEditService $service
    ): JsonResponse|RedirectResponse {
        try {
            $result = $service->update($id, $request->validated(), $request);
            $message = 'Purchase entry ' . $result['invoice_no'] . ' updated successfully.';
            $redirect = (string) $request->input('save_action', 'list') === 'view'
                ? route('purchase.entries.show', ['id' => $id])
                : route('purchase.entries.index', ['just_saved_purchase_id' => $id]);

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
            Log::error('Standalone Purchase entry update failed', [
                'transaction_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'business_id' => session('user.business_id'),
                'user_id' => auth()->id(),
            ]);

            $message = config('app.debug') ? $e->getMessage() : 'Something went wrong while updating the purchase entry.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->withInput()->withErrors(['purchase' => $message]);
        }
    }
}
