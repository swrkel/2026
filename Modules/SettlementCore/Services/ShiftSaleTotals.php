<?php

namespace Modules\SettlementCore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * S 639 - ONE SOURCE FOR A SHIFT'S SALE, PAYMENTS AND BALANCE.
 *
 * WHY THIS CLASS EXISTS
 * ---------------------
 * The same figure was being produced six different ways, from two different
 * tables, and they finally disagreed in public: the Pumper Dashboard closed a
 * shift at Balance to Settle 0.00 while PD Settlement showed Balance -0.01 for
 * the same shift.
 *
 * The implementations that existed before this class:
 *
 *   1. PetroPDController::getMeterSaleTotalByShift()          meter_sale_details.amount
 *   2. HandlesPdMeterSales::getMeterSaleTotalByShift()        meter_sale_details.amount (a second copy)
 *   3. HandlesPdMeterSales::getSettlementPDMeterSaleTotal()   pump_operator_meter_sales.balance
 *   4. AddPaymentController, inline                           pump_operator_meter_sales.balance
 *   5. PetroPDController:2348, inline                         details->sum('amount')
 *   6. Dashboard / day entry / payment controllers, inline    pumper_day_entries.amount
 *
 * Only the last reads the operator's actual metered work. The others read a
 * COPY written at shift close (ClosingShiftController), through dedup filters
 * that can silently drop a row, and across columns of different scale
 * (pumper_day_entries.amount is decimal(20,2); pump_operator_meter_sales.balance
 * is decimal(10,3)).
 *
 * THE RULE THIS CLASS ENFORCES
 * ----------------------------
 * The sale total comes from pumper_day_entries and from nowhere else. Every
 * page - Pumper Dashboard, PD Settlement, reports and, once wired, the account
 * books - reads it through here. pump_operator_meter_sales and its details stay
 * as the audit and print record of what was closed; they are never summed to
 * decide money again.
 *
 * WHY IT LIVES IN SettlementCore
 * ------------------------------
 * PetroPD, PumperDashboard, Petro and PetroDirect each carry their own
 * PumperDayEntry entity. SettlementCore's own module.json says it exists "so
 * Petro and Vat can share one reconciler instead of depending on each other" -
 * the same reason applies here. This class therefore uses the query builder and
 * raw table names only: it imports no module entity, so no module has to depend
 * on another to use it.
 *
 * ARITHMETIC
 * ----------
 * Every total is summed by the database over decimal columns, not in PHP over
 * floats. That removes the accumulation drift as well as the duplication: a
 * half-cent can no longer appear between two readers because there is now only
 * one reader.
 */
final class ShiftSaleTotals
{
    private int $businessId;

    private ?int $shiftId;

    private ?int $pumpOperatorId;

    private ?float $meterSales = null;

    private ?float $otherSales = null;

    /** @var array<string,float>|null */
    private ?array $payments = null;

    private static ?bool $dayEntriesHaveShiftId = null;

    private function __construct(int $businessId, ?int $shiftId, ?int $pumpOperatorId)
    {
        $this->businessId = $businessId;
        $this->shiftId = $shiftId !== null && $shiftId !== 0 ? (int) $shiftId : null;
        $this->pumpOperatorId = ! empty($pumpOperatorId) ? (int) $pumpOperatorId : null;
    }

    /**
     * Build the totals for one shift, optionally narrowed to one operator.
     *
     * Passing no operator gives the whole shift, which is what the settlement
     * list needs; passing one gives that operator's share, which is what the
     * dashboard and the settlement payment screen need.
     */
    public static function for(int $businessId, ?int $shiftId, ?int $pumpOperatorId = null): self
    {
        return new self($businessId, $shiftId, $pumpOperatorId);
    }

