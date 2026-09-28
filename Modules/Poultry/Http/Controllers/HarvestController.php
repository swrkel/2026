<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\Harvest;
use Modules\Poultry\Entities\Shared\BusinessLocation;
use Modules\Poultry\Entities\Shared\Contact;
use Modules\Poultry\Entities\Shared\Product;
use Modules\Poultry\Entities\Setting;
use Modules\Poultry\Services\BatchService;
use Modules\Poultry\Services\CostingService;
use Modules\Poultry\Services\StockGateway;
use Modules\Poultry\Services\WithdrawalGuard;

class HarvestController extends PoultryBaseController
{
    protected $stock;
    protected $withdrawal;
    protected $batches;
    protected $costing;

    public function __construct(
        StockGateway $stock,
        WithdrawalGuard $withdrawal,
        BatchService $batches,
        CostingService $costing
    ) {
        $this->stock      = $stock;
        $this->withdrawal = $withdrawal;
        $this->batches    = $batches;
        $this->costing    = $costing;
    }

    public function index(Request $request)
    {
        $this->authorizePermission('poultry.harvest.view');

        $harvests = Harvest::query()->forBusiness($this->businessId())
            ->with(['batch', 'buyer'])
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->input('batch_id'));
            })
            ->orderByDesc('harvest_date')
            ->paginate(50);

        return view('poultry::harvest.index', [
            'harvests' => $harvests,
            'batches'  => Batch::dropdown(null, false, $this->businessId()),
        ]);
    }

    public function create()
    {
        $this->authorizePermission('poultry.harvest.create');

        $businessId  = $this->businessId();
        $birdCategories = (array) Setting::get('live_bird_category_ids', []);

        return view('poultry::harvest.create', [
            'batches'   => Batch::dropdown(null, true, $businessId),
            'buyers'    => Contact::dropdown('customer', $businessId),
            'items'     => Product::variationDropdown($birdCategories, $businessId),
            'locations' => BusinessLocation::dropdown($businessId),
            'types'     => Harvest::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.harvest.create');

        $data = $request->validate([
            'batch_id'         => 'required|integer',
            'harvest_date'     => 'required|date|before_or_equal:today',
            'harvest_type'     => 'required|in:full,partial,cull_sale,spent_hen',
            'birds_qty'        => 'required|integer|min:1',
            'total_weight_kg'  => 'nullable|numeric|min:0',
            'buyer_contact_id' => 'nullable|integer',
            'variation_id'     => 'nullable|integer',
            'location_id'      => 'nullable|integer',
            'rate_per_kg'      => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($data['batch_id']);

        if ($data['birds_qty'] > $batch->current_qty) {
            return $this->fail(
                'Cannot harvest '.$data['birds_qty'].' birds - only '.$batch->current_qty.' are on hand.'
            );
        }

        /*
         * Hard stop. Meat from a batch inside a drug withdrawal period must not
         * enter the food chain, so unlike egg collection - where the record is
         * still useful - the harvest itself is refused.
         */
        if ($this->withdrawal->isBlocked($batch->id, $data['harvest_date'])) {
            $treatment = $this->withdrawal->activeFor($batch->id, $data['harvest_date']);

            return $this->fail(
                'Batch '.$batch->batch_code.' is under drug withdrawal for '.$treatment->name
                .' until '.$treatment->withdrawal_until->toDateString()
                .'. Harvesting for sale is not permitted until then.'
            );
        }

        try {
            $harvest = DB::transaction(function () use ($batch, $data) {
                $weightKg = $data['total_weight_kg'] ?? 0;

                $harvest = Harvest::create([
                    'business_id'      => $batch->business_id,
                    'batch_id'         => $batch->id,
                    'harvest_date'     => $data['harvest_date'],
                    'harvest_type'     => $data['harvest_type'],
                    'birds_qty'        => $data['birds_qty'],
                    'total_weight_kg'  => $weightKg,
                    'avg_weight_kg'    => $data['birds_qty'] > 0 ? round($weightKg / $data['birds_qty'], 4) : 0,
                    'buyer_contact_id' => $data['buyer_contact_id'] ?? null,
                    'variation_id'     => $data['variation_id'] ?? null,
                    'location_id'      => $data['location_id'] ?? null,
                    'rate_per_kg'      => $data['rate_per_kg'] ?? 0,
                    'total_value'      => round($weightKg * ($data['rate_per_kg'] ?? 0), 4),
                    'notes'            => $data['notes'] ?? null,
                ]);

                // Produce the live weight into shared stock so the existing
                // sales modules can invoice it. No invoicing code lives here.
                if (! empty($data['variation_id']) && ! empty($data['location_id']) && $weightKg > 0) {
                    $variation = \Modules\Poultry\Entities\Shared\Variation::find($data['variation_id']);

                    if ($variation) {
                        $transactionId = $this->stock->produce([
                            'business_id'      => $batch->business_id,
                            'product_id'       => $variation->product_id,
                            'variation_id'     => $variation->id,
                            'location_id'      => $data['location_id'],
                            'qty'              => $weightKg,
                            'total_cost'       => $harvest->total_value,
                            'date'             => $data['harvest_date'],
                            'transaction_type' => config('poultry.transaction_types.harvest'),
                            'notes'            => 'Harvest - batch '.$batch->batch_code,
                        ]);

                        if ($transactionId) {
                            $harvest->product_id           = $variation->product_id;
                            $harvest->stock_transaction_id = $transactionId;
                            $harvest->is_posted            = true;
                            $harvest->save();
                        }
                    }
                }

                $this->batches->recalculateHeadCount($batch);

                // A full depletion closes the batch and freezes its result.
                if ($data['harvest_type'] === 'full' || $batch->fresh()->current_qty <= 0) {
                    $this->batches->close($batch->fresh(), $data['harvest_date'], 'Closed on full depletion.');
                }

                return $harvest;
            });
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Could not record the harvest: '.$e->getMessage());
        }

        return $this->ok('Harvest recorded.', [
            'harvest' => $harvest,
            'result'  => $this->costing->result($batch->fresh()),
        ]);
    }

    public function data(Request $request)
    {
        $this->authorizePermission('poultry.harvest.view');

        $rows = Harvest::query()->forBusiness($this->businessId())
            ->with(['batch', 'buyer'])
            ->orderByDesc('harvest_date')
            ->limit(500)
            ->get();

        return response()->json(['data' => $rows]);
    }
}
