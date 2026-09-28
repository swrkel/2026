<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierProduct;
use Modules\Suppliers\Entities\SupplierProductMapping;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Services\SupplierRecordService;
use Modules\Suppliers\Utils\SupplierContextUtil;

class SupplierMappingTabController extends Controller
{
    protected $supplierService;

    public function __construct(SupplierRecordService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    public function index(Request $request)
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $selectedSupplierId = max(0, (int) $request->input('supplier_id', 0));

        if ($selectedSupplierId <= 0) {
            $firstSupplierQuery = Supplier::query()
                ->where('business_id', $businessId)
                ->whereIn('type', ['supplier', 'both']);

            if (Schema::hasColumn('contacts', 'active')) {
                $firstSupplierQuery->where('active', 1);
            }

            $selectedSupplierId = (int) ($firstSupplierQuery
                ->orderBy('name')
                ->value('id') ?? 0);
        }

        $suppliers = $this->supplierService->selectedDropdown($businessId, $selectedSupplierId, false);

        if ($selectedSupplierId > 0 && ! $suppliers->has($selectedSupplierId)) {
            $selectedSupplierId = 0;
            $suppliers = collect();
        }

        $oldMappingRows = old('mappings', []);
        $selectedProductIds = collect(is_array($oldMappingRows) ? $oldMappingRows : [])
            ->pluck('product_id')
            ->filter(static fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (old('product_id')) {
            $selectedProductIds->push((int) old('product_id'));
        }

        $products = $selectedProductIds->isEmpty()
            ? collect()
            : SupplierProduct::query()
                ->where('business_id', $businessId)
                ->whereIn('id', $selectedProductIds->all())
                ->orderBy('name')
                ->pluck('name', 'id');

        return view('suppliers::mappings.index', [
            'suppliers' => $suppliers,
            'products' => $products,
            'selectedSupplierId' => $selectedSupplierId,
            'supplierLookupUrl' => route('suppliers.lookup.suppliers'),
            'productLookupUrl' => route('suppliers.lookup.products'),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(
            SupplierContextUtil::can('supplier.update') || SupplierContextUtil::can('supplier.create'),
            403,
            'Unauthorized action.'
        );

        $businessId = SupplierContextUtil::businessId();

        // Keep the endpoint backward compatible with the earlier single-product form.
        // The new screen submits one or more rows through the mappings array.
        $mappingRows = $request->input('mappings');
        if (! is_array($mappingRows)) {
            $mappingRows = [[
                'product_id' => $request->input('product_id'),
                'supplier_sku' => $request->input('supplier_sku'),
            ]];
        }

        $request->merge(['mappings' => $mappingRows]);

        $data = $request->validate([
            'supplier_id' => 'required|integer',
            'mappings' => 'required|array|min:1',
            'mappings.*.product_id' => 'required|integer|distinct',
            'mappings.*.supplier_sku' => 'nullable|string|max:191',
        ], [
            'mappings.required' => 'Please add at least one product mapping.',
            'mappings.min' => 'Please add at least one product mapping.',
            'mappings.*.product_id.required' => 'Please select a product for every mapping row.',
            'mappings.*.product_id.distinct' => 'The same product cannot be selected more than once.',
        ]);

        $supplier = Supplier::query()
            ->where('business_id', $businessId)
            ->whereIn('type', ['supplier', 'both'])
            ->findOrFail((int) $data['supplier_id']);

        $productIds = collect($data['mappings'])
            ->pluck('product_id')
            ->map(static fn ($productId) => (int) $productId)
            ->values();

        $validProductIds = SupplierProduct::query()
            ->where('business_id', $businessId)
            ->whereIn('id', $productIds->all())
            ->pluck('id')
            ->map(static fn ($productId) => (int) $productId);

        if ($validProductIds->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'mappings' => 'One or more selected products are not available for this business.',
            ]);
        }

        abort_unless(Schema::hasTable('supplier_product_mappings'), 500, 'Supplier product mappings table is missing.');

        $hasBusinessId = Schema::hasColumn('supplier_product_mappings', 'business_id');
        $hasSupplierSku = Schema::hasColumn('supplier_product_mappings', 'supplier_sku');

        DB::transaction(function () use (
            $businessId,
            $supplier,
            $data,
            $hasBusinessId,
            $hasSupplierSku
        ): void {
            foreach ($data['mappings'] as $mappingRow) {
                $lookup = [
                    'supplier_id' => (int) $supplier->id,
                    'product_id' => (int) $mappingRow['product_id'],
                ];

                $values = [];

                // Existing tenant databases may still have the legacy table shape.
                // Use the optional columns only when they are present.
                if ($hasBusinessId) {
                    $lookup['business_id'] = $businessId;
                    $values['business_id'] = $businessId;
                }

                if ($hasSupplierSku) {
                    $values['supplier_sku'] = filled($mappingRow['supplier_sku'] ?? null)
                        ? trim((string) $mappingRow['supplier_sku'])
                        : null;
                }

                SupplierProductMapping::updateOrCreate($lookup, $values);
            }
        });

        return redirect()
            ->route('suppliers.mappings.index', ['supplier_id' => $supplier->id])
            ->with('status', [
                'success' => 1,
                'msg' => count($data['mappings']) . ' supplier product mapping(s) saved successfully.',
            ]);
    }

    public function destroy(Request $request, $id)
    {
        abort_unless(SupplierContextUtil::can('supplier.update'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $mapping = SupplierProductMapping::findOrFail((int) $id);

        $belongsToBusiness = Supplier::query()
            ->where('business_id', $businessId)
            ->whereIn('type', ['supplier', 'both'])
            ->whereKey((int) $mapping->supplier_id)
            ->exists();

        abort_unless($belongsToBusiness, 404);

        $mapping->delete();

        return back()->with('status', [
            'success' => 1,
            'msg' => __('suppliers::lang.product_mapping_deleted_successfully'),
        ]);
    }
}
