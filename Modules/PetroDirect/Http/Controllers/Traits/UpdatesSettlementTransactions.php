<?php

namespace Modules\PetroDirect\Http\Controllers\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroDirect\Exceptions\SettlementPeriodClosedException;

/**
 * PetroDirect settlement transaction update hook.
 *
 * MA-002 - PREVIOUSLY AN EMPTY STUB.
 * SettlementController::update() called $this->updateSettlementRelatedTransactions()
 * when a settlement's transaction date changed, but the method did not exist.
 * The call sat inside a try/catch that only logged, so the settlement row moved
 * to the new date while every related ledger row stayed on the OLD one, and the
 * operator saw a successful save. Confirmed in production logs:
 *   "Failed to update related transactions for settlement
 *    {"settlement_id":1,"error":"Method ...::updateSettlementRelatedTransactions
 *    does not exist."}"
 *
 * AGREED BEHAVIOUR (MA-002 Q1-Q3):
 *   Q1  Re-date IN PLACE - update the existing rows, do not reverse and re-post.
 *   Q2  All related ledger tables.
 *   Q3  BLOCK the edit when it touches a closed period.
 */
trait UpdatesSettlementTransactions
{
    /**
     * Ledger tables re-dated with a settlement, and the column that carries the
     * settlement's business date in each.
     *
     * SCOPE NOTE - this list is deliberately NOT "every table with a settlement
     * link and a date column". 38 tables match that description, and most of
     * their dates are NOT the settlement's business date: pump_operators.dob,
     * cheque_date, approved_at, resolved_at, sent_at and so on. Re-dating those
     * would corrupt unrelated data. Only columns that represent WHEN THE
     * SETTLEMENT WAS POSTED are moved.
     *
     * transactions and account_transactions both carry petro_settlement_id, so
     * the link is direct. transaction_payments has no settlement column and is
     * reached through its parent transaction.
     */
    protected function settlementLedgerDateColumns(): array
    {
        return [
            'transactions'         => 'transaction_date',
            'account_transactions' => 'operation_date',
        ];
    }

    /**
     * Re-date every ledger row belonging to a settlement.
     *
     * @param  object $settlement           the settlement BEFORE the update
     * @param  string $newTransactionDate   Y-m-d
     * @return array{transactions:int,account_transactions:int,transaction_payments:int}
     *
     * @throws SettlementPeriodClosedException when either date is in a closed period
     * @throws \InvalidArgumentException       when the new date is unusable
     */
    protected function updateSettlementRelatedTransactions($settlement, $newTransactionDate): array
    {
        if (empty($settlement) || empty($settlement->id)) {
            throw new \InvalidArgumentException('Settlement is required to re-date related transactions.');
        }

        $newDate = $this->normaliseSettlementDate($newTransactionDate);
        $oldDate = $this->normaliseSettlementDate($settlement->transaction_date);

        $businessId = (int) ($settlement->business_id ?? 0);
        if ($businessId <= 0) {
            throw new \InvalidArgumentException('Settlement has no business_id; refusing to re-date ledger rows.');
        }

        $this->assertSettlementPeriodOpen($oldDate, $businessId, 'current');
        $this->assertSettlementPeriodOpen($newDate, $businessId, 'new');

        if ($newDate === $oldDate) {
            return ['transactions' => 0, 'account_transactions' => 0, 'transaction_payments' => 0];
        }

        $settlementId = (int) $settlement->id;

        return DB::transaction(function () use ($settlement, $settlementId, $businessId, $newDate) {
            /*
             * IS2188: historical Direct Settlement rows are not uniform. Newer rows carry
             * transactions.petro_settlement_id; older rows can only be reached through
             * invoice_no/is_settlement or the transaction_id saved on a settlement payment.
             * Resolve all three paths before changing dates so an edit cannot leave part of
             * the settlement on the old MPCS date.
             */
            $transactionIds = $this->getDirectSettlementLinkedTransactionIds($settlement, $businessId);

            $counts = [
                'transactions' => 0,
                'account_transactions' => 0,
                'transaction_payments' => 0,
            ];

            if (empty($transactionIds)) {
                return $counts;
            }

            $counts['transactions'] = DB::table('transactions')
                ->where('business_id', $businessId)
                ->whereIn('id', $transactionIds)
                ->update([
                    'transaction_date' => DB::raw(
                        "TIMESTAMP('" . $newDate . "', TIME(`transaction_date`))"
                    ),
                ]);

            if (Schema::hasTable('account_transactions')) {
                $hasAccountTransactionId = Schema::hasColumn('account_transactions', 'transaction_id');
                $hasAccountSettlementId = Schema::hasColumn('account_transactions', 'petro_settlement_id');

                if ($hasAccountTransactionId || $hasAccountSettlementId) {
                    $accountQuery = DB::table('account_transactions')
                        ->where('business_id', $businessId)
                        ->where(function ($query) use (
                            $transactionIds,
                            $settlementId,
                            $hasAccountTransactionId,
                            $hasAccountSettlementId
                        ) {
                            if ($hasAccountTransactionId) {
                                $query->whereIn('transaction_id', $transactionIds);
                            }
                            if ($hasAccountSettlementId) {
                                $method = $hasAccountTransactionId ? 'orWhere' : 'where';
                                $query->{$method}('petro_settlement_id', $settlementId);
                            }
                        });

                    $counts['account_transactions'] = $accountQuery->update([
                        'operation_date' => DB::raw(
                            "TIMESTAMP('" . $newDate . "', TIME(`operation_date`))"
                        ),
                    ]);
                }
            }

            if (Schema::hasTable('transaction_payments')) {
                $paymentQuery = DB::table('transaction_payments')
                    ->whereIn('transaction_id', $transactionIds)
                    ->whereNotNull('paid_on');

                if (Schema::hasColumn('transaction_payments', 'business_id')) {
                    $paymentQuery->where('business_id', $businessId);
                }

                $counts['transaction_payments'] = $paymentQuery->update([
                    'paid_on' => DB::raw(
                        "TIMESTAMP('" . $newDate . "', TIME(`paid_on`))"
                    ),
                ]);
            }

            return $counts;
        });
    }

