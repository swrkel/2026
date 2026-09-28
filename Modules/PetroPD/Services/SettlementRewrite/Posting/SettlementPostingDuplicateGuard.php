<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Facades\DB;

class SettlementPostingDuplicateGuard
{
    public function accountTransactionExists(int $businessId, string $sourceType, string $sourceNo): bool
    {
        if ($businessId <= 0 || trim($sourceNo) === '') {
            return false;
        }

        if (! $this->tableExists('account_transactions')) {
            return false;
        }

        $sourceNo = trim($sourceNo);

        /*
         * IS1969 / ledger: only reference columns that actually exist.
         *
         * This method referenced at.note and at.description unconditionally.
         * account_transactions has no `description` column in these tenants, so
         * the query threw:
         *
         *   SQLSTATE[42S22]: Unknown column 'at.description' in 'WHERE'
         *
         * SettlementFinalSavePostingConnector catches that and logs
         * "PD Settlement rewrite posting connector failed" - so the settlement
         * saved and the ACCOUNT BOOKS WERE NEVER WRITTEN. Silent, because the
         * settlement itself looked successful. Observed on PDST1 to PDST4.
         *
         * accountTransactionLineExists() immediately below this method already
         * guarded the same two columns with columnExists(). This one did not.
         * The guards are now applied consistently.
         *
         * Every optional column is checked, not only description - a tenant
         * missing `note` or `sub_type` would have failed the same way, just
         * later.
         */
        $query = DB::table('account_transactions as at')
            ->leftJoin('transactions as t', 't.id', '=', 'at.transaction_id');

        $query->where(function ($scope) use ($businessId) {
            if ($this->columnExists('account_transactions', 'business_id')) {
                $scope->orWhere('at.business_id', $businessId);
            }

            if ($this->columnExists('transactions', 'business_id')) {
                $scope->orWhere('t.business_id', $businessId);
            }
        });

        if ($this->columnExists('account_transactions', 'deleted_at')) {
            $query->whereNull('at.deleted_at');
        }

        $query->where(function ($match) use ($sourceType, $sourceNo) {
            $matched = false;

            if ($this->columnExists('account_transactions', 'sub_type')) {
                $match->orWhere('at.sub_type', $sourceType)
                    ->orWhere('at.sub_type', 'petro_pd_settlement');
                $matched = true;
            }

            foreach (['note', 'description'] as $column) {
                if ($this->columnExists('account_transactions', $column)) {
                    $match->orWhere('at.' . $column, 'like', '%' . $sourceNo . '%');
                    $matched = true;
                }
            }

            foreach (['invoice_no', 'ref_no'] as $column) {
                if ($this->columnExists('transactions', $column)) {
                    $match->orWhere('t.' . $column, $sourceNo);
                    $matched = true;
                }
            }

            if ($this->columnExists('transactions', 'additional_notes')) {
                $match->orWhere('t.additional_notes', 'like', '%' . $sourceNo . '%');
                $matched = true;
            }

            /*
             * If not one of those columns exists there is nothing to match on.
             * whereRaw('1 = 0') keeps the query valid and makes the guard report
             * "no duplicate found", which is the safe answer - posting proceeds
             * rather than being skipped, and that is the failure we are fixing.
             */
            if (! $matched) {
                $match->whereRaw('1 = 0');
            }
        });

        return $query->exists();
    }

    /**
     * IS1497: Checks if the exact same posting row already exists before inserting.
     * This prevents duplicate card, stock, COGS and sales-income rows at source.
     */
    public function accountTransactionLineExists(int $businessId, array $line): bool
    {
        if (! $this->tableExists('account_transactions')) {
            return false;
        }

        $query = DB::table('account_transactions')->whereNull('deleted_at');

        if ($this->columnExists('account_transactions', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        foreach (['transaction_id', 'transaction_payment_id', 'account_id', 'type', 'sub_type', 'sell_line_id'] as $column) {
            if (array_key_exists($column, $line) && $this->columnExists('account_transactions', $column)) {
                if ($line[$column] === null || $line[$column] === '') {
                    $query->whereNull($column);
                } else {
                    $query->where($column, $line[$column]);
                }
            }
        }

        if (array_key_exists('amount', $line)) {
            $amount = (float) $line['amount'];
            $query->whereBetween('amount', [$amount - 0.0001, $amount + 0.0001]);
        }

        if (! empty($line['settlement_no'])) {
            $settlementNo = (string) $line['settlement_no'];
            $query->where(function ($q) use ($settlementNo) {
                if ($this->columnExists('account_transactions', 'note')) {
                    $q->orWhere('note', 'like', '%' . $settlementNo . '%');
                }
                if ($this->columnExists('account_transactions', 'description')) {
                    $q->orWhere('description', 'like', '%' . $settlementNo . '%');
                }
            });
        }

        return $query->exists();
    }

    public function stockTransactionExists(int $businessId, string $refNo): bool
    {
        if (! $this->tableExists('transactions')) {
            return false;
        }

        return DB::table('transactions')
            ->where('business_id', $businessId)
            ->where(function ($query) use ($refNo) {
                $query->where('invoice_no', $refNo)
                    ->orWhere('ref_no', $refNo)
                    ->orWhere('additional_notes', 'like', '%' . $refNo . '%');
            })
            ->whereNull('deleted_at')
            ->exists();
    }

    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function columnExists(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
