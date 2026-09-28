<?php

namespace Modules\Purchase\Http\Controllers\Entry;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Purchase\Services\Entry\PurchaseEntryDeleteService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseEntryDeleteController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function destroy(int $id, PurchaseEntryDeleteService $service): JsonResponse
    {
        abort_unless($this->access->canDelete(), 403, 'Unauthorized action.');

        try {
            $service->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Purchase entry deleted successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Standalone Purchase entry delete failed', [
                'transaction_id' => $id,
                'business_id' => session('user.business_id'),
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Unable to delete the purchase entry.',
            ], 500);
        }
    }
}