    /**
     * Total metered sale for the shift, from pumper_day_entries only.
     *
     * The shift link is the same one getClosingShiftSummary() uses: the
     * assignment's shift_id, or the day entry's own shift_id where that column
     * exists. Both are checked because pumper_day_entries.shift_id was added
     * later and is not present in every tenant.
     */
    public function meterSales(): float
    {
        if ($this->meterSales !== null) {
            return $this->meterSales;
        }

        if ($this->shiftId === null) {
            return $this->meterSales = 0.0;
        }

        $query = DB::table('pumper_day_entries')
            ->leftJoin(
                'pump_operator_assignments',
                'pumper_day_entries.pumper_assignment_id',
                '=',
                'pump_operator_assignments.id'
            )
            ->where('pumper_day_entries.business_id', $this->businessId)
            ->where(function ($shiftQuery) {
                $shiftQuery->where('pump_operator_assignments.shift_id', $this->shiftId);

                if (self::dayEntriesHaveShiftId()) {
                    $shiftQuery->orWhere('pumper_day_entries.shift_id', $this->shiftId);
                }
            });

        if ($this->pumpOperatorId !== null) {
            $query->where('pumper_day_entries.pump_operator_id', $this->pumpOperatorId);
        }

        /*
         * DISTINCT on the primary key, not on the amount.
         *
         * The left join to assignments can repeat a day entry, and the previous
         * implementations tried to correct that by deduplicating on the VALUES
         * (pump, meters, price, qty, amount). That is what could drop a genuine
         * second row for the same pump at the same reading. Keying on the row
         * id cannot: two rows are the same row only when they are the same row.
         */
        $ids = $query->distinct()->pluck('pumper_day_entries.id');

        if ($ids->isEmpty()) {
            return $this->meterSales = 0.0;
        }

        /*
         * S 639: round where a sale becomes money.
         *
         * pumper_day_entries.amount carries THREE decimals - litres x price
         * produces fractions of a cent, which is normal in fuel retail. On
         * shift 1 the sale summed to 153,442.775 and the due to 172,912.775.
         *
         * A half-cent due cannot be settled by any two-decimal payment: 442.78
         * leaves a balance of -0.005 and 442.77 leaves +0.005, and the finalize
         * gate tests abs(balance) < 0.005, so BOTH fail. The settlement could
         * not be saved at any amount.
         *
         * Rounding here fixes that and matches the Pumper Dashboard, which
         * already displays this figure through num_format at the same precision.
         */
        $precision = $this->currencyPrecision();

        /*
         * Rounded by the DATABASE, not by PHP.
         *
         * amount is a decimal column, so SUM() is exact and MySQL ROUND() is a
         * plain half-away-from-zero on that exact value. PHP's round() would
         * have to correct a binary approximation of 153,442.775 first, and
         * languages disagree about which way that goes - PHP answers .78, other
         * runtimes answer .77. Doing it in SQL removes the question.
         */
        return $this->meterSales = (float) DB::table('pumper_day_entries')
            ->whereIn('id', $ids->all())
            ->selectRaw('ROUND(COALESCE(SUM(amount), 0), ' . $precision . ') as total')
            ->value('total');
    }

    /**
     * Other sales for the shift, net of discount.
     *
     * Same expression the dashboard uses in getClosingShiftSummary().
     */
    public function otherSales(): float
    {
        if ($this->otherSales !== null) {
            return $this->otherSales;
        }

        if ($this->shiftId === null) {
            return $this->otherSales = 0.0;
        }

        $query = DB::table('pump_operator_other_sales')
            ->where('business_id', $this->businessId)
            ->where('shift_id', $this->shiftId);

        if ($this->pumpOperatorId !== null && Schema::hasColumn('pump_operator_other_sales', 'pump_operator_id')) {
            $query->where('pump_operator_id', $this->pumpOperatorId);
        }

        return $this->otherSales = (float) $query
            ->selectRaw('ROUND(COALESCE(SUM(sub_total - discount_amount), 0), ' . $this->currencyPrecision() . ') as total')
            ->value('total');
    }

