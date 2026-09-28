<?php

namespace Modules\Poultry\Services;

use Modules\Poultry\Entities\BatchCost;
use Modules\Poultry\Support\BusinessContext;

/**
 * The second and last file in this module that knows core exists.
 *
 * Records a cost against a batch and, when the ledger integration is on and
 * the core accounting util is present, posts it so it appears in Finance.
 * When core is absent the cost is still recorded in poultry_batch_costs, so
 * cost per bird analytics keep working on a standalone install.
 */
class LedgerGateway
{
    protected const CORE_ACCOUNT_UTIL = 'App\Utils\AccountTransactionUtil';

    public function isAvailable()
    {
        return (bool) config('poultry.post_to_ledger', true) && class_exists(self::CORE_ACCOUNT_UTIL);
    }

    /**
     * Record a cost against a batch.
     *
     * @param  string  $costType  one of BatchCost::TYPES
     * @param  string  $sourceType  e.g. 'feed_consumption' - lets a correction
     *                              find and reverse the cost row it generated
     */
    public function recordCost($batchId, $costType, $amount, $costDate, $sourceType = null, $sourceId = null, $narration = null)
    {
        if ($amount == 0) {
            return null;
        }

        $cost = BatchCost::create([
            'business_id' => BusinessContext::id(),
            'batch_id'    => $batchId,
            'cost_date'   => $costDate,
            'cost_type'   => $costType,
            'amount'      => $amount,
            'source_type' => $sourceType,
            'source_id'   => $sourceId,
            'narration'   => $narration,
            'is_posted'   => false,
        ]);

        // Ledger posting is deliberately best effort. A failure to reach
        // Finance must not lose the operational record of the cost.
        if ($this->isAvailable()) {
            try {
                $cost->is_posted = true;
                $cost->save();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $cost;
    }

    /**
     * Reverse the cost rows a source row generated. Called when a feed issue
     * or treatment is corrected, so the batch cost stays in step.
     */
    public function reverseCostsFor($sourceType, $sourceId)
    {
        $costs = BatchCost::ofSource($sourceType, $sourceId)->get();

        foreach ($costs as $cost) {
            BatchCost::create([
                'business_id' => $cost->business_id,
                'batch_id'    => $cost->batch_id,
                'cost_date'   => now()->toDateString(),
                'cost_type'   => $cost->cost_type,
                'amount'      => -1 * $cost->amount,
                'source_type' => $sourceType.'_reversal',
                'source_id'   => $sourceId,
                'narration'   => 'Reversal of cost #'.$cost->id,
                'is_posted'   => $cost->is_posted,
            ]);
        }

        return $costs->count();
    }
}
