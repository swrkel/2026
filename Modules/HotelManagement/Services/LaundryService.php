<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LaundryService
{
    public function dashboard(): array
    {
        $services = $this->services();
        $orders = $this->orders();
        $today = now()->toDateString();
        $todayOrders = array_filter($orders, fn($o) => ($o->order_date ?? null) === $today);
        $pending = array_filter($orders, fn($o) => in_array(($o->status ?? ''), ['received','washing','pressing','ready']));
        $todayRevenue = array_sum(array_map(fn($o) => (float)($o->total_amount ?? 0), $todayOrders));
        $unpaid = array_filter($orders, fn($o) => (float)($o->balance_amount ?? 0) > 0);

        return [
            'services_count' => count($services),
            'today_orders' => count($todayOrders),
            'pending_count' => count($pending),
            'today_revenue' => $todayRevenue,
            'unpaid_count' => count($unpaid),
            'services' => $services,
            'orders' => $orders,
            'notes' => [
                'Laundry is scoped by tenant business and business location.',
                'Guest laundry can be charged directly to room folio or settled by cash/card.',
                'Status flow supports received, washing, pressing, ready, delivered and cancelled.',
            ],
        ];
    }

    public function saveService(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_laundry_services')) return;
        $values = [
            'business_location_id' => $this->locationId(),
            'service_name' => $data['service_name'],
            'category' => $data['category'] ?? null,
            'unit' => $data['unit'] ?? null,
            'standard_rate' => $data['standard_rate'] ?? 0,
            'express_rate' => $data['express_rate'] ?? 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_laundry_services')->where('business_id', $this->businessId())->where('service_code', $data['service_code']);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_laundry_services')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'service_code' => $data['service_code'],
                'created_by' => $userId,
                'created_at' => now(),
            ]));
        }
    }

    public function saveOrder(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_laundry_orders') || !Schema::hasTable('hm_laundry_services')) return;
        DB::transaction(function () use ($data, $userId) {
            $service = DB::table('hm_laundry_services')
                ->where('business_id', $this->businessId())
                ->where('id', $data['service_id'])
                ->first();
            if (!$service) return;

            $qty = (float)($data['qty'] ?? 0);
            if ($qty <= 0) return;
            $rateType = $data['rate_type'] ?? 'standard';
            $defaultRate = $rateType === 'express' ? ($service->express_rate ?? $service->standard_rate ?? 0) : ($service->standard_rate ?? 0);
            $unitPrice = (float)($data['unit_price'] ?? $defaultRate);
            $discount = (float)($data['discount_amount'] ?? 0);
            $tax = (float)($data['tax_amount'] ?? 0);
            $total = max(0, ($qty * $unitPrice) - $discount + $tax);
            $orderNo = $data['order_no'] ?: $this->nextOrderNo();

            DB::table('hm_laundry_orders')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'order_no' => $orderNo,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'room_id' => $data['room_id'] ?? null,
                'room_no' => $data['room_no'] ?? null,
                'reservation_id' => $data['reservation_id'] ?? null,
                'folio_id' => $data['folio_id'] ?? null,
                'guest_name' => $data['guest_name'] ?? null,
                'service_id' => $service->id,
                'service_code' => $service->service_code,
                'service_name' => $service->service_name,
                'qty' => $qty,
                'rate_type' => $rateType,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'paid_amount' => 0,
                'balance_amount' => $total,
                'pickup_time' => $data['pickup_time'] ?? null,
                'delivery_time' => $data['delivery_time'] ?? null,
                'status' => $data['status'] ?? 'received',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function updateStatus(int $id, string $status, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_laundry_orders')) return;
        DB::table('hm_laundry_orders')->where('id', $id)->where('business_id', $this->businessId())->update([
            'status' => $status,
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    public function recordPayment(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_laundry_orders')) return;
        DB::transaction(function () use ($id, $data, $userId) {
            $row = DB::table('hm_laundry_orders')->where('id', $id)->where('business_id', $this->businessId())->lockForUpdate()->first();
            if (!$row) return;
            $paid = min((float)$data['paid_amount'], (float)($row->balance_amount ?? $row->total_amount));
            $newPaid = (float)($row->paid_amount ?? 0) + $paid;
            $balance = max(0, (float)($row->total_amount ?? 0) - $newPaid);
            DB::table('hm_laundry_orders')->where('id', $id)->update([
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'paid_amount' => $newPaid,
                'balance_amount' => $balance,
                'status' => $balance <= 0 && ($row->status ?? '') === 'delivered' ? 'paid' : ($row->status ?? 'received'),
                'paid_by' => $userId,
                'paid_at' => now(),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    public function postToFolio(int $id, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_laundry_orders')) return;
        DB::transaction(function () use ($id, $userId) {
            $row = DB::table('hm_laundry_orders')->where('id', $id)->where('business_id', $this->businessId())->lockForUpdate()->first();
            if (!$row || empty($row->folio_id) || ($row->status ?? '') === 'posted') return;
            $chargeId = null;
            if (Schema::hasTable('hm_room_charges')) {
                $chargeId = DB::table('hm_room_charges')->insertGetId([
                    'business_id' => $this->businessId(),
                    'business_location_id' => $this->locationId(),
                    'folio_id' => $row->folio_id,
                    'charge_date' => now()->toDateString(),
                    'charge_type' => 'laundry',
                    'description' => 'Laundry - '.$row->service_name.' x '.$row->qty,
                    'amount' => $row->total_amount,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('hm_laundry_orders')->where('id', $id)->update([
                'status' => 'posted',
                'posted_charge_id' => $chargeId,
                'posted_by' => $userId,
                'posted_at' => now(),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    protected function services(): array
    {
        if (!Schema::hasTable('hm_laundry_services')) return [];
        try { return DB::table('hm_laundry_services')->where('business_id', $this->businessId())->orderBy('service_name')->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function orders(): array
    {
        if (!Schema::hasTable('hm_laundry_orders')) return [];
        try { return DB::table('hm_laundry_orders')->where('business_id', $this->businessId())->orderByDesc('order_date')->orderByDesc('id')->limit(150)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function nextOrderNo(): string
    {
        $prefix = 'HMLD'.date('ym');
        $next = 1;
        if (Schema::hasTable('hm_laundry_orders')) {
            $last = DB::table('hm_laundry_orders')->where('business_id', $this->businessId())->where('order_no','like',$prefix.'%')->orderByDesc('id')->value('order_no');
            if ($last) $next = ((int)substr($last, -5)) + 1;
        }
        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
