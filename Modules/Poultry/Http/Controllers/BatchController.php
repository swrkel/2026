<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\Breed;
use Modules\Poultry\Entities\Farm;
use Modules\Poultry\Entities\House;
use Modules\Poultry\Entities\Shared\Contact;
use Modules\Poultry\Services\BatchService;
use Modules\Poultry\Services\CostingService;
use Modules\Poultry\Services\HealthService;
use Modules\Poultry\Services\PerformanceCalculator;

class BatchController extends PoultryBaseController
{
    protected $batches;
    protected $performance;
    protected $costing;
    protected $health;

    public function __construct(
        BatchService $batches,
        PerformanceCalculator $performance,
        CostingService $costing,
        HealthService $health
    ) {
        $this->batches     = $batches;
        $this->performance = $performance;
        $this->costing     = $costing;
        $this->health      = $health;
    }

    public function index(Request $request)
    {
        $this->authorizePermission('poultry.batch.view');

        $businessId = $this->businessId();

        $batches = Batch::query()->forBusiness($businessId)
            ->with(['farm', 'house', 'breed'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->when($request->filled('bird_type'), function ($q) use ($request) {
                $q->where('bird_type', $request->input('bird_type'));
            })
            ->when($request->filled('farm_id'), function ($q) use ($request) {
                $q->where('farm_id', $request->input('farm_id'));
            })
            ->orderByDesc('placement_date')
            ->paginate(25);

        return view('poultry::batches.index', [
            'batches'    => $batches,
            'farms'      => Farm::dropdown($businessId),
            'birdTypes'  => Batch::BIRD_TYPES,
            'statuses'   => Batch::STATUSES,
            'filters'    => $request->only(['status', 'bird_type', 'farm_id']),
        ]);
    }

    public function create()
    {
        $this->authorizePermission('poultry.batch.create');

        $businessId = $this->businessId();

        return view('poultry::batches.create', [
            'farms'     => Farm::dropdown($businessId),
            'houses'    => House::dropdown(null, $businessId),
            'breeds'    => Breed::dropdown(null, $businessId),
            'suppliers' => Contact::dropdown('supplier', $businessId),
            'birdTypes' => Batch::BIRD_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.batch.create');

        $data = $request->validate([
            'farm_id'             => 'required|integer',
            'house_id'            => 'required|integer',
            'breed_id'            => 'nullable|integer',
            'bird_type'           => 'required|in:broiler,layer,pullet,breeder',
            'batch_code'          => 'nullable|string|max:64',
            'placement_date'      => 'required|date',
            'initial_qty'         => 'required|integer|min:1',
            'male_qty'            => 'nullable|integer|min:0',
            'female_qty'          => 'nullable|integer|min:0',
            'supplier_contact_id' => 'nullable|integer',
            'doc_unit_cost'       => 'nullable|numeric|min:0',
            'expected_depletion_date' => 'nullable|date|after:placement_date',
            'notes'               => 'nullable|string',
        ]);

        // A house already holding an active batch cannot take another.
        $occupied = Batch::query()->forBusiness()
            ->where('house_id', $data['house_id'])
            ->where('status', 'active')
            ->exists();

        if ($occupied) {
            return back()->withInput()
                ->with('status', ['success' => 0, 'msg' => 'That house already holds an active batch. Close or transfer it first.']);
        }

        try {
            $batch = $this->batches->place($data);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()
                ->with('status', ['success' => 0, 'msg' => 'Could not place the batch: '.$e->getMessage()]);
        }

        return redirect()
            ->route('poultry.batch.show', $batch->id)
            ->with('status', ['success' => 1, 'msg' => 'Batch '.$batch->batch_code.' placed.']);
    }

    public function show($id)
    {
        $this->authorizePermission('poultry.batch.view');

        $batch = Batch::query()->forBusiness($this->businessId())
            ->with(['farm', 'house', 'breed', 'supplier'])
            ->findOrFail($id);

        return view('poultry::batches.show', [
            'batch'       => $batch,
            'summary'     => $this->performance->summary($batch),
            'costing'     => $this->costing->result($batch),
            'breakdown'   => $this->costing->costBreakdown($batch),
            'vaccination' => $this->health->schedule($batch),
            'withdrawal'  => $batch->activeWithdrawal(),
            'recent'      => $batch->dailyRecords()->orderByDesc('record_date')->limit(14)->get(),
        ]);
    }

    public function edit($id)
    {
        $this->authorizePermission('poultry.batch.edit');

        $businessId = $this->businessId();
        $batch      = Batch::query()->forBusiness($businessId)->findOrFail($id);

        return view('poultry::batches.edit', [
            'batch'     => $batch,
            'farms'     => Farm::dropdown($businessId),
            'houses'    => House::dropdown($batch->farm_id, $businessId),
            'breeds'    => Breed::dropdown(null, $businessId),
            'suppliers' => Contact::dropdown('supplier', $businessId),
            'birdTypes' => Batch::BIRD_TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('poultry.batch.edit');

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($id);

        $data = $request->validate([
            'house_id'                => 'required|integer',
            'breed_id'                => 'nullable|integer',
            'supplier_contact_id'     => 'nullable|integer',
            'doc_unit_cost'           => 'nullable|numeric|min:0',
            'expected_depletion_date' => 'nullable|date',
            'notes'                   => 'nullable|string',
        ]);

        $batch->fill($data)->save();

        return redirect()->route('poultry.batch.show', $batch->id)
            ->with('status', ['success' => 1, 'msg' => 'Batch updated.']);
    }

    public function close(Request $request, $id)
    {
        $this->authorizePermission('poultry.batch.close');

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($id);

        $summary = $this->batches->close(
            $batch,
            $request->input('closed_on'),
            $request->input('notes')
        );

        return redirect()->route('poultry.batch.show', $batch->id)
            ->with('status', ['success' => 1, 'msg' => 'Batch closed. Final FCR: '.($summary['fcr'] ?? 'n/a')]);
    }

    public function transferForm($id)
    {
        $this->authorizePermission('poultry.batch.edit');

        $businessId = $this->businessId();
        $batch      = Batch::query()->forBusiness($businessId)->findOrFail($id);

        return view('poultry::batches.transfer', [
            'batch'  => $batch,
            'houses' => House::dropdown(null, $businessId),
        ]);
    }

    public function transfer(Request $request, $id)
    {
        $this->authorizePermission('poultry.batch.edit');

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($id);

        $data = $request->validate([
            'to_house_id'   => 'required|integer',
            'transfer_date' => 'required|date',
            'qty'           => 'required|integer|min:1|max:'.max(1, $batch->current_qty),
            'batch_code'    => 'nullable|string|max:64',
        ]);

        try {
            $layer = $this->batches->transferToLay($batch, $data);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()
                ->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }

        return redirect()->route('poultry.batch.show', $layer->id)
            ->with('status', ['success' => 1, 'msg' => 'Transferred to lay as '.$layer->batch_code.'.']);
    }

    /** Ajax - houses belonging to a farm, for the cascading select. */
    public function housesByFarm($farmId)
    {
        $this->authorizePermission('poultry.batch.view');

        return response()->json(House::dropdown($farmId, $this->businessId()));
    }

    /** Ajax - batch list for datatables style consumers. */
    public function data(Request $request)
    {
        $this->authorizePermission('poultry.batch.view');

        $rows = Batch::query()->forBusiness($this->businessId())
            ->with(['farm', 'house'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->orderByDesc('placement_date')
            ->get()
            ->map(function ($batch) {
                return [
                    'id'          => $batch->id,
                    'batch_code'  => $batch->batch_code,
                    'bird_type'   => Batch::BIRD_TYPES[$batch->bird_type] ?? $batch->bird_type,
                    'farm'        => optional($batch->farm)->name,
                    'house'       => optional($batch->house)->name,
                    'placed'      => optional($batch->placement_date)->format('Y-m-d'),
                    'age_days'    => $batch->age_days,
                    'placed_qty'  => $batch->initial_qty,
                    'current_qty' => $batch->current_qty,
                    'mortality'   => $batch->mortality_pct,
                    'status'      => $batch->status,
                ];
            });

        return response()->json(['data' => $rows]);
    }
}
