<?php

namespace Modules\Poultry\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\BatchTransfer;
use Modules\Poultry\Entities\DailyRecord;
use Modules\Poultry\Support\BusinessContext;

/**
 * Batch lifecycle: placement, daily entry, transfer at point of lay, closure.
 */
class BatchService
{
    protected $performance;
    protected $ledger;

    public function __construct(PerformanceCalculator $performance, LedgerGateway $ledger)
    {
        $this->performance = $performance;
        $this->ledger      = $ledger;
    }

    /**
     * Place a new batch. The day old chick cost is recorded immediately - it
     * is the opening cost of the flock and everything else accumulates on top.
     */
    public function place(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['business_id'] = BusinessContext::id();
            $data['current_qty'] = $data['initial_qty'];
            $data['status']      = $data['status'] ?? 'active';

            if (empty($data['batch_code'])) {
                $data['batch_code'] = $this->generateBatchCode($data['bird_type'], $data['placement_date']);
            }

            $data['doc_total_cost'] = ($data['doc_unit_cost'] ?? 0) * $data['initial_qty'];

            $batch = Batch::create($data);

            if ($batch->doc_total_cost > 0) {
                $this->ledger->recordCost(
                    $batch->id,
                    'doc',
                    $batch->doc_total_cost,
                    $batch->placement_date,
                    'batch_placement',
                    $batch->id,
                    'Day old chicks - '.$batch->batch_code
                );
            }

            return $batch;
        });
    }

    /**
     * Record or update a day's entry. Deliberately updateOrCreate: staff
     * resubmitting the same day must correct, never duplicate.
     */
    public function recordDay(Batch $batch, array $data)
    {
        return DB::transaction(function () use ($batch, $data) {
            $recordDate = Carbon::parse($data['record_date'])->toDateString();

            $record = DailyRecord::updateOrCreate(
                ['batch_id' => $batch->id, 'record_date' => $recordDate],
                [
                    'business_id'     => $batch->business_id,
                    'age_days'        => $batch->ageInDays($recordDate),
                    'mortality'       => $data['mortality'] ?? 0,
                    'culls'           => $data['culls'] ?? 0,
                    'feed_kg'         => $data['feed_kg'] ?? 0,
                    'water_litres'    => $data['water_litres'] ?? 0,
                    'temperature_c'   => $data['temperature_c'] ?? null,
                    'humidity_pct'    => $data['humidity_pct'] ?? null,
                    'avg_weight_g'    => $data['avg_weight_g'] ?? null,
                    'mortality_cause' => $data['mortality_cause'] ?? null,
                    'notes'           => $data['notes'] ?? null,
                ]
            );

            $this->recalculateHeadCount($batch);

            return $record;
        });
    }

    /**
     * Rebuild the denormalised head count from the underlying rows. Called
     * after any entry or correction - never incremented, because backdated
     * edits would leave an incremented counter permanently wrong.
     */
    public function recalculateHeadCount(Batch $batch)
    {
        $batch->current_qty = $this->performance->headCountAt($batch);
        $batch->save();

        return $batch->current_qty;
    }

    /**
     * Move a rearing (pullet) flock to point of lay, creating the layer batch
     * that succeeds it and carrying the accumulated rearing cost across as its
     * opening capitalised value. This is the moment the costing treatment
     * changes from work in progress to amortising asset.
     */
    public function transferToLay(Batch $pullet, array $data)
    {
        if ($pullet->bird_type !== 'pullet') {
            throw new \InvalidArgumentException('Only a pullet batch can be transferred to lay.');
        }

        return DB::transaction(function () use ($pullet, $data) {
            $qty = (int) $data['qty'];

            $rearingCost = (float) $pullet->costs()->sum('amount');
            $perBird     = $pullet->current_qty > 0 ? $rearingCost / $pullet->current_qty : 0;
            $transferred = round($perBird * $qty, 4);

            $layer = Batch::create([
                'business_id'         => $pullet->business_id,
                'farm_id'             => $data['farm_id'] ?? $pullet->farm_id,
                'house_id'            => $data['to_house_id'],
                'breed_id'            => $pullet->breed_id,
                'batch_code'          => $data['batch_code'] ?? $pullet->batch_code.'-L',
                'bird_type'           => 'layer',
                'placement_date'      => $data['transfer_date'],
                'initial_qty'         => $qty,
                'female_qty'          => $qty,
                'current_qty'         => $qty,
                'supplier_contact_id' => $pullet->supplier_contact_id,
                'doc_unit_cost'       => $perBird,
                'doc_total_cost'      => $transferred,
                'status'              => 'active',
                'notes'               => 'Transferred at point of lay from '.$pullet->batch_code,
            ]);

            BatchTransfer::create([
                'business_id'      => $pullet->business_id,
                'batch_id'         => $pullet->id,
                'from_house_id'    => $pullet->house_id,
                'to_house_id'      => $data['to_house_id'],
                'to_batch_id'      => $layer->id,
                'transfer_date'    => $data['transfer_date'],
                'qty'              => $qty,
                'cost_transferred' => $transferred,
                'reason'           => 'Point of lay transfer',
            ]);

            $this->ledger->recordCost(
                $layer->id,
                'doc',
                $transferred,
                $data['transfer_date'],
                'point_of_lay_transfer',
                $pullet->id,
                'Capitalised rearing cost from '.$pullet->batch_code
            );

            $this->recalculateHeadCount($pullet);

            if ($pullet->current_qty <= 0) {
                $pullet->status   = 'transferred';
                $pullet->closed_on = $data['transfer_date'];
                $pullet->save();
            }

            return $layer;
        });
    }

    /** Close a depleted batch and freeze its final performance figures. */
    public function close(Batch $batch, $closedOn = null, $notes = null)
    {
        $closedOn = $closedOn ?: Carbon::today()->toDateString();

        $batch->status    = 'closed';
        $batch->closed_on = $closedOn;

        if ($notes) {
            $batch->notes = trim($batch->notes."\n".$notes);
        }

        $batch->save();

        return $this->performance->summary($batch, $closedOn);
    }

    /** BR-YYYYMM-0001 style code, unique per tenant. */
    protected function generateBatchCode($birdType, $placementDate)
    {
        $prefix = strtoupper(substr($birdType, 0, 2));
        $period = Carbon::parse($placementDate)->format('Ym');
        $stem   = $prefix.'-'.$period.'-';

        $last = Batch::query()
            ->forBusiness()
            ->where('batch_code', 'like', $stem.'%')
            ->orderByDesc('batch_code')
            ->value('batch_code');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $stem.str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
