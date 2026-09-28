<?php

namespace Modules\Poultry\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\DailyRecord;
use Modules\Poultry\Entities\WeightSample;
use Modules\Poultry\Services\BatchService;
use Modules\Poultry\Services\PerformanceCalculator;

/**
 * Daily entry. Written for the reality that this is filled in on a phone, in a
 * shed, often for yesterday - so the same day resubmitted updates rather than
 * duplicates, and backdating is allowed rather than blocked.
 */
class DailyRecordController extends PoultryBaseController
{
    protected $batches;
    protected $performance;

    public function __construct(BatchService $batches, PerformanceCalculator $performance)
    {
        $this->batches     = $batches;
        $this->performance = $performance;
    }

    public function index(Request $request)
    {
        $this->authorizePermission('poultry.daily.view');

        $businessId = $this->businessId();
        $from = $request->input('from', Carbon::today()->subDays(14)->toDateString());
        $to   = $request->input('to', Carbon::today()->toDateString());

        $records = DailyRecord::query()->forBusiness($businessId)
            ->between($from, $to)
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->input('batch_id'));
            })
            ->with('batch')
            ->orderByDesc('record_date')
            ->paginate(50);

        return view('poultry::daily.index', [
            'records' => $records,
            'batches' => Batch::dropdown(null, false, $businessId),
            'filters' => compact('from', 'to') + $request->only('batch_id'),
        ]);
    }

    public function entry(Request $request)
    {
        $this->authorizePermission('poultry.daily.create');

        $businessId = $this->businessId();
        $date       = $request->input('date', Carbon::today()->toDateString());

        $batches = Batch::query()->forBusiness($businessId)->open()
            ->with(['house', 'farm'])
            ->orderBy('batch_code')
            ->get();

        // Pre-fill anything already entered for this date so the screen edits
        // rather than silently creating a second version.
        $existing = DailyRecord::query()->forBusiness($businessId)
            ->whereDate('record_date', $date)
            ->get()
            ->keyBy('batch_id');

        return view('poultry::daily.entry', compact('batches', 'existing', 'date'));
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.daily.create');

        $data = $request->validate([
            'batch_id'        => 'required|integer',
            'record_date'     => 'required|date|before_or_equal:today',
            'mortality'       => 'nullable|integer|min:0',
            'culls'           => 'nullable|integer|min:0',
            'feed_kg'         => 'nullable|numeric|min:0',
            'water_litres'    => 'nullable|numeric|min:0',
            'temperature_c'   => 'nullable|numeric',
            'humidity_pct'    => 'nullable|numeric|min:0|max:100',
            'avg_weight_g'    => 'nullable|numeric|min:0',
            'mortality_cause' => 'nullable|string|max:255',
            'notes'           => 'nullable|string',
        ]);

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($data['batch_id']);

        if (! $batch->is_open) {
            return $this->fail('Batch '.$batch->batch_code.' is closed and cannot accept entries.');
        }

        $loss = (int) ($data['mortality'] ?? 0) + (int) ($data['culls'] ?? 0);

        if ($loss > $batch->current_qty) {
            return $this->fail(
                'Recorded loss of '.$loss.' exceeds the current head count of '.$batch->current_qty.'.'
            );
        }

        $record = $this->batches->recordDay($batch, $data);

        return $this->ok('Daily record saved.', [
            'record'      => $record,
            'current_qty' => $batch->fresh()->current_qty,
            'summary'     => $this->performance->summary($batch->fresh()),
        ]);
    }

    public function destroy($id)
    {
        $this->authorizePermission('poultry.daily.create');

        $record = DailyRecord::query()->forBusiness($this->businessId())->findOrFail($id);
        $batch  = $record->batch;

        $record->delete();

        if ($batch) {
            $this->batches->recalculateHeadCount($batch);
        }

        return $this->ok('Daily record removed and head count recalculated.');
    }

    /**
     * Weight sampling. Accepts either a list of individual weights - in which
     * case uniformity CV is computed - or a pre-totalled sample.
     */
    public function storeWeightSample(Request $request)
    {
        $this->authorizePermission('poultry.daily.create');

        $data = $request->validate([
            'batch_id'    => 'required|integer',
            'sample_date' => 'required|date|before_or_equal:today',
            'weights'     => 'nullable|array',
            'weights.*'   => 'numeric|min:0',
            'birds_sampled'  => 'nullable|integer|min:1',
            'total_weight_g' => 'nullable|numeric|min:0',
        ]);

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($data['batch_id']);

        if (! empty($data['weights'])) {
            $weights       = array_map('floatval', $data['weights']);
            $birdsSampled  = count($weights);
            $totalWeight   = array_sum($weights);
            $uniformityCv  = $this->performance->uniformityCv($weights);
        } else {
            $birdsSampled = $data['birds_sampled'] ?? null;
            $totalWeight  = $data['total_weight_g'] ?? null;
            $uniformityCv = null;

            if (! $birdsSampled || ! $totalWeight) {
                return $this->fail('Provide either individual weights or both a sample size and total weight.');
            }
        }

        $avgWeight = $birdsSampled > 0 ? round($totalWeight / $birdsSampled, 2) : 0;

        $sample = WeightSample::create([
            'business_id'     => $batch->business_id,
            'batch_id'        => $batch->id,
            'sample_date'     => $data['sample_date'],
            'age_days'        => $batch->ageInDays($data['sample_date']),
            'birds_sampled'   => $birdsSampled,
            'total_weight_g'  => $totalWeight,
            'avg_weight_g'    => $avgWeight,
            'uniformity_cv'   => $uniformityCv,
            'target_weight_g' => $batch->breed
                ? $batch->breed->targetWeightAt($batch->ageInDays($data['sample_date']))
                : null,
        ]);

        return $this->ok('Weight sample recorded.', [
            'sample'       => $sample,
            'pct_of_target' => $sample->pct_of_target,
            'uniformity'   => $sample->uniformity_band,
        ]);
    }

    public function data(Request $request)
    {
        $this->authorizePermission('poultry.daily.view');

        $rows = DailyRecord::query()->forBusiness($this->businessId())
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->input('batch_id'));
            })
            ->with('batch')
            ->orderByDesc('record_date')
            ->limit(500)
            ->get();

        return response()->json(['data' => $rows]);
    }
}
