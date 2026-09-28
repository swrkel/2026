<?php

namespace Modules\Poultry\Services;

use Illuminate\Support\Facades\DB;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\FeedConsumption;
use Modules\Poultry\Entities\Shared\Variation;
use Modules\Poultry\Support\BusinessContext;

/**
 * Issuing feed and medication to a batch.
 *
 * The item is a shared product; the stock movement goes through StockGateway;
 * the cost lands in poultry_batch_costs via LedgerGateway. Nothing here writes
 * to variation_location_details directly.
 */
class FeedService
{
    protected $stock;
    protected $ledger;

    public function __construct(StockGateway $stock, LedgerGateway $ledger)
    {
        $this->stock  = $stock;
        $this->ledger = $ledger;
    }

    public function issue(Batch $batch, array $data)
    {
        return DB::transaction(function () use ($batch, $data) {
            $variation = Variation::findOrFail($data['variation_id']);

            $unitCost  = $data['unit_cost'] ?? $variation->issue_cost;
            $totalCost = round($unitCost * $data['qty'], 4);

            $consumption = FeedConsumption::create([
                'business_id'      => $batch->business_id,
                'batch_id'         => $batch->id,
                'consumption_date' => $data['consumption_date'],
                'product_id'       => $variation->product_id,
                'variation_id'     => $variation->id,
                'location_id'      => $data['location_id'],
                'qty'              => $data['qty'],
                'unit_cost'        => $unitCost,
                'total_cost'       => $totalCost,
                'notes'            => $data['notes'] ?? null,
                'is_posted'        => false,
            ]);

            $transactionId = $this->stock->issue([
                'business_id'  => $batch->business_id,
                'product_id'   => $variation->product_id,
                'variation_id' => $variation->id,
                'location_id'  => $data['location_id'],
                'qty'          => $data['qty'],
                'total_cost'   => $totalCost,
                'date'         => $data['consumption_date'],
                'notes'        => 'Feed issue - batch '.$batch->batch_code,
                'allow_overdraw' => $data['allow_overdraw'] ?? false,
            ]);

            if ($transactionId) {
                $consumption->stock_transaction_id = $transactionId;
                $consumption->is_posted            = true;
                $consumption->save();
            }

            $this->ledger->recordCost(
                $batch->id,
                $data['cost_type'] ?? 'feed',
                $totalCost,
                $data['consumption_date'],
                'feed_consumption',
                $consumption->id,
                'Feed issued to '.$batch->batch_code
            );

            return $consumption;
        });
    }

    /**
     * Reverse an issue. The original row is kept and a reversing stock
     * movement is posted, so the audit trail stays intact.
     */
    public function reverse(FeedConsumption $consumption)
    {
        return DB::transaction(function () use ($consumption) {
            if ($consumption->is_posted && $consumption->stock_transaction_id) {
                $this->stock->reverse($consumption->stock_transaction_id, [
                    'direction'    => 'issue',
                    'product_id'   => $consumption->product_id,
                    'variation_id' => $consumption->variation_id,
                    'location_id'  => $consumption->location_id,
                    'qty'          => $consumption->qty,
                    'total_cost'   => $consumption->total_cost,
                    'business_id'  => $consumption->business_id,
                    'notes'        => 'Reversal of feed issue #'.$consumption->id,
                ]);
            }

            $this->ledger->reverseCostsFor('feed_consumption', $consumption->id);

            $consumption->delete();

            return true;
        });
    }

    /** Feed consumed per batch over a period, for the feed report. */
    public function consumptionByBatch($from, $to, $businessId = null)
    {
        return FeedConsumption::query()
            ->forBusiness($businessId)
            ->between($from, $to)
            ->selectRaw('batch_id, SUM(qty) as total_qty, SUM(total_cost) as total_cost')
            ->groupBy('batch_id')
            ->with('batch')
            ->get();
    }
}
