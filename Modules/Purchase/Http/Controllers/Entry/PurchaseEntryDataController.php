<?php

namespace Modules\Purchase\Http\Controllers\Entry;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Entry\PurchaseEntryDataService;
use Modules\Purchase\Services\Entry\PurchaseEntryTankService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseEntryDataController extends Controller
{
    public function __construct(
        protected PurchaseEntryDataService $service,
        protected PurchaseAccessUtil $access
    ) {
    }

    public function suppliers(Request $request): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate([
            'term' => ['nullable', 'string', 'max:191'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        return response()->json($this->service->supplierPage(
            (string) ($validated['term'] ?? ''),
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 50)
        ));
    }

    public function supplier(int $id): JsonResponse
    {
        $this->authorizeCreate();
        $supplier = $this->service->supplier($id);
        abort_if(! $supplier, 404, 'Supplier not found.');

        return response()->json(['supplier' => $supplier]);
    }

    public function purchaseOrders(Request $request): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json([
            'results' => $this->service->pendingPurchaseOrders((int) $validated['supplier_id']),
        ]);
    }

    public function purchaseOrder(Request $request, int $id): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'min:1'],
        ]);

        $order = $this->service->purchaseOrder($id, (int) $validated['supplier_id']);
        abort_if(! $order, 404, 'The selected purchase order is no longer pending or is not available for this supplier.');

        return response()->json(['purchase_order' => $order]);
    }

    public function storeSupplier(Request $request): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'supplier_business_name' => ['nullable', 'string', 'max:191'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:191'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'pay_term_number' => ['nullable', 'integer', 'min:0'],
            'pay_term_type' => ['nullable', 'in:days,months'],
        ]);

        return response()->json([
            'success' => true,
            'supplier' => $this->service->createSupplier($validated),
        ]);
    }

    public function stores(Request $request): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate(['location_id' => ['required', 'integer', 'min:1']]);

        return response()->json([
            'results' => $this->service->stores((int) $validated['location_id']),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate([
            'term' => ['nullable', 'string', 'max:191'],
            'location_id' => ['required', 'integer', 'min:1'],
            'store_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'results' => $this->service->products(
                (string) ($validated['term'] ?? ''),
                (int) $validated['location_id'],
                isset($validated['store_id']) ? (int) $validated['store_id'] : null
            ),
        ]);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'sku' => ['nullable', 'string', 'max:191'],
            'unit_id' => ['required', 'integer', 'min:1'],
            'tax_id' => ['nullable', 'integer', 'min:1'],
            'tax_type' => ['nullable', 'in:exclusive,inclusive'],
            'enable_stock' => ['nullable', 'boolean'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'location_id' => ['required', 'integer', 'min:1'],
            'store_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = $this->service->createProduct(
            $validated,
            (int) $validated['location_id'],
            isset($validated['store_id']) ? (int) $validated['store_id'] : null
        );

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function product(Request $request, int $variationId): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'min:1'],
            'store_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $product = $this->service->product(
            $variationId,
            (int) $validated['location_id'],
            isset($validated['store_id']) ? (int) $validated['store_id'] : null
        );
        abort_if(! $product, 404, 'Product variation not found.');

        return response()->json(['product' => $product]);
    }

    public function unloadTanks(Request $request, PurchaseEntryTankService $tanks): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'location_id' => ['required', 'integer', 'min:1'],
        ]);

        $row = $tanks->rowData((int) $validated['product_id'], (int) $validated['location_id']);

        return response()->json([
            'required' => (bool) $row['is_fuel'],
            'product_id' => (int) $row['product_id'],
            'tanks_count' => $row['tanks']->count(),
            'html' => $row['is_fuel']
                ? view('purchase::entries.partials.unload_tank_row', $row)->render()
                : '',
        ]);
    }

    public function referenceCheck(Request $request): JsonResponse
    {
        $this->authorizeCreate();
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'min:1'],
            'ref_no' => ['required', 'string', 'max:255'],
            'exclude_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json([
            'exists' => $this->service->referenceExists(
                (int) $validated['supplier_id'],
                trim((string) $validated['ref_no']),
                isset($validated['exclude_id']) ? (int) $validated['exclude_id'] : null
            ),
        ]);
    }

    protected function authorizeCreate(): void
    {
        abort_unless($this->access->canCreate() || $this->access->canEdit(), 403, 'Unauthorized action.');
    }
}
