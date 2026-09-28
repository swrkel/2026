<?php

namespace Modules\Purchase\Http\Controllers\Return;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Return\PurchaseReturnCreateService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseReturnDataController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function purchases(Request $request, PurchaseReturnCreateService $service): JsonResponse
    {
        abort_unless($this->access->canCreateReturns(), 403, 'Unauthorized action.');

        return response()->json([
            'results' => $service->purchases(
                trim((string) $request->query('q', '')),
                $request->integer('supplier_id') ?: null,
                $request->integer('location_id') ?: null
            ),
        ]);
    }

    public function purchase(int $id, PurchaseReturnCreateService $service): JsonResponse
    {
        abort_unless($this->access->canCreateReturns(), 403, 'Unauthorized action.');

        $purchase = $service->purchaseDetails($id);
        abort_unless($purchase, 404, 'Purchase not found.');

        return response()->json(['purchase' => $purchase]);
    }
}
