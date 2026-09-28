<?php

namespace Modules\POS\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class POSInventoryService extends POSBaseService
{
    public function dashboard(): array
    {
        return [
            'products' => DB::table('pos_products')->whereNull('deleted_at')->count(),
            'stock_value' => (float) DB::table('pos_products')->whereNull('deleted_at')->selectRaw('COALESCE(SUM(current_stock * cost_price),0) as v')->value('v'),
            'low_stock' => DB::table('pos_products')->whereNull('deleted_at')->whereColumn('current_stock', '<=', 'alert_quantity')->count(),
            'categories' => DB::table('pos_categories')->whereNull('deleted_at')->count(),
        ];
    }

    public function listProducts(array $filters = [])
    {
        $q = DB::table('pos_products as p')
            ->leftJoin('pos_categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('pos_brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('pos_units as u', 'u.id', '=', 'p.unit_id')
            ->whereNull('p.deleted_at')
            ->select('p.*', 'c.name as category_name', 'b.name as brand_name', 'u.short_name as unit_short_name');

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $q->where(function ($w) use ($s) {
                $w->where('p.name', 'like', $s)->orWhere('p.sku', 'like', $s)->orWhere('p.barcode', 'like', $s);
            });
        }
        if (!empty($filters['category_id'])) $q->where('p.category_id', $filters['category_id']);
        if (!empty($filters['brand_id'])) $q->where('p.brand_id', $filters['brand_id']);
        if (($filters['stock_status'] ?? '') === 'low') $q->whereColumn('p.current_stock', '<=', 'p.alert_quantity');
        if (($filters['stock_status'] ?? '') === 'out') $q->where('p.current_stock', '<=', 0);

        return $q->orderBy('p.name')->paginate(25)->appends($filters);
    }

    public function lookups(): array
    {
        return [
            'categories' => DB::table('pos_categories')->whereNull('deleted_at')->orderBy('name')->get(),
            'brands' => DB::table('pos_brands')->whereNull('deleted_at')->orderBy('name')->get(),
            'units' => DB::table('pos_units')->whereNull('deleted_at')->orderBy('name')->get(),
        ];
    }

