<?php

namespace Modules\POS\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class POSSaleCheckoutService extends POSBaseService
{
    public function __construct(private POSCustomersModuleBridgeService $customersBridge) {}
    public function checkout(Request $request, array $data): array
    {
        foreach (['pos_carts','pos_cart_lines','pos_sales','pos_sale_lines','pos_payments'] as $table) {
            if (!Schema::hasTable($table)) {
                return ['success' => false, 'message' => "Missing POS table: {$table}. Please run the S347 SQL."];
            }
        }

        $cart = DB::table('pos_carts')
            ->where('business_id', $this->businessId())
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if (!$cart) return ['success' => false, 'message' => 'Empty cart.'];
        $lines = DB::table('pos_cart_lines')->where('cart_id', $cart->id)->get();
        if ($lines->isEmpty()) return ['success' => false, 'message' => 'Please add at least one item.'];

        $manualDiscount = (float) ($data['discount_amount'] ?? 0);
        $manualTax = (float) ($data['tax_amount'] ?? 0);
        $subtotal = (float) $lines->sum(fn($l) => ((float)$l->quantity * (float)$l->unit_price));
        $lineDiscount = (float) $lines->sum('discount_amount');
        $lineTax = (float) $lines->sum('tax_amount');
        $discount = $lineDiscount + $manualDiscount;
        $tax = $lineTax + $manualTax;
        $total = max(0, $subtotal - $discount + $tax);
        $paid = (float) $data['paid_amount'];
        $balance = $paid - $total;
        if ($paid + 0.0001 < $total && ($data['payment_method'] ?? '') !== 'credit') {
            return ['success' => false, 'message' => 'Paid amount is less than total. Use Credit payment for outstanding balance.'];
        }

        return DB::transaction(function () use ($request, $cart, $lines, $data, $subtotal, $discount, $tax, $total, $paid, $balance) {
            $saleNo = 'POS-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
            $saleId = DB::table('pos_sales')->insertGetId([
                'business_id' => $this->businessId(),
                'business_location_id' => $cart->business_location_id,
                'register_id' => $cart->register_id,
                'session_id' => $cart->session_id,
                'sale_no' => $saleNo,
                'invoice_no' => $saleNo,
                'customer_id' => $cart->customer_id,
                'customer_name' => $this->customersBridge->displayName(!empty($cart->customer_id) ? (int) $cart->customer_id : null, $cart->customer_name ?: 'Walk-in Customer'),
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'balance_amount' => $balance,
                'status' => 'final',
                'payment_status' => $data['payment_method'] === 'credit' ? 'credit' : ($balance >= -0.0001 ? 'paid' : 'partial'),
                'sale_date' => now(),
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($lines as $line) {
                DB::table('pos_sale_lines')->insert([
                    'sale_id' => $saleId,
                    'pos_sale_id' => $saleId,
                    'product_id' => $line->product_id,
                    'product_name' => $this->productName($line->product_id),
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_amount' => $line->discount_amount,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->reduceStock($line->product_id, (float) $line->quantity);
            }

            DB::table('pos_payments')->insert([
                'business_id' => $this->businessId(),
                'sale_id' => $saleId,
                'pos_sale_id' => $saleId,
                'payment_method' => $data['payment_method'],
                'method' => $data['payment_method'],
                'amount' => $paid,
                'reference_no' => $data['reference_no'] ?? null,
                'payment_date' => now(),
                'paid_on' => now()->toDateString(),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (($data['payment_method'] ?? '') === 'credit' && !empty($cart->customer_id)) {
                // Customer credit is posted to the standalone Customers module ledger.
                $this->customersBridge->postCreditSale((int) $cart->customer_id, $saleId, $saleNo, $total, 'POS credit sale');
            }

            DB::table('pos_cart_lines')->where('cart_id', $cart->id)->delete();
            DB::table('pos_carts')->where('id', $cart->id)->update(['status' => 'completed', 'updated_at' => now()]);

            return ['success' => true, 'sale_id' => $saleId, 'sale_no' => $saleNo,
                'invoice_no' => $saleNo, 'receipt_url' => route('pos.sales.receipt', $saleId)];
        });
    }

    public function receipt(int $saleId): ?array
    {
        if (!Schema::hasTable('pos_sales')) return null;
        $sale = DB::table('pos_sales')->where('id', $saleId)->first();
        if (!$sale) return null;
        $lines = Schema::hasTable('pos_sale_lines') ? DB::table('pos_sale_lines')->where('sale_id', $saleId)->get() : collect();
        $payments = Schema::hasTable('pos_payments') ? DB::table('pos_payments')->where('sale_id', $saleId)->get() : collect();
        return compact('sale', 'lines', 'payments');
    }

    public function salesList(Request $request)
    {
        if (!Schema::hasTable('pos_sales')) return collect();
        $query = DB::table('pos_sales')->where('business_id', $this->businessId());
        if ($request->filled('q')) {
            $q = '%' . $request->input('q') . '%';
            $query->where(function ($w) use ($q) { $w->where('sale_no', 'like', $q)->orWhere('customer_name', 'like', $q); });
        }
        return $query->orderByDesc('id')->paginate(25);
    }

    private function productName($productId): string
    {
        if (!Schema::hasTable('pos_products')) return 'Item';
        return (string) (DB::table('pos_products')->where('id', $productId)->value('name') ?: 'Item');
    }

    private function reduceStock($productId, float $qty): void
    {
        if (!Schema::hasTable('pos_products')) return;
        if (Schema::hasColumn('pos_products', 'stock_quantity')) {
            DB::table('pos_products')->where('id', $productId)->update([
                'stock_quantity' => DB::raw('GREATEST(COALESCE(stock_quantity,0) - ' . ((float)$qty) . ', 0)'),
                'updated_at' => now(),
            ]);
        }
    }
}
