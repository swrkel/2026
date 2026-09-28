<?php

namespace Modules\Poultry\Services;

use Carbon\Carbon;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\DailyRecord;
use Modules\Poultry\Entities\EggCollection;
use Modules\Poultry\Entities\FeedConsumption;
use Modules\Poultry\Entities\Harvest;

/**
 * The performance KPIs a poultry operation is actually run on.
 *
 * Everything here is recomputed from the underlying daily rows rather than
 * read from a running total, because backdated corrections are normal on a
 * farm - a mortality miscount from three days ago is discovered and fixed, and
 * every dependent figure has to move with it.
 */
class PerformanceCalculator
{
    /**
     * Head count at a given date: placed, less mortality, culls, harvests and
     * transfers out, up to and including that date.
     */
    public function headCountAt(Batch $batch, $asAt = null)
    {
        $asAt = $asAt ? Carbon::parse($asAt)->toDateString() : Carbon::today()->toDateString();

        $losses = DailyRecord::where('batch_id', $batch->id)
            ->whereDate('record_date', '<=', $asAt)
            ->selectRaw('COALESCE(SUM(mortality),0) as m, COALESCE(SUM(culls),0) as c')
            ->first();

        $harvested = (int) Harvest::where('batch_id', $batch->id)
            ->whereDate('harvest_date', '<=', $asAt)
            ->sum('birds_qty');

        $transferred = (int) $batch->transfersOut()
            ->whereDate('transfer_date', '<=', $asAt)
            ->sum('qty');

        $count = $batch->initial_qty - (int) $losses->m - (int) $losses->c - $harvested - $transferred;

        return max(0, $count);
    }

    /** Cumulative mortality percentage including culls. */
    public function mortalityPct(Batch $batch, $asAt = null)
    {
        if ($batch->initial_qty <= 0) {
            return 0.0;
        }

        $asAt = $asAt ? Carbon::parse($asAt)->toDateString() : Carbon::today()->toDateString();

        $losses = DailyRecord::where('batch_id', $batch->id)
            ->whereDate('record_date', '<=', $asAt)
            ->selectRaw('COALESCE(SUM(mortality),0) + COALESCE(SUM(culls),0) as total')
            ->value('total');

        return round(((int) $losses / $batch->initial_qty) * 100, 2);
    }

    /**
     * Feed Conversion Ratio - kg of feed per kg of live weight gained.
     * The single most watched broiler number, because feed is roughly 70
     * percent of the cost of production. Lower is better.
     */
    public function fcr(Batch $batch, $asAt = null)
    {
        $feedKg = $this->totalFeedKg($batch, $asAt);

        if ($feedKg <= 0) {
            return null;
        }

        $liveWeightKg = $this->liveWeightKg($batch, $asAt);

        if ($liveWeightKg <= 0) {
            return null;
        }

        return round($feedKg / $liveWeightKg, 3);
    }

    public function totalFeedKg(Batch $batch, $asAt = null)
    {
        $query = FeedConsumption::where('batch_id', $batch->id);

        if ($asAt) {
            $query->whereDate('consumption_date', '<=', Carbon::parse($asAt)->toDateString());
        }

        return (float) $query->sum('qty');
    }

    /**
     * Total live weight carried by the flock: harvested weight plus the
     * estimated weight still standing, from the latest weight sample.
     */
    public function liveWeightKg(Batch $batch, $asAt = null)
    {
        $harvestedKg = (float) Harvest::where('batch_id', $batch->id)
            ->when($asAt, function ($q) use ($asAt) {
                $q->whereDate('harvest_date', '<=', Carbon::parse($asAt)->toDateString());
            })
            ->sum('total_weight_kg');

        $latestSample = $batch->weightSamples()
            ->when($asAt, function ($q) use ($asAt) {
                $q->whereDate('sample_date', '<=', Carbon::parse($asAt)->toDateString());
            })
            ->orderByDesc('sample_date')
            ->first();

        $standingKg = 0.0;
        if ($latestSample && $latestSample->avg_weight_g > 0) {
            $standingKg = ($this->headCountAt($batch, $asAt) * $latestSample->avg_weight_g) / 1000;
        }

        return $harvestedKg + $standingKg;
    }

