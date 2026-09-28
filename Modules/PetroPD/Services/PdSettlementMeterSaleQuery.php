<?php

namespace Modules\PetroPD\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;

/**
 * ONE source for "which meter sales belong to this settlement?".
 *
 * WHY THIS EXISTS
 * ---------------
 * On 22 August a two-pump shift displayed one pump. Finding out why took a full
 * day, because three screens answered that question three different ways and
 * the missing reading failed each for a different reason:
 *
 *   Settlement page      forPdSettlement scope   excluded any payment-linked row
 *   Meter Sale table     the same scope, but reached through a DIFFERENT
 *                        controller that holds a near-identical copy of the
 *                        settlement logic
 *   Reconfirmation       the meter_sales_pd relation, which matches on
 *                        settlement_no - and the row had none
 *
 * Three fixes were needed for one fault. Counted across the module there are 46
 * query sites reading meter sales in 7 files; 13 carry their own copy of the
 * payment-link exclusion and only 2 use the shared scope.
 *
 * This class is the one they should all call.
 *
 * HOW TO ADOPT IT - READ THIS BEFORE CHANGING ANY CALLER
 * ------------------------------------------------------
 * Do NOT switch callers over in bulk. Some existing exclusions are correct for
 * their context - the payment controller's queries in particular are often
 * about payments rather than sales, where excluding a settled row is right.
 *
 * Use compareWithLegacy() first. It runs this class alongside an existing query
 * and logs where they differ, WITHOUT changing what the screen shows. Every
 * difference is either a bug here or a bug there, and must be understood before
 * a caller moves.
 *
 * Then move one caller at a time, display screens before posting paths, and
 * check a known settlement after each.
 */
class PdSettlementMeterSaleQuery
{
    /**
     * The rule, in one place.
     *
     * A meter sale belongs to a PD settlement when:
     *
     *   it is this business, shift and operator
     *   AND it is a closing reading (or was entered by hand here - see below)
     *   AND it is not owned by a Direct Settlement
     *   AND either it carries no payment link,
     *       OR its payment has not been settled anywhere
     *
     * @param  array  $pdPrefixes  settlement number prefixes owned by PD, e.g. ['PDST']
     */
    public function query(
        int $businessId,
        int $shiftId,
        int $pumpOperatorId,
        array $pdPrefixes = ['PDST']
    ): Builder {
        return PumpOperatorMeterSale::query()
            ->with(['details', 'details.pump'])
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->where('pump_operator_id', $pumpOperatorId)
            ->where(fn ($q) => $this->applyClosingReadingRule($q))
            ->where(fn ($q) => $this->applyUnsettledPaymentRule($q))
            ->where(fn ($q) => $this->applyOwnedByPdRule($q, $pdPrefixes));
    }

    /**
     * Closing readings only - but NOT by filtering on source alone.
     *
     * A meter sale added by hand in the settlement screen's Manual Entry is
     * saved with NO source at all. Filtering source = 'closing' outright would
     * make every hand-added row vanish the moment it was saved, breaking the
     * tool used to correct settlements. That warning is in the existing code
     * and is honoured here.
     */
    private function applyClosingReadingRule($query)
    {
        if (! Schema::hasColumn('pump_operator_meter_sales', 'source')) {
            return $query;
        }

        return $query->where('source', 'closing')
            ->orWhereNull('source')
            ->orWhere('source', '');
    }

    /**
     * The 22 August correction, and the reason this class exists.
     *
     * The old rule was a flat whereNull('p_o_payment_id') - exclude anything
     * carrying a payment link. Its intent is right and is kept: a sale entered
     * on the Payments page has already been handed over and is settled in Petro
     * Direct, so counting it here as well charges the operator TWICE.
     *
     * But a payment LINK is not proof the payment was SETTLED. Payment 286 was
     * linked and unused; its reading - a real 951.42 litres - disappeared from
     * every screen and the shift could not be settled at all.
     *
     * So settled-ness is tested rather than assumed:
     *
     *     is_used = 1   settled elsewhere -> excluded, as before
     *     is_used = 0   settled nowhere   -> included; counting it here is the
     *                                        first and only time and cannot
     *                                        double-charge anyone
     *     no payment row                  -> included; nothing can have settled
     *                                        a payment that does not exist
     */
    private function applyUnsettledPaymentRule($query)
    {
        return $query
            ->whereNull('p_o_payment_id')
            ->orWhereNotExists(function ($settled) {
                $settled
                    ->select(DB::raw(1))
                    ->from('pump_operator_payments')
                    ->whereColumn(
                        'pump_operator_payments.id',
                        'pump_operator_meter_sales.p_o_payment_id'
                    )
                    ->where('pump_operator_payments.is_used', 1);
            });
    }

