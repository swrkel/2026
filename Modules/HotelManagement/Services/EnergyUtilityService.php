<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EnergyUtilityService
{
    public function dashboard(): array
    {
        $meters = $this->rows('hm_utility_meters');
        $readings = $this->rows('hm_utility_readings');
        $allocations = $this->rows('hm_utility_cost_allocations');
        $totalUnits = array_sum(array_map(fn($r) => (float)($r->consumption_units ?? 0), $readings));
        $totalCost = array_sum(array_map(fn($r) => (float)($r->total_cost ?? 0), $readings));
        $openAlloc = count(array_filter($allocations, fn($r) => ($r->status ?? '') !== 'posted'));
        return compact('meters','readings','allocations','totalUnits','totalCost','openAlloc') + [
            'active_meter_count' => count(array_filter($meters, fn($r) => (int)($r->is_active ?? 0) === 1)),
            'notes' => [
                'Utility records are tenant, business and location scoped.',
                'Readings calculate consumption and total cost automatically.',
                'Cost allocations support room, department and manual posting review.'
            ],
        ];
    }

    public function meter(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_utility_meters')) return;
        DB::table('hm_utility_meters')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'meter_no' => $data['meter_no'] ?? $this->nextNumber('hm_utility_meters','meter_no','UTM'),
            'meter_name' => $data['meter_name'], 'utility_type' => $data['utility_type'],
            'department' => $data['department'] ?? null, 'linked_room_id' => $data['linked_room_id'] ?? null,
            'unit_name' => $data['unit_name'] ?? 'Unit', 'rate_per_unit' => (float)($data['rate_per_unit'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0, 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function reading(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_utility_readings')) return;
        $previous = (float)($data['previous_reading'] ?? 0);
        $current = (float)($data['current_reading'] ?? 0);
        $units = max(0, $current - $previous);
        $rate = (float)($data['rate_per_unit'] ?? 0);
        if (!$rate && Schema::hasTable('hm_utility_meters')) {
            $rate = (float) DB::table('hm_utility_meters')->where('business_id',$this->businessId())->where('id',$data['meter_id'])->value('rate_per_unit');
        }
        DB::table('hm_utility_readings')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'reading_no' => $data['reading_no'] ?? $this->nextNumber('hm_utility_readings','reading_no','UTR'),
            'meter_id' => $data['meter_id'], 'reading_date' => $data['reading_date'] ?? date('Y-m-d'),
            'previous_reading' => $previous, 'current_reading' => $current, 'consumption_units' => $units,
            'rate_per_unit' => $rate, 'total_cost' => $units * $rate, 'status' => $data['status'] ?? 'draft',
            'remarks' => $data['remarks'] ?? null, 'created_by' => $userId, 'updated_by' => $userId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function allocation(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_utility_cost_allocations')) return;
        DB::table('hm_utility_cost_allocations')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'allocation_no' => $data['allocation_no'] ?? $this->nextNumber('hm_utility_cost_allocations','allocation_no','UTA'),
            'reading_id' => $data['reading_id'] ?? null, 'allocation_type' => $data['allocation_type'] ?? 'department',
            'target_reference' => $data['target_reference'] ?? null, 'allocated_amount' => (float)($data['allocated_amount'] ?? 0),
            'status' => $data['status'] ?? 'draft', 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function postAllocation(int $id, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_utility_cost_allocations')) return;
        DB::table('hm_utility_cost_allocations')->where('id',$id)->where('business_id',$this->businessId())->update(['status'=>'posted','updated_by'=>$userId,'updated_at'=>now()]);
    }

    private function rows(string $table): array
    {
        if (!Schema::hasTable($table)) return [];
        try { return DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get()->all(); }
        catch (Throwable $e) { return []; }
    }
    private function nextNumber(string $table, string $column, string $prefix): string
    { $last = Schema::hasTable($table) ? DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->value($column) : null; $n=1; if ($last && preg_match('/(\d+)$/',$last,$m)) $n=((int)$m[1])+1; return $prefix.'-'.date('ym').'-'.str_pad((string)$n,5,'0',STR_PAD_LEFT); }
    private function businessId(): int { return (int)(session('business.id') ?? session('business_id') ?? 1); }
    private function locationId(): ?int { return session('business_location_id') ?? session('location_id') ?? null; }
}
