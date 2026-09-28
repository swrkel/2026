<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AssetEquipmentService
{
    public function dashboard(): array
    {
        $assets = $this->rows('hm_assets', 500);
        $assignments = $this->rows('hm_asset_assignments', 300);
        $inspections = $this->rows('hm_asset_inspections', 300);
        $rooms = $this->rows('hm_rooms', 300);
        $activeAssets = array_filter($assets, fn($a) => ($a->status ?? 'active') === 'active');
        $maintenanceDue = array_filter($inspections, fn($i) => !empty($i->maintenance_required) || (($i->next_inspection_date ?? null) && strtotime($i->next_inspection_date) <= strtotime(date('Y-m-d'))));

        return [
            'assets' => $assets,
            'assignments' => $assignments,
            'inspections' => $inspections,
            'rooms' => $rooms,
            'total_assets' => count($assets),
            'active_assets' => count($activeAssets),
            'assigned_assets' => count(array_filter($assignments, fn($a) => ($a->status ?? 'active') === 'active')),
            'maintenance_due' => count($maintenanceDue),
            'asset_value' => array_sum(array_map(fn($a) => (float)($a->current_value ?? $a->purchase_cost ?? 0), $activeAssets)),
            'notes' => [
                'Asset records are tenant database, business and location scoped.',
                'This control layer helps hotels track room equipment, F&B equipment, housekeeping tools and operational assets.',
                'Inspection records can be used to trigger maintenance work orders without duplicating the existing maintenance module.',
            ],
        ];
    }

    public function asset(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_assets')) return;
        DB::table('hm_assets')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'asset_code' => $data['asset_code'] ?? $this->nextNumber('hm_assets','asset_code','HAS'),
            'asset_name' => $data['asset_name'],
            'asset_category' => $data['asset_category'],
            'department' => $data['department'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'purchase_date' => $data['purchase_date'] ?? null,
            'purchase_cost' => $data['purchase_cost'] ?? 0,
            'current_value' => $data['current_value'] ?? ($data['purchase_cost'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function assignment(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_asset_assignments')) return;
        DB::table('hm_asset_assignments')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'asset_id' => $data['asset_id'],
            'assignment_no' => $this->nextNumber('hm_asset_assignments','assignment_no','HAA'),
            'assigned_to_type' => $data['assigned_to_type'],
            'assigned_to_id' => $data['assigned_to_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'assigned_date' => $data['assigned_date'],
            'return_due_date' => $data['return_due_date'] ?? null,
            'condition_out' => $data['condition_out'] ?? 'good',
            'status' => 'active',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if (Schema::hasTable('hm_assets')) {
            DB::table('hm_assets')->where('id',$data['asset_id'])->where('business_id',$this->businessId())->update(['status'=>'assigned','updated_by'=>$userId,'updated_at'=>now()]);
        }
    }

    public function inspection(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_asset_inspections')) return;
        DB::table('hm_asset_inspections')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'asset_id' => $data['asset_id'],
            'inspection_no' => $this->nextNumber('hm_asset_inspections','inspection_no','HAI'),
            'inspection_date' => $data['inspection_date'],
            'condition_status' => $data['condition_status'],
            'next_inspection_date' => $data['next_inspection_date'] ?? null,
            'maintenance_required' => !empty($data['maintenance_required']) ? 1 : 0,
            'estimated_cost' => $data['estimated_cost'] ?? 0,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function dispose(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_assets')) return;
        DB::table('hm_assets')->where('id',$id)->where('business_id',$this->businessId())->update([
            'status' => 'disposed',
            'disposal_date' => $data['disposal_date'],
            'disposal_value' => $data['disposal_value'] ?? 0,
            'disposal_reason' => $data['disposal_reason'],
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    private function rows(string $table, int $limit = 100): array
    {
        if (!Schema::hasTable($table)) return [];
        try { return DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->limit($limit)->get()->all(); }
        catch (Throwable $e) { return []; }
    }

    private function nextNumber(string $table, string $column, string $prefix): string
    {
        $last = Schema::hasTable($table) ? DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->value($column) : null;
        $n=1; if ($last && preg_match('/(\d+)$/',$last,$m)) $n=((int)$m[1])+1;
        return $prefix.'-'.date('ym').'-'.str_pad((string)$n,5,'0',STR_PAD_LEFT);
    }

    private function businessId(): int { return (int)(session('business.id') ?? session('business_id') ?? 1); }
    private function locationId(): ?int { return session('business_location_id') ?? session('location_id') ?? null; }
}
