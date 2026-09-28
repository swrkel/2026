<?php

namespace Modules\Poultry\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\FeedConsumption;
use Modules\Poultry\Entities\Setting;
use Modules\Poultry\Entities\Shared\BusinessLocation;
use Modules\Poultry\Entities\Shared\Product;
use Modules\Poultry\Services\FeedService;
use Modules\Poultry\Services\StockGateway;

class FeedController extends PoultryBaseController
{
    protected $feed;
    protected $stock;

    public function __construct(FeedService $feed, StockGateway $stock)
    {
        $this->feed  = $feed;
        $this->stock = $stock;
    }

    public function index(Request $request)
    {
        $this->authorizePermission('poultry.feed.view');

        $businessId = $this->businessId();
        $from = $request->input('from', Carbon::today()->subDays(30)->toDateString());
        $to   = $request->input('to', Carbon::today()->toDateString());

        $consumptions = FeedConsumption::query()->forBusiness($businessId)
            ->between($from, $to)
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->input('batch_id'));
            })
            ->with(['batch', 'variation.product'])
            ->orderByDesc('consumption_date')
            ->paginate(50);

        return view('poultry::feed.index', [
            'consumptions' => $consumptions,
            'batches'      => Batch::dropdown(null, false, $businessId),
            'summary'      => $this->feed->consumptionByBatch($from, $to, $businessId),
            'filters'      => compact('from', 'to') + $request->only('batch_id'),
        ]);
    }

    public function issueForm()
    {
        $this->authorizePermission('poultry.feed.create');

        $businessId = $this->businessId();

        /*
         * Feed and medication are ordinary products in the shared catalogue.
         * Which categories count as feed is a per-tenant setting, so the module
         * points at whatever category tree the business already uses rather
         * than imposing its own.
         */
        $feedCategories = (array) Setting::get('feed_category_ids', []);

        return view('poultry::feed.issue', [
            'batches'   => Batch::dropdown(null, true, $businessId),
            'items'     => Product::variationDropdown($feedCategories, $businessId),
            'locations' => BusinessLocation::dropdown($businessId),
            'stockOn'   => $this->stock->isAvailable(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.feed.create');

        $data = $request->validate([
            'batch_id'         => 'required|integer',
            'consumption_date' => 'required|date|before_or_equal:today',
            'variation_id'     => 'required|integer',
            'location_id'      => 'required|integer',
            'qty'              => 'required|numeric|min:0.0001',
            'unit_cost'        => 'nullable|numeric|min:0',
            'cost_type'        => 'nullable|in:feed,medication',
            'allow_overdraw'   => 'nullable|boolean',
            'notes'            => 'nullable|string',
        ]);

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($data['batch_id']);

        if (! $batch->is_open) {
            return $this->fail('Batch '.$batch->batch_code.' is closed.');
        }

        try {
            $consumption = $this->feed->issue($batch, $data);
        } catch (\RuntimeException $e) {
            // Insufficient stock - a normal operator error, not a crash.
            return $this->fail($e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Could not issue feed: '.$e->getMessage());
        }

        return $this->ok(
            $consumption->is_posted
                ? 'Feed issued and stock updated.'
                : 'Feed recorded. Shared stock posting is switched off.',
            ['consumption' => $consumption]
        );
    }

    public function destroy($id)
    {
        $this->authorizePermission('poultry.feed.create');

        $consumption = FeedConsumption::query()->forBusiness($this->businessId())->findOrFail($id);

        try {
            $this->feed->reverse($consumption);
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Could not reverse the issue: '.$e->getMessage());
        }

        return $this->ok('Feed issue reversed and stock restored.');
    }

    /** Ajax - available stock for a variation at a location. */
    public function stock($variationId, $locationId)
    {
        $this->authorizePermission('poultry.feed.view');

        return response()->json([
            'qty_available' => $this->stock->availableQty($variationId, $locationId),
        ]);
    }

    public function data(Request $request)
    {
        $this->authorizePermission('poultry.feed.view');

        $rows = FeedConsumption::query()->forBusiness($this->businessId())
            ->with(['batch', 'variation.product'])
            ->orderByDesc('consumption_date')
            ->limit(500)
            ->get();

        return response()->json(['data' => $rows]);
    }
}
