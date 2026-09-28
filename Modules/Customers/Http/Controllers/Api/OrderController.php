<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderController extends BaseDealerApiController
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('customer_portal_orders')) {
            return $this->success(['rows' => []]);
        }

        $query = DB::table('customer_portal_orders')
            ->where('business_id', $this->businessId($request))
            ->where('contact_id', $this->customerId($request));

        if (Schema::hasColumn('customer_portal_orders', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('search')) {
            $search = '%' . $request->get('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', $search)->orWhere('remarks', 'like', $search);
            });
        }

        $rows = $query->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit($this->paginateLimit($request, 100, 500))
            ->get();

        return $this->success(['rows' => $rows]);
    }

    public function show(Request $request, $id)
    {
        if (!Schema::hasTable('customer_portal_orders')) {
            return $this->fail('Order table is not available.', 404);
        }

        $order = DB::table('customer_portal_orders')
            ->where('business_id', $this->businessId($request))
            ->where('contact_id', $this->customerId($request))
            ->where('id', (int) $id)
            ->first();

        if (empty($order)) {
            return $this->fail('Order not found.', 404);
        }

        $lines = collect();
        if (Schema::hasTable('customer_portal_order_lines')) {
            $lines = DB::table('customer_portal_order_lines')
                ->where('customer_portal_order_id', (int) $order->id)
                ->orderBy('id')
                ->get();
        }

        return $this->success(['order' => $order, 'lines' => $lines]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('customer_portal_orders') || !Schema::hasTable('customer_portal_order_lines')) {
            return $this->fail('Customer portal order tables are missing. Please run CUS_024 SQL first.', 503);
        }

        $data = $request->validate([
            'required_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.remarks' => 'nullable|string|max:1000',
        ]);

        $businessId = $this->businessId($request);
        $customerId = $this->customerId($request);
        $productIds = collect($data['lines'])->pluck('product_id')->map(fn($id) => (int) $id)->all();
        $products = $this->products($businessId, $productIds)->keyBy('id');

        $lines = [];
        $totalQty = 0.0;
        $totalAmount = 0.0;

        foreach ($data['lines'] as $line) {
            $product = $products->get((int) $line['product_id']);
            $qty = (float) $line['quantity'];
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
                'sku' => $product->sku ?? '',
                'unit' => $product->unit ?? '',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'remarks' => $line['remarks'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (count($lines) === 0) {
            return $this->fail('Please add at least one valid product with quantity.');
        }

        DB::beginTransaction();
        try {
            $orderId = DB::table('customer_portal_orders')->insertGetId([
                'business_id' => $businessId,
                'contact_id' => $customerId,
                'order_no' => $this->nextOrderNo($businessId),
                'order_date' => date('Y-m-d'),
                'required_date' => $data['required_date'] ?? null,
                'status' => 'submitted',
                'remarks' => $data['remarks'] ?? null,
                'total_qty' => $totalQty,
                'total_amount' => $totalAmount,
                'created_by_customer' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($lines as &$line) {
                $line['customer_portal_order_id'] = $orderId;
            }
            DB::table('customer_portal_order_lines')->insert($lines);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CUS_031 API dealer order submit failed', ['error' => $e->getMessage()]);
            return $this->fail('Unable to submit order. Please try again.', 500);
        }

        return $this->success(['order_id' => $orderId], 'Order submitted successfully.', 201);
    }

    protected function products(int $businessId, array $productIds)
    {
        if (empty($productIds) || !Schema::hasTable('products')) {
            return collect();
        }

        $rows = DB::table('products')
            ->where('business_id', $businessId)
            ->whereIn('id', $productIds)
            ->whereNull('deleted_at')
            ->select(['id', 'name', Schema::hasColumn('products', 'sku') ? 'sku' : DB::raw("'' as sku")])
            ->get();

        $prices = [];
        if (Schema::hasTable('variations')) {
            $prices = DB::table('variations')
                ->whereIn('product_id', $productIds)
                ->select(['product_id', DB::raw('MAX(default_sell_price) as price')])
                ->groupBy('product_id')
                ->pluck('price', 'product_id')
                ->toArray();
        }

        return $rows->map(function ($row) use ($prices) {
            $row->unit_price = (float) ($prices[$row->id] ?? 0);
            $row->unit = '';
            return $row;
        });
    }

    protected function nextOrderNo(int $businessId): string
    {
        $prefix = 'DD-' . date('Ymd') . '-';
        $count = ((int) DB::table('customer_portal_orders')
            ->where('business_id', $businessId)
            ->where('order_no', 'like', $prefix . '%')
            ->count()) + 1;
        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
