<?php

namespace Modules\Poultry\Services;

use Illuminate\Support\Facades\DB;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\EggCollection;
use Modules\Poultry\Entities\EggGrade;

/**
 * Egg collection, and posting saleable grades into shared stock so the
 * existing POS and Distribution modules can sell them.
 *
 * The withdrawal check runs BEFORE posting. A batch under an active drug
 * withdrawal may still have its eggs recorded - the farm needs the production
 * figure - but they must not become saleable stock.
 */
class EggProductionService
{
    protected $stock;
    protected $withdrawal;

    public function __construct(StockGateway $stock, WithdrawalGuard $withdrawal)
    {
        $this->stock      = $stock;
        $this->withdrawal = $withdrawal;
    }

    /**
     * Record a collection. Returns the row plus a flag saying whether it was
     * posted to stock and, if not, why.
     */
    public function collect(Batch $batch, array $data)
    {
        return DB::transaction(function () use ($batch, $data) {
            $grade = EggGrade::findOrFail($data['grade_id']);

            $collection = EggCollection::updateOrCreate(
                [
                    'batch_id'        => $batch->id,
                    'collection_date' => $data['collection_date'],
                    'slot'            => $data['slot'] ?? 'morning',
                    'grade_id'        => $grade->id,
                ],
                [
                    'business_id' => $batch->business_id,
                    'qty'         => $data['qty'],
                    'weight_kg'   => $data['weight_kg'] ?? null,
                ]
            );

            $result = ['collection' => $collection, 'posted' => false, 'reason' => null];

            if (! $grade->is_stocked) {
                $result['reason'] = 'Grade is not mapped to a saleable product.';

                return $result;
            }

            if ($this->withdrawal->isBlocked($batch->id, $data['collection_date'])) {
                $treatment = $this->withdrawal->activeFor($batch->id, $data['collection_date']);
                $result['reason'] = 'Batch under drug withdrawal until '
                    .$treatment->withdrawal_until.' - eggs recorded but not added to saleable stock.';

                return $result;
            }

            // Already posted and unchanged - do not double post on a re-save.
            if ($collection->is_posted && $collection->stock_transaction_id) {
                $result['posted'] = true;

                return $result;
            }

            $transactionId = $this->stock->produce([
                'business_id'      => $batch->business_id,
                'product_id'       => $grade->product_id,
                'variation_id'     => $grade->variation_id,
                'location_id'      => $data['location_id'],
                'qty'              => $data['qty'],
                'date'             => $data['collection_date'],
                'transaction_type' => config('poultry.transaction_types.production'),
                'notes'            => 'Egg production - batch '.$batch->batch_code,
            ]);

            if ($transactionId) {
                $collection->stock_transaction_id = $transactionId;
                $collection->is_posted            = true;
                $collection->save();
                $result['posted'] = true;
            }

            return $result;
        });
    }

    /** Daily totals by grade for a batch over a period. */
    public function summaryByGrade(Batch $batch, $from, $to)
    {
        return EggCollection::where('batch_id', $batch->id)
            ->between($from, $to)
            ->selectRaw('grade_id, SUM(qty) as total_qty, SUM(weight_kg) as total_weight')
            ->groupBy('grade_id')
            ->with('grade')
            ->get();
    }

    /** Total eggs for a batch on a date, across all slots and grades. */
    public function dailyTotal(Batch $batch, $date)
    {
        return (int) EggCollection::where('batch_id', $batch->id)
            ->whereDate('collection_date', $date)
            ->sum('qty');
    }
}
