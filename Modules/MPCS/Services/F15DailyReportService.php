<?php

namespace Modules\MPCS\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\MPCS\Entities\MpcsF15DailyReport;

class F15DailyReportService
{
    private const MAX_CARRY_DAYS = 3660;

    /*
     * IS2029 performance: request-scoped caches.
     *
     * previousTotals() replays the report day by day from the last saved report
     * (or the last F22) up to the selected date. Each simulated day issued about
     * 18 queries, so a month's gap cost ~540 and a year ~6,500 - which is why
     * picking a date felt slow instead of instant.
     *
     * Most of that work is IDENTICAL on every day of the replay. These caches
     * hold what cannot change between days of a single request.
     *
     * Deliberately per-instance rather than a shared cache: the service is
     * resolved per request, so nothing leaks between requests or businesses and
     * no invalidation is needed when an F22 or a report is saved.
     */
    private array $categoryIdsCache = [];

    private array $lastF22DateCache = [];

    /** Dates in the replay window that have an F22, loaded in one query. */
    private ?array $f22DateSet = null;

    /** Saved reports in the replay window, keyed by date, loaded in one query. */
    private ?array $savedReportsByDate = null;

    /** Inclusive bounds of the pre-loaded window, as Y-m-d strings. */
    private ?string $replayWindowFrom = null;

    private ?string $replayWindowTo = null;

    /**
     * Build the complete F15 Daily Report for one business location and one date.
     */
    public function build(int $businessId, int $locationId, string $date): array
    {
        $reportDate = Carbon::parse($date)->toDateString();
        $saved = $this->findSavedReport($businessId, $locationId, $reportDate);
        $manual = $this->manualValues($saved);

        $previous = $this->previousTotals($businessId, $locationId, $reportDate);
        $today = $this->todayValues($businessId, $locationId, $reportDate, $manual);
        // Computed once and reused for is_f22_reset below.
        $isF22Day = $this->isF22Date($businessId, $locationId, $reportDate);

        $rows = $this->composeRows($previous, $today, $isF22Day);

        return [
            'date' => $reportDate,
            'form_no' => $saved && $saved->form_no
                ? (string) $saved->form_no
                : $this->nextFormNumber($businessId, $locationId, $reportDate),
            'is_saved' => (bool) $saved,
            'is_f22_reset' => $isF22Day,
            'manual' => $manual,
            'notes' => (string) ($saved->notes ?? ''),
            'prepared_by' => (string) ($saved->prepared_by ?? ''),
            'prepared_date' => $this->dateString($saved->prepared_date ?? null),
            'checked_by' => (string) ($saved->checked_by ?? ''),
            'checked_date' => $this->dateString($saved->checked_date ?? null),
            'approved_by' => (string) ($saved->approved_by ?? ''),
            'approved_date' => $this->dateString($saved->approved_date ?? null),
            'rows' => $rows,
            'totals' => collect($rows)
                ->filter(function (array $row) {
                    return ($row['type'] ?? null) === 'row' && isset($row['key']);
                })
                ->mapWithKeys(function (array $row) {
                    return [$row['key'] => (float) $row['total']];
                })
                ->all(),
        ];
    }

    /**
     * Persist manual rows/signatures and save the complete Total column snapshot.
     */
    public function save(int $businessId, int $locationId, string $date, array $input, int $userId): array
    {
        $reportDate = Carbon::parse($date)->toDateString();

        return DB::transaction(function () use ($businessId, $locationId, $reportDate, $input, $userId) {
            $report = MpcsF15DailyReport::query()->firstOrNew([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'report_date' => $reportDate,
            ]);

            if (! $report->exists) {
                $report->form_no = $this->nextFormNumber($businessId, $locationId, $reportDate);
                $report->created_by = $userId;
            }

            $report->changes_addition = $this->number($input['changes_addition'] ?? 0);
            $report->changes_deduction = $this->number($input['changes_deduction'] ?? 0);
            $report->damaged = $this->number($input['damaged'] ?? 0);
            $report->others = $this->number($input['others'] ?? 0);
            $report->total_return = $this->number($input['total_return'] ?? 0);
            $report->notes = $this->nullableText($input['notes'] ?? null);
            $report->prepared_by = $this->nullableText($input['prepared_by'] ?? null);
            $report->prepared_date = $this->nullableDate($input['prepared_date'] ?? null);
            $report->checked_by = $this->nullableText($input['checked_by'] ?? null);
            $report->checked_date = $this->nullableDate($input['checked_date'] ?? null);
            $report->approved_by = $this->nullableText($input['approved_by'] ?? null);
            $report->approved_date = $this->nullableDate($input['approved_date'] ?? null);
            $report->updated_by = $userId;
            $report->save();

            // Rebuild after saving manual values so the stored snapshot exactly matches the screen.
            $built = $this->build($businessId, $locationId, $reportDate);
            $report->totals_json = $built['totals'];
            $report->save();

            return $this->build($businessId, $locationId, $reportDate);
        });
    }

