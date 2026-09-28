<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerPortalOrderService
{
    public function dealerOrders(int $businessId, int $customerId, int $limit = 1000)
    {
        $items = collect();

        if (Schema::hasTable('customer_portal_orders')) {
            $portalOrders = DB::table('customer_portal_orders')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereNull('deleted_at')
                ->select([
                    'id',
                    'order_date',
                    'required_date',
                    'order_no',
                    'status',
                    'total_qty',
                    'total_amount',
                    'created_at',
                    DB::raw("'portal' as source"),
                ])
                ->orderByDesc('order_date')
                ->orderByDesc('id')
                ->limit($limit)
                ->get();

            foreach ($portalOrders as $row) {
                $row->transaction_date = $row->order_date;
                $row->ref_no = $row->required_date;
                $row->payment_status = $row->status;
                $row->final_total = $row->total_amount;
                $row->paid_amount = 0;
                $row->balance = $row->total_amount;
                $row->qty = $row->total_qty;
                $items->push($row);
            }
        }

        return $items->sortByDesc('created_at')->values()->take($limit);
    }

    public function productRows(int $businessId, ?string $search = null, int $limit = 500)
    {
        if (!Schema::hasTable('products')) {
            return collect();
        }

        $query = DB::table('products')
            ->where('products.business_id', $businessId)
            ->whereNull('products.deleted_at');

        if (Schema::hasColumn('products', 'not_for_selling')) {
            $query->where(function ($q) {
                $q->where('products.not_for_selling', 0)->orWhereNull('products.not_for_selling');
            });
        }

        if (Schema::hasColumn('products', 'is_inactive')) {
            $query->where(function ($q) {
                $q->where('products.is_inactive', 0)->orWhereNull('products.is_inactive');
            });
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', '%' . $search . '%');
                if (Schema::hasColumn('products', 'sku')) {
                    $q->orWhere('products.sku', 'like', '%' . $search . '%');
                }
            });
        }

        $select = [
            'products.id',
            'products.name',
            Schema::hasColumn('products', 'sku') ? 'products.sku' : DB::raw("'' as sku"),
            Schema::hasColumn('products', 'enable_stock') ? 'products.enable_stock' : DB::raw('0 as enable_stock'),
        ];

        $rows = $query->select($select)
            ->orderBy('products.name')
            ->limit($limit)
            ->get();

        $productIds = $rows->pluck('id')->all();
        $prices = $this->productPrices($productIds);
        $stocks = $this->productStocks($productIds);
        $units = $this->productUnits($productIds);

        return $rows->map(function ($row) use ($prices, $stocks, $units) {
            $row->unit_price = (float) ($prices[$row->id] ?? 0);
            $row->available_stock = (float) ($stocks[$row->id] ?? 0);
            $row->unit = $units[$row->id] ?? '';
            return $row;
        });
    }

    public function buildOrderLines(int $businessId, array $productIds, array $qtys, array $lineRemarks = []): array
    {
        $products = $this->productRows($businessId, null, 5000)->keyBy('id');
        $lines = [];
        $totalQty = 0.0;
        $totalAmount = 0.0;

        foreach (array_values($productIds) as $index => $productId) {
            $product = $products->get((int) $productId);
            $qty = (float) ($qtys[$index] ?? 0);

            if (empty($product) || $qty <= 0) {
                continue;
            }

            $unitPrice = (float) ($product->unit_price ?? 0);
            $lineTotal = $qty * $unitPrice;
            $totalQty += $qty;
            $totalAmount += $lineTotal;

            $lines[] = [
                'business_id' => $businessId,
                'product_id' => (int) $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'remarks' => $lineRemarks[$index] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return [$lines, $totalQty, $totalAmount];
    }

    public function orderWithLines(int $businessId, int $customerId, int $orderId): array
    {
        if (!Schema::hasTable('customer_portal_orders') || !Schema::hasTable('customer_portal_order_lines')) {
            return [null, collect()];
        }

        $order = DB::table('customer_portal_orders')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->whereNull('deleted_at')
            ->where('id', $orderId)
            ->first();

        if (empty($order)) {
            return [null, collect()];
        }

        $lines = DB::table('customer_portal_order_lines')
            ->where('customer_portal_order_id', $orderId)
            ->orderBy('id')
            ->get();

        return [$order, $lines];
    }

    public function orderCanBeAmended($order): bool
    {
        $status = strtolower((string) ($order->status ?? ''));
        return in_array($status, ['draft', 'submitted', 'under_review', 'amendment_requested'], true);
    }

    public function orderCanBeCancelled($order): bool
    {
        $status = strtolower((string) ($order->status ?? ''));
        return !in_array($status, ['dispatched', 'delivered', 'cancelled', 'cancellation_requested'], true);
    }

    public function favouriteProductIds(int $businessId, int $customerId): array
    {
        if (!Schema::hasTable('customer_portal_favourite_products')) {
            return [];
        }

        return DB::table('customer_portal_favourite_products')
            ->where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->pluck('product_id')
            ->map(function ($id) { return (int) $id; })
            ->toArray();
    }

    public function orderEvents(int $businessId, int $customerId, int $orderId, $order)
    {
        if (Schema::hasTable('customer_portal_order_events')) {
            $events = DB::table('customer_portal_order_events')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->where('customer_portal_order_id', $orderId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            if ($events->count() > 0) {
                return $events;
            }
        }

        return collect([
            (object) [
                'status' => 'submitted',
                'remarks' => 'Order submitted by dealer.',
                'created_at' => $order->created_at ?? now(),
            ],
            (object) [
                'status' => $order->status ?? 'submitted',
                'remarks' => 'Current order status.',
                'created_at' => $order->updated_at ?? $order->created_at ?? now(),
            ],
        ]);
    }

    public function recordOrderEvent(int $businessId, int $customerId, int $orderId, string $status, ?string $remarks = null): void
    {
        if (!Schema::hasTable('customer_portal_order_events')) {
            return;
        }

        DB::table('customer_portal_order_events')->insert([
            'business_id' => $businessId,
            'contact_id' => $customerId,
            'customer_portal_order_id' => $orderId,
            'status' => $status,
            'remarks' => $remarks,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function nextOrderNo(int $businessId): string
    {
        $prefix = 'DD-' . date('Ymd') . '-';
        $count = 1;

        if (Schema::hasTable('customer_portal_orders')) {
            $count = ((int) DB::table('customer_portal_orders')
                ->where('business_id', $businessId)
                ->where('order_no', 'like', $prefix . '%')
                ->count()) + 1;
        }

        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function productPrices(array $productIds): array
    {
        if (empty($productIds) || !Schema::hasTable('variations')) {
            return [];
        }

        $rows = DB::table('variations')
            ->whereIn('product_id', $productIds)
            ->select(['product_id', DB::raw('MAX(default_sell_price) as price')])
            ->groupBy('product_id')
            ->pluck('price', 'product_id')
            ->toArray();

        return array_map('floatval', $rows);
    }

    protected function productStocks(array $productIds): array
    {
        if (empty($productIds) || !Schema::hasTable('variation_location_details') || !Schema::hasTable('variations')) {
            return [];
        }

        return DB::table('variation_location_details as vld')
            ->join('variations as v', 'v.id', '=', 'vld.variation_id')
            ->whereIn('v.product_id', $productIds)
            ->select(['v.product_id', DB::raw('SUM(vld.qty_available) as stock')])
            ->groupBy('v.product_id')
            ->pluck('stock', 'product_id')
            ->map(function ($value) { return (float) $value; })
            ->toArray();
    }

    protected function productUnits(array $productIds): array
    {
        if (empty($productIds) || !Schema::hasTable('products') || !Schema::hasTable('units') || !Schema::hasColumn('products', 'unit_id')) {
            return [];
        }

        return DB::table('products')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->whereIn('products.id', $productIds)
            ->pluck('units.short_name', 'products.id')
            ->toArray();
    }
}
