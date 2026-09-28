<?php

namespace Modules\StockTransferNew\Services;

use Modules\StockTransferNew\Entities\DockSchedule;
use Illuminate\Support\Facades\Auth;

class DockScheduleService
{
    public function list(array $filters)
    {
        $query = DockSchedule::query()->orderByDesc('schedule_date')->orderBy('start_time');
        foreach (['business_id','location_id','store_id','dock_no','status'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (!empty($filters['date'])) {
            $query->whereDate('schedule_date', $filters['date']);
        }
        return $query->paginate(25);
    }

    public function create(array $data): DockSchedule
    {
        $data['status'] = 'open';
        $data['created_by'] = Auth::id();
        return DockSchedule::create($data);
    }

    public function close(int $id): void
    {
        DockSchedule::whereKey($id)->update(['status' => 'closed', 'closed_by' => Auth::id(), 'closed_at' => now()]);
    }
}
