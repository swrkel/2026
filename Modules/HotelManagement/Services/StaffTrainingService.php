<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class StaffTrainingService
{
    public function dashboard(): array
    {
        $staff = $this->rows('hm_staff_members', 300);
        $courses = $this->rows('hm_staff_training_courses', 200);
        $sessions = $this->rows('hm_staff_training_sessions', 200);
        $records = $this->rows('hm_staff_training_records', 400);

        return [
            'staff' => $staff,
            'courses' => $courses,
            'sessions' => $sessions,
            'records' => $records,
            'active_courses' => count(array_filter($courses, fn($c) => ($c->status ?? 'active') === 'active')),
            'planned_sessions' => count(array_filter($sessions, fn($s) => ($s->status ?? 'planned') === 'planned')),
            'completed_records' => count(array_filter($records, fn($r) => ($r->result_status ?? '') === 'passed')),
            'expired_records' => count(array_filter($records, fn($r) => !empty($r->valid_until) && strtotime($r->valid_until) < strtotime(date('Y-m-d')))),
            'notes' => [
                'Training records are scoped by tenant database, business and business location.',
                'This layer supports hotel operational training without replacing the standalone HR Manager module.',
                'Mandatory courses can be used during audit/checklist review for front office, housekeeping, security and F&B staff.',
            ],
        ];
    }

    public function course(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_training_courses')) return;
        DB::table('hm_staff_training_courses')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'course_code' => $data['course_code'] ?? $this->nextNumber('hm_staff_training_courses','course_code','HTC'),
            'course_name' => $data['course_name'],
            'department' => $data['department'] ?? null,
            'training_type' => $data['training_type'],
            'validity_days' => $data['validity_days'] ?? 0,
            'is_mandatory' => !empty($data['is_mandatory']) ? 1 : 0,
            'status' => $data['status'] ?? 'active',
            'description' => $data['description'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function session(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_training_sessions')) return;
        DB::table('hm_staff_training_sessions')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'course_id' => $data['course_id'],
            'session_no' => $this->nextNumber('hm_staff_training_sessions','session_no','HTS'),
            'trainer_name' => $data['trainer_name'] ?? null,
            'training_date' => $data['training_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'venue' => $data['venue'] ?? null,
            'capacity' => $data['capacity'] ?? 0,
            'status' => $data['status'] ?? 'planned',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function assign(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_training_records')) return;
        $session = Schema::hasTable('hm_staff_training_sessions') ? DB::table('hm_staff_training_sessions')->where('id',$data['session_id'])->where('business_id',$this->businessId())->first() : null;
        $course = $session && Schema::hasTable('hm_staff_training_courses') ? DB::table('hm_staff_training_courses')->where('id',$session->course_id)->first() : null;
        $completedAt = (($data['result_status'] ?? 'assigned') === 'passed') ? now()->toDateString() : null;
        $validUntil = ($completedAt && (int)($course->validity_days ?? 0) > 0) ? date('Y-m-d', strtotime($completedAt.' +'.(int)$course->validity_days.' days')) : null;

        DB::table('hm_staff_training_records')->updateOrInsert(
            ['business_id' => $this->businessId(), 'session_id' => $data['session_id'], 'staff_id' => $data['staff_id']],
            [
                'business_location_id' => $this->locationId(),
                'course_id' => $session->course_id ?? null,
                'attendance_status' => $data['attendance_status'] ?? 'assigned',
                'score' => $data['score'] ?? null,
                'result_status' => $data['result_status'] ?? 'assigned',
                'completed_at' => $completedAt,
                'valid_until' => $validUntil,
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => $userId,
                'updated_at' => now(),
                'created_by' => $userId,
                'created_at' => now(),
            ]
        );
    }

    public function result(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_training_records')) return;
        $record = DB::table('hm_staff_training_records')->where('id',$id)->where('business_id',$this->businessId())->first();
        if (!$record) return;
        $course = Schema::hasTable('hm_staff_training_courses') ? DB::table('hm_staff_training_courses')->where('id',$record->course_id)->first() : null;
        $completedAt = $data['result_status'] === 'passed' ? now()->toDateString() : null;
        $validUntil = ($completedAt && (int)($course->validity_days ?? 0) > 0) ? date('Y-m-d', strtotime($completedAt.' +'.(int)$course->validity_days.' days')) : null;
        DB::table('hm_staff_training_records')->where('id',$id)->where('business_id',$this->businessId())->update([
            'attendance_status' => $data['attendance_status'],
            'score' => $data['score'] ?? null,
            'result_status' => $data['result_status'],
            'completed_at' => $completedAt,
            'valid_until' => $validUntil,
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
