<?php

namespace Modules\Poultry\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\DailyRecord;
use Modules\Poultry\Services\CostingService;
use Modules\Poultry\Services\EggProductionService;
use Modules\Poultry\Services\PerformanceCalculator;

class ReportController extends PoultryBaseController
{
    protected $performance;
    protected $costing;
    protected $eggs;

    public function __construct(
        PerformanceCalculator $performance,
        CostingService $costing,
        EggProductionService $eggs
    ) {
        $this->performance = $performance;
        $this->costing     = $costing;
        $this->eggs        = $eggs;
    }

    public function index()
    {
        $this->authorizePermission('poultry.report.view');

        return view('poultry::reports.index');
    }

    /** Flock performance against breed standard - the core operations report. */
    public function performance(Request $request)
    {
        $this->authorizePermission('poultry.report.view');

        $batches = Batch::query()->forBusiness($this->businessId())
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            }, function ($q) {
                $q->open();
            })
            ->with(['farm', 'house', 'breed'])
            ->get();

        $rows = $batches->map(function ($batch) {
            $summary = $this->performance->summary($batch);

            return $summary + [
                'batch'        => $batch,
                'target_fcr'   => optional($batch->breed)->target_fcr,
                'fcr_variance' => ($summary['fcr'] && optional($batch->breed)->target_fcr)
                    ? round($summary['fcr'] - $batch->breed->target_fcr, 3)
                    : null,
            ];
        });

        return view('poultry::reports.performance', compact('rows'));
    }

    /** Egg or meat production over a period. */
    public function production(Request $request)
    {
        $this->authorizePermission('poultry.report.view');

        $from = $request->input('from', Carbon::today()->startOfMonth()->toDateString());
        $to   = $request->input('to', Carbon::today()->toDateString());

        $batches = Batch::query()->forBusiness($this->businessId())->laying()->get();

        $rows = $batches->map(function ($batch) use ($from, $to) {
            return [
                'batch'       => $batch,
                'by_grade'    => $this->eggs->summaryByGrade($batch, $from, $to),
                'avg_hen_day' => $this->performance->henDayPctRange($batch, $from, $to),
            ];
        });

        return view('poultry::reports.production', compact('rows', 'from', 'to'));
    }

    /**
     * Batch profitability. Broilers and layers are shown separately because
     * their costing treatments are not comparable - see CostingService.
     */
    public function costing(Request $request)
    {
        $this->authorizePermission('poultry.report.view');

        $batches = Batch::query()->forBusiness($this->businessId())
            ->when($request->filled('bird_type'), function ($q) use ($request) {
                $q->where('bird_type', $request->input('bird_type'));
            })
            ->with('breed')
            ->orderByDesc('placement_date')
            ->get();

        $rows = $batches->map(function ($batch) use ($request) {
            return [
                'batch'     => $batch,
                'result'    => $this->costing->result($batch, $request->input('from'), $request->input('to')),
                'breakdown' => $this->costing->costBreakdown($batch),
            ];
        });

        return view('poultry::reports.costing', compact('rows'));
    }

    /** Mortality curve, for spotting a disease event early. */
    public function mortality(Request $request)
    {
        $this->authorizePermission('poultry.report.view');

        $from = $request->input('from', Carbon::today()->subDays(30)->toDateString());
        $to   = $request->input('to', Carbon::today()->toDateString());

        $rows = DailyRecord::query()->forBusiness($this->businessId())
            ->between($from, $to)
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->input('batch_id'));
            })
            ->selectRaw('batch_id, record_date, SUM(mortality) as mortality, SUM(culls) as culls')
            ->groupBy('batch_id', 'record_date')
            ->orderBy('record_date')
            ->with('batch')
            ->get();

        $byCause = DailyRecord::query()->forBusiness($this->businessId())
            ->between($from, $to)
            ->whereNotNull('mortality_cause')
            ->selectRaw('mortality_cause, SUM(mortality) as total')
            ->groupBy('mortality_cause')
            ->orderByDesc('total')
            ->get();

        return view('poultry::reports.mortality', [
            'rows'    => $rows,
            'byCause' => $byCause,
            'batches' => Batch::dropdown(null, false, $this->businessId()),
            'from'    => $from,
            'to'      => $to,
        ]);
    }
}