    private function composeRows(array $previousBase, array $todayBase, bool $isF22Day = false): array
    {
        /*
         * IS2009: settle the opening stock BEFORE the columns are calculated.
         *
         * calculateColumn() folds oil/gas opening stock into section_grand_total,
         * which then flows into balance_stock_sale_price and grand_total. If the
         * balance were only corrected at row-rendering time (below), those
         * derived totals would still be built from the old per-column figures and
         * the report would not add up - Section Grand Total would disagree with
         * rows 8 + 9 + 10 shown above it.
         *
         * Resolving it here means both columns calculate from the same governing
         * F22 balance, so every derived total stays internally consistent.
         */
        foreach (['oil_opening_stock', 'gas_opening_stock'] as $balanceKey) {
            $todayValue = $this->number($todayBase[$balanceKey] ?? 0);
            $previousValue = $this->number($previousBase[$balanceKey] ?? 0);
            $balance = $todayValue !== 0.0 ? $todayValue : $previousValue;

            $previousBase[$balanceKey] = $balance;
            $todayBase[$balanceKey] = $balance;
        }

        /*
         * IS2029: Today's Row 9 (Oil Opening Stock) carries forward from the
         * Previous Day column.
         *
         *   Today row 9 = (prev row 9 + prev row 3) - (prev row 12 + prev row 14)
         *
         * i.e. opening stock plus purchases, less cash and credit sales - the
         * closing oil stock at the end of the Previous Day column, which is what
         * today opens with.
         *
         * The Previous Day column holds figures ACCUMULATED since the last stock
         * take, so row 3 and rows 12/14 here are the totals for that whole span,
         * not a single day. That is what makes this equivalent to
         * "counted stock, plus everything bought since, less everything sold
         * since".
         *
         *
         * EXCEPT ON A STOCK-TAKING DAY.
         *
         * When an F22 is saved for the selected date - 30 June 2026, for example
         * - Row 9 must show the value counted on that form. A physical count
         * supersedes any carried figure, so $isF22Day skips this calculation and
         * leaves the value openingStockAtSalePrice() already took from the F22
         * itself. The formula then governs every following day until the next
         * F22 is saved, at which point the new count takes over.
         *
         * Placed after the balance settlement above so it reads the settled
         * Previous Day figure, and before calculateColumn() so section_grand_total
         * and the rows derived from it are built from the new value - otherwise
         * rows 8 + 9 + 10 would not agree with row 11.
         *
         * Applies to BOTH product families, each using its own rows:
         *   Row 9  (oil) = (row 9  + row 3) - (row 12 + row 14)
         *   Row 10 (gas) = (row 10 + row 4) - (row 13 + row 15)
         */
        if (! $isF22Day) {
            $todayBase['oil_opening_stock'] =
                ($this->number($previousBase['oil_opening_stock'] ?? 0)
                    + $this->number($previousBase['oil_purchase'] ?? 0))
                - ($this->number($previousBase['oil_cash_sale'] ?? 0)
                    + $this->number($previousBase['oil_credit_sale'] ?? 0));

            // Gas follows the identical rule with its own rows:
            //   Today row 10 = (prev row 10 + prev row 4) - (prev row 13 + prev row 15)
            $todayBase['gas_opening_stock'] =
                ($this->number($previousBase['gas_opening_stock'] ?? 0)
                    + $this->number($previousBase['gas_purchase'] ?? 0))
                - ($this->number($previousBase['gas_cash_sale'] ?? 0)
                    + $this->number($previousBase['gas_credit_sale'] ?? 0));
        }

        $previous = $this->calculateColumn($previousBase);
        $today = $this->calculateColumn($todayBase);

        $definitions = [
            ['section' => 'Purchases & Additions'],
            ['no' => '1', 'key' => 'f18_oil_purchase', 'description' => 'F 18 Oil Purchase'],
            ['no' => '2', 'key' => 'f18_gas_purchase', 'description' => 'F 18 Gas Purchase'],
            ['no' => '3', 'key' => 'oil_purchase', 'description' => 'Oil Purchase'],
            ['no' => '4', 'key' => 'gas_purchase', 'description' => 'Gas Purchase'],
            ['no' => '5', 'key' => 'purchase_subtotal', 'description' => 'Sub Total (1 + 2 + 3 + 4)', 'total_row' => true],
            ['no' => '6', 'key' => 'price_increment', 'description' => 'Price Increment'],
            ['no' => '7', 'key' => 'changes_addition', 'description' => 'Changes', 'manual' => true],
            ['no' => '8', 'key' => 'purchases_total', 'description' => 'Total (5 + 6 + 7)', 'total_row' => true],
            ['no' => '9', 'key' => 'oil_opening_stock', 'description' => 'Oil Opening Stock'],
            ['no' => '10', 'key' => 'gas_opening_stock', 'description' => 'Gas Opening Stock'],
            ['no' => '11', 'key' => 'section_grand_total', 'description' => 'Section Grand Total (8 + 9 + 10)', 'grand_row' => true],
            ['section' => 'Sales & Deductions'],
            ['no' => '12', 'key' => 'oil_cash_sale', 'description' => 'Oil Cash Sale'],
            ['no' => '13', 'key' => 'gas_cash_sale', 'description' => 'Gas Cash Sale'],
            ['no' => '14', 'key' => 'oil_credit_sale', 'description' => 'Oil Credit Sale'],
            ['no' => '15', 'key' => 'gas_credit_sale', 'description' => 'Gas Credit Sale'],
            ['no' => '16', 'key' => 'sales_subtotal', 'description' => 'Total (12 + 13 + 14 + 15)', 'total_row' => true],
            ['no' => '17', 'key' => 'changes_deduction', 'description' => 'Changes - 18 F', 'manual' => true],
            ['no' => '18', 'key' => 'price_reduction', 'description' => 'Price Reduction'],
            ['no' => '19', 'key' => 'damaged', 'description' => 'Damaged', 'manual' => true],
            ['no' => '20', 'key' => 'others', 'description' => 'Others', 'manual' => true],
            ['no' => '21', 'key' => 'total_return', 'description' => 'Total Return', 'manual' => true],
            ['no' => '22', 'key' => 'total_sale', 'description' => 'Total Sale (16 + 17 + 18 + 19 + 20 + 21)', 'total_row' => true],
            // IS2029 item 2: this row is now numbered 23.
            ['no' => '23', 'key' => 'balance_stock_sale_price', 'description' => 'Balance Stock in Sale Price', 'balance_row' => true],
            // IS2029: Grand Total numbered 24.
            ['no' => '24', 'key' => 'grand_total', 'description' => 'Grand Total', 'grand_row' => true],
        ];

        /*
         * IS2009: opening stock is a BALANCE, not a flow.
         *
         * Every row here computed total = previous + today. That is right for
         * purchases and sales, which accumulate, but wrong for opening stock:
         * the reported screenshot showed Previous 5,074,758.92 plus Today
         * 2,039,422.64 giving 7,114,181.56 - a figure that is not any real stock
         * value.
         *
         * Both columns now hold the same governing F22 balance (set above), so
         * for these keys the As of Today column must show that balance rather
         * than double it. section_grand_total and the rows derived from it are
         * included because they CONTAIN the opening stock; summing them would
         * reintroduce the same doubling one level up.
         */
        $balanceKeys = [
            'oil_opening_stock',
            'gas_opening_stock',
            'section_grand_total',
            'balance_stock_sale_price',
            'grand_total',
        ];

        $rows = [];
        foreach ($definitions as $definition) {
            if (isset($definition['section'])) {
                $rows[] = [
                    'type' => 'section',
                    'description' => $definition['section'],
                ];
                continue;
            }

            $key = $definition['key'];
            $previousValue = (float) ($previous[$key] ?? 0);
            $todayValue = (float) ($today[$key] ?? 0);

            if (in_array($key, $balanceKeys, true)) {
                /*
                 * IS2029: "As of Today" repeats the PREVIOUS DAY figure for the
                 * stock rows.
                 *
                 * Rows 9, 10 and 11 previously showed the Today value here. Since
                 * Today's opening stock is now carried forward - yesterday's
                 * stock plus purchases less sales - the requirement is that the
                 * As of Today column continues to report the Previous Day
                 * position for these rows.
                 *
                 * Applied to rows 9, 10 and 11 only. Row 23 (Balance Stock) and
                 * row 24 (Grand Total) keep showing the Today value, which is
                 * what they were verified against earlier; they are listed in
                 * $balanceKeys so they are still not SUMMED, only presented
                 * differently.
                 */
                $useLeadingValue = in_array($key, [
                    'oil_opening_stock',
                    'gas_opening_stock',
                    'section_grand_total',
                ], true);

                $rows[] = array_merge($definition, [
                    'type' => 'row',
                    'previous' => $previousValue,
                    'today' => $todayValue,
                    'total' => $useLeadingValue ? $previousValue : $todayValue,
                ]);

                continue;
            }

            $rows[] = array_merge($definition, [
                'type' => 'row',
                'previous' => $previousValue,
                'today' => $todayValue,
                'total' => $previousValue + $todayValue,
            ]);
        }

        /*
         * IS2029: row 24 "As of Today" = row 22 + row 23 of the SAME column.
         *
         * Grand Total is computed per column as balance + total_sale, but the As
         * of Today column is not a column the service calculates - it is built
         * per row while the rows are rendered above. Row 24 was falling through
         * to the balance treatment and simply repeating its Today value.
         *
         * It is therefore settled here, once both contributing rows exist, by
         * adding their As of Today figures:
         *
         *   row 22 As of Today  (a flow: previous + today)
         * + row 23 As of Today  (a balance: the Today figure)
         *
         * Doing it afterwards rather than in calculateColumn() is deliberate -
         * only at this point are the two totals known in their final form.
         */
        $totalsByKey = [];

        foreach ($rows as $row) {
            if (($row['type'] ?? null) === 'row' && isset($row['key'])) {
                $totalsByKey[$row['key']] = (float) ($row['total'] ?? 0);
            }
        }

        if (array_key_exists('total_sale', $totalsByKey)
            && array_key_exists('balance_stock_sale_price', $totalsByKey)) {
            foreach ($rows as $index => $row) {
                if (($row['key'] ?? null) === 'grand_total') {
                    $rows[$index]['total'] =
                        $totalsByKey['total_sale'] + $totalsByKey['balance_stock_sale_price'];
                    break;
                }
            }
        }

        return $rows;
    }

