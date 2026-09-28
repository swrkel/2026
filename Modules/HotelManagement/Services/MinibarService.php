<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MinibarService
{
    public function dashboard(): array
    {
        $items = $this->items();
        $consumptions = $this->consumptions();
        $today = now()->toDateString();
        $todayConsumptions = array_filter($consumptions, fn($c) => ($c->consumption_date ?? null) === $today);
        $pending = array_filter($consumptions, fn($c) => in_array(($c->status ?? ''), ['draft','confirmed']));
        $todayRevenue = array_sum(array_map(fn($c) => (float)($c->total_amount ?? 0), $todayConsumptions));
        $lowStock = array_filter($items, fn($i) => (float)($i->current_stock ?? 0) <= (float)($i->reorder_level ?? 0));

        return [
            'items_count' => count($items),
            'today_consumptions' => count($todayConsumptions),
            'pending_count' => count($pending),
            'today_revenue' => $todayRevenue,
            'low_stock_count' => count($lowStock),
            'items' => $items,
            'consumptions' => $consumptions,
            'notes' => [
                'Mini bar is scoped by tenant business and business location.',
                'Consumption can be posted to the hotel folio through the local room-charge bridge.',
                'Stock is reduced when a consumption entry is saved, with negative-stock protection.',
            ],
        ];
    }

    public function saveItem(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_minibar_items')) return;
        $values = [
            'business_location_id' => $this->locationId(),
            'item_name' => $data['item_name'],
            'category' => $data['category'] ?? null,
            'unit' => $data['unit'] ?? null,
            'selling_price' => $data['selling_price'] ?? 0,
            'cost_price' => $data['cost_price'] ?? 0,
            'current_stock' => $data['current_stock'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_minibar_items')->where('business_id', $this->businessId())->where('item_code', $data['item_code']);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_minibar_items')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'item_code' => $data['item_code'],
                'created_by' => $userId,
                'created_at' => now(),
            ]));
        }
    }

    public function saveConsumption(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_minibar_consumptions') || !Schema::hasTable('hm_minibar_items')) return;
        DB::transaction(function () use ($data, $userId) {
            $item = DB::table('hm_minibar_items')
                ->where('business_id', $this->businessId())
                ->where('id', $data['item_id'])
                ->lockForUpdate()
                ->first();
            if (!$item) return;

            $qty = (float)($data['qty'] ?? 0);
            if ($qty <= 0) return;
            $available = (float)($item->current_stock ?? 0);
            if ($available < $qty) {
                throw new \RuntimeException('Not enough mini bar stock for '.$item->item_name.'. Available: '.$available);
            }

            $unitPrice = (float)($data['unit_price'] ?? $item->selling_price ?? 0);
            $discount = (float)($data['discount_amount'] ?? 0);
            $tax = (float)($data['tax_amount'] ?? 0);
            $total = max(0, ($qty * $unitPrice) - $discount + $tax);
            $consumptionNo = $data['consumption_no'] ?: $this->nextConsumptionNo();

            DB::table('hm_minibar_consumptions')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'consumption_no' => $consumptionNo,
                'room_id' => $data['room_id'] ?? null,
                'room_no' => $data['room_no'] ?? null,
                'reservation_id' => $data['reservation_id'] ?? null,
                'folio_id' => $data['folio_id'] ?? null,
                'guest_name' => $data['guest_name'] ?? null,
                'item_id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'consumption_date' => $data['consumption_date'] ?? now()->toDateString(),
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'status' => $data['status'] ?? 'draft',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('hm_minibar_items')->where('id', $item->id)->update([
                'current_stock' => DB::raw('COALESCE(current_stock,0) - '.$qty),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    public function updateStatus(int $id, string $status, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_minibar_consumptions')) return;
        DB::table('hm_minibar_consumptions')->where('id', $id)->where('business_id', $this->businessId())->update([
            'status' => $status,
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    public function postToFolio(int $id, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_minibar_consumptions')) return;
        DB::transaction(function () use ($id, $userId) {
            $row = DB::table('hm_minibar_consumptions')->where('id', $id)->where('business_id', $this->businessId())->lockForUpdate()->first();
            if (!$row || empty($row->folio_id) || ($row->status ?? '') === 'posted') return;
            $chargeId = null;
            if (Schema::hasTable('hm_room_charges')) {
                $chargeId = DB::table('hm_room_charges')->insertGetId([
                    'business_id' => $this->businessId(),
                    'business_location_id' => $this->locationId(),
                    'folio_id' => $row->folio_id,
                    'charge_date' => now()->toDateString(),
                    'charge_type' => 'mini_bar',
                    'description' => 'Mini Bar - '.$row->item_name.' x '.$row->qty,
                    'amount' => $row->total_amount,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('hm_minibar_consumptions')->where('id', $id)->update([
                'status' => 'posted',
                'posted_charge_id' => $chargeId,
                'posted_by' => $userId,
                'posted_at' => now(),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    protected function items(): array
    {
        if (!Schema::hasTable('hm_minibar_items')) return [];
        try { return DB::table('hm_minibar_items')->where('business_id', $this->businessId())->orderBy('item_name')->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function consumptions(): array
    {
        if (!Schema::hasTable('hm_minibar_consumptions')) return [];
        try { return DB::table('hm_minibar_consumptions')->where('business_id', $this->businessId())->orderByDesc('consumption_date')->orderByDesc('id')->limit(150)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function nextConsumptionNo(): string
    {
        $prefix = 'HMMB'.date('ym');
        $next = 1;
        if (Schema::hasTable('hm_minibar_consumptions')) {
            $last = DB::table('hm_minibar_consumptions')->where('business_id', $this->businessId())->where('consumption_no','like',$prefix.'%')->orderByDesc('id')->value('consumption_no');
            if ($last) $next = ((int)substr($last, -5)) + 1;
        }
        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
