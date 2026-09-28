<?php

namespace Modules\Poultry\Entities;

use Carbon\Carbon;
use Modules\Poultry\Entities\Shared\Variation;

/**
 * Medication administered to a batch.
 *
 * withdrawal_until is the field that matters most here. While it is in the
 * future, eggs and meat from this batch must not enter the food chain. That is
 * a regulatory requirement, so it is enforced in Services\WithdrawalGuard and
 * checked before any production is posted to saleable stock - not left to the
 * operator to remember.
 */
class Treatment extends PoultryModel
{
    protected $table = 'poultry_treatments';

    protected $dates = ['started_on', 'ended_on', 'withdrawal_until'];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    /**
     * Withdrawal runs from the LAST day of treatment, not the first. Setting
     * it from started_on is a common and consequential mistake.
     */
    public function calculateWithdrawalUntil()
    {
        if (empty($this->withdrawal_days) || $this->withdrawal_days <= 0) {
            return null;
        }

        $lastDose = $this->ended_on ?: $this->started_on;

        if (empty($lastDose)) {
            return null;
        }

        return Carbon::parse($lastDose)->addDays((int) $this->withdrawal_days)->toDateString();
    }

    public function scopeActiveWithdrawal($query, $asAt = null)
    {
        $asAt = $asAt ?: Carbon::today()->toDateString();

        return $query->whereNotNull('withdrawal_until')
                     ->whereDate('withdrawal_until', '>=', $asAt);
    }

    public function getDaysRemainingAttribute()
    {
        if (empty($this->withdrawal_until)) {
            return 0;
        }

        $until = Carbon::parse($this->withdrawal_until);

        return $until->isPast() ? 0 : Carbon::today()->diffInDays($until);
    }
}
