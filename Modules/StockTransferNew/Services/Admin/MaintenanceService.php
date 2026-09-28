<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\StockTransferNew\Entities\MaintenanceTask;
use Modules\StockTransferNew\Entities\MaintenanceLog;

class MaintenanceService
{
    public function summary(int $businessId, array $filters = []): array
    {
        $query = $this->baseQuery($businessId, $filters);
        return [
            'open' => (clone $query)->whereIn('status', ['open', 'in_progress'])->count(),
            'overdue' => (clone $query)->whereIn('status', ['open', 'in_progress'])->whereDate('due_date', '<', now()->toDateString())->count(),
            'high_priority' => (clone $query)->whereIn('priority', ['high', 'critical'])->whereIn('status', ['open', 'in_progress'])->count(),
            'closed_this_month' => (clone $query)->where('status', 'closed')->whereBetween('closed_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];
    }

    public function list(int $businessId, array $filters = [])
    {
        return $this->baseQuery($businessId, $filters)->orderByRaw("FIELD(priority, 'critical','high','medium','low')")->orderBy('due_date')->paginate(25);
    }

    public function create(int $businessId, int $userId, array $data): MaintenanceTask
    {
        $task = MaintenanceTask::create([
            'business_id' => $businessId,
            'title' => trim((string) ($data['title'] ?? '')),
            'task_type' => $data['task_type'] ?? 'general',
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by' => $userId,
        ]);
        $this->log($businessId, $task->id, $userId, 'created', 'Maintenance task created');
        return $task;
    }

    public function updateStatus(int $businessId, int $userId, int $id, string $status, ?string $remarks = null): void
    {
        if (! in_array($status, ['open', 'in_progress', 'on_hold', 'closed'], true)) {
            throw new InvalidArgumentException('Invalid maintenance status.');
        }
        $task = MaintenanceTask::where('business_id', $businessId)->findOrFail($id);
        $old = $task->status;
        $task->status = $status;
        if ($status === 'closed') {
            $task->closed_at = now();
            $task->closed_by = $userId;
        }
        $task->save();
        $this->log($businessId, $task->id, $userId, 'status_changed', "Status changed from {$old} to {$status}. {$remarks}");
    }

    public function calendar(int $businessId, string $month): array
    {
        $start = date('Y-m-01', strtotime($month . '-01'));
        $end = date('Y-m-t', strtotime($start));
        return MaintenanceTask::where('business_id', $businessId)
            ->whereBetween('due_date', [$start, $end])
            ->orderBy('due_date')
            ->get()
            ->groupBy('due_date')
            ->toArray();
    }

    protected function baseQuery(int $businessId, array $filters)
    {
        $query = MaintenanceTask::where('business_id', $businessId);
        foreach (['status', 'priority', 'owner_user_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['from_date'])) {
            $query->whereDate('due_date', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->whereDate('due_date', '<=', $filters['to_date']);
        }
        return $query;
    }

    protected function log(int $businessId, int $taskId, int $userId, string $action, string $remarks): void
    {
        MaintenanceLog::create([
            'business_id' => $businessId,
            'maintenance_task_id' => $taskId,
            'action' => $action,
            'remarks' => $remarks,
            'created_by' => $userId,
        ]);
    }
}
