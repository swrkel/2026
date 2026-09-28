<?php

namespace Modules\POS\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class POSCartService extends POSBaseService
{
    public function getActiveCart(Request $request): array
    {
        $cart = $this->cartRecord($request, true);
        return $this->formatCart($cart);
    }

    public function addLine(Request $request, array $data): array
    {
        if (!Schema::hasTable('pos_cart_lines')) {
            return ['success' => false, 'message' => __('pos::page_003.cart_table_not_ready')];
        }
        $cart = $this->cartRecord($request, true);
        $qty = (float) ($data['quantity'] ?? 1);
        $price = (float) ($data['unit_price'] ?? $this->lookupPrice($data['product_id']));
        $discount = (float) ($data['discount_amount'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);
        $lineTotal = max(0, ($qty * $price) - $discount + $tax);

        DB::table('pos_cart_lines')->insert([
            'cart_id' => $cart->id,
            'product_id' => $data['product_id'],
            'quantity' => $qty,
            'unit_price' => $price,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'line_total' => $lineTotal,
            'note' => $data['note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->recalculate($cart->id);
        return ['success' => true, 'cart' => $this->formatCart(DB::table('pos_carts')->find($cart->id))];
    }

    public function updateLine(Request $request, int $lineId, array $data): array
    {
        $line = Schema::hasTable('pos_cart_lines') ? DB::table('pos_cart_lines')->find($lineId) : null;
        if (!$line) return ['success' => false, 'message' => __('pos::page_003.line_not_found')];
        $qty = (float) ($data['quantity'] ?? $line->quantity);
        $price = (float) ($data['unit_price'] ?? $line->unit_price);
        $discount = (float) ($data['discount_amount'] ?? $line->discount_amount);
        $tax = (float) ($data['tax_amount'] ?? $line->tax_amount);
        $lineTotal = max(0, ($qty * $price) - $discount + $tax);
        DB::table('pos_cart_lines')->where('id', $lineId)->update([
            'quantity' => $qty,
            'unit_price' => $price,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'line_total' => $lineTotal,
            'note' => $data['note'] ?? $line->note,
            'updated_at' => now(),
        ]);
        $this->recalculate($line->cart_id);
        return ['success' => true, 'cart' => $this->formatCart(DB::table('pos_carts')->find($line->cart_id))];
    }

    public function removeLine(Request $request, int $lineId): array
    {
        $line = Schema::hasTable('pos_cart_lines') ? DB::table('pos_cart_lines')->find($lineId) : null;
        if (!$line) return ['success' => false];
        DB::table('pos_cart_lines')->where('id', $lineId)->delete();
        $this->recalculate($line->cart_id);
        return ['success' => true, 'cart' => $this->formatCart(DB::table('pos_carts')->find($line->cart_id))];
    }

    public function clear(Request $request): array
    {
        $cart = $this->cartRecord($request, false);
        if ($cart && Schema::hasTable('pos_cart_lines')) {
            DB::table('pos_cart_lines')->where('cart_id', $cart->id)->delete();
            $this->recalculate($cart->id);
        }
        return ['success' => true, 'cart' => $cart ? $this->formatCart(DB::table('pos_carts')->find($cart->id)) : []];
    }

    public function setCustomer(Request $request, array $data): array
    {
        $cart = $this->cartRecord($request, true);
        $customerId = !empty($data['customer_id']) ? (int) $data['customer_id'] : null;
        $customerName = $data['customer_name'] ?? null;

        if ($customerId) {
            $bridge = app(\Modules\POS\Services\POSCustomersModuleBridgeService::class);
            $customer = $bridge->find($customerId);
            if (!$customer) {
                return ['success' => false, 'message' => 'Selected customer was not found in the Customers module.'];
            }
            $customerName = $customer->name ?? $customerName;
        }

        DB::table('pos_carts')->where('id', $cart->id)->update([
            'customer_id' => $customerId,
            'customer_name' => $customerName ?: 'Walk-in Customer',
            'updated_at' => now(),
        ]);
        return ['success' => true, 'cart' => $this->formatCart(DB::table('pos_carts')->find($cart->id))];
    }

    public function hold(Request $request, ?string $note = null): array
    {
        return $this->markCart($request, 'held', $note);
    }

    public function suspend(Request $request, ?string $note = null): array
    {
        return $this->markCart($request, 'suspended', $note);
    }

    public function resume(Request $request, int $cartId): array
    {
        DB::table('pos_carts')->where('id', $cartId)->update(['status' => 'active', 'user_id' => auth()->id(), 'updated_at' => now()]);
        return ['success' => true, 'cart' => $this->formatCart(DB::table('pos_carts')->find($cartId))];
    }

    public function heldSales(Request $request)
    {
        return $this->cartList('held');
    }

    public function suspendedSales(Request $request)
    {
        return $this->cartList('suspended');
    }

    public function quotations(Request $request)
    {
        return Schema::hasTable('pos_quotations') ? DB::table('pos_quotations')->orderByDesc('id')->paginate(25) : collect();
    }

    public function createQuotation(Request $request, ?string $note = null): array
    {
        if (!Schema::hasTable('pos_quotations')) return ['success' => false];
        $cart = $this->cartRecord($request, false);
        if (!$cart) return ['success' => false, 'message' => __('pos::page_003.empty_cart')];
        $quotationId = DB::table('pos_quotations')->insertGetId([
            'business_id' => $this->businessId(),
            'business_location_id' => $cart->business_location_id,
            'quotation_no' => 'QT-' . now()->format('YmdHis'),
            'customer_id' => $cart->customer_id,
            'customer_name' => $cart->customer_name,
            'subtotal' => $cart->subtotal,
            'discount_amount' => $cart->discount_amount,
            'tax_amount' => $cart->tax_amount,
            'total_amount' => $cart->total_amount,
            'status' => 'draft',
            'note' => $note,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if (Schema::hasTable('pos_quotation_lines')) {
            foreach (DB::table('pos_cart_lines')->where('cart_id', $cart->id)->get() as $line) {
                DB::table('pos_quotation_lines')->insert([
                    'quotation_id' => $quotationId,
                    'product_id' => $line->product_id,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_amount' => $line->discount_amount,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                    'note' => $line->note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        return ['success' => true, 'quotation_id' => $quotationId];
    }

    private function cartRecord(Request $request, bool $create): ?object
    {
        if (!Schema::hasTable('pos_carts')) return null;
        $cart = DB::table('pos_carts')
            ->where('business_id', $this->businessId())
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
        if (!$cart && $create) {
            $id = DB::table('pos_carts')->insertGetId([
                'business_id' => $this->businessId(),
                'business_location_id' => $request->session()->get('user.business_location_id') ?? $request->input('location_id'),
                'register_id' => $request->input('register_id'),
                'session_id' => $request->input('session_id'),
                'user_id' => auth()->id(),
                'status' => 'active',
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return DB::table('pos_carts')->find($id);
        }
        return $cart;
    }

    private function formatCart(?object $cart): array
    {
        if (!$cart) return ['lines' => [], 'subtotal' => 0, 'discount_amount' => 0, 'tax_amount' => 0, 'total_amount' => 0];
        $lines = Schema::hasTable('pos_cart_lines') ? DB::table('pos_cart_lines')->where('cart_id', $cart->id)->get() : collect();
        return ['id' => $cart->id, 'customer_id' => $cart->customer_id ?? null, 'customer_name' => $cart->customer_name ?? __('pos::page_003.walk_in_customer'), 'status' => $cart->status, 'lines' => $lines, 'subtotal' => (float) $cart->subtotal, 'discount_amount' => (float) $cart->discount_amount, 'tax_amount' => (float) $cart->tax_amount, 'total_amount' => (float) $cart->total_amount];
    }

    private function recalculate(int $cartId): void
    {
        $summary = DB::table('pos_cart_lines')->where('cart_id', $cartId)->selectRaw('SUM(quantity * unit_price) subtotal, SUM(discount_amount) discount_amount, SUM(tax_amount) tax_amount, SUM(line_total) total_amount')->first();
        DB::table('pos_carts')->where('id', $cartId)->update(['subtotal' => $summary->subtotal ?? 0, 'discount_amount' => $summary->discount_amount ?? 0, 'tax_amount' => $summary->tax_amount ?? 0, 'total_amount' => $summary->total_amount ?? 0, 'updated_at' => now()]);
    }

    private function lookupPrice($productId): float
    {
        if (! $this->tableExists('pos_products')) {
            return 0.0;
        }

        $column = $this->firstExistingColumn('pos_products', [
            'sell_price',
            'selling_price',
            'default_sell_price',
            'unit_price',
            'price',
        ]);

        return $column
            ? (float) $this->connection()->table('pos_products')->where('id', $productId)->value($column)
            : 0.0;
    }

    private function markCart(Request $request, string $status, ?string $note): array
    {
        $cart = $this->cartRecord($request, false);
        if (!$cart) return ['success' => false, 'message' => __('pos::page_003.empty_cart')];
        DB::table('pos_carts')->where('id', $cart->id)->update(['status' => $status, 'note' => $note, 'updated_at' => now()]);
        return ['success' => true];
    }

    private function cartList(string $status)
    {
        return Schema::hasTable('pos_carts') ? DB::table('pos_carts')->where('status', $status)->orderByDesc('id')->paginate(25) : collect();
    }

    public function searchProducts(?string $query = null, int $limit = 24): array
    {
        if (! $this->tableExists('pos_products')) {
            return [];
        }

        $columns = $this->columns('pos_products');
        // Use the exact connection selected for this tenant request.  Do not
        // allow a schema check on one connection and the search query on a
        // different/default connection in multi-tenant deployments.
        $products = $this->connection()->table('pos_products');
        $businessId = $this->businessId();

        // Multi-business safety: only apply the business filter when the current
        // business can actually be resolved. The old code used only
        // session('business.id'); on this ERP the reliable key is normally
        // user.business_id, so a null value could hide every valid product.
        if (in_array('business_id', $columns, true) && $businessId !== null) {
            $products->where(function ($w) use ($businessId) {
                $w->whereNull('business_id')->orWhere('business_id', $businessId);
            });
        }

        if (in_array('deleted_at', $columns, true)) {
            $products->whereNull('deleted_at');
        }
        if (in_array('is_active', $columns, true)) {
            $products->where('is_active', 1);
        }

        $term = trim((string) $query);
        $searchColumns = array_values(array_intersect(['name', 'sku', 'barcode'], $columns));

        if ($term !== '' && $searchColumns !== []) {
            $like = '%' . $term . '%';
            $products->where(function ($w) use ($like, $searchColumns) {
                foreach ($searchColumns as $index => $column) {
                    if ($index === 0) {
                        $w->where($column, 'like', $like);
                    } else {
                        $w->orWhere($column, 'like', $like);
                    }
                }
            });
        }

        $priceColumns = ['sell_price', 'selling_price', 'default_sell_price', 'unit_price', 'price'];
        $stockColumns = ['stock_quantity', 'stock_qty', 'current_stock', 'qty_available'];

        $orderColumn = in_array('name', $columns, true) ? 'name' : (in_array('id', $columns, true) ? 'id' : null);
        if ($orderColumn !== null) {
            $products->orderBy($orderColumn);
        }

        return $products->limit(max(1, min($limit, 100)))
            ->get()
            ->map(function ($p) use ($priceColumns, $stockColumns) {
                $price = 0.0;
                foreach ($priceColumns as $column) {
                    if (property_exists($p, $column) && $p->{$column} !== null) {
                        $price = (float) $p->{$column};
                        break;
                    }
                }

                $stock = 0.0;
                foreach ($stockColumns as $column) {
                    if (property_exists($p, $column) && $p->{$column} !== null) {
                        $stock = (float) $p->{$column};
                        break;
                    }
                }

                return [
                    'id' => $p->id,
                    'name' => $p->name ?? 'Item',
                    'sku' => $p->sku ?? '',
                    'barcode' => $p->barcode ?? '',
                    'sell_price' => $price,
                    'stock_quantity' => $stock,
                ];
            })->toArray();
    }

    public function addByBarcode(Request $request, string $barcode): array
    {
        if (! $this->tableExists('pos_products')) {
            return ['success' => false, 'message' => 'POS products table not ready.'];
        }

        $columns = $this->columns('pos_products');
        $businessId = $this->businessId();
        $query = $this->connection()->table('pos_products');

        if (in_array('business_id', $columns, true) && $businessId !== null) {
            $query->where(function ($w) use ($businessId) {
                $w->whereNull('business_id')->orWhere('business_id', $businessId);
            });
        }
        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('deleted_at');
        }
        if (in_array('is_active', $columns, true)) {
            $query->where('is_active', 1);
        }

        $query->where(function ($w) use ($barcode, $columns) {
            $started = false;
            if (in_array('barcode', $columns, true)) {
                $w->where('barcode', $barcode);
                $started = true;
            }
            if (in_array('sku', $columns, true)) {
                $started ? $w->orWhere('sku', $barcode) : $w->where('sku', $barcode);
            }
        });

        $product = $query->first();
        if (! $product) {
            return ['success' => false, 'message' => 'Barcode item was not found in POS products.'];
        }

        return $this->addLine($request, [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $this->lookupPrice($product->id),
        ]);
    }

}
