<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PreventiveMaintenanceService
{
    public function dashboard(): array
    {
        $plans = $this->rows('hm_preventive_maintenance_plans', 500);
        $tasks = $this->rows('hm_preventive_maintenance_tasks', 500);
        $checklists = $this->rows('hm_preventive_maintenance_checklists', 500);
        $assets = $this->rows('hm_assets', 500);
        $rooms = $this->rows('hm_rooms', 500);
        $today = strtotime(date('Y-m-d'));
        $due = array_filter($tasks, fn($t) => in_array(($t->status ?? 'scheduled'), ['scheduled','assigned','in_progress']) && !empty($t->due_date) && strtotime($t->due_date) <= $today);
        $overdue = array_filter($due, fn($t) => strtotime($t->due_date) < $today);
        $completed = array_filter($tasks, fn($t) => ($t->status ?? '') === 'completed');
        $cost = array_sum(array_map(fn($t) => (float)($t->actual_cost ?? $t->estimated_cost ?? 0), $completed));
        return [
            'plans' => $plans,
            'tasks' => $tasks,
            'checklists' => $checklists,
            'assets' => $assets,
            'rooms' => $rooms,
            'plan_count' => count($plans),
            'due_count' => count($due),
            'overdue_count' => count($overdue),
            'completed_count' => count($completed),
            'maintenance_cost' => $cost,
            'notes' => [
                'Preventive maintenance is tenant database, business and location scoped.',
                'Plans can be linked to hotel assets or rooms and can generate repeat maintenance tasks.',
                'Task completion records checklist verification, actual cost and next service date.',
            ],
        ];
    }

    public function plan(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_preventive_maintenance_plans')) return;
        DB::table('hm_preventive_maintenance_plans')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'plan_no' => $data['plan_no'] ?? $this->nextNumber('hm_preventive_maintenance_plans','plan_no','HPM'),
            'plan_name' => $data['plan_name'],
            'asset_id' => $data['asset_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'department' => $data['department'] ?? null,
            'frequency_type' => $data['frequency_type'] ?? 'monthly',
            'frequency_value' => $data['frequency_value'] ?? 1,
            'start_date' => $data['start_date'],
            'next_due_date' => $data['next_due_date'] ?? $data['start_date'],
            'priority' => $data['priority'] ?? 'medium',
            'estimated_cost' => $data['estimated_cost'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'instructions' => $data['instructions'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function generateTask(int $planId, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_preventive_maintenance_plans') || !Schema::hasTable('hm_preventive_maintenance_tasks')) return;
        $plan = DB::table('hm_preventive_maintenance_plans')->where('business_id',$this->businessId())->where('id',$planId)->first();
        if (!$plan) return;
        $dueDate = $data['due_date'] ?? $plan->next_due_date ?? date('Y-m-d');
        DB::table('hm_preventive_maintenance_tasks')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'plan_id' => $plan->id,
            'task_no' => $this->nextNumber('hm_preventive_maintenance_tasks','task_no','HMT'),
            'asset_id' => $plan->asset_id,
            'room_id' => $plan->room_id,
            'task_title' => $data['task_title'] ?? $plan->plan_name,
            'due_date' => $dueDate,
            'assigned_to' => $data['assigned_to'] ?? null,
            'priority' => $data['priority'] ?? $plan->priority,
            'estimated_cost' => $data['estimated_cost'] ?? $plan->estimated_cost,
            'status' => 'scheduled',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function checklist(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_preventive_maintenance_checklists')) return;
        DB::table('hm_preventive_maintenance_checklists')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'plan_id' => $data['plan_id'],
            'check_item' => $data['check_item'],
            'required_result' => $data['required_result'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function status(int $taskId, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_preventive_maintenance_tasks')) return;
        $payload = [
            'status' => $data['status'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'completed_date' => $data['status'] === 'completed' ? ($data['completed_date'] ?? date('Y-m-d')) : null,
            'actual_cost' => $data['actual_cost'] ?? 0,
            'checklist_verified' => !empty($data['checklist_verified']) ? 1 : 0,
            'completion_notes' => $data['completion_notes'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        DB::table('hm_preventive_maintenance_tasks')->where('business_id',$this->businessId())->where('id',$taskId)->update($payload);
        if (($data['status'] ?? '') === 'completed') {
            $task = DB::table('hm_preventive_maintenance_tasks')->where('business_id',$this->businessId())->where('id',$taskId)->first();
            if ($task && $task->plan_id && Schema::hasTable('hm_preventive_maintenance_plans')) {
                DB::table('hm_preventive_maintenance_plans')->where('business_id',$this->businessId())->where('id',$task->plan_id)->update([
                    'last_completed_date' => $payload['completed_date'],
                    'next_due_date' => $data['next_due_date'] ?? $this->nextDate($payload['completed_date'], $task->plan_id),
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function nextDate(?string $from, int $planId): ?string
    {
        if (!$from || !Schema::hasTable('hm_preventive_maintenance_plans')) return null;
        $plan = DB::table('hm_preventive_maintenance_plans')->where('business_id',$this->businessId())->where('id',$planId)->first();
        if (!$plan) return null;
        $value = max(1, (int)($plan->frequency_value ?? 1));
        $type = $plan->frequency_type ?? 'monthly';
        $date = new \DateTime($from);
        $map = ['daily'=>'days','weekly'=>'weeks','monthly'=>'months','quarterly'=>'months','yearly'=>'years'];
        $unit = $map[$type] ?? 'months';
        if ($type === 'quarterly') $value *= 3;
        $date->modify("+{$value} {$unit}");
        return $date->format('Y-m-d');
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
