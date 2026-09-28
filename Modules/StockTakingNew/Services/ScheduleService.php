<?php

namespace Modules\StockTakingNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StockTakingNew\Entities\StockTakeSchedule;
use Modules\StockTakingNew\Entities\StockTakeTemplate;

class ScheduleService
{
    public function __construct(private SessionService $sessions, private AuditService $audit) {}

    public function nextRun(array $data, ?Carbon $after = null): Carbon
    {
        $after = ($after ?: now())->copy()->startOfMinute();
        [$hour, $minute] = array_pad(explode(':', (string) ($data['run_time'] ?? '00:05')), 2, '0');
        $candidate = $after->copy()->setTime((int) $hour, (int) $minute);

        return match ((string) ($data['frequency'] ?? 'daily')) {
            'weekly' => $this->nextWeekly($candidate, $after, (string) ($data['day_of_week'] ?? 'monday')),
            'monthly' => $this->nextMonthly($candidate, $after, (int) ($data['day_of_month'] ?? 1), 1),
            'quarterly' => $this->nextMonthly($candidate, $after, (int) ($data['day_of_month'] ?? 1), 3),
            default => $candidate->lte($after) ? $candidate->addDay() : $candidate,
        };
    }

    public function runDue(int $limit = 100): array
    {
        $created = 0;
        $failed = 0;

        StockTakeSchedule::query()
            ->where('is_active', 1)
            ->where(function ($query): void {
                $query->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
            })
            ->orderBy('next_run_at')
            ->limit(max(1, min($limit, 1000)))
            ->get()
            ->each(function (StockTakeSchedule $schedule) use (&$created, &$failed): void {
                try {
                    DB::transaction(function () use ($schedule): void {
                        $locked = StockTakeSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
                        if (! $locked->is_active || ($locked->next_run_at && $locked->next_run_at->isFuture())) {
                            return;
                        }

                        $template = $locked->template_id
                            ? StockTakeTemplate::where('business_id', $locked->business_id)->find($locked->template_id)
                            : null;
                        $scope = array_merge((array) $locked->scope_json, (array) ($template?->scope_json ?? []));

                        $session = $this->sessions->create((int) $locked->business_id, $locked->created_by, [
                            'location_id' => (int) $locked->location_id,
                            'store_id' => $locked->store_id ? (int) $locked->store_id : null,
                            'template_id' => $locked->template_id,
                            'title' => $locked->name . ' - ' . now()->format('d M Y'),
                            'count_date' => now()->toDateString(),
                            'cutoff_at' => now(),
                            'count_method' => $template?->count_method ?: 'cycle',
                            'count_mode' => config('stocktakingnew.defaults.count_mode', 'blind'),
                            'scope' => $scope,
                            'freeze_stock' => false,
                            'require_recount' => (bool) config('stocktakingnew.defaults.require_recount_for_variance', true),
                            'variance_qty_threshold' => (float) config('stocktakingnew.defaults.variance_qty_threshold', 0),
                            'variance_value_threshold' => (float) config('stocktakingnew.defaults.variance_value_threshold', 0),
                            'notes' => 'Automatically generated from schedule #' . $locked->id,
                        ]);
                        $this->sessions->prepare($session, $locked->created_by);

                        $locked->update([
                            'last_run_at' => now(),
                            'next_run_at' => $this->nextRun($locked->toArray(), now()),
                        ]);
                        $this->audit->log($locked->business_id, $session->id, 'scheduled_session_generated', 'schedule', $locked->id);
                    });
                    $created++;
                } catch (\Throwable $exception) {
                    report($exception);
                    $failed++;
                }
            });

        return compact('created', 'failed');
    }

    private function nextWeekly(Carbon $candidate, Carbon $after, string $day): Carbon
    {
        $days = [
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4,
            'friday' => 5, 'saturday' => 6, 'sunday' => 7,
        ];
        $target = $days[strtolower($day)] ?? 1;
        $offset = ($target - $candidate->dayOfWeekIso + 7) % 7;
        $candidate->addDays($offset);
        if ($candidate->lte($after)) {
            $candidate->addWeek();
        }
        return $candidate;
    }

    private function nextMonthly(Carbon $candidate, Carbon $after, int $day, int $months): Carbon
    {
        $day = max(1, min(31, $day));
        $candidate->day(min($day, $candidate->daysInMonth));
        while ($candidate->lte($after)) {
            $candidate->addMonthsNoOverflow($months)->day(min($day, $candidate->daysInMonth));
        }
        return $candidate;
    }
}
