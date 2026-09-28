<?php

namespace Modules\Poultry\Services;

use Carbon\Carbon;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\Treatment;

/**
 * Blocks produce from a batch under an active drug withdrawal from reaching
 * saleable stock.
 *
 * This is a food safety control, not a convenience. A batch treated with an
 * antibiotic carrying a seven day withdrawal must not have its eggs or meat
 * enter the food chain until that period has elapsed. Because this module
 * posts production into the SHARED stock tables - where POS and Distribution
 * can sell it immediately - the check has to happen before posting, not as a
 * warning on a report someone reads later.
 */
class WithdrawalGuard
{
    /** The active withdrawal for a batch on a given date, or null. */
    public function activeFor($batchId, $asAt = null)
    {
        $asAt = $asAt ? Carbon::parse($asAt)->toDateString() : Carbon::today()->toDateString();

        return Treatment::query()
            ->where('batch_id', $batchId)
            ->activeWithdrawal($asAt)
            ->orderByDesc('withdrawal_until')
            ->first();
    }

    public function isBlocked($batchId, $asAt = null)
    {
        return $this->activeFor($batchId, $asAt) !== null;
    }

    /**
     * Assert that produce from this batch may be sold. Throws rather than
     * returning false so a caller cannot post to stock by forgetting to check.
     *
     * @throws \RuntimeException
     */
    public function assertSaleable($batchId, $asAt = null)
    {
        $treatment = $this->activeFor($batchId, $asAt);

        if ($treatment === null) {
            return true;
        }

        $batch = Batch::find($batchId);

        throw new \RuntimeException(sprintf(
            'Batch %s is under drug withdrawal for %s until %s (%d day(s) remaining). '
            .'Produce may be recorded but must not be posted to saleable stock.',
            $batch ? $batch->batch_code : $batchId,
            $treatment->name,
            Carbon::parse($treatment->withdrawal_until)->toDateString(),
            $treatment->days_remaining
        ));
    }

    /** Every batch currently under withdrawal - drives the dashboard alert. */
    public function activeBatches($businessId = null)
    {
        $asAt = Carbon::today()->toDateString();

        return Treatment::query()
            ->forBusiness($businessId)
            ->activeWithdrawal($asAt)
            ->with('batch')
            ->orderBy('withdrawal_until')
            ->get();
    }
}
