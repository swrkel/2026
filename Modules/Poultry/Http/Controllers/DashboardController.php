<?php

namespace Modules\Poultry\Http\Controllers;

use Carbon\Carbon;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\DailyRecord;
use Modules\Poultry\Entities\EggCollection;
use Modules\Poultry\Entities\Farm;
use Modules\Poultry\Services\HealthService;
use Modules\Poultry\Services\PerformanceCalculator;
use Modules\Poultry\Services\WithdrawalGuard;

class DashboardController extends PoultryBaseController
{
    protected $performance;
    protected $withdrawal;
    protected $health;

    public function __construct(
        PerformanceCalculator $performance,
        WithdrawalGuard $withdrawal,
        HealthService $health
    ) {
        $this->performance = $performance;
        $this->withdrawal  = $withdrawal;
        $this->health      = $health;
    }

    public function index()
    {
        $this->authorizePermission('poultry.dashboard');

        $businessId = $this->businessId();
        $today      = Carbon::today()->toDateString();

        $batches = Batch::query()->forBusiness($businessId)->open()
            ->with(['farm', 'house', 'breed'])
            ->orderBy('placement_date')
            ->get();

        $summaries = $batches->map(function ($batch) {
            return array_merge(
                ['batch' => $batch],
                $this->performance->summary($batch)
            );
        });

        $stats = [
            'farms'          => Farm::query()->forBusiness($businessId)->active()->count(),
            'active_batches' => $batches->count(),
            'total_birds'    => (int) $batches->sum('current_qty'),
            'eggs_today'     => (int) EggCollection::query()->forBusiness($businessId)
                                    ->whereDate('collection_date', $today)->sum('qty'),
            'mortality_today' => (int) DailyRecord::query()->forBusiness($businessId)
                                    ->whereDate('record_date', $today)
                                    ->selectRaw('COALESCE(SUM(mortality),0) + COALESCE(SUM(culls),0) as t')
                                    ->value('t'),
        ];

        return view('poultry::dashboard.index', compact('summaries', 'stats'))
            ->with('withdrawals', $this->withdrawal->activeBatches($businessId))
            ->with('alerts', $this->buildAlerts($batches));
    }

    /** Ajax refresh for the alert panel. */
    public function alerts()
    {
        $this->authorizePermission('poultry.dashboard');

        $batches = Batch::query()->forBusiness($this->businessId())->open()->get();

        return response()->json(['alerts' => $this->buildAlerts($batches)]);
    }

    /**
     * The three things a farm manager needs to see first thing: birds dying
     * faster than expected, vaccinations that have slipped, and batches whose
     * produce must not be sold.
     */
    protected function buildAlerts($batches)
    {
        $alerts    = [];
        $today     = Carbon::today()->toDateString();
        $threshold = (float) config('poultry.defaults.mortality_alert_pct', 1.0);

        foreach ($batches as $batch) {
            $record = DailyRecord::where('batch_id', $batch->id)
                ->whereDate('record_date', $today)
                ->first();

            if ($record && $batch->current_qty > 0) {
                $pct = $record->mortalityPct($batch->current_qty + $record->total_loss);

                if ($pct >= $threshold) {
                    $alerts[] = [
                        'level'   => 'danger',
                        'batch'   => $batch->batch_code,
                        'message' => 'Daily mortality '.$pct.'% exceeds the '.$threshold.'% alert threshold.',
                    ];
                }
            }

            foreach ($this->health->schedule($batch) as $row) {
                if ($row['is_overdue']) {
                    $alerts[] = [
                        'level'   => 'warning',
                        'batch'   => $batch->batch_code,
                        'message' => $row['schedule']->name.' was due on '.$row['due_date'].' and has not been recorded.',
                    ];
                }
            }

            if ($withdrawal = $this->withdrawal->activeFor($batch->id)) {
                $alerts[] = [
                    'level'   => 'info',
                    'batch'   => $batch->batch_code,
                    'message' => 'Under withdrawal for '.$withdrawal->name.' until '
                                 .Carbon::parse($withdrawal->withdrawal_until)->toDateString()
                                 .'. Produce must not be sold.',
                ];
            }
        }

        return $alerts;
    }
}