    /**
     * Everything the operator has handed over for this shift, by type.
     *
     * Shortage and excess are included in the total because they are what
     * settles the balance - this is the same set getClosingShiftSummary()
     * reports as Total Payments.
     *
     * @return array<string,float>
     */
    public function paymentsBreakdown(): array
    {
        if ($this->payments !== null) {
            return $this->payments;
        }

        $empty = [
            'cash' => 0.0,
            'card' => 0.0,
            'cheque' => 0.0,
            'credit' => 0.0,
            'other' => 0.0,
            'shortage_excess' => 0.0,
            'total' => 0.0,
        ];

        if ($this->shiftId === null) {
            return $this->payments = $empty;
        }

        $query = DB::table('pump_operator_payments')
            ->where('business_id', $this->businessId)
            ->where('shift_id', $this->shiftId);

        if ($this->pumpOperatorId !== null) {
            $query->where('pump_operator_id', $this->pumpOperatorId);
        }

        $row = $query->select(
            DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = \'cash\' THEN payment_amount ELSE 0 END), 0) as cash'),
            DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = \'card\' THEN payment_amount ELSE 0 END), 0) as card'),
            DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = \'cheque\' THEN payment_amount ELSE 0 END), 0) as cheque'),
            DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = \'credit\' THEN payment_amount ELSE 0 END), 0) as credit'),
            DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = \'other\' THEN payment_amount ELSE 0 END), 0) as other'),
            DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) IN (\'shortage\', \'excess\') THEN payment_amount ELSE 0 END), 0) as shortage_excess'),
            DB::raw('ROUND(COALESCE(SUM(payment_amount), 0), ' . $this->currencyPrecision() . ') as total')
        )->first();

        if (empty($row)) {
            return $this->payments = $empty;
        }

        return $this->payments = [
            'cash' => (float) $row->cash,
            'card' => (float) $row->card,
            'cheque' => (float) $row->cheque,
            'credit' => (float) $row->credit,
            'other' => (float) $row->other,
            'shortage_excess' => (float) $row->shortage_excess,
            'total' => (float) $row->total,
        ];
    }

    public function payments(): float
    {
        return $this->paymentsBreakdown()['total'];
    }

    /**
     * What the operator owes for the shift: metered sale plus other sales.
     *
     * This is the figure PD Settlement shows as Total Amount and the dashboard
     * shows as Total Sale of All Closed Pumps plus Total Other Sales. They are
     * now the same number because they are now the same query.
     */
    public function totalDue(): float
    {
        return $this->meterSales() + $this->otherSales();
    }

    public function balanceToSettle(): float
    {
        return round($this->totalDue() - $this->payments(), $this->currencyPrecision());
    }

    /**
     * True when the shift is square at the business's currency precision.
     *
     * Half a unit of the last place, so 0.005 at two decimals - the same
     * tolerance the PD Settlement finalize gate already used.
     */
    public function isSettled(int $precision = 2): bool
    {
        $tolerance = 0.5 / pow(10, max(0, $precision));

        return abs($this->balanceToSettle()) < $tolerance;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'business_id' => $this->businessId,
            'shift_id' => $this->shiftId,
            'pump_operator_id' => $this->pumpOperatorId,
            'meter_sales' => $this->meterSales(),
            'other_sales' => $this->otherSales(),
            'total_due' => $this->totalDue(),
            'payments' => $this->paymentsBreakdown(),
            'balance_to_settle' => $this->balanceToSettle(),
        ];
    }

    /**
     * PHASE 1 EVIDENCE.
     *
     * Records where a legacy aggregator disagreed with this reader, so the size
     * and spread of the drift can be seen on real data before the account book
     * posting is moved over. Logs only on a real difference, so a correct system
     * writes nothing.
     *
     * Deliberately never throws: a logging helper must not be able to break a
     * settlement screen.
     */
    public function logDivergence(string $caller, float $legacyValue, int $precision = 2): void
    {
        try {
            $tolerance = 0.5 / pow(10, max(0, $precision));
            $authoritative = $this->meterSales();

            if (abs($authoritative - $legacyValue) < $tolerance) {
                return;
            }

            Log::warning('S639 ShiftSaleTotals divergence', [
                'caller' => $caller,
                'business_id' => $this->businessId,
                'shift_id' => $this->shiftId,
                'pump_operator_id' => $this->pumpOperatorId,
                'authoritative_day_entries' => $authoritative,
                'legacy_value' => $legacyValue,
                'difference' => $authoritative - $legacyValue,
            ]);
        } catch (\Throwable $e) {
            // Evidence gathering must never affect the page.
        }
    }

    /**
     * Currency precision for this business, resolved once per request.
     */
    public function currencyPrecision(): int
    {
        static $cache = [];

        if (array_key_exists($this->businessId, $cache)) {
            return $cache[$this->businessId];
        }

        try {
            $precision = DB::table('business')->where('id', $this->businessId)->value('currency_precision');
        } catch (\Throwable $e) {
            $precision = null;
        }

        return $cache[$this->businessId] = ($precision === null || $precision === '') ? 2 : (int) $precision;
    }

    private static function dayEntriesHaveShiftId(): bool
    {
        if (self::$dayEntriesHaveShiftId === null) {
            try {
                self::$dayEntriesHaveShiftId = Schema::hasColumn('pumper_day_entries', 'shift_id');
            } catch (\Throwable $e) {
                self::$dayEntriesHaveShiftId = false;
            }
        }

        return self::$dayEntriesHaveShiftId;
    }
}