    private function calculateColumn(array $base): array
    {
        $v = array_merge([
            'f18_oil_purchase' => 0,
            'f18_gas_purchase' => 0,
            'oil_purchase' => 0,
            'gas_purchase' => 0,
            'price_increment' => 0,
            'changes_addition' => 0,
            'oil_opening_stock' => 0,
            'gas_opening_stock' => 0,
            'oil_cash_sale' => 0,
            'gas_cash_sale' => 0,
            'oil_credit_sale' => 0,
            'gas_credit_sale' => 0,
            'changes_deduction' => 0,
            'price_reduction' => 0,
            'damaged' => 0,
            'others' => 0,
            'total_return' => 0,
        ], $base);

        foreach ($v as $key => $amount) {
            $v[$key] = $this->number($amount);
        }

        $v['purchase_subtotal'] = $v['f18_oil_purchase']
            + $v['f18_gas_purchase']
            + $v['oil_purchase']
            + $v['gas_purchase'];
        $v['purchases_total'] = $v['purchase_subtotal']
            + $v['price_increment']
            + $v['changes_addition'];
        $v['section_grand_total'] = $v['purchases_total']
            + $v['oil_opening_stock']
            + $v['gas_opening_stock'];

        $v['sales_subtotal'] = $v['oil_cash_sale']
            + $v['gas_cash_sale']
            + $v['oil_credit_sale']
            + $v['gas_credit_sale'];
        $v['total_sale'] = $v['sales_subtotal']
            + $v['changes_deduction']
            + $v['price_reduction']
            + $v['damaged']
            + $v['others']
            + $v['total_return'];
        /*
         * IS2029: Balance Stock in Sale Price (row 23) = row 11 - row 22.
         *
         *   row 23 = section_grand_total - total_sale
         *
         * i.e. everything brought in, less everything sold - taken per column,
         * so Previous Day and Today each use their own figures.
         *
         * This replaces the earlier per-product form
         *   (row 9 - row 12 - row 14) + (row 10 - row 13 - row 15)
         * which stopped agreeing with the rest of the report once rows 9 and 10
         * began carrying forward: it read the stock rows directly and so missed
         * the purchases that row 11 includes.
         *
         * Deriving it from row 11 keeps the report internally consistent by
         * construction - row 23 can no longer disagree with the section total
         * above it, whatever changes to rows 1 to 10.
         */
        $v['balance_stock_sale_price'] = $v['section_grand_total'] - $v['total_sale'];
        $v['grand_total'] = $v['balance_stock_sale_price'] + $v['total_sale'];

        return $v;
    }

