<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class StaffRosteringService
{
    public function dashboard(): array
    {
        $roles = $this->rows('hm_staff_roles');
        $staff = $this->rows('hm_staff_members');
        $shifts = $this->rows('hm_staff_roster_shifts');
        $attendance = $this->rows('hm_staff_attendance_logs');
        $today = date('Y-m-d');
        return [
            'roles' => $roles,
            'staff' => $staff,
            'shifts' => $shifts,
            'attendance' => $attendance,
            'active_staff' => count(array_filter($staff, fn($r) => ($r->status ?? 'active') === 'active')),
            'today_shifts' => count(array_filter($shifts, fn($r) => (string)($r->shift_date ?? '') === $today)),
            'open_attendance' => count(array_filter($attendance, fn($r) => !empty($r->clock_in) && empty($r->clock_out))),
            'unassigned_shifts' => count(array_filter($shifts, fn($r) => empty($r->staff_id))),
            'notes' => [
                'Roster, staff and attendance records are tenant, business and location scoped.',
                'This hotel staff layer is standalone and does not alter the HR Manager module.',
                'Attendance logs can be matched with roster shifts for shortage/excess staffing reviews.',
            ],
        ];
    }

    public function role(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_roles')) return;
        DB::table('hm_staff_roles')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'role_name' => $data['role_name'], 'department' => $data['department'] ?? null,
            'standard_hours' => $data['standard_hours'] ?? 0, 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function staff(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_members')) return;
        DB::table('hm_staff_members')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'employee_no' => $data['employee_no'] ?? $this->nextNumber('hm_staff_members','employee_no','HST'),
            'name' => $data['name'], 'mobile' => $data['mobile'] ?? null, 'email' => $data['email'] ?? null,
            'department' => $data['department'] ?? null, 'role_id' => $data['role_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function shift(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_roster_shifts')) return;
        DB::table('hm_staff_roster_shifts')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'shift_no' => $data['shift_no'] ?? $this->nextNumber('hm_staff_roster_shifts','shift_no','RS'),
            'staff_id' => $data['staff_id'], 'shift_date' => $data['shift_date'],
            'start_time' => $data['start_time'], 'end_time' => $data['end_time'],
            'department' => $data['department'] ?? null, 'station' => $data['station'] ?? null,
            'status' => $data['status'] ?? 'planned', 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function attendance(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_attendance_logs')) return;
        DB::table('hm_staff_attendance_logs')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'staff_id' => $data['staff_id'], 'attendance_date' => $data['attendance_date'],
            'clock_in' => $data['clock_in'] ?? null, 'clock_out' => $data['clock_out'] ?? null,
            'status' => $data['status'] ?? 'present', 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function shiftStatus(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_roster_shifts')) return;
        DB::table('hm_staff_roster_shifts')->where('id',$id)->where('business_id',$this->businessId())->update([
            'status' => $data['status'], 'remarks' => $data['remarks'] ?? DB::raw('remarks'), 'updated_by' => $userId, 'updated_at' => now(),
        ]);
    }

    private function rows(string $table): array
    {
        if (!Schema::hasTable($table)) return [];
        try { return DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->limit(150)->get()->all(); }
        catch (Throwable $e) { return []; }
    }

    private function nextNumber(string $table, string $column, string $prefix): string
    { $last = Schema::hasTable($table) ? DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->value($column) : null; $n=1; if ($last && preg_match('/(\d+)$/',$last,$m)) $n=((int)$m[1])+1; return $prefix.'-'.date('ym').'-'.str_pad((string)$n,5,'0',STR_PAD_LEFT); }
    private function businessId(): int { return (int)(session('business.id') ?? session('business_id') ?? 1); }
    private function locationId(): ?int { return session('business_location_id') ?? session('location_id') ?? null; }
}