    /**
     * Direct Settlement's rows stay out of PD Settlement.
     *
     * PDST belongs to PD, ST to Direct. An unset settlement_no means the row is
     * not yet claimed by either and is still available to this settlement.
     */
    private function applyOwnedByPdRule($query, array $pdPrefixes)
    {
        $query->whereNull('settlement_no')->orWhere('settlement_no', '');

        foreach ($pdPrefixes as $prefix) {
            if ($prefix === null || $prefix === '') {
                continue;
            }

            $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
        }

        return $query;
    }

    /**
     * The rows, resolved.
     */
    public function get(
        int $businessId,
        int $shiftId,
        int $pumpOperatorId,
        array $pdPrefixes = ['PDST']
    ): Collection {
        return $this->query($businessId, $shiftId, $pumpOperatorId, $pdPrefixes)->get();
    }

    /**
     * The total, from the same rows the screens show.
     *
     * Summed from the DETAIL rows, because that is where the money is. A sale
     * header can carry a stale amount; the details are what the settlement
     * posts and what the tables display.
     */
    public function total(
        int $businessId,
        int $shiftId,
        int $pumpOperatorId,
        array $pdPrefixes = ['PDST']
    ): float {
        $total = 0.0;

        foreach ($this->get($businessId, $shiftId, $pumpOperatorId, $pdPrefixes) as $sale) {
            foreach ($sale->details as $detail) {
                $total += (float) ($detail->amount ?? 0);
            }
        }

        return round($total, 2);
    }

    /**
     * STAGE ONE. Run this class beside an existing query and log the difference.
     *
     * This changes nothing on screen. It exists so that a caller can be checked
     * BEFORE it is moved across, because some of the differences will turn out
     * to be deliberate and some will be bugs - and the two must be told apart
     * while nothing depends on the answer.
     *
     * Call it from a screen, leave it for a few days, then read the log:
     *
     *     grep 'PD-ONE-SOURCE' storage/logs/tenants/<tenant>/laravel-*.log
     *
     * A line only appears when the two disagree. Silence means that caller is
     * safe to move.
     *
     * @param  \Illuminate\Support\Collection  $legacyRows  what the caller found
     * @param  string  $caller  where from, e.g. 'PetroPDController@getManualEntryMeterSales'
     */
    public function compareWithLegacy(
        Collection $legacyRows,
        string $caller,
        int $businessId,
        int $shiftId,
        int $pumpOperatorId,
        array $pdPrefixes = ['PDST']
    ): void {
        try {
            $mine = $this->get($businessId, $shiftId, $pumpOperatorId, $pdPrefixes)
                ->pluck('id')
                ->sort()
                ->values()
                ->toArray();

            $theirs = $legacyRows->pluck('id')->sort()->values()->toArray();

            if ($mine === $theirs) {
                return;
            }

            Log::warning('PD-ONE-SOURCE difference', [
                'caller' => $caller,
                'business_id' => $businessId,
                'shift_id' => $shiftId,
                'pump_operator_id' => $pumpOperatorId,
                'one_source_ids' => $mine,
                'caller_ids' => $theirs,
                'only_in_one_source' => array_values(array_diff($mine, $theirs)),
                'only_in_caller' => array_values(array_diff($theirs, $mine)),
            ]);
        } catch (\Throwable $e) {
            // A comparison must never break the screen it is watching.
            Log::warning('PD-ONE-SOURCE comparison failed', [
                'caller' => $caller,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
