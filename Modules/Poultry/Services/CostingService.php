<?php

namespace Modules\Poultry\Services;

use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\BatchCost;
use Modules\Poultry\Entities\EggCollection;
use Modules\Poultry\Entities\Harvest;

/**
 * Batch profitability.
 *
 * THE DISTINCTION THAT MATTERS
 *   A broiler batch is a work in progress job. Day old chicks, feed and
 *   medication accumulate as cost and release to cost of sales when the birds
 *   are harvested. Cost per kg of live weight is the meaningful figure.
 *
 *   A layer batch is a depreciating asset. The rearing cost through to point
 *   of lay is capitalised, then amortised across the laying cycle, with the
 *   spent hen sale as residual value. Cost per egg is the meaningful figure,
 *   and it is only correct if the capitalised rearing cost is spread rather
 *   than dumped into the first month of lay.
 *
 *   Posting both the same way is the single most common way a poultry costing
 *   report ends up producing numbers nobody trusts.
 */
class CostingService
{
    protected $performance;

    public function __construct(PerformanceCalculator $performance)
    {
        $this->performance = $performance;
    }

    /** Total accumulated cost for a batch, optionally to a date. */
    public function totalCost(Batch $batch, $asAt = null)
    {
        $query = BatchCost::where('batch_id', $batch->id);

        if ($asAt) {
            $query->whereDate('cost_date', '<=', $asAt);
        }

        return (float) $query->sum('amount');
    }

    /** Cost broken out by type, for the batch P&L screen. */
    public function costBreakdown(Batch $batch, $asAt = null)
    {
        $rows = BatchCost::where('batch_id', $batch->id)
            ->when($asAt, function ($q) use ($asAt) {
                $q->whereDate('cost_date', '<=', $asAt);
            })
            ->selectRaw('cost_type, SUM(amount) as total')
            ->groupBy('cost_type')
            ->pluck('total', 'cost_type')
            ->toArray();

        $total = array_sum($rows);

        $breakdown = [];
        foreach ($rows as $type => $amount) {
            $breakdown[] = [
                'cost_type' => $type,
                'label'     => BatchCost::TYPES[$type] ?? $type,
                'amount'    => round($amount, 2),
                'pct'       => $total > 0 ? round(($amount / $total) * 100, 1) : 0,
            ];
        }

        usort($breakdown, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        return ['lines' => $breakdown, 'total' => round($total, 2)];
    }

    /**
     * Broiler costing - work in progress released at harvest.
     */
    public function broilerResult(Batch $batch, $asAt = null)
    {
        $cost      = $this->totalCost($batch, $asAt);
        $revenue   = (float) Harvest::where('batch_id', $batch->id)
            ->when($asAt, function ($q) use ($asAt) {
                $q->whereDate('harvest_date', '<=', $asAt);
            })
            ->sum('total_value');

        $weightKg  = (float) Harvest::where('batch_id', $batch->id)->sum('total_weight_kg');
        $birdsOut  = (int) Harvest::where('batch_id', $batch->id)->sum('birds_qty');

        return [
            'treatment'        => 'work_in_progress',
            'total_cost'       => round($cost, 2),
            'total_revenue'    => round($revenue, 2),
            'gross_margin'     => round($revenue - $cost, 2),
            'cost_per_bird'    => $birdsOut > 0 ? round($cost / $birdsOut, 4) : null,
            'cost_per_kg'      => $weightKg > 0 ? round($cost / $weightKg, 4) : null,
            'revenue_per_kg'   => $weightKg > 0 ? round($revenue / $weightKg, 4) : null,
            'birds_harvested'  => $birdsOut,
            'live_weight_kg'   => round($weightKg, 2),
        ];
    }

    /**
     * Layer costing - rearing cost amortised across the laying cycle.
     *
     * The amortisation charge for the period is the capitalised rearing cost
     * less expected residual (spent hen) value, spread over the configured
     * laying cycle. Only that charge - not the whole capitalised amount -
     * belongs in cost per egg.
     */
    public function layerResult(Batch $batch, $from = null, $to = null)
    {
        $capitalised = (float) $batch->doc_total_cost;

        $cycleWeeks = (int) \Modules\Poultry\Entities\Setting::get(
            'laying_cycle_weeks',
            config('poultry.defaults.laying_cycle_weeks')
        );

        $residual = (float) Harvest::where('batch_id', $batch->id)
            ->where('harvest_type', 'spent_hen')
            ->sum('total_value');

        $amortisable   = max(0, $capitalised - $residual);
        $weeklyCharge  = $cycleWeeks > 0 ? $amortisable / $cycleWeeks : 0;
        $weeksInLay    = max(0, $batch->week_of_lay);
        $amortisedToDate = round($weeklyCharge * $weeksInLay, 2);

        // Running costs exclude the capitalised doc cost - that is amortised.
        $runningCost = (float) BatchCost::where('batch_id', $batch->id)
            ->where('cost_type', '!=', 'doc')
            ->when($from, function ($q) use ($from) {
                $q->whereDate('cost_date', '>=', $from);
            })
            ->when($to, function ($q) use ($to) {
                $q->whereDate('cost_date', '<=', $to);
            })
            ->sum('amount');

        $eggs = (int) EggCollection::where('batch_id', $batch->id)
            ->when($from, function ($q) use ($from) {
                $q->whereDate('collection_date', '>=', $from);
            })
            ->when($to, function ($q) use ($to) {
                $q->whereDate('collection_date', '<=', $to);
            })
            ->sum('qty');

        $chargedCost = $runningCost + $amortisedToDate;

        return [
            'treatment'          => 'amortising_asset',
            'capitalised_cost'   => round($capitalised, 2),
            'residual_value'     => round($residual, 2),
            'weekly_amortisation' => round($weeklyCharge, 4),
            'amortised_to_date'  => $amortisedToDate,
            'running_cost'       => round($runningCost, 2),
            'total_charged_cost' => round($chargedCost, 2),
            'eggs_produced'      => $eggs,
            'cost_per_egg'       => $eggs > 0 ? round($chargedCost / $eggs, 4) : null,
            'weeks_in_lay'       => $weeksInLay,
        ];
    }

    /** Dispatches to the right treatment for the batch type. */
    public function result(Batch $batch, $from = null, $to = null)
    {
        if (in_array($batch->bird_type, ['layer', 'breeder'], true)) {
            return $this->layerResult($batch, $from, $to);
        }

        return $this->broilerResult($batch, $to);
    }
}