    private function todayValues(int $businessId, int $locationId, string $date, array $manual): array
    {
        $oilIds = $this->categoryIds($businessId, 'oil');
        $gasIds = $this->categoryIds($businessId, 'gas');
        $allIds = array_values(array_unique(array_merge($oilIds, $gasIds)));

        $oilSale = $this->salesTotal($businessId, $locationId, $date, $oilIds);
        $gasSale = $this->salesTotal($businessId, $locationId, $date, $gasIds);
        $oilCredit = $this->creditSales($businessId, $locationId, $date, $oilIds);
        $gasCredit = $this->creditSales($businessId, $locationId, $date, $gasIds);

        return [
            'f18_oil_purchase' => $this->f18Purchase($businessId, $locationId, $date, $oilIds),
            'f18_gas_purchase' => $this->f18Purchase($businessId, $locationId, $date, $gasIds),
            'oil_purchase' => $this->f16Purchase($businessId, $locationId, $date, $oilIds),
            'gas_purchase' => $this->f16Purchase($businessId, $locationId, $date, $gasIds),
            'price_increment' => $this->f17PriceChange($businessId, $locationId, $date, $allIds, 'increase'),
            'changes_addition' => $manual['changes_addition'],
            'oil_opening_stock' => $this->openingStockAtSalePrice($businessId, $locationId, $date, $oilIds),
            'gas_opening_stock' => $this->openingStockAtSalePrice($businessId, $locationId, $date, $gasIds),
            'oil_cash_sale' => max(0, $oilSale - $oilCredit),
            'gas_cash_sale' => max(0, $gasSale - $gasCredit),
            'oil_credit_sale' => $oilCredit,
            'gas_credit_sale' => $gasCredit,
            'changes_deduction' => $manual['changes_deduction'],
            'price_reduction' => $this->f17PriceChange($businessId, $locationId, $date, $allIds, 'decrease'),
            'damaged' => $manual['damaged'],
            'others' => $manual['others'],
            'total_return' => $manual['total_return'],
        ];
    }

    private function previousTotals(int $businessId, int $locationId, string $date): array
    {
        $selected = Carbon::parse($date)->startOfDay();
        $previousDate = $selected->copy()->subDay();

        $savedPrevious = $this->findSavedReport(
            $businessId,
            $locationId,
            $previousDate->toDateString()
        );
        if ($savedPrevious && is_array($savedPrevious->totals_json)) {
            return $this->applyF22PreviousDayReset(
                $businessId,
                $locationId,
                $date,
                $this->baseOnly($savedPrevious->totals_json)
            );
        }

        $carry = [];
        $startDate = null;

        if (Schema::hasTable('mpcs_f15_daily_reports')) {
            $latestSaved = MpcsF15DailyReport::query()
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->whereDate('report_date', '<', $date)
                ->whereNotNull('totals_json')
                ->orderByDesc('report_date')
                ->first();

            if ($latestSaved && is_array($latestSaved->totals_json)) {
                $carry = $this->baseOnly($latestSaved->totals_json);
                $startDate = Carbon::parse($latestSaved->report_date)->addDay();
            }
        }

        if (! $startDate) {
            $lastF22Date = $this->lastF22DateOnOrBefore($businessId, $locationId, $previousDate->toDateString());
            if ($lastF22Date) {
                $startDate = Carbon::parse($lastF22Date);
            } else {
                $openingDate = $this->f15OpeningDate($businessId);
                $startDate = $openingDate ? Carbon::parse($openingDate) : $previousDate->copy();
            }
        }

        if ($startDate->gt($previousDate)) {
            return $this->applyF22PreviousDayReset($businessId, $locationId, $date, $carry);
        }

        // IS2029: load the whole replay window before iterating, so the loop
        // does no per-day F22 or saved-report queries.
        $this->warmReplayWindow(
            $businessId,
            $locationId,
            $startDate->toDateString(),
            $previousDate->toDateString()
        );

        /*
         * IS2029 performance: the day-by-day replay is collapsed into one range.
         *
         * This used to walk every date from $startDate to $previousDate, running
         * ~12 queries per day. A month's gap cost several hundred queries and a
         * year several thousand, which is why changing the date was slow.
         *
         * Only the days AFTER the last reset point can affect the result - a
         * saved report replaces the carry, and an F22 clears it - so everything
         * before that point is discarded rather than recomputed. The remaining
         * span is then summed in a fixed number of queries by rangeValues(),
         * which produces the same arithmetic because combineBase() is a plain
         * sum.
         *
         * MAX_CARRY_DAYS is still honoured as a guard: a span longer than that
         * is clamped, as the loop's iteration cap did.
         */
        $reset = $this->lastResetPoint($startDate->toDateString(), $previousDate->toDateString());

        if ($reset['carry'] !== null) {
            $carry = $reset['carry'];
        }

        $rangeFrom = Carbon::parse($reset['from'])->startOfDay();

        if ($rangeFrom->diffInDays($previousDate) > self::MAX_CARRY_DAYS) {
            $rangeFrom = $previousDate->copy()->subDays(self::MAX_CARRY_DAYS);
        }

        if ($rangeFrom->lte($previousDate)) {
            $carry = $this->combineBase(
                $carry,
                $this->rangeValues(
                    $businessId,
                    $locationId,
                    $rangeFrom->toDateString(),
                    $previousDate->toDateString()
                )
            );
        }

        return $this->applyF22PreviousDayReset($businessId, $locationId, $date, $carry);
    }

    /**
     * Apply the F22 reset to the F15 Previous Day column.
     *
     * At the beginning of a new month every previous-day value starts at zero.
     * For a stock taking saved during the month, only the purchase/addition side
     * is reset; sales and deductions remain available for the daily continuity.
     */
    private function applyF22PreviousDayReset(
        int $businessId,
        int $locationId,
        string $date,
        array $totals
    ): array {
        $totals = $this->baseOnly($totals);

        if (! $this->isF22Date($businessId, $locationId, $date)) {
            return $totals;
        }

        if (Carbon::parse($date)->day === 1) {
            return $this->baseOnly([]);
        }

        foreach ([
            'f18_oil_purchase',
            'f18_gas_purchase',
            'oil_purchase',
            'gas_purchase',
        ] as $purchaseKey) {
            $totals[$purchaseKey] = 0.0;
        }

        return $totals;
    }

    private function combineBase(array $previous, array $today): array
    {
        $previous = $this->baseOnly($previous);
        $today = $this->baseOnly($today);
        $combined = [];

        foreach ($today as $key => $amount) {
            $combined[$key] = $this->number($previous[$key] ?? 0) + $this->number($amount);
        }

        return $combined;
    }

    private function baseOnly(array $totals): array
    {
        $keys = [
            'f18_oil_purchase', 'f18_gas_purchase', 'oil_purchase', 'gas_purchase',
            'price_increment', 'changes_addition', 'oil_opening_stock', 'gas_opening_stock',
            'oil_cash_sale', 'gas_cash_sale', 'oil_credit_sale', 'gas_credit_sale',
            'changes_deduction', 'price_reduction', 'damaged', 'others', 'total_return',
        ];

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->number($totals[$key] ?? 0);
        }

