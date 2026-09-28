<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierProduct;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Utils\SupplierContextUtil;

/**
 * Lightweight Select2 lookups used by supplier tabs.
 *
 * Large supplier and product lists are never loaded into the initial HTML.
 * This keeps the tab shell fast while still allowing type-and-filter access to
 * every record through small, paged JSON responses.
 */
class SupplierLookupController extends Controller
{
    private const PAGE_SIZE = 20;

    public function suppliers(Request $request): JsonResponse
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $term = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));

        $query = Supplier::query()
            ->select(['id', 'name', 'supplier_business_name', 'contact_id'])
            ->where('business_id', $businessId)
            ->whereIn('type', ['supplier', 'both']);

        if (Schema::hasColumn('contacts', 'active')) {
            $query->where('active', 1);
        }

        if ($term !== '') {
            $like = $term . '%';
            $query->where(function ($filter) use ($like): void {
                $filter->where('name', 'like', $like)
                    ->orWhere('supplier_business_name', 'like', $like)
                    ->orWhere('contact_id', 'like', $like);
            });
        }

        $rows = $query
            ->orderBy('name')
            ->orderBy('id')
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->limit(self::PAGE_SIZE + 1)
            ->get();

        $more = $rows->count() > self::PAGE_SIZE;
        $results = $rows->take(self::PAGE_SIZE)->map(static function (Supplier $supplier): array {
            $businessName = trim((string) $supplier->supplier_business_name);
            $code = trim((string) $supplier->contact_id);
            $text = trim((string) $supplier->name);

            if ($businessName !== '') {
                $text .= ' - ' . $businessName;
            }

            if ($code !== '') {
                $text .= ' (' . $code . ')';
            }

            return ['id' => (int) $supplier->id, 'text' => $text];
        })->values();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $more],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $term = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $hasSku = Schema::hasColumn('products', 'sku');

        $columns = ['id', 'name'];
        if ($hasSku) {
            $columns[] = 'sku';
        }

        $query = SupplierProduct::query()
            ->select($columns)
            ->where('business_id', $businessId);

        if (Schema::hasColumn('products', 'is_inactive')) {
            $query->where('is_inactive', 0);
        }

        if ($term !== '') {
            $like = $term . '%';
            $query->where(function ($filter) use ($like, $hasSku): void {
                $filter->where('name', 'like', $like);
                if ($hasSku) {
                    $filter->orWhere('sku', 'like', $like);
                }
            });
        }

        $rows = $query
            ->orderBy('name')
            ->orderBy('id')
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->limit(self::PAGE_SIZE + 1)
            ->get();

        $more = $rows->count() > self::PAGE_SIZE;
        $results = $rows->take(self::PAGE_SIZE)->map(static function (SupplierProduct $product) use ($hasSku): array {
            $text = trim((string) $product->name);
            $sku = $hasSku ? trim((string) ($product->sku ?? '')) : '';

            if ($sku !== '') {
                $text .= ' (' . $sku . ')';
            }

            return ['id' => (int) $product->id, 'text' => $text];
        })->values();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $more],
        ]);
    }
}