    public function storeProduct(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $now = now();
            $id = DB::table('pos_products')->insertGetId($this->normaliseProductData($data) + ['created_at' => $now, 'updated_at' => $now]);
            $qty = (float)($data['current_stock'] ?? 0);
            if ($qty != 0.0) $this->recordMovement($id, 'opening_stock', $qty, null, (float)($data['cost_price'] ?? 0), 'Opening stock', null);
            return $id;
        });
    }

    public function updateProduct(int $id, array $data): void
    {
        DB::table('pos_products')->where('id', $id)->whereNull('deleted_at')->update($this->normaliseProductData($data) + ['updated_at' => now()]);
    }

    public function deleteProduct(int $id): void
    {
        DB::table('pos_products')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    protected function normaliseProductData(array $data): array
    {
        $name = trim((string)$data['name']);
        return [
            'name' => $name,
            'sku' => trim((string)($data['sku'] ?? 'POS-' . strtoupper(Str::random(6)))),
            'barcode' => trim((string)($data['barcode'] ?? '')) ?: null,
            'category_id' => $data['category_id'] ?: null,
            'brand_id' => $data['brand_id'] ?: null,
            'unit_id' => $data['unit_id'] ?: null,
            'cost_price' => (float)($data['cost_price'] ?? 0),
            'selling_price' => (float)($data['selling_price'] ?? 0),
            'tax_rate' => (float)($data['tax_rate'] ?? 0),
            'current_stock' => (float)($data['current_stock'] ?? 0),
            'alert_quantity' => (float)($data['alert_quantity'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'description' => $data['description'] ?? null,
        ];
    }

    public function upsertLookup(string $table, array $data): void
    {
        DB::table($table)->insert(['name' => trim($data['name']), 'short_name' => $data['short_name'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function adjustStock(array $data): void
    {
        DB::transaction(function () use ($data) {
            $product = DB::table('pos_products')->where('id', $data['product_id'])->lockForUpdate()->first();
            if (!$product) return;
            $qty = (float)$data['quantity'];
            if ($data['movement_type'] === 'stock_out' || $data['movement_type'] === 'damage' || $data['movement_type'] === 'correction_minus') $qty = -abs($qty);
            else $qty = abs($qty);
            DB::table('pos_products')->where('id', $product->id)->update(['current_stock' => (float)$product->current_stock + $qty, 'updated_at' => now()]);
            $this->recordMovement($product->id, $data['movement_type'], $qty, (float)$product->current_stock, (float)($data['unit_cost'] ?? $product->cost_price), $data['note'] ?? null, null);
        });
    }


    public function activeProducts()
    {
        return DB::table('pos_products')->whereNull('deleted_at')->where('is_active', 1)->orderBy('name')->get();
    }

    public function purchaseDashboard(): array
    {
        return [
            'purchases' => DB::table('pos_purchase_headers')->whereNull('deleted_at')->count(),
            'purchase_value' => (float) DB::table('pos_purchase_headers')->whereNull('deleted_at')->where('status', 'posted')->sum('total_amount'),
            'items_received' => (float) DB::table('pos_purchase_lines as l')->join('pos_purchase_headers as h', 'h.id', '=', 'l.purchase_id')->whereNull('h.deleted_at')->where('h.status', 'posted')->sum('l.quantity'),
            'products' => DB::table('pos_products')->whereNull('deleted_at')->count(),
        ];
    }

    public function listPurchases(array $filters = [])
    {
        $q = DB::table('pos_purchase_headers')->whereNull('deleted_at');
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $q->where(function($w) use ($s) { $w->where('reference_no', 'like', $s)->orWhere('supplier_name', 'like', $s); });
        }
        if (!empty($filters['date_from'])) $q->whereDate('purchase_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $q->whereDate('purchase_date', '<=', $filters['date_to']);
        return $q->orderByDesc('purchase_date')->orderByDesc('id')->paginate(25)->appends($filters);
    }

    public function storePurchase(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $now = now();
            $total = 0;
            foreach ($data['product_id'] as $i => $pid) {
                $total += ((float)$data['quantity'][$i]) * ((float)$data['unit_cost'][$i]);
            }
            $purchaseId = DB::table('pos_purchase_headers')->insertGetId([
                'reference_no' => $data['reference_no'] ?: ('PUR-' . date('Ymd-His')),
                'supplier_name' => $data['supplier_name'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'status' => 'posted',
                'total_amount' => $total,
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach ($data['product_id'] as $i => $pid) {
                $product = DB::table('pos_products')->where('id', $pid)->lockForUpdate()->first();
                if (!$product) continue;
                $qty = (float)$data['quantity'][$i];
                $cost = (float)$data['unit_cost'][$i];
                $lineTotal = $qty * $cost;
                DB::table('pos_purchase_lines')->insert([
                    'purchase_id' => $purchaseId,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'line_total' => $lineTotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $before = (float)$product->current_stock;
                DB::table('pos_products')->where('id', $product->id)->update([
                    'current_stock' => $before + $qty,
                    'cost_price' => $cost,
                    'updated_at' => $now,
                ]);
                $this->recordMovement($product->id, 'purchase', $qty, $before, $cost, 'Purchase stock: ' . ($data['reference_no'] ?? $purchaseId), $purchaseId);
            }
            return $purchaseId;
        });
    }

    public function purchaseDetails(int $id): array
    {
        $purchase = DB::table('pos_purchase_headers')->where('id', $id)->whereNull('deleted_at')->first();
        abort_if(!$purchase, 404);
        $lines = DB::table('pos_purchase_lines as l')->join('pos_products as p', 'p.id', '=', 'l.product_id')->select('l.*', 'p.name as product_name', 'p.sku', 'p.barcode')->where('l.purchase_id', $id)->get();
        return ['purchase' => $purchase, 'lines' => $lines];
    }

    public function voidPurchase(int $id): void
    {
        DB::transaction(function () use ($id) {
            $purchase = DB::table('pos_purchase_headers')->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
            if (!$purchase || $purchase->status === 'void') return;
            $lines = DB::table('pos_purchase_lines')->where('purchase_id', $id)->get();
            foreach ($lines as $line) {
                $product = DB::table('pos_products')->where('id', $line->product_id)->lockForUpdate()->first();
                if (!$product) continue;
                $before = (float)$product->current_stock;
                $qty = -abs((float)$line->quantity);
                DB::table('pos_products')->where('id', $product->id)->update(['current_stock' => $before + $qty, 'updated_at' => now()]);
                $this->recordMovement($product->id, 'purchase_void', $qty, $before, (float)$line->unit_cost, 'Void purchase: ' . $purchase->reference_no, $id);
            }
            DB::table('pos_purchase_headers')->where('id', $id)->update(['status' => 'void', 'deleted_at' => now(), 'updated_at' => now()]);
        });
    }

    public function recordMovement(int $productId, string $type, float $qty, ?float $before, float $unitCost, ?string $note, ?int $refId): void
    {
        $before = $before ?? 0;
        DB::table('pos_stock_movements')->insert([
            'product_id' => $productId, 'movement_type' => $type, 'reference_type' => null, 'reference_id' => $refId,
            'quantity' => $qty, 'stock_before' => $before, 'stock_after' => $before + $qty,
            'unit_cost' => $unitCost, 'note' => $note, 'created_by' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