        return $result;
    }

    private function manualValues($saved): array
    {
        return [
            'changes_addition' => $this->number($saved->changes_addition ?? 0),
            'changes_deduction' => $this->number($saved->changes_deduction ?? 0),
            'damaged' => $this->number($saved->damaged ?? 0),
            'others' => $this->number($saved->others ?? 0),
            'total_return' => $this->number($saved->total_return ?? 0),
        ];
    }

    private function findSavedReport(int $businessId, int $locationId, string $date)
    {
        // IS2029: served from the pre-loaded window during the replay loop.
        if ($this->savedReportsByDate !== null) {
            $key = Carbon::parse($date)->toDateString();

            if (array_key_exists($key, $this->savedReportsByDate)) {
                return $this->savedReportsByDate[$key];
            }

            // The window covers the replay range; a date outside it still needs
            // a lookup rather than being reported as "not saved".
            if ($this->withinReplayWindow($key)) {
                return null;
            }
        }

        if (! Schema::hasTable('mpcs_f15_daily_reports')) {
            return null;
        }

        return MpcsF15DailyReport::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->whereDate('report_date', $date)
            ->first();
    }

    private function categoryIds(int $businessId, string $type): array
    {
        // IS2029: the category tree cannot change during one request, yet this
        // was re-queried and re-walked for every simulated day, twice over.
        $cacheKey = $businessId . '|' . $type;

        if (array_key_exists($cacheKey, $this->categoryIdsCache)) {
            return $this->categoryIdsCache[$cacheKey];
        }

        if (! Schema::hasTable('categories')) {
            return $this->categoryIdsCache[$cacheKey] = [];
        }

        $categories = DB::table('categories')
            ->where('business_id', $businessId)
            ->select('id', 'parent_id', 'name')
            ->get();

        $roots = $categories->filter(function ($category) use ($type) {
            $name = strtolower(trim((string) $category->name));
            if ($type === 'gas') {
                return strpos($name, 'gas') !== false;
            }

            if (strpos($name, 'gas') !== false) {
                return false;
            }

            return strpos($name, 'lubricant') !== false
                || preg_match('/(^|[^a-z])oil([^a-z]|$)/i', $name) === 1;
        })->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();

        $resolved = $roots;
        $frontier = $roots;
        while ($frontier !== []) {
            $children = $categories
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->all();
            $children = array_values(array_diff($children, $resolved));
            $resolved = array_values(array_unique(array_merge($resolved, $children)));
            $frontier = $children;
        }

        return $this->categoryIdsCache[$cacheKey] = $resolved;
    }

    private function f18Purchase(int $businessId, int $locationId, string $date, array $categoryIds, ?string $endDate = null): float
    {
        if ($categoryIds === [] || ! Schema::hasTable('form_f18_headers') || ! Schema::hasTable('form_f18_details')) {
            return 0.0;
        }

        return (float) DB::table('form_f18_headers as h')
            ->join('form_f18_details as d', 'd.header_id', '=', 'h.id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->where('h.business_id', $businessId)
            ->whereDate('h.form_date', '>=', $date)
            ->whereDate('h.form_date', '<=', $endDate ?? $date)
            ->where('h.from_location_id', $locationId)
            ->where(function ($query) use ($categoryIds) {
                $query->whereIn('p.category_id', $categoryIds)
                    ->orWhereIn('p.sub_category_id', $categoryIds);
            })
            ->sum('d.received_sale_total');
    }

    private function f16Purchase(int $businessId, int $locationId, string $date, array $categoryIds, ?string $endDate = null): float
    {
        if ($categoryIds === [] || ! Schema::hasTable('transactions') || ! Schema::hasTable('purchase_lines')) {
            return 0.0;
        }

        $priceParts = [];
        if (Schema::hasColumn('purchase_lines', 'sell_price_at_purchase')) {
            $priceParts[] = 'NULLIF(pl.sell_price_at_purchase, 0)';
        }
        if (Schema::hasTable('variations') && Schema::hasColumn('variations', 'sell_price_inc_tax')) {
            $priceParts[] = 'NULLIF(v.sell_price_inc_tax, 0)';
        }
        if (Schema::hasTable('variations') && Schema::hasColumn('variations', 'default_sell_price')) {
            $priceParts[] = 'NULLIF(v.default_sell_price, 0)';
        }
        $priceParts[] = '0';
        $priceExpression = 'COALESCE(' . implode(', ', $priceParts) . ')';

        $query = DB::table('transactions as t')
            ->join('purchase_lines as pl', 'pl.transaction_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'pl.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id')
            ->where('t.business_id', $businessId)
            ->where('t.location_id', $locationId)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->whereDate('t.transaction_date', '>=', $date)
            ->whereDate('t.transaction_date', '<=', $endDate ?? $date)
            ->where(function ($sub) use ($categoryIds) {
                $sub->whereIn('p.category_id', $categoryIds)
                    ->orWhereIn('p.sub_category_id', $categoryIds);
            });

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }
        if (Schema::hasColumn('purchase_lines', 'deleted_at')) {
            $query->whereNull('pl.deleted_at');
        }

        return (float) $query->selectRaw(
            "COALESCE(SUM(($priceExpression) * COALESCE(pl.quantity, 0)), 0) AS amount"
        )->value('amount');
    }

    private function f17PriceChange(
        int $businessId,
        int $locationId,
        string $date,
        array $categoryIds,
        string $mode,
        ?string $endDate = null
    ): float {
        if ($categoryIds === [] || ! Schema::hasTable('form_f17_headers') || ! Schema::hasTable('form_f17_details')) {
            return 0.0;
        }

        $column = $mode === 'decrease' ? 'price_changed_loss' : 'price_changed_gain';
        if (! Schema::hasColumn('form_f17_details', $column)) {
            return 0.0;
        }

        return (float) DB::table('form_f17_headers as h')
            ->join('form_f17_details as d', 'd.header_id', '=', 'h.id')
            ->join('products as p', 'p.id', '=', 'd.product_id')
            ->where('h.business_id', $businessId)
            ->where('h.location_id', $locationId)
            ->whereDate('h.date', '>=', $date)
            ->whereDate('h.date', '<=', $endDate ?? $date)
            ->where('d.select_mode', $mode)
            ->where(function ($query) use ($categoryIds) {
                $query->whereIn('p.category_id', $categoryIds)
                    ->orWhereIn('p.sub_category_id', $categoryIds);
            })
            ->sum('d.' . $column);
    }

    private function openingStockAtSalePrice(
        int $businessId,
        int $locationId,
        string $date,
        array $categoryIds
    ): float {
        if ($categoryIds === []) {
            return 0.0;
        }

        /*
         * IS2009: hold the F22 opening balance until the next F22 is saved.
         *
         * This used to match only an F22 saved on EXACTLY the selected date
         * (whereDate('form_date', $date)). So on the day the F22 was entered the
         * figure was right, and from the very next day it silently fell through
         * to the live variation_location_details calculation below - a different
         * number that drifts with every sale. The ticket asks for the same
         * opening balance to show in Previous Day, Today and As of Today until
         * the end of the month or until another F22 is saved.
         *
         * Taking the LATEST F22 on or before the date gives exactly that: the
         * value holds from the day it is entered, and changes only when a newer
         * F22 supersedes it. lastF22DateOnOrBefore() already implements this
         * lookup for the carry-forward logic in previousTotals(), so the two now
         * agree on which F22 governs a given date.
         */
        if (Schema::hasTable('form_f22_headers') && Schema::hasTable('form_f22_details')) {
            $effectiveF22Date = $this->lastF22DateOnOrBefore($businessId, $locationId, $date);

            if ($effectiveF22Date !== null) {
                $headerIds = DB::table('form_f22_headers')
                    ->where('business_id', $businessId)
                    ->where('location_id', $locationId)
                    ->whereDate('form_date', $effectiveF22Date)
                    ->pluck('id');

                if ($headerIds->isNotEmpty()) {
                    return (float) DB::table('form_f22_details as d')
                        ->join('products as p', 'p.sku', '=', 'd.product_code')
                        ->whereIn('d.header_id', $headerIds->all())
                        ->where('p.business_id', $businessId)
                        ->where(function ($query) use ($categoryIds) {
                            $query->whereIn('p.category_id', $categoryIds)
                                ->orWhereIn('p.sub_category_id', $categoryIds);
                        })
                        ->selectRaw('COALESCE(SUM(COALESCE(d.sales_price_total, d.stock_count * d.unit_sale_price, 0)), 0) AS amount')
                        ->value('amount');
                }
            }
        }

        if (! Schema::hasTable('variation_location_details') || ! Schema::hasTable('variations')) {
            return 0.0;
        }

        $priceParts = [];
        if (Schema::hasColumn('variations', 'sell_price_inc_tax')) {
            $priceParts[] = 'NULLIF(v.sell_price_inc_tax, 0)';
        }
        if (Schema::hasColumn('variations', 'default_sell_price')) {
            $priceParts[] = 'NULLIF(v.default_sell_price, 0)';
        }
        $priceParts[] = '0';
        $priceExpression = 'COALESCE(' . implode(', ', $priceParts) . ')';

        return (float) DB::table('variation_location_details as vld')
            ->join('variations as v', 'v.id', '=', 'vld.variation_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->where('vld.location_id', $locationId)
            ->where('p.business_id', $businessId)
            ->where(function ($query) use ($categoryIds) {
                $query->whereIn('p.category_id', $categoryIds)
                    ->orWhereIn('p.sub_category_id', $categoryIds);
            })
            ->selectRaw("COALESCE(SUM(COALESCE(vld.qty_available, 0) * ($priceExpression)), 0) AS amount")
            ->value('amount');
    }

    private function salesTotal(int $businessId, int $locationId, string $date, array $categoryIds, ?string $endDate = null): float
    {
        if ($categoryIds === [] || ! Schema::hasTable('transactions') || ! Schema::hasTable('transaction_sell_lines')) {
            return 0.0;
        }

        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->where('t.business_id', $businessId)
            ->where('t.location_id', $locationId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereDate('t.transaction_date', '>=', $date)
            ->whereDate('t.transaction_date', '<=', $endDate ?? $date)
            ->where(function ($sub) use ($categoryIds) {
                $sub->whereIn('p.category_id', $categoryIds)
                    ->orWhereIn('p.sub_category_id', $categoryIds);
            });

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }
        if (Schema::hasColumn('transaction_sell_lines', 'deleted_at')) {
            $query->whereNull('tsl.deleted_at');
        }

        return (float) $query->sum(DB::raw('tsl.unit_price_inc_tax * tsl.quantity'));
    }

    private function creditSales(int $businessId, int $locationId, string $date, array $categoryIds, ?string $endDate = null): float
    {
        if ($categoryIds === []) {
            return 0.0;
        }

        $total = 0.0;

        if (Schema::hasTable('transactions') && Schema::hasTable('transaction_sell_lines')) {
            $full = DB::table('transactions as t')
                ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
                ->join('products as p', 'p.id', '=', 'tsl.product_id')
                ->where('t.business_id', $businessId)
                ->where('t.location_id', $locationId)
                ->where('t.type', 'sell')
                ->where('t.is_credit_sale', 1)
                ->whereDate('t.transaction_date', '>=', $date)
                ->whereDate('t.transaction_date', '<=', $endDate ?? $date)
                ->where(function ($sub) use ($categoryIds) {
                    $sub->whereIn('p.category_id', $categoryIds)
                        ->orWhereIn('p.sub_category_id', $categoryIds);
                });

            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $full->whereNull('t.deleted_at');
            }
            if (Schema::hasColumn('transaction_sell_lines', 'deleted_at')) {
                $full->whereNull('tsl.deleted_at');
            }

            $total += (float) $full->sum(DB::raw('tsl.unit_price_inc_tax * tsl.quantity'));

            if (Schema::hasTable('transaction_payments')) {
                $paymentWhere = Schema::hasColumn('transaction_payments', 'deleted_at')
                    ? ' AND deleted_at IS NULL'
                    : '';

                $partial = DB::table('transactions as t')
                    ->join('transaction_sell_lines as tsl', 'tsl.transaction_id', '=', 't.id')
                    ->join('products as p', 'p.id', '=', 'tsl.product_id')
                    ->leftJoin(DB::raw(
                        '(SELECT transaction_id, SUM(amount) AS credit_payment_amount '
                        . 'FROM transaction_payments WHERE method = "credit_sale"'
                        . $paymentWhere
                        . ' GROUP BY transaction_id) AS tp'
                    ), 'tp.transaction_id', '=', 't.id')
                    ->where('t.business_id', $businessId)
                    ->where('t.location_id', $locationId)
                    ->where('t.type', 'sell')
                    ->where('t.status', 'final')
                    ->whereDate('t.transaction_date', '>=', $date)
                    ->whereDate('t.transaction_date', '<=', $endDate ?? $date)
                    ->whereNotNull('tp.credit_payment_amount');

                if (Schema::hasColumn('transactions', 'deleted_at')) {
                    $partial->whereNull('t.deleted_at');
                }
                if (Schema::hasColumn('transaction_sell_lines', 'deleted_at')) {
                    $partial->whereNull('tsl.deleted_at');
                }

                $ids = implode(',', array_map('intval', $categoryIds));
                $rows = $partial->select(
                    't.id',
                    'tp.credit_payment_amount',
                    DB::raw('SUM(tsl.unit_price_inc_tax * tsl.quantity) AS total_sell_amount'),
                    DB::raw(
                        'SUM(CASE WHEN p.category_id IN (' . $ids . ') '
                        . 'OR p.sub_category_id IN (' . $ids . ') '
                        . 'THEN tsl.unit_price_inc_tax * tsl.quantity ELSE 0 END) AS selected_sell_amount'
                    )
                )->groupBy('t.id', 'tp.credit_payment_amount')->get();

                foreach ($rows as $row) {
                    $whole = (float) $row->total_sell_amount;
                    if ($whole > 0) {
                        $total += (float) $row->credit_payment_amount
                            * ((float) $row->selected_sell_amount / $whole);
                    }
                }
            }
        }

        if (Schema::hasTable('settlement_credit_sale_payments') && Schema::hasTable('settlements')) {
            $petro = DB::table('settlement_credit_sale_payments as cs')
                ->join('settlements as s', function ($join) {
                    $join->on('cs.settlement_no', '=', 's.id')
                        ->orOn('cs.settlement_no', '=', 's.settlement_no');
                })
                ->join('products as p', 'p.id', '=', 'cs.product_id')
                ->where('cs.business_id', $businessId)
                ->where('s.location_id', $locationId)
                ->whereDate('s.transaction_date', '>=', $date)
                ->whereDate('s.transaction_date', '<=', $endDate ?? $date)
                ->where(function ($sub) use ($categoryIds) {
                    $sub->whereIn('p.category_id', $categoryIds)
                        ->orWhereIn('p.sub_category_id', $categoryIds);
                });

            $amountExpression = Schema::hasColumn('settlement_credit_sale_payments', 'total_discount')
                ? 'cs.amount - COALESCE(cs.total_discount, 0)'
                : 'cs.amount';
            $total += (float) $petro->sum(DB::raw($amountExpression));
        }

        return $total;
    }

    /**
     * IS2029 performance: load the whole replay window in two queries.
     *
     * The carry-forward loop asked, for EVERY day it simulated, "is there an F22
     * on this date?" and "is there a saved report for this date?" - two queries
     * per day, on top of the sixteen inside todayValues().
     *
     * Both answers come from a single date range, so they are fetched once here
     * and served from memory during the loop. A replay of 365 days drops from
     * 730 queries to 2 for these two lookups alone.
     */

    /**
     * IS2029 performance: the carry-forward totals for a whole DATE RANGE, in a
     * fixed number of queries.
     *
     * This replaces simulating each day and adding the results together. It is
     * valid because combineBase() is a plain sum: the sum of a metric over N
     * days equals that metric summed over the range in one query. Fourteen
     * queries now cover a range of any length, where the loop needed twelve per
     * day.
     *
     * Two properties of the range make this safe, and both are guaranteed by the
     * caller choosing the range to begin AFTER the last reset point:
     *
     *   1. NO SAVED REPORT falls inside it. A saved report replaced the carry
     *      outright, so one inside the range would make a plain sum wrong. The
     *      caller starts the range after the latest saved report.
     *
     *   2. NO F22 DATE falls strictly inside it. An F22 clears the carry. The
     *      caller starts the range ON the latest F22 date, so the only F22 is
     *      the first day - already a fresh start.
     *
     * Because no saved report is inside the range, every day in it has NO manual
     * values, so changes_addition, changes_deduction, damaged, others and
     * total_return are all zero - matching manualValues(null) in the old loop.
     *
     * Opening stock is a BALANCE, not a flow. lastF22DateOnOrBefore() is
     * constant across the range (no F22 inside it), so the per-day figure is
     * identical every day and the loop's repeated addition becomes
     * balance x number of days. That reproduces the old arithmetic exactly
     * rather than changing it.
     */
    private function rangeValues(int $businessId, int $locationId, string $from, string $to): array
    {
        $oilIds = $this->categoryIds($businessId, 'oil');
        $gasIds = $this->categoryIds($businessId, 'gas');
        $allIds = array_values(array_unique(array_merge($oilIds, $gasIds)));

        $days = Carbon::parse($from)->startOfDay()->diffInDays(Carbon::parse($to)->startOfDay()) + 1;

        $oilSale = $this->salesTotal($businessId, $locationId, $from, $oilIds, $to);
        $gasSale = $this->salesTotal($businessId, $locationId, $from, $gasIds, $to);
        $oilCredit = $this->creditSales($businessId, $locationId, $from, $oilIds, $to);
        $gasCredit = $this->creditSales($businessId, $locationId, $from, $gasIds, $to);

        // Constant across the range - see the note above.
        $oilOpening = $this->openingStockAtSalePrice($businessId, $locationId, $from, $oilIds);
        $gasOpening = $this->openingStockAtSalePrice($businessId, $locationId, $from, $gasIds);

        return [
            'f18_oil_purchase' => $this->f18Purchase($businessId, $locationId, $from, $oilIds, $to),
            'f18_gas_purchase' => $this->f18Purchase($businessId, $locationId, $from, $gasIds, $to),
            'oil_purchase' => $this->f16Purchase($businessId, $locationId, $from, $oilIds, $to),
            'gas_purchase' => $this->f16Purchase($businessId, $locationId, $from, $gasIds, $to),
            'price_increment' => $this->f17PriceChange($businessId, $locationId, $from, $allIds, 'increase', $to),
            'changes_addition' => 0.0,
            'oil_opening_stock' => $oilOpening * $days,
            'gas_opening_stock' => $gasOpening * $days,
            'oil_cash_sale' => max(0, $oilSale - $oilCredit),
            'gas_cash_sale' => max(0, $gasSale - $gasCredit),
            'oil_credit_sale' => $oilCredit,
            'gas_credit_sale' => $gasCredit,
            'changes_deduction' => 0.0,
            'price_reduction' => $this->f17PriceChange($businessId, $locationId, $from, $allIds, 'decrease', $to),
            'damaged' => 0.0,
            'others' => 0.0,
            'total_return' => 0.0,
        ];
    }

    /**
     * The latest point in the window at which the carry restarts, and what it
     * restarts from.
     *
     * Everything before this is irrelevant to the final figure, which is what
     * lets the replay be collapsed into one range.
     */
    private function lastResetPoint(string $from, string $to): array
    {
        $cursor = Carbon::parse($to)->startOfDay();
        $start = Carbon::parse($from)->startOfDay();

        while ($cursor->gte($start)) {
            $day = $cursor->toDateString();

            // A saved report replaces the carry outright, so it wins over an F22
            // on the same date - the order the original loop applied them in.
            if (isset($this->savedReportsByDate[$day])
                && is_array($this->savedReportsByDate[$day]->totals_json)) {
                return [
                    'carry' => $this->baseOnly($this->savedReportsByDate[$day]->totals_json),
                    'from' => $cursor->copy()->addDay()->toDateString(),
                ];
            }

            if (isset($this->f22DateSet[$day])) {
                // An F22 clears the carry; that day's own values still count.
                return ['carry' => [], 'from' => $day];
            }

            $cursor->subDay();
        }

        return ['carry' => null, 'from' => $from];
    }

    private function warmReplayWindow(int $businessId, int $locationId, string $from, string $to): void
    {
        $this->replayWindowFrom = Carbon::parse($from)->toDateString();
        $this->replayWindowTo = Carbon::parse($to)->toDateString();

        if ($this->f22DateSet === null) {
            $this->f22DateSet = [];

            if (Schema::hasTable('form_f22_headers')) {
                $dates = DB::table('form_f22_headers')
                    ->where('business_id', $businessId)
                    ->where('location_id', $locationId)
                    ->whereDate('form_date', '>=', $from)
                    ->whereDate('form_date', '<=', $to)
                    ->pluck('form_date');

                foreach ($dates as $value) {
                    $this->f22DateSet[Carbon::parse($value)->toDateString()] = true;
                }
            }
        }

        if ($this->savedReportsByDate === null) {
            $this->savedReportsByDate = [];

            if (Schema::hasTable('mpcs_f15_daily_reports')) {
                $reports = MpcsF15DailyReport::query()
                    ->where('business_id', $businessId)
                    ->where('location_id', $locationId)
                    ->whereDate('report_date', '>=', $from)
                    ->whereDate('report_date', '<=', $to)
                    ->get();

                foreach ($reports as $report) {
                    $this->savedReportsByDate[Carbon::parse($report->report_date)->toDateString()] = $report;
                }
            }
        }
    }

    /**
     * Is this date covered by the pre-loaded window? Outside it, a miss in the
     * map means "not loaded", not "not saved", so the caller must still query.
     */
    private function withinReplayWindow(string $date): bool
    {
        if ($this->replayWindowFrom === null || $this->replayWindowTo === null) {
            return false;
        }

        return $date >= $this->replayWindowFrom && $date <= $this->replayWindowTo;
    }

    private function isF22Date(int $businessId, int $locationId, string $date): bool
    {
        // IS2029: answered from the pre-loaded window when it covers this date.
        if ($this->f22DateSet !== null) {
            $key = Carbon::parse($date)->toDateString();

            if (isset($this->f22DateSet[$key]) || $this->withinReplayWindow($key)) {
                return isset($this->f22DateSet[$key]);
            }
        }

        if (! Schema::hasTable('form_f22_headers')) {
            return false;
        }

        return DB::table('form_f22_headers')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->whereDate('form_date', $date)
            ->exists();
    }

    private function lastF22DateOnOrBefore(int $businessId, int $locationId, string $date): ?string
    {
        // IS2029: called twice per simulated day from openingStockAtSalePrice().
        $cacheKey = $businessId . '|' . $locationId . '|' . $date;

        if (array_key_exists($cacheKey, $this->lastF22DateCache)) {
            return $this->lastF22DateCache[$cacheKey];
        }

        if (! Schema::hasTable('form_f22_headers')) {
            return $this->lastF22DateCache[$cacheKey] = null;
        }

        $value = DB::table('form_f22_headers')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->whereDate('form_date', '<=', $date)
            ->max('form_date');

        return $this->lastF22DateCache[$cacheKey] = $value
            ? Carbon::parse($value)->toDateString()
            : null;
    }

    private function f15OpeningDate(int $businessId): ?string
    {
        if (! Schema::hasTable('mpcs_form_f15_headers')) {
            return null;
        }

        $value = DB::table('mpcs_form_f15_headers')
            ->where('business_id', $businessId)
            ->min('dated_at');

        return $value ? Carbon::parse($value)->toDateString() : null;
    }

    private function nextFormNumber(int $businessId, int $locationId, string $date): string
    {
        $base = 1;
        $openingDate = $this->f15OpeningDate($businessId) ?: $date;

        if (Schema::hasTable('mpcs_form_f15_headers') && Schema::hasTable('mpcs_form_f15_details')) {
            $settingHeader = DB::table('mpcs_form_f15_headers')
                ->where('business_id', $businessId)
                ->whereDate('dated_at', '<=', $date)
                ->orderByDesc('dated_at')
                ->orderByDesc('id')
                ->first();

            if ($settingHeader) {
                $openingDate = Carbon::parse($settingHeader->dated_at)->toDateString();
                $configured = DB::table('mpcs_form_f15_details')
                    ->where('f15_form_id', $settingHeader->id)
                    ->where('form15_label_id', 1)
                    ->value('rupees');
                if (is_numeric($configured)) {
                    $base = (int) $configured;
                }
            }
        }

        $count = 0;
        if (Schema::hasTable('mpcs_f15_daily_reports')) {
            $count = MpcsF15DailyReport::query()
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->whereDate('report_date', '>=', $openingDate)
                ->whereDate('report_date', '<', $date)
                ->count();
        }

        return (string) ($base + $count);
    }

    private function number($value): float
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', trim($value));
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function nullableText($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function nullableDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function dateString($value): string
    {
        return $value ? Carbon::parse($value)->toDateString() : '';
    }
}
