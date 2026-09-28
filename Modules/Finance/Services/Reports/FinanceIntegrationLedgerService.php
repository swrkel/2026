<?php

namespace Modules\Finance\Services\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only integration helpers for Finance account-book reporting.
 *
 * Finance must treat account_transactions as the authoritative ledger source.
 * Legacy Petro/Settlement source tables are used only as a display fallback when
 * no posted account transaction exists for the same settlement operation.
 */
class FinanceIntegrationLedgerService
{
    /**
     * One Daily Collection row per business and collection number.
     *
     * Historical duplicate collection rows must not multiply Finance ledger rows.
     */
    public function dailyCollectionSettlementLookup(int $businessId): Builder
    {
        return DB::table('daily_collections as finance_dc')
            ->where('finance_dc.business_id', $businessId)
            ->whereNotNull('finance_dc.collection_form_no')
            ->selectRaw(
                'finance_dc.business_id, finance_dc.collection_form_no, '
                . 'MAX(finance_dc.id) as daily_collection_id, '
                . 'MAX(finance_dc.settlement_id) as settlement_id'
            )
            ->groupBy('finance_dc.business_id', 'finance_dc.collection_form_no');
    }

    /**
     * Return every known key (numeric id and human-readable number) for settlements
     * that already have real ledger rows for the requested transaction sub-types.
     *
     * @param array<int, string> $subTypes
     * @return array<int, string>
     */
    public function coveredSettlementKeys(int $businessId, array $subTypes): array
    {
        $invoiceNumbers = DB::table('transactions as finance_t')
            ->join('account_transactions as finance_at', function ($join) use ($businessId) {
                $join->on('finance_at.transaction_id', '=', 'finance_t.id')
                    /*
                     * MA-002 (LA-1134) - tolerate a NULL business_id here.
                     *
                     * This join decides whether a settlement's ledger rows
                     * already exist. If it finds none, Finance's Cash Account
                     * book SYNTHESISES its own rows from
                     * SettlementLoanPayment and merges them in - which is what
                     * produced the duplicate Loan Payment entries reported in
                     * LA-1134.
                     *
                     * The rows DO exist. PetroDirect's settlement finalisation
                     * creates a Cash debit and a Cash credit per loan payment
                     * (SettlementController ~3916 and ~3932). But they were
                     * written through TransactionUtil::createAccountTransaction,
                     * which NEVER SET business_id - the defect fixed separately
                     * in this same parcel. Every such row created before that
                     * fix has business_id NULL, so this equality test excluded
                     * them, the guard returned an empty list, and the
                     * synthetic rows were added on top of the real ones.
                     *
                     * A NULL business_id here cannot leak across businesses:
                     * finance_t.business_id is already constrained below, and
                     * an account_transaction is reached only through its own
                     * parent transaction.
                     *
                     * Keeping this tolerance means the report stays correct
                     * even on tenants where the historical rows have not been
                     * backfilled yet.
                     */
                    ->where(function ($scope) use ($businessId) {
                        $scope->where('finance_at.business_id', '=', $businessId)
                            ->orWhereNull('finance_at.business_id');
                    })
                    ->whereNull('finance_at.deleted_at');
            })
            ->where('finance_t.business_id', $businessId)
            ->where('finance_t.type', 'settlement')
            ->whereIn('finance_t.sub_type', $subTypes)
            ->where('finance_t.is_settlement', 1)
            ->whereNull('finance_t.deleted_at')
            ->whereNotNull('finance_t.invoice_no')
            ->distinct()
            ->pluck('finance_t.invoice_no')
            ->map(static fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($invoiceNumbers->isEmpty()) {
            return [];
        }

        $settlements = DB::table('settlements')
            ->where('business_id', $businessId)
            ->where(function ($query) use ($invoiceNumbers) {
                $query->whereIn('settlement_no', $invoiceNumbers->all());

                $numericIds = $invoiceNumbers
                    ->filter(static fn ($value) => ctype_digit((string) $value))
                    ->map(static fn ($value) => (int) $value)
                    ->values()
                    ->all();

                if ($numericIds !== []) {
                    $query->orWhereIn('id', $numericIds);
                }
            })
            ->get(['id', 'settlement_no']);

        return $invoiceNumbers
            ->merge($settlements->pluck('id')->map(static fn ($value) => (string) $value))
            ->merge($settlements->pluck('settlement_no')->map(static fn ($value) => trim((string) $value)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param iterable<int|string> $accountIds
     * @return array<int, string>
     */
    public function accountNameMap(int $businessId, iterable $accountIds): array
    {
        $ids = $this->normaliseIds($accountIds);

        if ($ids === []) {
            return [];
        }

        return DB::table('accounts')
            ->where('business_id', $businessId)
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->mapWithKeys(static fn ($name, $id) => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * @param iterable<int|string> $contactIds
     * @return array<int, string>
     */
    public function contactNameMap(int $businessId, iterable $contactIds): array
    {
        $ids = $this->normaliseIds($contactIds);

        if ($ids === []) {
            return [];
        }

        return DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->mapWithKeys(static fn ($name, $id) => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * @param iterable<int|string> $values
     * @return array<int, int>
     */
    private function normaliseIds(iterable $values): array
    {
        return Collection::make($values)
            ->map(static fn ($value) => (int) $value)
            ->filter(static fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }
}