    /**
     * Hen-day production percentage - eggs collected as a share of the hens
     * alive that day. The core layer KPI. A good commercial layer peaks
     * around 90-95 percent in weeks 26-30 of lay.
     */
    public function henDayPct(Batch $batch, $date)
    {
        $date = Carbon::parse($date)->toDateString();

        $eggs = (int) EggCollection::where('batch_id', $batch->id)
            ->whereDate('collection_date', $date)
            ->sum('qty');

        $hens = $this->headCountAt($batch, $date);

        if ($hens <= 0) {
            return 0.0;
        }

        return round(($eggs / $hens) * 100, 2);
    }

    /** Average hen-day across a period. */
    public function henDayPctRange(Batch $batch, $from, $to)
    {
        $from = Carbon::parse($from);
        $to   = Carbon::parse($to);

        $days  = 0;
        $total = 0.0;

        for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
            $total += $this->henDayPct($batch, $cursor->toDateString());
            $days++;
        }

        return $days > 0 ? round($total / $days, 2) : 0.0;
    }

    /**
     * European Production Efficiency Factor - the single figure broiler
     * operations benchmark on. Combines liveability, growth rate and feed
     * efficiency. Above 300 is decent, above 400 is very good.
     *
     *   EPEF = (liveability% x avg weight kg) / (age days x FCR) x 100
     */
    public function epef(Batch $batch, $asAt = null)
    {
        $fcr = $this->fcr($batch, $asAt);
        $age = $batch->ageInDays($asAt);

        if (! $fcr || $age <= 0 || $batch->initial_qty <= 0) {
            return null;
        }

        $liveability = 100 - $this->mortalityPct($batch, $asAt);

        $headCount = $this->headCountAt($batch, $asAt);
        if ($headCount <= 0) {
            return null;
        }

        $avgWeightKg = $this->liveWeightKg($batch, $asAt) / $headCount;

        return round((($liveability * $avgWeightKg) / ($age * $fcr)) * 100, 1);
    }

    /**
     * Uniformity as coefficient of variation, from an array of individual
     * bird weights. Below 10 percent is a well grown, even flock.
     */
    public function uniformityCv(array $weights)
    {
        $n = count($weights);

        if ($n < 2) {
            return null;
        }

        $mean = array_sum($weights) / $n;

        if ($mean <= 0) {
            return null;
        }

        $variance = 0.0;
        foreach ($weights as $weight) {
            $variance += pow($weight - $mean, 2);
        }

        $stdDev = sqrt($variance / ($n - 1));

        return round(($stdDev / $mean) * 100, 2);
    }

    /** Everything the batch detail screen and dashboard need, in one call. */
    public function summary(Batch $batch, $asAt = null)
    {
        $headCount = $this->headCountAt($batch, $asAt);

        return [
            'age_days'       => $batch->ageInDays($asAt),
            'age_weeks'      => (int) floor($batch->ageInDays($asAt) / 7),
            'head_count'     => $headCount,
            'mortality_pct'  => $this->mortalityPct($batch, $asAt),
            'liveability_pct' => round(100 - $this->mortalityPct($batch, $asAt), 2),
            'total_feed_kg'  => $this->totalFeedKg($batch, $asAt),
            'feed_per_bird_kg' => $headCount > 0
                ? round($this->totalFeedKg($batch, $asAt) / $headCount, 3)
                : null,
            'fcr'            => $this->fcr($batch, $asAt),
            'epef'           => in_array($batch->bird_type, ['broiler'], true)
                ? $this->epef($batch, $asAt)
                : null,
            'hen_day_pct'    => in_array($batch->bird_type, ['layer', 'breeder'], true)
                ? $this->henDayPct($batch, $asAt ?: Carbon::today()->toDateString())
                : null,
            'week_of_lay'    => $batch->week_of_lay,
        ];
    }
}
