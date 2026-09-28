<?php
namespace Modules\StockTransferNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\TransferSchedule;
use Modules\StockTransferNew\Entities\ScheduleExecutionLog;

class StockTransferSchedulingService
{
    public function dueSchedules(int $businessId, ?string $date = null)
    {
        $date = $date ?: Carbon::today()->toDateString();
        return TransferSchedule::with('items')
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->whereDate('next_run_date', '<=', $date)
            ->orderBy('next_run_date')
            ->get();
    }

    public function store(array $data, array $items, int $userId): TransferSchedule
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $schedule = TransferSchedule::create(array_merge($data, [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));

            foreach ($items as $item) {
                if ((float)($item['qty'] ?? 0) <= 0) {
                    continue;
                }
                $schedule->items()->create([
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'unit_id' => $item['unit_id'] ?? null,
                    'qty' => $item['qty'],
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return $schedule->fresh('items');
        });
    }

    public function calculateNextRunDate(TransferSchedule $schedule): ?string
    {
        $next = Carbon::parse($schedule->next_run_date);
        switch ($schedule->schedule_type) {
            case 'daily': return $next->addDay()->toDateString();
            case 'weekly': return $next->addWeek()->toDateString();
            case 'monthly': return $next->addMonthNoOverflow()->toDateString();
            default: return null;
        }
    }

    public function markExecuted(TransferSchedule $schedule, string $status, ?int $transferId = null, ?string $message = null, ?int $userId = null): void
    {
        ScheduleExecutionLog::create([
            'business_id' => $schedule->business_id,
            'schedule_id' => $schedule->id,
            'transfer_id' => $transferId,
            'run_date' => Carbon::today()->toDateString(),
            'run_status' => $status,
            'message' => $message,
            'created_by' => $userId,
        ]);

        $nextDate = $this->calculateNextRunDate($schedule);
        if ($nextDate) {
            $schedule->update(['next_run_date' => $nextDate]);
        } else {
            $schedule->update(['status' => 'completed']);
        }
    }
}