    /**
     * Resolve every posted transaction that belongs to this Direct Settlement,
     * including legacy rows created before petro_settlement_id was populated.
     */
    protected function getDirectSettlementLinkedTransactionIds($settlement, int $businessId): array
    {
        if (empty($settlement) || empty($settlement->id) || $businessId <= 0 || ! Schema::hasTable('transactions')) {
            return [];
        }

        $settlementId = (int) $settlement->id;
        $settlementNo = trim((string) ($settlement->settlement_no ?? ''));
        $ids = collect();

        $transactionQuery = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where(function ($query) use ($settlementId, $settlementNo) {
                $hasBranch = false;

                if (Schema::hasColumn('transactions', 'petro_settlement_id')) {
                    $query->where('petro_settlement_id', $settlementId);
                    $hasBranch = true;
                }

                if ($settlementNo !== '' && Schema::hasColumn('transactions', 'invoice_no')) {
                    $method = $hasBranch ? 'orWhere' : 'where';
                    $query->{$method}(function ($invoiceQuery) use ($settlementNo) {
                        $invoiceQuery->where('invoice_no', $settlementNo);
                        if (Schema::hasColumn('transactions', 'is_settlement')) {
                            $invoiceQuery->where('is_settlement', 1);
                        }
                    });
                }
            });

        $ids = $ids->merge($transactionQuery->pluck('id'));

        // Explicit links are the safest fallback for older credit/card/cash/cheque rows.
        $sourceTables = [
            'settlement_credit_sale_payments',
            'settlement_card_payments',
            'settlement_cash_payments',
            'settlement_cheque_payments',
            'settlement_expense_payments',
            'settlement_excess_payments',
            'settlement_shortage_payments',
            'settlement_loan_payments',
            'settlement_drawing_payments',
            'settlement_customer_loans',
        ];
        $settlementKeys = array_values(array_unique(array_filter([
            (string) $settlementId,
            $settlementNo,
        ], static fn ($value) => $value !== '')));

        foreach ($sourceTables as $table) {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'transaction_id')
                || ! Schema::hasColumn($table, 'settlement_no')) {
                continue;
            }

            $sourceQuery = DB::table($table)
                ->whereIn('settlement_no', $settlementKeys)
                ->whereNotNull('transaction_id');

            if (Schema::hasColumn($table, 'business_id')) {
                $sourceQuery->where('business_id', $businessId);
            }

            $ids = $ids->merge($sourceQuery->pluck('transaction_id'));
        }

        return $ids
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @throws SettlementPeriodClosedException
     */
    protected function assertSettlementPeriodOpen(string $date, int $businessId, string $which): void
    {
        if ($this->isSettlementPeriodClosed($date, $businessId)) {
            throw new SettlementPeriodClosedException(
                "The {$which} settlement date ({$date}) falls in a closed financial year. "
                . 'Re-dating a settlement across a closed period is not permitted.'
            );
        }
    }

    /**
     * Is $date inside a closed accounting period?
     *
     * *** ASSUMPTION - PLEASE CONFIRM ***
     * This application has no explicit book-close or period-lock feature. The
     * only closure concept available is the financial year, derived from
     * business.fy_start_month. So "closed" is defined here as ANY FINANCIAL
     * YEAR EARLIER THAN THE ONE IN PROGRESS.
     *
     * If you later add a real period-lock table, this is the single method to
     * change - nothing else in the trait needs to know.
     */
    protected function isSettlementPeriodClosed(string $date, int $businessId): bool
    {
        $fyStartMonth = (int) DB::table('business')
            ->where('id', $businessId)
            ->value('fy_start_month');

        if ($fyStartMonth < 1 || $fyStartMonth > 12) {
            $fyStartMonth = 1;
        }

        $currentFyStart = $this->financialYearStart(Carbon::now(), $fyStartMonth);
        $dateFyStart    = $this->financialYearStart(Carbon::parse($date), $fyStartMonth);

        return $dateFyStart->lt($currentFyStart);
    }

    protected function financialYearStart(Carbon $moment, int $fyStartMonth): Carbon
    {
        $start = Carbon::create($moment->year, $fyStartMonth, 1, 0, 0, 0);

        if ($moment->lt($start)) {
            $start->subYear();
        }

        return $start->startOfDay();
    }

    /**
     * Accept a date, Carbon instance or datetime string; return Y-m-d.
     */
    protected function normaliseSettlementDate($value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);
        if ($value === '') {
            throw new \InvalidArgumentException('Settlement transaction date is empty.');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("Unparseable settlement transaction date: {$value}");
        }
    }
}
