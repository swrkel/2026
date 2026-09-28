<?php

namespace Modules\Purchase\Http\Controllers\Return;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Purchase\Http\Requests\StorePurchaseReturnRequest;
use Modules\Purchase\Services\Return\PurchaseReturnCreateService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseReturnCreateController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function create(PurchaseReturnCreateService $service): View
    {
        abort_unless($this->access->canCreateReturns(), 403, 'Unauthorized action.');

        return view('purchase::returns.create', $service->formData());
    }

    public function store(StorePurchaseReturnRequest $request, PurchaseReturnCreateService $service): JsonResponse|RedirectResponse
    {
        try {
            $result = $service->store($request->validated());
            $message = 'Purchase return ' . $result['ref_no'] . ' saved successfully.';
            $redirect = route('purchase.returns.index', ['just_saved_return_id' => $result['transaction_id']]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => $redirect,
                    'return' => $result,
                ]);
            }

            return redirect($redirect)->with('status', ['success' => 1, 'msg' => $message]);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['purchase_return' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Standalone Purchase return save failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'business_id' => session('user.business_id'),
                'user_id' => auth()->id(),
            ]);

            $message = config('app.debug') ? $e->getMessage() : 'Something went wrong while saving the purchase return.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->withInput()->withErrors(['purchase_return' => $message]);
        }
    }
}
