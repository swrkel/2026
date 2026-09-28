<?php

namespace Modules\Poultry\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\EggCollection;
use Modules\Poultry\Entities\EggGrade;
use Modules\Poultry\Entities\Shared\BusinessLocation;
use Modules\Poultry\Services\EggProductionService;
use Modules\Poultry\Services\PerformanceCalculator;

class EggCollectionController extends PoultryBaseController
{
    protected $production;
    protected $performance;

    public function __construct(EggProductionService $production, PerformanceCalculator $performance)
    {
        $this->production  = $production;
        $this->performance = $performance;
    }

    public function index(Request $request)
    {
        $this->authorizePermission('poultry.egg.view');

        $businessId = $this->businessId();
        $from = $request->input('from', Carbon::today()->subDays(7)->toDateString());
        $to   = $request->input('to', Carbon::today()->toDateString());

        $collections = EggCollection::query()->forBusiness($businessId)
            ->between($from, $to)
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->input('batch_id'));
            })
            ->with(['batch', 'grade'])
            ->orderByDesc('collection_date')
            ->paginate(50);

        return view('poultry::eggs.index', [
            'collections' => $collections,
            'batches'     => Batch::dropdown('layer', false, $businessId),
            'grades'      => EggGrade::dropdown($businessId),
            'filters'     => compact('from', 'to') + $request->only('batch_id'),
        ]);
    }

    public function entry(Request $request)
    {
        $this->authorizePermission('poultry.egg.create');

        $businessId = $this->businessId();
        $date       = $request->input('date', Carbon::today()->toDateString());

        $batches = Batch::query()->forBusiness($businessId)->open()->laying()
            ->with('house')
            ->orderBy('batch_code')
            ->get();

        $grades = EggGrade::query()->forBusiness($businessId)->ordered()->get();

        $existing = EggCollection::query()->forBusiness($businessId)
            ->whereDate('collection_date', $date)
            ->get()
            ->groupBy(function ($row) {
                return $row->batch_id.'-'.$row->slot.'-'.$row->grade_id;
            });

        return view('poultry::eggs.entry', [
            'batches'   => $batches,
            'grades'    => $grades,
            'slots'     => EggCollection::SLOTS,
            'existing'  => $existing,
            'date'      => $date,
            'locations' => BusinessLocation::dropdown($businessId),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.egg.create');

        $data = $request->validate([
            'batch_id'        => 'required|integer',
            'collection_date' => 'required|date|before_or_equal:today',
            'slot'            => 'required|in:morning,midday,afternoon,evening',
            'grade_id'        => 'required|integer',
            'qty'             => 'required|integer|min:0',
            'weight_kg'       => 'nullable|numeric|min:0',
            'location_id'     => 'nullable|integer',
        ]);

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($data['batch_id']);

        if (! $batch->is_open) {
            return $this->fail('Batch '.$batch->batch_code.' is closed.');
        }

        $data['location_id'] = $data['location_id'] ?? $this->locationId();

        try {
            $result = $this->production->collect($batch, $data);
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Could not record the collection: '.$e->getMessage());
        }

        $henDay = $this->performance->henDayPct($batch, $data['collection_date']);

        /*
         * A collection that was recorded but deliberately not posted to stock -
         * withdrawal period, or an unmapped grade - is a success, not a
         * failure. The reason is surfaced so the operator understands why the
         * eggs are not yet sellable.
         */
        return $this->ok(
            $result['posted']
                ? 'Collection recorded and added to saleable stock.'
                : 'Collection recorded. '.$result['reason'],
            [
                'posted'      => $result['posted'],
                'reason'      => $result['reason'],
                'hen_day_pct' => $henDay,
                'daily_total' => $this->production->dailyTotal($batch, $data['collection_date']),
            ]
        );
    }

    public function data(Request $request)
    {
        $this->authorizePermission('poultry.egg.view');

        $rows = EggCollection::query()->forBusiness($this->businessId())
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->input('batch_id'));
            })
            ->with(['batch', 'grade'])
            ->orderByDesc('collection_date')
            ->limit(500)
            ->get();

        return response()->json(['data' => $rows]);
    }
}
