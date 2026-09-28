<?php

namespace Modules\ProductsNew\Services\CommandCenter;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewCommandCenterSnapshot;
use Modules\ProductsNew\Entities\ProductsNewInventoryMovement;
use Modules\ProductsNew\Entities\ProductsNewMedia;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Entities\ProductsNewProductRelationship;
use Modules\ProductsNew\Entities\ProductsNewTimeline;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductCommandCenterService
{
    public function search(array $filters = [], int $limit = 25)
    {
        $businessId = ProductsNewTenantGuard::businessId($filters['business_id'] ?? null);
        $term = trim((string) ($filters['q'] ?? ''));
        $columns = $this->productColumns();

        // The core ERP products table differs between installations. Never
        // select or search an optional field unless it exists in this tenant DB.
        if (! in_array('id', $columns, true)
            || ! in_array('business_id', $columns, true)) {
            return collect();
        }

        $query = ProductsNewProduct::query()
            ->where('business_id', $businessId);

        $searchableColumns = array_values(array_intersect(
            ['name', 'sku', 'barcode', 'product_custom_field1', 'product_custom_field2'],
            $columns
        ));

        if ($term !== '') {
            if ($searchableColumns === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($inner) use ($term, $searchableColumns): void {
                    foreach ($searchableColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $inner->{$method}($column, 'like', '%' . $term . '%');
                    }
                });
            }
        }

        $query->orderBy(
            in_array('name', $columns, true) ? 'name' : 'id'
        );

        return $query
            ->limit(max(1, min(100, $limit)))
            ->get($this->searchSelectColumns($columns));
    }

    public function workspace(int $productId, array $filters = []): array
    {
        $businessId = ProductsNewTenantGuard::businessId($filters['business_id'] ?? null);
        $product = ProductsNewProduct::where('business_id', $businessId)->findOrFail($productId);

        return [
            'product' => $product,
            'summary' => $this->summary($product),
            'inventory' => $this->inventory($product, $filters),
            'finance' => $this->finance($product, $filters),
            'sales' => $this->sales($product, $filters),
            'purchasing' => $this->purchasing($product, $filters),
            'timeline' => $this->timeline($product),
            'media' => $this->media($product),
            'relationships' => $this->relationships($product),
            'alerts' => $this->alerts($product),
            'integrations' => $this->integrations($product),
            'actions' => $this->actions($product),
        ];
    }

    public function saveSnapshot(int $productId, array $filters = []): ProductsNewCommandCenterSnapshot
    {
        $workspace = $this->workspace($productId, $filters);

        return ProductsNewCommandCenterSnapshot::create([
            'business_id' => $workspace['product']->business_id,
            'product_id' => $productId,
            'summary_payload' => $workspace['summary'],
            'inventory_payload' => $workspace['inventory'],
            'finance_payload' => $workspace['finance'],
            'sales_payload' => $workspace['sales'],
            'purchase_payload' => $workspace['purchasing'],
            'alerts_payload' => $workspace['alerts'],
            'integration_payload' => $workspace['integrations'],
            'created_by' => auth()->id(),
        ]);
    }

    private function summary(ProductsNewProduct $product): array
    {
        return [
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $this->barcodeValue($product),
            'type' => $product->type ?? 'single',
            'status' => $this->isInactive($product) ? 'inactive' : 'active',
            'health_score' => $this->healthScore($product),
            'image_url' => $product->image ? asset('uploads/img/' . $product->image) : null,
        ];
    }

    private function inventory(ProductsNewProduct $product, array $filters): array
    {
        $movement = ProductsNewInventoryMovement::where('business_id', $product->business_id)
            ->where('product_id', $product->id);
        $in = (clone $movement)
            ->whereIn('movement_type', ['opening', 'purchase', 'transfer_in', 'adjustment_in', 'return_in'])
            ->sum('quantity');
        $out = (clone $movement)
            ->whereIn('movement_type', ['sale', 'transfer_out', 'adjustment_out', 'return_out'])
            ->sum('quantity');

        return [
            'available' => round((float) $in - (float) $out, 4),
            'total_in' => round((float) $in, 4),
            'total_out' => round((float) $out, 4),
            'reserved' => 0,
            'allocated' => 0,
            'on_order' => 0,
            'in_transit' => 0,
            'last_movement' => optional((clone $movement)->latest('transaction_date')->first())->transaction_date,
        ];
    }

    private function finance(ProductsNewProduct $product, array $filters): array
    {
        $purchase = (float) ($product->purchase_price_inc_tax ?? $product->default_purchase_price ?? 0);
        $sell = (float) ($product->sell_price_inc_tax ?? $product->default_sell_price ?? 0);
        $margin = $sell > 0 ? (($sell - $purchase) / $sell) * 100 : 0;

        return [
            'last_cost' => $purchase,
            'average_cost' => $purchase,
            'selling_price' => $sell,
            'gross_profit' => round($sell - $purchase, 4),
            'margin_percentage' => round($margin, 2),
            'inventory_value' => 0,
        ];
    }

    private function sales(ProductsNewProduct $product, array $filters): array
    {
        return [
            'last_sale' => null,
            'sales_30_days' => 0,
            'sales_value_30_days' => 0,
            'returns_30_days' => 0,
            'top_customer' => null,
        ];
    }

    private function purchasing(ProductsNewProduct $product, array $filters): array
    {
        return [
            'last_purchase' => null,
            'preferred_supplier' => null,
            'lead_time_days' => 0,
            'pending_purchase_orders' => 0,
            'supplier_rating' => null,
        ];
    }

    private function timeline(ProductsNewProduct $product)
    {
        return ProductsNewTimeline::where('business_id', $product->business_id)
            ->where('product_id', $product->id)
            ->latest()
            ->limit(20)
            ->get();
    }

    private function media(ProductsNewProduct $product)
    {
        return ProductsNewMedia::where('business_id', $product->business_id)
            ->where('product_id', $product->id)
            ->latest()
            ->limit(12)
            ->get();
    }

    private function relationships(ProductsNewProduct $product)
    {
        return ProductsNewProductRelationship::where('business_id', $product->business_id)
            ->where('product_id', $product->id)
            ->latest()
            ->limit(12)
            ->get();
    }

    private function alerts(ProductsNewProduct $product): array
    {
        $alerts = [];

        if (empty($product->image)) {
            $alerts[] = ['level' => 'warning', 'message' => 'Product image is missing'];
        }
        if ($this->barcodeValue($product) === null) {
            $alerts[] = ['level' => 'warning', 'message' => 'Barcode/SKU is missing'];
        }
        if ((float) ($product->sell_price_inc_tax ?? 0) < (float) ($product->purchase_price_inc_tax ?? 0)) {
            $alerts[] = ['level' => 'danger', 'message' => 'Selling price is below cost'];
        }
        if (empty($product->category_id)) {
            $alerts[] = ['level' => 'info', 'message' => 'Category is not selected'];
        }

        return $alerts;
    }

    private function integrations(ProductsNewProduct $product): array
    {
        return [
            ['module' => 'POS', 'status' => 'ready', 'route' => null],
            ['module' => 'Purchasing', 'status' => 'ready', 'route' => null],
            ['module' => 'Distribution', 'status' => 'bridge_ready', 'route' => null],
            ['module' => 'Manufacturing', 'status' => 'bridge_ready', 'route' => null],
            ['module' => 'Finance', 'status' => 'bridge_ready', 'route' => null],
        ];
    }

    private function actions(ProductsNewProduct $product): array
    {
        return ['edit', 'duplicate', 'print_barcode', 'stock_adjustment', 'transfer_stock', 'export', 'audit_log'];
    }

    private function healthScore(ProductsNewProduct $product): int
    {
        $checks = [
            ! empty($product->name),
            $this->barcodeValue($product) !== null,
            ! empty($product->category_id),
            ! empty($product->brand_id),
            ! empty($product->unit_id),
            ! empty($product->image),
        ];

        return (int) round((count(array_filter($checks)) / count($checks)) * 100);
    }

    private function productColumns(): array
    {
        return Schema::hasTable('products')
            ? Schema::getColumnListing('products')
            : [];
    }

    private function searchSelectColumns(array $columns): array
    {
        return [
            'id',
            $this->columnOrFallback($columns, 'name', "CONCAT('Product #', id)", 'name'),
            $this->columnOrFallback($columns, 'sku', "''", 'sku'),
            in_array('barcode', $columns, true)
                ? 'barcode'
                : (in_array('sku', $columns, true)
                    ? DB::raw('sku as barcode')
                    : DB::raw("'' as barcode")),
            $this->columnOrFallback($columns, 'type', "'single'", 'type'),
            $this->columnOrFallback($columns, 'category_id', 'NULL', 'category_id'),
            $this->columnOrFallback($columns, 'brand_id', 'NULL', 'brand_id'),
            $this->columnOrFallback($columns, 'enable_stock', '0', 'enable_stock'),
            in_array('is_inactive', $columns, true)
                ? 'is_inactive'
                : (in_array('not_for_selling', $columns, true)
                    ? DB::raw('not_for_selling as is_inactive')
                    : DB::raw('0 as is_inactive')),
        ];
    }

    private function columnOrFallback(array $columns, string $column, string $fallback, string $alias)
    {
        return in_array($column, $columns, true)
            ? $column
            : DB::raw($fallback . ' as ' . $alias);
    }

    private function barcodeValue(ProductsNewProduct $product): ?string
    {
        $barcode = trim((string) $product->getAttribute('barcode'));
        if ($barcode !== '') {
            return $barcode;
        }

        $sku = trim((string) $product->getAttribute('sku'));

        return $sku !== '' ? $sku : null;
    }

    private function isInactive(ProductsNewProduct $product): bool
    {
        $inactive = $product->getAttribute('is_inactive');

        if ($inactive === null) {
            $inactive = $product->getAttribute('not_for_selling');
        }

        return (bool) $inactive;
    }
}
