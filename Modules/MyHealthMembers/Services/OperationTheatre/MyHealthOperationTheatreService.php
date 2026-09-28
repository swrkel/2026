<?php

namespace Modules\MyHealthMembers\Services\OperationTheatre;

use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthOperationTheatreRoom;
use Modules\MyHealthMembers\Entities\MyHealthOperativeRecord;
use Modules\MyHealthMembers\Entities\MyHealthPostOperativeNote;
use Modules\MyHealthMembers\Entities\MyHealthSurgeryChecklist;
use Modules\MyHealthMembers\Entities\MyHealthSurgerySchedule;

class MyHealthOperationTheatreService
{
    private function connectionName(): string
    {
        return config('myhealthmembers.central_connection', config('database.default'));
    }

    public function dashboardCounts(): array
    {
        $today = now()->toDateString();

        return [
            'scheduled_today' => MyHealthSurgerySchedule::whereDate('scheduled_start_at', $today)->count(),
            'emergency_today' => MyHealthSurgerySchedule::whereDate('scheduled_start_at', $today)->where('priority', 'emergency')->count(),
            'completed_today' => MyHealthSurgerySchedule::whereDate('actual_end_at', $today)->where('status', 'completed')->count(),
            'cancelled_today' => MyHealthSurgerySchedule::whereDate('updated_at', $today)->where('status', 'cancelled')->count(),
            'pending_checklists' => MyHealthSurgeryChecklist::where('checklist_status', 'pending')->count(),
            'open_theatres' => MyHealthOperationTheatreRoom::where('status', 'available')->count(),
            'operative_records' => MyHealthOperativeRecord::whereDate('created_at', $today)->count(),
            'post_op_notes' => MyHealthPostOperativeNote::whereDate('created_at', $today)->count(),
        ];
    }

    public function nextSurgeryNo(): string
    {
        return $this->nextNumber('myhealth_surgery_schedules', 'surgery_no', 'SURG');
    }

    public function nextOperationNo(): string
    {
        return $this->nextNumber('myhealth_operative_records', 'operation_no', 'OP');
    }

    private function nextNumber(string $table, string $column, string $prefix): string
    {
        $last = DB::connection($this->connectionName())->table($table)
            ->where($column, 'like', $prefix . '-%')
            ->orderByDesc('id')
            ->value($column);

        $number = 1;
        if ($last && preg_match('/(\d+)$/', $last, $matches)) {
            $number = ((int) $matches[1]) + 1;
        }

        return $prefix . '-' . str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    public function surgeryStatuses(): array
    {
        return ['scheduled', 'pre_op', 'in_theatre', 'completed', 'cancelled', 'postponed'];
    }

    public function priorities(): array
    {
        return ['elective', 'urgent', 'emergency'];
    }
}
