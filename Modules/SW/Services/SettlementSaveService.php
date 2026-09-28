<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\Shift;
use Modules\SW\Entities\Settlement;

/**
 * Saves a settlement and marks its shifts settled.
 *
 * The settlement is AUTHORITATIVE. It holds its own figures, and the daily
 * entries behind it are never altered - a shift's records are fixed once it
 * closes, and a difference between recorded and settled is something to see,
 * not something to erase.
 *
 * Every change is logged with both the settlement number and the shift numbers
 * it covers, so "why is this different from what the operator handed over" has
 * an answer.
 */
class SettlementSaveService
{
    public function __construct(
        protected NumberService $numbers,
        protected LogService $logs,
        protected SettlementFinancePostingService $financePosting
    ) {
    }

    public function create(int $businessId, array $data): int
    {
        $shiftIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) ($data['shift_ids'] ?? [])
        ))));
        $locationId = (int) ($data['location_id'] ?? 0);
        $operatorId = (int) ($data['pump_operator_id'] ?? 0);

        /*
         | S731/S730: normalise discounted sale amounts on the server too.
         |
         | A cached browser can still submit the older fixed-discount formula,
         | where the entered fixed value was multiplied by quantity. Recalculate
         | the authoritative values here so saved Meter Sales / Other Sales and
         | the balance test cannot inherit that stale client-side error.
         */
        $data = $this->normaliseDiscountedSaleAmounts($data);

        /*
         | S732: Daily Shift Cash is authoritative. Settlement consumes CLOSED
         | shifts, so a browser must never be able to add/remove/alter their cash
         | after closure. Replace any submitted Cash rows with the live Daily Cash
         | rows for the selected shifts/operator before the balance test and save.
         */
        $data = $this->replaceWithAuthoritativeDailyCash(
            $businessId,
            $operatorId,
            $shiftIds,
            $data
        );

        $this->assertBalancedSettlementPayload($data);

        /*
         | S753: never run DDL from the interactive Save Settlement request.
         |
         | The former preflight called ALTER TABLE for older tenant schemas.
         | ALTER requires a metadata lock and can wait behind ordinary queries,
         | leaving the browser at "Saving..." even though no settlement has
         | committed. Schema repair belongs to the supplied migrations.
         |
         | Runtime compatibility remains safe without DDL: the header is filtered
         | to live columns below, the legacy mandatory sw_shift_id is populated
         | from the already-validated first shift, and optional detail columns are
         | filtered by compatibleInsertRow().
        */

        return DB::transaction(function () use ($businessId, $data, $shiftIds, $locationId, $operatorId) {

            /*
             | Verify the ACTIVE TENANT database, business, location, operator
             | and shifts all agree before a settlement row is written. Numeric
             | business IDs repeat between tenant databases, so checking only
             | `business_id = 3` is not enough unless the current DB is also the
             | correct tenant DB.
            */
            $this->assertSettlementContext(
                $businessId,
                $locationId,
                $operatorId,
                $shiftIds
            );

            // Do not trust a cached/hand-crafted Meter Sale payload. The pumps
            // being settled must belong to this business/location and, when the
            // Pump Operator has an assigned pump, to that exact operator. The
            // operator itself has already been proved against every selected SW
            // Shift by assertSettlementContext().
            $this->assertMeterLinesBelongToShiftContext(
                $businessId,
                $locationId,
                $operatorId,
                $shiftIds,
                (array) ($data['lines'] ?? [])
            );

            $settlementNo = $this->numbers->next($businessId, $locationId, 'settlement');

            /*
             | Build the settlement header against the schema that is actually
             | present in this tenant.  Some older tenant databases already had
             | `sw_settlements` before pump_operator_id was introduced, so the
             | original migration is marked as run and Laravel never revisits
             | that CREATE TABLE block.  Writing the newer column unconditionally
             | then raises SQLSTATE[42S22] and the whole save fails.
             |
             | The repair migration shipped with IS2205 adds the missing column,
             | but this guard keeps Save working safely even before that migration
             | is applied.  The selected operator is still recoverable from the
             | settlement's linked SW shifts in that compatibility case.
            */
            $header = [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'settlement_no' => $settlementNo,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'finish_date' => $data['finish_date'] ?? null,
                'note' => $data['note'] ?? null,

                'total_meter_sales' => $data['total_meter_sales'] ?? 0,
                'total_other_sales' => $data['total_other_sales'] ?? 0,
                'total_other_income' => $data['total_other_income'] ?? 0,
                'total_credit_sales' => $data['total_credit_sales'] ?? 0,
                // These three are authoritative caches of the detail tables.
                // They are recomputed again after all lines/payments are stored.
                'total_sales' => 0,
                'total_collected' => 0,
                'variance' => 0,

                // sw_settlements.status is numeric: 0 draft, 1 open,
                // 2 settled/final, 3 void.
                'status' => Settlement::STATUS_SETTLED,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $existingHeaderColumns = $this->currentTableColumns('sw_settlements');

            if (isset($existingHeaderColumns['pump_operator_id'])) {
                $header['pump_operator_id'] = $operatorId > 0
                    ? $operatorId
                    : null;
            }

            /*
             | Legacy settlement headers used one mandatory sw_shift_id with a
             | foreign key to sw_shifts.id.  The current SW design supports more
             | than one shift through sw_settlement_shifts and therefore no
             | longer needs that header column.  Older tenant databases can still
             | have the NOT NULL/FK column, though; omitting it lets MySQL apply
             | the old default (commonly 0) and the insert then fails FK 1452.
             |
             | Keep the modern join table authoritative.  When this legacy column
             | is physically present, store the first already-validated selected
             | shift only as a compatibility anchor.  All selected shifts are
             | still written to sw_settlement_shifts below.
            */
            /*
             | IMPORTANT: filter with SHOW COLUMNS on the SAME connection used by
             | DB::table below.  Some tenant installations switch the `mysql`
             | database dynamically; Schema facade metadata can otherwise be read
             | from a different/stale connection during impersonation or tenant
             | switching.  The live SQL in IS2205 proved the old safeguard was not
             | filtering the row that actually reached MySQL.
            */
            if ($existingHeaderColumns) {
                $header = array_intersect_key($header, $existingHeaderColumns);
            } else {
                // Fail safe: if metadata lookup itself is unavailable, keep only
                // the long-standing base columns. Optional totals are represented
                // by their detail rows and must never make Save crash.
                $header = array_intersect_key($header, array_flip([
                    'business_id', 'location_id', 'settlement_no',
                    'transaction_date', 'finish_date', 'note', 'status',
                    'created_by', 'created_at', 'updated_at',
                ]));
            }

            /*
             | FINAL legacy-FK guard.
             |
             | Some old tenant databases expose sw_shift_id through the actual
             | table/FK but it has not been returned reliably by the generic
             | column-list path during dynamic tenant switching.  If that happens,
             | array_intersect_key() above removes sw_shift_id again and MySQL
             | inserts the legacy default (usually 0), which fails the foreign key
             | to sw_shifts.id with SQLSTATE[23000]/1452.
             |
             | Check this one compatibility column independently against
             | INFORMATION_SCHEMA on DATABASE() - the exact database used by this
             | INSERT - and append it AFTER optional-column filtering.  The shift
             | id is safe because assertShiftsSettleable() has already proved it
             | exists in sw_shifts for this same business.
            */
            $legacyShiftColumn = $this->legacyShiftColumnInfo();
            if ($legacyShiftColumn) {
                // Always supply a valid compatibility anchor when the obsolete
                // column exists. Some old schemas made it nullable but retained
                // DEFAULT 0; omitting it can therefore still fail the legacy FK.
                // The join table remains authoritative for all selected shifts.
                $header['sw_shift_id'] = (int) $shiftIds[0];
            }

            $settlementId = DB::table('sw_settlements')->insertGetId($header);

            foreach ($shiftIds as $shiftId) {
                DB::table('sw_settlement_shifts')->insert([
                    'settlement_id' => $settlementId,
                    'sw_shift_id' => $shiftId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->saveMeterLines($settlementId, $data['lines'] ?? []);

            /*
             | The other four sections.
             |
             | Until now only meter lines were stored - a settlement saved with
             | other sales, other income, credit sales or payments lost them
             | silently. That is worse than a section not existing, because the
             | user watched it work.
            */
            $this->saveOtherSales($settlementId, $data['other_sales'] ?? []);
            $this->saveOtherIncome($settlementId, $data['other_income'] ?? []);
            $this->saveCreditSales(
                $settlementId,
                $operatorId,
                $data['credit_sales'] ?? []
            );
            $this->savePayments($settlementId, $data['payments'] ?? []);

            /*
             | IS2211: the detail rows are the source of truth. The list page
             | used to show 0.00 because total_sales / total_collected were never
             | refreshed after those rows were inserted. Rebuild the caches now,
             | then post this FINAL settlement into the application's standard
             | Finance ledger. Both happen inside the same DB transaction.
            */
            $authoritativeTotals = $this->refreshSettlementTotals($settlementId);
            $this->financePosting->post($settlementId);

            /*
             | The shifts become SETTLED, not merely closed.
             |
             | That is what stops them appearing on another settlement, and what
             | the daily tabs check before refusing an edit.
            */
            DB::table('sw_shifts')
                ->whereIn('id', $shiftIds)
                ->update([
                    'status' => Shift::storageStatusValue('settled'),
                    'updated_at' => now(),
                ]);

            $shiftNumbers = DB::table('sw_shifts')
                ->whereIn('id', $shiftIds)
                ->pluck('sw_shift_no')
                ->implode(', ');

            $this->logs->record(
                'settlement', $settlementId, $settlementNo, 'created',
                [],
                [
                    'shifts' => $shiftNumbers,
                    'meter_sales' => number_format((float) ($authoritativeTotals['total_meter_sales'] ?? 0), 2),
                    'other_sales' => number_format((float) ($authoritativeTotals['total_other_sales'] ?? 0), 2),
                    'other_income' => number_format((float) ($authoritativeTotals['total_other_income'] ?? 0), 2),
                    'credit_sales' => number_format((float) ($authoritativeTotals['total_credit_sales'] ?? 0), 2),
                    'sales' => number_format((float) ($authoritativeTotals['total_sales'] ?? 0), 2),
                    'collected' => number_format((float) ($authoritativeTotals['total_collected'] ?? 0), 2),
                ],
                [
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'document_status' => 'Final',
                    'note' => 'Shifts settled: ' . $shiftNumbers,
                ]
            );

            return $settlementId;
        });
    }

    /**
     * Recalculate line amounts from quantity, rate and discount semantics.
     *
     * Fixed discount = one fixed amount for the line.
     * Percentage discount = percentage of the line value before discount.
     */
    protected function normaliseDiscountedSaleAmounts(array $data): array
    {
        foreach (['lines', 'other_sales'] as $section) {
            $rows = (array) ($data[$section] ?? []);

            foreach ($rows as $index => $row) {
                $quantity = max(0, (float) ($row['quantity'] ?? 0));
                $rate = max(0, (float) ($row['rate'] ?? 0));
                $before = round($quantity * $rate, 4);
                $discountType = strtolower(trim((string) ($row['discount_type'] ?? 'fixed')));
                $discountValue = max(0, (float) ($row['discount_value'] ?? 0));

                if ($discountType === 'percentage') {
                    $discount = $before * ($discountValue / 100);
                } else {
                    // Fixed is a single amount for the complete line, not per qty.
                    $discount = $discountValue;
                    $discountType = 'fixed';
                }

                $after = round($before - $discount, 4);

                $rows[$index]['discount_type'] = $discountType;
                $rows[$index]['discount_value'] = $discountValue;
                $rows[$index]['amount_before_discount'] = $before;
                $rows[$index]['amount'] = $after;
            }

            $data[$section] = $rows;
        }

        $data['total_meter_sales'] = round(array_sum(array_map(
            fn ($row) => (float) ($row['amount'] ?? 0),
            (array) ($data['lines'] ?? [])
        )), 4);

        $data['total_other_sales'] = round(array_sum(array_map(
            fn ($row) => (float) ($row['amount'] ?? 0),
            (array) ($data['other_sales'] ?? [])
        )), 4);

        $data['total_other_income'] = round(array_sum(array_map(
            fn ($row) => (float) ($row['amount'] ?? 0),
            (array) ($data['other_income'] ?? [])
        )), 4);

        $data['total_credit_sales'] = round(array_sum(array_map(
            fn ($row) => (float) ($row['amount'] ?? 0),
            (array) ($data['credit_sales'] ?? [])
        )), 4);

        return $data;
    }

    /**
     * Replace submitted Cash rows with the authoritative Daily Shift Cash rows.
     *
     * Cash may be edited/removed/added only on SW Payments -> Daily Cash while
     * the shift is OPEN. By the time a shift reaches Settlement it is CLOSED,
     * therefore its cash is immutable and must be read from sw_daily_cash rather
     * than trusted from browser hidden inputs or an old cached page.
     */
    protected function replaceWithAuthoritativeDailyCash(
        int $businessId,
        int $operatorId,
        array $shiftIds,
        array $data
    ): array {
        $payments = array_values(array_filter(
            (array) ($data['payments'] ?? []),
            static fn ($row): bool => strtolower(trim((string) ($row['payment_method'] ?? ''))) !== 'cash'
        ));

        if ($businessId <= 0 || $operatorId <= 0 || empty($shiftIds)) {
            $data['payments'] = $payments;
            return $data;
        }

        $cashColumns = $this->currentTableColumns('sw_daily_cash');
        $shiftColumns = $this->currentTableColumns('sw_shifts');
        if (! $cashColumns || ! $shiftColumns
            || ! isset($cashColumns['sw_shift_id'])
            || ! isset($cashColumns['pump_operator_id'])
            || ! isset($shiftColumns['id'])
            || ! isset($shiftColumns['business_id'])) {
            $data['payments'] = $payments;
            return $data;
        }

        $amountColumn = isset($cashColumns['current_amount'])
            ? 'current_amount'
            : (isset($cashColumns['amount']) ? 'amount' : null);
        if (! $amountColumn) {
            $data['payments'] = $payments;
            return $data;
        }

        try {
            $select = [
                'd.id',
                'd.' . $amountColumn . ' as cash_amount',
            ];
            if (isset($cashColumns['collection_form_no'])) {
                $select[] = 'd.collection_form_no';
            }
            if (isset($cashColumns['note'])) {
                $select[] = 'd.note';
            }

            $rows = DB::table('sw_daily_cash as d')
                ->join('sw_shifts as s', 's.id', '=', 'd.sw_shift_id')
                ->where('s.business_id', $businessId)
                ->whereIn('d.sw_shift_id', $shiftIds)
                ->where('d.pump_operator_id', $operatorId)
                ->orderBy('d.id')
                ->get($select);

            foreach ($rows as $row) {
                $amount = round((float) ($row->cash_amount ?? 0), 4);
                if ($amount <= 0) {
                    continue;
                }

                $payments[] = [
                    'payment_method' => 'cash',
                    'account_id' => null,
                    'contact_id' => null,
                    'amount' => $amount,
                    'reference' => (string) ($row->collection_form_no ?? ''),
                    'note' => (string) ($row->note ?? ''),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('SW settlement could not reload authoritative Daily Cash.', [
                'business_id' => $businessId,
                'operator_id' => $operatorId,
                'shift_ids' => $shiftIds,
                'message' => $e->getMessage(),
            ]);
        }

        $data['payments'] = array_values($payments);
        return $data;
    }

    /**
     * Refuse a final settlement while Total Amount and Total Paid do not balance.
     * This mirrors the browser rule and protects direct/cached submissions.
     */
    protected function assertBalancedSettlementPayload(array $data): void
    {
        $sales = round(
            (float) ($data['total_meter_sales'] ?? 0)
            + (float) ($data['total_other_sales'] ?? 0)
            + (float) ($data['total_other_income'] ?? 0),
            4
        );

        $creditSales = round((float) ($data['total_credit_sales'] ?? 0), 4);
        $hasDetailedCreditSales = abs($creditSales) >= 0.00005;
        $paid = 0.0;
        $countedPaymentTotals = [];

        foreach ((array) ($data['payments'] ?? []) as $payment) {
            $method = strtolower(trim((string) ($payment['payment_method'] ?? '')));
            $amount = round((float) ($payment['amount'] ?? 0), 4);

            // Detailed Credit Sales are authoritative; do not double-count a
            // stale generic credit_sale payment submitted by an older cache.
            if ($method === 'credit_sale' && $hasDetailedCreditSales) {
                continue;
            }

            $countedAmount = 0.0;
            if ($method === 'excess') {
                if (abs($amount) >= 0.00005) {
                    $countedAmount = $amount;
                }
            } elseif ($amount > 0) {
                $countedAmount = $amount;
            }

            if (abs($countedAmount) >= 0.00005) {
                $paid += $countedAmount;
                $paymentKey = $method !== '' ? $method : 'unknown';
                $countedPaymentTotals[$paymentKey] = round(
                    (float) ($countedPaymentTotals[$paymentKey] ?? 0) + $countedAmount,
                    4
                );
            }
        }

        $paid = round($paid + $creditSales, 4);
        $balance = round($sales - $paid, 4);

        // The screen is two-decimal currency. A sub-half-cent remainder displays
        // as 0.00, so use the same tolerance on the server.
        if (abs($balance) >= 0.005) {
            // Numeric aggregates only: enough to diagnose a client/server
            // calculation mismatch without logging customer or account data.
            Log::warning('SW settlement payload balance mismatch.', [
                'meter_sales' => round((float) ($data['total_meter_sales'] ?? 0), 4),
                'other_sales' => round((float) ($data['total_other_sales'] ?? 0), 4),
                'other_income' => round((float) ($data['total_other_income'] ?? 0), 4),
                'credit_sales' => $creditSales,
                'payment_totals' => $countedPaymentTotals,
                'sales' => $sales,
                'paid' => $paid,
                'balance' => $balance,
                'row_counts' => [
                    'meter_sales' => count((array) ($data['lines'] ?? [])),
                    'other_sales' => count((array) ($data['other_sales'] ?? [])),
                    'other_income' => count((array) ($data['other_income'] ?? [])),
                    'credit_sales' => count((array) ($data['credit_sales'] ?? [])),
                    'payments' => count((array) ($data['payments'] ?? [])),
                ],
            ]);

            throw new \RuntimeException(__('sw::lang.settlement_balance_must_be_zero'));
        }
    }

    /**
     * Return the live column map from the same database connection used for the
     * settlement INSERT. Keys are column names for fast isset() checks.
     */
    protected function currentTableColumns(string $table): array
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return [];
        }

        try {
            $rows = DB::select('SHOW COLUMNS FROM `' . $table . '`');
            $columns = [];

            foreach ($rows as $row) {
                $name = $row->Field ?? $row->field ?? null;
                if ($name) {
                    $columns[(string) $name] = true;
                }
            }

            if ($columns) {
                return $columns;
            }
        } catch (\Throwable $e) {
            // Fall through to Schema as a secondary compatibility path.
        }

        try {
            return array_fill_keys(Schema::getColumnListing($table), true);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Check a column against INFORMATION_SCHEMA for the database that is live on
     * this connection right now.  This is intentionally separate from
     * currentTableColumns(): legacy mandatory/FK columns must never be lost to a
     * stale metadata list during tenant switching.
     */
    protected function liveTableHasColumn(string $table, string $column): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)
            || ! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }

        try {
            $row = DB::selectOne(
                'SELECT COUNT(*) AS aggregate
                   FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?',
                [$table, $column]
            );

            if ((int) ($row->aggregate ?? 0) > 0) {
                return true;
            }
        } catch (\Throwable $e) {
            // Continue to the same-connection SHOW COLUMNS fallback below.
        }

        try {
            $quotedTable = '`' . str_replace('`', '``', $table) . '`';
            $rows = DB::select("SHOW COLUMNS FROM {$quotedTable} LIKE '{$column}'");

            return ! empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Return the live database selected by tenant.context on this request. */
    protected function currentDatabaseName(): ?string
    {
        try {
            $row = DB::selectOne('SELECT DATABASE() AS db_name');

            return ! empty($row->db_name) ? (string) $row->db_name : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Read the obsolete single-shift header column directly from MySQL.
     * SHOW COLUMNS is intentionally used on the active/default connection so
     * this metadata cannot come from the central DB while the INSERT goes to a
     * tenant DB.
     */
    protected function legacyShiftColumnInfo(): ?object
    {
        try {
            $rows = DB::select("SHOW COLUMNS FROM `sw_settlements` LIKE 'sw_shift_id'");

            return $rows[0] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The current SW schema is multi-shift through sw_settlement_shifts. Older
     * tenant DBs can still have sw_settlements.sw_shift_id as NOT NULL with a
     * foreign key. Keeping that old column mandatory makes a modern settlement
     * fail before the authoritative join rows are written. Preserve the FK and
     * column, but make it nullable/default NULL.
     *
     * If ALTER is not permitted, create() falls back to writing the first
     * validated selected shift into the legacy field.
     */
    protected function ensureLegacyShiftHeaderCompatibility(): void
    {
        $column = $this->legacyShiftColumnInfo();
        if (! $column) {
            return;
        }

        $nullable = strtoupper((string) ($column->Null ?? $column->null ?? 'NO')) === 'YES';
        $default = $column->Default ?? $column->default ?? null;

        if ($nullable && $default === null) {
            return;
        }

        $type = (string) ($column->Type ?? $column->type ?? '');
        if ($type === '' || ! preg_match('/^[A-Za-z0-9(), ]+$/', $type)) {
            Log::warning('SW: could not normalise legacy sw_shift_id because its type was unexpected.', [
                'database' => $this->currentDatabaseName(),
                'column_type' => $type,
            ]);

            return;
        }

        try {
            DB::statement(
                'ALTER TABLE `sw_settlements` MODIFY COLUMN `sw_shift_id` ' . $type . ' NULL DEFAULT NULL'
            );
        } catch (\Throwable $e) {
            Log::warning('SW: legacy sw_shift_id could not be made nullable; Save will use a validated compatibility shift.', [
                'database' => $this->currentDatabaseName(),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify tenant + business + location + operator + shift ownership together.
     * Business IDs are only unique inside one tenant DB, so every settlement
     * save must establish the active database before trusting the numeric id.
     */
    protected function assertSettlementContext(
        int $businessId,
        int $locationId,
        int $operatorId,
        array $shiftIds
    ): void {
        $database = $this->currentDatabaseName();

        if ($businessId <= 0 || $locationId <= 0 || empty($shiftIds)) {
            abort(422, 'The SW settlement is missing its tenant business, location or shift context.');
        }

        if (Schema::hasTable('business')
            && ! DB::table('business')->where('id', $businessId)->exists()) {
            Log::error('SW settlement tenant/business mismatch.', [
                'database' => $database,
                'business_id' => $businessId,
                'location_id' => $locationId,
                'shift_ids' => $shiftIds,
                'auth_user_id' => auth()->id(),
                'auth_business_id' => optional(auth()->user())->business_id,
                'session_business_id' => session('business.id'),
            ]);

            abort(422, 'The selected business does not belong to the active tenant database.');
        }

        if (Schema::hasTable('business_locations')
            && ! DB::table('business_locations')
                ->where('id', $locationId)
                ->where('business_id', $businessId)
                ->exists()) {
            Log::error('SW settlement location/business mismatch.', [
                'database' => $database,
                'business_id' => $businessId,
                'location_id' => $locationId,
                'shift_ids' => $shiftIds,
            ]);

            abort(422, 'The selected location does not belong to the active SW business.');
        }

        if ($operatorId > 0
            && Schema::hasTable('pump_operators')
            && Schema::hasColumn('pump_operators', 'business_id')
            && ! DB::table('pump_operators')
                ->where('id', $operatorId)
                ->where('business_id', $businessId)
                ->exists()) {
            Log::error('SW settlement operator/business mismatch.', [
                'database' => $database,
                'business_id' => $businessId,
                'pump_operator_id' => $operatorId,
            ]);

            abort(422, 'The selected pump operator does not belong to the active SW business.');
        }

        $this->assertShiftsSettleable($businessId, $locationId, $shiftIds);

        // The selected operator must be linked to EVERY selected SW Shift. The
        // browser already filters this way; repeat it here so a stale URL/form
        // cannot settle somebody else's shift merely because IDs are valid.
        if ($operatorId <= 0 || ! Schema::hasTable('sw_shift_operators')) {
            abort(422, 'The selected pump operator is not linked to the selected SW shift(s).');
        }

        $linkedCount = DB::table('sw_shift_operators')
            ->where('pump_operator_id', $operatorId)
            ->whereIn('sw_shift_id', $shiftIds)
            ->distinct()
            ->count('sw_shift_id');

        abort_if(
            $linkedCount !== count($shiftIds),
            422,
            'The selected pump operator is not linked to every selected SW shift.'
        );
    }

    /**
     * Meter rows are accepted only from pumps that belong to the selected
     * business/location. When the operator has an assigned pump that assignment
     * is authoritative. There is no SW shift-to-pump join table, so operator +
     * assigned pump + exact SW shift linkage is the strongest valid authority.
     */
    protected function assertMeterLinesBelongToShiftContext(
        int $businessId,
        int $locationId,
        int $operatorId,
        array $shiftIds,
        array $lines
    ): void {
        $pumpIds = array_values(array_unique(array_filter(array_map(
            'intval',
            array_column($lines, 'pump_id')
        ))));

        if (empty($pumpIds)) {
            return;
        }

        abort_unless(Schema::hasTable('pumps'), 422, 'The selected pump(s) are unavailable.');

        $query = DB::table('pumps')->whereIn('pumps.id', $pumpIds);
        if (Schema::hasColumn('pumps', 'business_id')) {
            $query->where('pumps.business_id', $businessId);
        } elseif (Schema::hasColumn('pumps', 'product_id')
            && Schema::hasTable('products') && Schema::hasColumn('products', 'business_id')) {
            $query->join('products', 'products.id', '=', 'pumps.product_id')
                ->where('products.business_id', $businessId);
        } else {
            abort(422, 'The selected Meter Sale pumps cannot be verified against the active SW business.');
        }
        if (Schema::hasColumn('pumps', 'location_id')) {
            $query->where('pumps.location_id', $locationId);
        }
        if (Schema::hasColumn('pumps', 'deleted_at')) {
            $query->whereNull('pumps.deleted_at');
        }
        if (Schema::hasColumn('pumps', 'is_active')) {
            $query->where('pumps.is_active', 1);
        }

        abort_if(
            $query->distinct()->count('pumps.id') !== count($pumpIds),
            422,
            'One or more Meter Sale pumps do not belong to the selected SW business/location.'
        );

        if ($operatorId > 0 && Schema::hasTable('pump_operators')
            && Schema::hasColumn('pump_operators', 'assigned_pump_id')) {
            $operatorQuery = DB::table('pump_operators')->where('id', $operatorId);
            if (Schema::hasColumn('pump_operators', 'business_id')) {
                $operatorQuery->where('business_id', $businessId);
            }
            if (Schema::hasColumn('pump_operators', 'location_id')) {
                $operatorQuery->where('location_id', $locationId);
            }

            $assignedPumpId = (int) ($operatorQuery->value('assigned_pump_id') ?? 0);
            if ($assignedPumpId > 0) {
                abort_if(
                    count($pumpIds) !== 1 || (int) $pumpIds[0] !== $assignedPumpId,
                    422,
                    'The Meter Sale pump does not match the pump assigned to this operator.'
                );
            }
        }
    }

    /**
     * Self-heal the four current settlement section totals on an older tenant.
     * This runs before DB::transaction() because ALTER TABLE causes an implicit
     * commit in MySQL.  Any DDL failure is intentionally non-fatal: create()
     * subsequently filters optional fields against the actual live schema, so
     * the settlement itself can still be saved safely.
     */
    protected function ensureSettlementHeaderCompatibility(): void
    {
        $columns = $this->currentTableColumns('sw_settlements');
        if (! $columns) {
            return;
        }

        $definitions = [
            'total_meter_sales' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
            'total_other_sales' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
            'total_other_income' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
            'total_credit_sales' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
            'total_sales' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
            'total_collected' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
            'variance' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
        ];

        foreach ($definitions as $column => $definition) {
            if (isset($columns[$column])) {
                continue;
            }

            try {
                DB::statement(
                    'ALTER TABLE `sw_settlements` ADD COLUMN `' . $column . '` ' . $definition
                );
                $columns[$column] = true;
            } catch (\Throwable $e) {
                // Do not convert a schema-maintenance problem into a failed
                // settlement. The insert below will omit this optional column.
            }
        }
    }


    /**
     * IS2208: repair discount columns added after some tenant databases had
     * already created their SW settlement detail tables.
     *
     * The original CREATE migration cannot update an already-created table,
     * which is why those tenants raised SQLSTATE[42S22] for discount_type when
     * Save Settlement inserted the meter line. Run DDL before the transaction
     * (MySQL ALTER TABLE implicitly commits) and keep the save path compatible
     * if the DB user is not allowed to ALTER.
     */
    protected function ensureSettlementDetailCompatibility(): void
    {
        $tables = [
            'sw_settlement_lines' => [
                'discount_type' => 'VARCHAR(20) NULL',
                'discount_value' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
                'amount_before_discount' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
            ],
            'sw_other_sales' => [
                'discount_type' => 'VARCHAR(20) NULL',
                'discount_value' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
                'amount_before_discount' => 'DECIMAL(22,4) NOT NULL DEFAULT 0',
            ],
        ];

        foreach ($tables as $table => $definitions) {
            $columns = $this->currentTableColumns($table);
            if (! $columns) {
                continue;
            }

            foreach ($definitions as $column => $definition) {
                if (isset($columns[$column])) {
                    continue;
                }

                try {
                    DB::statement(
                        'ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition
                    );
                    $columns[$column] = true;
                } catch (\Throwable $e) {
                    Log::warning('SW: settlement detail compatibility column could not be added.', [
                        'database' => $this->currentDatabaseName(),
                        'table' => $table,
                        'column' => $column,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Filter only optional/new fields against the live tenant table.
     *
     * This is the second safety net for IS2208. If an older tenant DB user does
     * not have ALTER privilege, the settlement still saves using the columns it
     * actually has. The already-calculated final amount remains authoritative;
     * a warning records which audit columns could not be stored.
     */
    protected function compatibleInsertRow(string $table, array $row, array $optionalColumns): array
    {
        $columns = $this->currentTableColumns($table);
        if (! $columns) {
            // Let the real INSERT surface a genuinely missing table instead of
            // silently discarding the row.
            return $row;
        }

        $unknown = array_values(array_diff(array_keys($row), array_keys($columns)));
        if (! $unknown) {
            return $row;
        }

        $nonOptional = array_values(array_diff($unknown, $optionalColumns));
        if ($nonOptional) {
            // Do not hide structural corruption. Only the known IS2208 columns
            // are compatibility-optional; missing core fields must still fail
            // loudly so the tenant schema can be repaired correctly.
            Log::error('SW: settlement detail table is missing required columns.', [
                'database' => $this->currentDatabaseName(),
                'table' => $table,
                'missing_columns' => $nonOptional,
            ]);

            return $row;
        }

        static $logged = [];
        $signature = $table . ':' . implode(',', $unknown);

        if (! isset($logged[$signature])) {
            $logged[$signature] = true;
            Log::warning('SW: older settlement detail schema; compatibility columns omitted from insert.', [
                'database' => $this->currentDatabaseName(),
                'table' => $table,
                'omitted_columns' => $unknown,
            ]);
        }

        return array_intersect_key($row, $columns);
    }

    /** Other Sales - 8044. */
    protected function saveOtherSales(int $settlementId, array $rows): void
    {
        foreach ($rows as $r) {
            if (empty($r['product_id']) || (float) ($r['quantity'] ?? 0) <= 0) {
                continue;
            }

            $row = [
                'settlement_id' => $settlementId,
                'store_id' => $r['store_id'] ?? null,
                'product_id' => $r['product_id'],
                'variation_id' => $r['variation_id'] ?? null,

                // What the stock showed AT THE TIME. Read later it would give
                // today's figure, which says nothing about the sale.
                'balance_stock' => $r['balance_stock'] ?? 0,

                'quantity' => $r['quantity'] ?? 0,
                'rate' => $r['rate'] ?? 0,
                'discount_type' => $r['discount_type'] ?? 'fixed',
                'discount_value' => $r['discount_value'] ?? 0,
                'amount_before_discount' => $r['amount_before_discount'] ?? 0,
                'amount' => $r['amount'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('sw_other_sales')->insert(
                $this->compatibleInsertRow('sw_other_sales', $row, [
                    'discount_type', 'discount_value', 'amount_before_discount',
                ])
            );
        }
    }

    /** Other Income - 8045. */
    protected function saveOtherIncome(int $settlementId, array $rows): void
    {
        foreach ($rows as $r) {
            if (empty($r['product_id']) || (float) ($r['quantity'] ?? 0) <= 0) {
                continue;
            }

            $edited = ! empty($r['price_edited']);

            DB::table('sw_other_income')->insert([
                'settlement_id' => $settlementId,
                'product_id' => $r['product_id'],
                'details' => $r['details'] ?? null,
                'quantity' => $r['quantity'] ?? 0,
                'rate' => $r['rate'] ?? 0,
                'amount' => $r['amount'] ?? 0,

                /*
                 | WHO changed a price, not merely that one changed.
                 |
                 | 8045 restricts the edit to a permitted user. Recording only
                 | the flag would leave no way to ask them about it.
                */
                'price_edited' => $edited ? 1 : 0,
                'price_edited_by' => $edited ? auth()->id() : null,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** Credit Sales - 8047. */
    protected function saveCreditSales(int $settlementId, int $operatorId, array $rows): void
    {
        foreach ($rows as $r) {
            if (empty($r['contact_id']) || (float) ($r['amount'] ?? 0) == 0.0) {
                continue;
            }

            $row = [
                'settlement_id' => $settlementId,
                'daily_credit_sale_id' => ! empty($r['daily_credit_sale_id'])
                    ? (int) $r['daily_credit_sale_id'] : null,
                'contact_id' => $r['contact_id'],
                'vehicle_no' => $r['vehicle_no'] ?? null,
                'amount' => $r['amount'] ?? 0,
                'reference' => $r['order_no'] ?? null,
                'note' => $r['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('sw_settlement_credit_sales', 'pump_operator_id')) {
                $row['pump_operator_id'] = $operatorId ?: null;
            }

            /*
             | The product columns are written only where they exist.
             |
             | They come from 71-292's migration, and a tenant that has not run
             | it yet must still be able to settle - a missing column should not
             | stop money being recorded.
            */
            foreach ([
                'order_no', 'order_date', 'product_id', 'variation_id',
                'quantity', 'unit_price', 'unit_discount', 'amount_before_discount',
            ] as $col) {
                if (Schema::hasColumn('sw_settlement_credit_sales', $col)) {
                    $row[$col] = $r[$col] ?? null;
                }
            }

            DB::table('sw_settlement_credit_sales')->insert($row);
        }
    }

    /** Payments - 8048. */
    /**
     * Rebuild the settlement header totals from the rows just saved.
     *
     * Never trust browser totals for accounting.  This also works on older
     * tenants where one optional cache column could not be ALTERed: only columns
     * physically present in the live sw_settlements table are updated.
     */
    protected function refreshSettlementTotals(int $settlementId): array
    {
        $sum = function (string $table) use ($settlementId): float {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'settlement_id')
                || ! Schema::hasColumn($table, 'amount')) {
                return 0.0;
            }

            return round((float) DB::table($table)
                ->where('settlement_id', $settlementId)
                ->sum('amount'), 4);
        };

        $meter = $sum('sw_settlement_lines');
        $otherSales = $sum('sw_other_sales');
        $otherIncome = $sum('sw_other_income');
        $creditSales = $sum('sw_settlement_credit_sales');

        /*
         | IS2245: Credit Sales are a PAYMENT allocation to Accounts Receivable.
         | They belong in Total Paid / total_collected, but not in Total Sales.
         |
         | Older builds could also write a generic sw_collections row with
         | payment_method=credit_sale. Exclude that legacy duplicate before
         | adding the authoritative detailed credit-sale total.
         */
        $collectionBase = 0.0;
        if (Schema::hasTable('sw_collections')
            && Schema::hasColumn('sw_collections', 'settlement_id')
            && Schema::hasColumn('sw_collections', 'amount')) {
            $collectionQuery = DB::table('sw_collections')
                ->where('settlement_id', $settlementId);

            if (Schema::hasColumn('sw_collections', 'payment_method')) {
                $collectionQuery->where(function ($q) {
                    $q->whereNull('payment_method')
                      ->orWhereRaw("LOWER(TRIM(payment_method)) <> 'credit_sale'");
                });
            }

            $collectionBase = round((float) $collectionQuery->sum('amount'), 4);
        }

        $collected = round($collectionBase + $creditSales, 4);

        /*
         | IS2240: Credit Sales are already contained in the underlying sale
         | value and are collected as an Accounts Receivable allocation. Adding
         | them here doubles Total Sales and the settlement variance.
        */
        $sales = round($meter + $otherSales + $otherIncome, 4);

        $totals = [
            'total_meter_sales' => $meter,
            'total_other_sales' => $otherSales,
            'total_other_income' => $otherIncome,
            'total_credit_sales' => $creditSales,
            'total_sales' => $sales,
            'total_collected' => $collected,
            'variance' => round($collected - $sales, 4),
            'updated_at' => now(),
        ];

        $columns = $this->currentTableColumns('sw_settlements');
        if ($columns) {
            DB::table('sw_settlements')
                ->where('id', $settlementId)
                ->update(array_intersect_key($totals, $columns));
        }

        return $totals;
    }

    protected function savePayments(int $settlementId, array $rows): void
    {
        // Detailed Credit Sales are authoritative on the current page. Cached
        // older pages may still submit a generic credit_sale payment row as
        // well; suppress that duplicate only when detailed rows really exist.
        $hasDetailedCreditSales = Schema::hasTable('sw_settlement_credit_sales')
            && Schema::hasColumn('sw_settlement_credit_sales', 'settlement_id')
            && DB::table('sw_settlement_credit_sales')->where('settlement_id', $settlementId)->exists();

        foreach ($rows as $r) {
            $paymentMethod = strtolower(trim((string) ($r['payment_method'] ?? '')));
            $amount = round((float) ($r['amount'] ?? 0), 4);

            // IS2249: Excess can intentionally be negative so an over-paid
            // settlement balance (e.g. -11003.13) returns to zero. Keep every
            // other payment type positive-only for backwards compatibility.
            if ($paymentMethod === ''
                || ($paymentMethod === 'excess' && abs($amount) < 0.00005)
                || ($paymentMethod !== 'excess' && $amount <= 0)) {
                continue;
            }

            if ($paymentMethod === 'credit_sale' && $hasDetailedCreditSales) {
                continue;
            }

            DB::table('sw_collections')->insert([
                'settlement_id' => $settlementId,
                'payment_method' => $r['payment_method'],
                'account_id' => ! empty($r['account_id']) ? (int) $r['account_id'] : null,
                'contact_id' => ! empty($r['contact_id']) ? (int) $r['contact_id'] : null,
                'amount' => $amount,
                'reference' => $r['reference'] ?? null,
                'note' => $r['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Meter sale lines.
     *
     * The rate is stored ON THE LINE, not looked up later. A fuel price changes
     * whenever the tanks are refilled, and a settlement that re-reads today's
     * price would restate what was sold last week.
     */
    protected function saveMeterLines(int $settlementId, array $lines): void
    {
        foreach ($lines as $line) {
            if (empty($line['pump_id']) || (float) ($line['quantity'] ?? 0) <= 0) {
                continue;
            }

            $row = [
                'settlement_id' => $settlementId,
                'pump_id' => (int) $line['pump_id'],
                'product_id' => $line['product_id'] ?? null,
                'opening_meter' => $line['opening_meter'] ?? 0,
                'closing_meter' => $line['closing_meter'] ?? 0,
                'testing_qty' => $line['testing_qty'] ?? 0,
                'quantity' => $line['quantity'] ?? 0,
                'rate' => $line['rate'] ?? 0,
                'discount_type' => $line['discount_type'] ?? 'fixed',
                'discount_value' => $line['discount_value'] ?? 0,
                'amount_before_discount' => $line['amount_before_discount'] ?? 0,
                'amount' => $line['amount'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('sw_settlement_lines')->insert(
                $this->compatibleInsertRow('sw_settlement_lines', $row, [
                    'discount_type', 'discount_value', 'amount_before_discount',
                ])
            );
        }
    }

    protected function assertShiftsSettleable(int $businessId, int $locationId, array $shiftIds): void
    {
        abort_if(empty($shiftIds), 422, __('sw::lang.choose_at_least_one_shift'));

        $shiftColumns = ['id', 'business_id', 'location_id', 'sw_shift_no', 'status'];
        if (Schema::hasColumn('sw_shifts', 'closed_at')) {
            $shiftColumns[] = 'closed_at';
        }

        $shifts = DB::table('sw_shifts')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->whereIn('id', $shiftIds)
            ->get($shiftColumns);

        if ($shifts->count() !== count($shiftIds)) {
            $found = DB::table('sw_shifts')
                ->whereIn('id', $shiftIds)
                ->get(['id', 'business_id', 'location_id', 'sw_shift_no', 'status']);

            Log::error('SW settlement selected shift belongs to another tenant business/location context.', [
                'database' => $this->currentDatabaseName(),
                'expected_business_id' => $businessId,
                'expected_location_id' => $locationId,
                'selected_shift_ids' => $shiftIds,
                'found_shifts' => $found->map(fn ($row) => (array) $row)->all(),
            ]);

            abort(422, 'The selected SW shift does not belong to this tenant business/location.');
        }

        $open = $shifts->filter(function ($shift) {
            return Shift::normalizeStatusValue($shift->status, $shift->closed_at ?? null) === Shift::STATUS_OPEN;
        })->pluck('sw_shift_no');
        abort_if($open->isNotEmpty(), 422,
            __('sw::lang.shift_still_open', ['numbers' => $open->implode(', ')]));

        $settled = $shifts->filter(function ($shift) {
            return Shift::normalizeStatusValue($shift->status, $shift->closed_at ?? null) === Shift::STATUS_SETTLED;
        })->pluck('sw_shift_no');
        abort_if($settled->isNotEmpty(), 422,
            __('sw::lang.shift_already_settled', ['numbers' => $settled->implode(', ')]));
    }
}
