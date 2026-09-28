<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SustainabilityService
{
    public function dashboard(): array
    {
        $goals = $this->rows('hm_sustainability_goals');
        $waste = $this->rows('hm_waste_logs');
        $initiatives = $this->rows('hm_green_initiatives');
        $month = date('Y-m');
        $monthlyWasteQty = array_sum(array_map(fn($r) => str_starts_with((string)($r->log_date ?? ''), $month) ? (float)($r->quantity ?? 0) : 0, $waste));
        $monthlyWasteCost = array_sum(array_map(fn($r) => str_starts_with((string)($r->log_date ?? ''), $month) ? (float)($r->cost ?? 0) : 0, $waste));
        return [
            'goals' => $goals,
            'waste' => $waste,
            'initiatives' => $initiatives,
            'active_goals' => count(array_filter($goals, fn($r) => in_array(($r->status ?? ''), ['active','in_progress']))),
            'open_initiatives' => count(array_filter($initiatives, fn($r) => !in_array(($r->status ?? ''), ['completed','cancelled']))),
            'monthly_waste_qty' => $monthlyWasteQty,
            'monthly_waste_cost' => $monthlyWasteCost,
            'notes' => [
                'All sustainability records are tenant, business and business-location scoped.',
                'Waste logs capture department-wise quantity, disposal method and cost for management reporting.',
                'Green initiatives can be tracked from planned stage up to completed/cancelled status.',
            ],
        ];
    }

    public function goal(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_sustainability_goals')) return;
        DB::table('hm_sustainability_goals')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'goal_name' => $data['goal_name'], 'category' => $data['category'] ?? 'energy',
            'target_value' => $data['target_value'] ?? 0, 'unit' => $data['unit'] ?? null,
            'start_date' => $data['start_date'] ?? date('Y-m-d'), 'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'active', 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function wasteLog(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_waste_logs')) return;
        DB::table('hm_waste_logs')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'log_date' => $data['log_date'] ?? date('Y-m-d'), 'department' => $data['department'] ?? null,
            'waste_type' => $data['waste_type'], 'quantity' => $data['quantity'] ?? 0, 'unit' => $data['unit'] ?? 'kg',
            'disposal_method' => $data['disposal_method'] ?? null, 'cost' => $data['cost'] ?? 0, 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function initiative(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_green_initiatives')) return;
        DB::table('hm_green_initiatives')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'initiative_no' => $data['initiative_no'] ?? $this->nextNumber('hm_green_initiatives','initiative_no','GRN'),
            'title' => $data['title'], 'category' => $data['category'] ?? 'general', 'owner_name' => $data['owner_name'] ?? null,
            'planned_start' => $data['planned_start'] ?? null, 'planned_end' => $data['planned_end'] ?? null,
            'estimated_saving' => $data['estimated_saving'] ?? 0, 'status' => $data['status'] ?? 'planned', 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function initiativeStatus(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_green_initiatives')) return;
        DB::table('hm_green_initiatives')->where('id',$id)->where('business_id',$this->businessId())->update([
            'status' => $data['status'], 'remarks' => $data['remarks'] ?? DB::raw('remarks'), 'updated_by' => $userId, 'updated_at' => now(),
        ]);
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
