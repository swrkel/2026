<?php

namespace Modules\Purchase\Http\Controllers\Return;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Purchase\Services\Return\PurchaseReturnDeleteService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseReturnDeleteController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function destroy(int $id, PurchaseReturnDeleteService $service)
    {
        abort_unless($this->access->canDeleteReturns(), 403, 'Unauthorized action.');

        try {
            $service->delete($id);

            return response()->json(['success' => true, 'msg' => 'Purchase return deleted and reversed successfully.']);
        } catch (\Throwable $e) {
            Log::error('Standalone Purchase return delete failed', [
                'return_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Unable to delete the purchase return.',
            ], 500);
        }
    }
}
