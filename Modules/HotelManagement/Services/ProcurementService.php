<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProcurementService
{
    public function dashboard(): array
    {
        $suppliers = $this->suppliers();
        $requests = $this->purchaseRequests();
        $orders = $this->purchaseOrders();
        $grns = $this->grns();
        $openOrders = array_filter($orders, fn($o) => in_array(($o->status ?? ''), ['draft','approved','ordered','partial']));
        $pendingRequests = array_filter($requests, fn($r) => in_array(($r->status ?? ''), ['draft','submitted','approved']));
        $orderedValue = array_sum(array_map(fn($o) => (float)($o->net_amount ?? 0), $orders));
        $receivedValue = array_sum(array_map(fn($g) => (float)($g->accepted_value ?? 0), $grns));

        return [
            'suppliers_count' => count($suppliers),
            'pending_requests' => count($pendingRequests),
            'open_orders' => count($openOrders),
            'ordered_value' => $orderedValue,
            'received_value' => $receivedValue,
            'suppliers' => $suppliers,
            'requests' => $requests,
            'orders' => $orders,
            'grns' => $grns,
            'notes' => [
                'Procurement is scoped by tenant business and business location to prevent cross-business purchases.',
                'Purchase request, purchase order and GRN records are standalone HotelManagement records.',
                'Accepted GRN quantity can later be bridged to the existing Inventory module without duplicating Inventory master logic.',
            ],
        ];
    }

    public function saveSupplier(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_procurement_suppliers')) return;
        $values = [
            'business_location_id' => $this->locationId(),
            'contact_person' => $data['contact_person'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'category' => $data['category'] ?? null,
            'credit_days' => $data['credit_days'] ?? 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_procurement_suppliers')->where('business_id', $this->businessId())->where('supplier_name', $data['supplier_name']);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_procurement_suppliers')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'supplier_name' => $data['supplier_name'],
                'supplier_code' => $this->nextNo('hm_procurement_suppliers', 'supplier_code', 'HMSUP'),
                'created_by' => $userId,
                'created_at' => now(),
            ]));
        }
    }

    public function savePurchaseRequest(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_purchase_requests')) return;
        $qty = (float)($data['qty'] ?? 0);
        $unit = (float)($data['estimated_unit_cost'] ?? 0);
        $requestNo = $data['request_no'] ?: $this->nextNo('hm_purchase_requests', 'request_no', 'HMPR');
        DB::table('hm_purchase_requests')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'request_no' => $requestNo,
            'request_date' => $data['request_date'] ?? now()->toDateString(),
            'department' => $data['department'],
            'requested_by' => $data['requested_by'] ?? null,
            'required_date' => $data['required_date'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'item_name' => $data['item_name'],
            'description' => $data['description'] ?? null,
            'qty' => $qty,
            'estimated_unit_cost' => $unit,
            'estimated_total' => $qty * $unit,
            'status' => $data['status'] ?? 'submitted',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function savePurchaseOrder(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_purchase_orders')) return;
        $qty = (float)($data['qty'] ?? 0);
        $unit = (float)($data['unit_cost'] ?? 0);
        $discount = (float)($data['discount_amount'] ?? 0);
        $tax = (float)($data['tax_amount'] ?? 0);
        $gross = $qty * $unit;
        $net = max(0, $gross - $discount + $tax);
        DB::table('hm_purchase_orders')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'po_no' => $data['po_no'] ?: $this->nextNo('hm_purchase_orders', 'po_no', 'HMPO'),
            'po_date' => $data['po_date'] ?? now()->toDateString(),
            'supplier_id' => $data['supplier_id'],
            'request_id' => $data['request_id'] ?? null,
            'item_name' => $data['item_name'],
            'description' => $data['description'] ?? null,
            'qty' => $qty,
            'unit_cost' => $unit,
            'gross_amount' => $gross,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'net_amount' => $net,
            'received_qty' => 0,
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'status' => $data['status'] ?? 'ordered',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function receiveGoods(int $poId, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_purchase_orders') || !Schema::hasTable('hm_goods_received_notes')) return;
        DB::transaction(function () use ($poId, $data, $userId) {
            $po = DB::table('hm_purchase_orders')->where('id', $poId)->where('business_id', $this->businessId())->first();
            if (!$po) return;
            $received = (float)($data['received_qty'] ?? 0);
            $accepted = (float)($data['accepted_qty'] ?? $received);
            $rejected = (float)($data['rejected_qty'] ?? max(0, $received - $accepted));
            $acceptedValue = $accepted * (float)($po->unit_cost ?? 0);
            DB::table('hm_goods_received_notes')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'grn_no' => $data['grn_no'] ?: $this->nextNo('hm_goods_received_notes', 'grn_no', 'HMGRN'),
                'po_id' => $poId,
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'received_qty' => $received,
                'accepted_qty' => $accepted,
                'rejected_qty' => $rejected,
                'accepted_value' => $acceptedValue,
                'received_by' => $data['received_by'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $totalReceived = (float)($po->received_qty ?? 0) + $accepted;
            DB::table('hm_purchase_orders')->where('id', $poId)->update([
                'received_qty' => $totalReceived,
                'status' => $totalReceived >= (float)($po->qty ?? 0) ? 'received' : 'partial',
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    public function updateStatus(string $type, int $id, array $data, ?int $userId = null): void
    {
        $table = ['request' => 'hm_purchase_requests', 'order' => 'hm_purchase_orders'][$type] ?? null;
        if (!$table || !Schema::hasTable($table)) return;
        DB::table($table)->where('id', $id)->where('business_id', $this->businessId())->update([
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? DB::raw('remarks'),
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    protected function suppliers(): array
    {
        if (!Schema::hasTable('hm_procurement_suppliers')) return [];
        try { return DB::table('hm_procurement_suppliers')->where('business_id', $this->businessId())->orderBy('supplier_name')->limit(150)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function purchaseRequests(): array
    {
        if (!Schema::hasTable('hm_purchase_requests')) return [];
        try { return DB::table('hm_purchase_requests')->where('business_id', $this->businessId())->orderByDesc('id')->limit(150)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function purchaseOrders(): array
    {
        if (!Schema::hasTable('hm_purchase_orders')) return [];
        try {
            return DB::table('hm_purchase_orders as po')
                ->leftJoin('hm_procurement_suppliers as s','po.supplier_id','=','s.id')
                ->where('po.business_id', $this->businessId())
                ->select('po.*','s.supplier_name')
                ->orderByDesc('po.id')->limit(150)->get()->toArray();
        } catch (Throwable $e) { return []; }
    }

    protected function grns(): array
    {
        if (!Schema::hasTable('hm_goods_received_notes')) return [];
        try { return DB::table('hm_goods_received_notes')->where('business_id', $this->businessId())->orderByDesc('id')->limit(100)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function nextNo(string $table, string $column, string $prefix): string
    {
        $prefix = $prefix.date('ym');
        $next = 1;
        if (Schema::hasTable($table)) {
            $last = DB::table($table)->where($column, 'like', $prefix.'%')->orderByDesc('id')->value($column);
            if ($last) $next = ((int)substr($last, -5)) + 1;
        }
        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? session('business_id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
