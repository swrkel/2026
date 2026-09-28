<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Reconciliation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verifies that every downstream posting area can be checked from one settlement number.
 *
 * This class does not calculate settlement totals. It only verifies whether posting rows
 * exist using the saved settlement reference. List/View/Print/Reports must continue to
 * use the saved settlement snapshot as their source of truth.
 */
class SettlementPostingVerifier
{
    public function verify(int $businessId, string $settlementNo, ?int $settlementId = null): array
    {
        return [
            'settlement_no' => $settlementNo,
            'settlement_id' => $settlementId,
            'payment_account_books' => $this->existsInTable('account_transactions', $businessId, $settlementNo, ['ref_no', 'payment_ref_no', 'note', 'description']),
            'stock_account_books' => $this->existsInTable('account_transactions', $businessId, $settlementNo, ['ref_no', 'payment_ref_no', 'note', 'description'], ['sub_type' => ['stock', 'stock_account', 'petro_pd_stock']]),
            'sales_income_accounts' => $this->existsInTable('account_transactions', $businessId, $settlementNo, ['ref_no', 'payment_ref_no', 'note', 'description'], ['sub_type' => ['sales_income', 'income', 'petro_pd_sales']]),
            'cogs_accounts' => $this->existsInTable('account_transactions', $businessId, $settlementNo, ['ref_no', 'payment_ref_no', 'note', 'description'], ['sub_type' => ['cogs', 'cost_of_goods_sold', 'petro_pd_cogs']]),
            'customer_ledgers' => $this->existsInTable('contact_ledger', $businessId, $settlementNo, ['ref_no', 'description', 'payment_method']),
            'pump_operator_ledgers' => $this->existsInTable('pump_operator_ledgers', $businessId, $settlementNo, ['settlement_no', 'ref_no', 'description']),
            'stock_transactions' => $this->existsInTable('stock_transactions', $businessId, $settlementNo, ['ref_no', 'transaction_no', 'description']),
        ];
    }

    private function existsInTable(string $table, int $businessId, string $settlementNo, array $referenceColumns, array $optionalFilters = []): array
    {
        if (! Schema::hasTable($table)) {
            return ['status' => 'missing_table', 'table' => $table, 'count' => 0];
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        $availableReferenceColumns = array_values(array_filter($referenceColumns, fn ($column) => Schema::hasColumn($table, $column)));

        if (empty($availableReferenceColumns)) {
            return ['status' => 'missing_reference_column', 'table' => $table, 'count' => 0];
        }

        $query->where(function ($q) use ($availableReferenceColumns, $settlementNo) {
            foreach ($availableReferenceColumns as $column) {
                $q->orWhere($column, $settlementNo)
                    ->orWhere($column, 'like', '%' . $settlementNo . '%');
            }
        });

        foreach ($optionalFilters as $column => $values) {
            if (Schema::hasColumn($table, $column)) {
                $query->whereIn($column, (array) $values);
            }
        }

        $count = (int) $query->count();

        return [
            'status' => $count > 0 ? 'posted' : 'not_found',
            'table' => $table,
            'count' => $count,
            'checked_columns' => $availableReferenceColumns,
        ];
    }
}
