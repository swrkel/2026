<?php

namespace Modules\Vat\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * VAT-owned tax ledger service.
 *
 * This class deliberately uses the query builder only. The VAT module can read
 * the shared transaction tables without depending on core application models.
 */
class VatTaxLedgerService
{
    /**
     * Shared transaction types that can carry VAT.
     */
    public const TAX_TRANSACTION_TYPES = ['purchase', 'sell', 'expense', 'vat_penalty'];

    /**
     * Map the report selection to shared transaction types.
     *
     * A null/empty selection means ALL VAT transaction types. This is important
     * for the main VAT Report Ledger, which IS2313 requires to show purchases,
     * sales and expenses together.
     *
     * @return array<int, string>|null
     */
    public function typesFor(?string $taxType): ?array
    {
        switch ($taxType) {
            case 'input':
                return ['purchase'];

            case 'output':
                return ['sell', 'vat_penalty'];

            case 'expense':
                return ['expense'];

            default:
                return null;
        }
    }

    /**
     * Balance carried into the reporting period.
     */
    public function broughtForward(
        int $businessId,
        string $startDate,
        string $minimumDate,
        ?string $taxType = null
    ): float {
        $balance = 0.0;

        if (Schema::hasTable('transactions') || Schema::hasTable('pos_sales')) {
            $base = $this->transactionQuery($businessId, $taxType);

            $totals = DB::query()
                ->fromSub($base, 'vat_tx')
                ->whereDate('vat_tx.transaction_date', '<', $startDate)
                ->whereDate('vat_tx.transaction_date', '>', $minimumDate)
                ->selectRaw("SUM(IF(vat_tx.type = 'purchase', vat_tx.vat_effective_tax_amount, 0)) as purchase_tax")
                ->selectRaw("SUM(IF(vat_tx.type = 'expense', vat_tx.vat_effective_tax_amount, 0)) as expense_tax")
                ->selectRaw("SUM(IF(vat_tx.type = 'sell', vat_tx.vat_effective_tax_amount, 0)) as sell_tax")
                ->selectRaw("SUM(IF(vat_tx.type = 'vat_penalty', vat_tx.vat_effective_tax_amount, 0)) as penalty_tax")
                ->first();

            if (! empty($totals)) {
                $balance = (float) $totals->sell_tax
                    + (float) $totals->penalty_tax
                    - ((float) $totals->purchase_tax + (float) $totals->expense_tax);
            }
        }

        if (Schema::hasTable('vat_payable_to_accounts')) {
            $openingBalances = DB::table('vat_payable_to_accounts')
                ->where('business_id', $businessId)
                ->whereDate('created_at', '<', $startDate)
                ->whereDate('created_at', '>', $minimumDate)
                ->selectRaw("SUM(IF(type = 'vat_receivable_account', amount, 0)) as input_ob")
                ->selectRaw("SUM(IF(type = 'vat_payable_account', amount, 0)) as output_ob")
                ->first();

            if (! empty($openingBalances)) {
                $balance += (float) $openingBalances->output_ob - (float) $openingBalances->input_ob;
            }
        }

        if (Schema::hasTable('vat_payments')) {
            $balance -= (float) DB::table('vat_payments')
                ->where('business_id', $businessId)
                ->whereDate('date', '<', $startDate)
                ->whereDate('date', '>', $minimumDate)
                ->sum('amount');
        }

        return $balance;
    }

    /**
     * VAT transactions, VAT payments and VAT opening balances inside the period.
     */
    public function ledger(
        int $businessId,
        string $startDate,
        string $endDate,
        string $minimumDate,
        ?string $taxType = null
    ) {
        if (! Schema::hasTable('transactions') && ! Schema::hasTable('pos_sales')) {
            return collect();
        }

        $transactions = DB::query()
            ->fromSub($this->transactionQuery($businessId, $taxType), 'vat_tx')
            ->whereDate('vat_tx.transaction_date', '>', $minimumDate)
            ->whereDate('vat_tx.transaction_date', '>=', $startDate)
            ->whereDate('vat_tx.transaction_date', '<=', $endDate)
            ->select([
                'vat_tx.id',
                'vat_tx.transaction_date as date',
                'vat_tx.type as type',
                'vat_tx.vat_effective_tax_amount as amount',
                'vat_tx.transaction_note',
            ]);

        /*
         * Payments and opening balances belong to the running VAT balance and
         * remain visible whichever VAT type is selected.
         */
        if (Schema::hasTable('vat_payments')) {
            $transactions = $transactions->unionAll(
                DB::table('vat_payments')
                    ->where('business_id', $businessId)
                    ->whereDate('date', '>', $minimumDate)
                    ->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate)
                    ->select([
                        'id',
                        'date as date',
                        DB::raw('"vat_payment" as type'),
                        'amount as amount',
                        'note as transaction_note',
                    ])
            );
        }

        if (Schema::hasTable('vat_payable_to_accounts')) {
            $transactions = $transactions->unionAll(
                DB::table('vat_payable_to_accounts')
                    ->where('business_id', $businessId)
                    ->whereDate('created_at', '>', $minimumDate)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->select([
                        'id',
                        'created_at as date',
                        DB::raw('CASE
                                    WHEN type = "vat_receivable_account" THEN "input_ob"
                                    WHEN type = "vat_payable_account" THEN "output_ob"
                                 END as type'),
                        'amount as amount',
                        'note as transaction_note',
                    ])
            );
        }

        return $transactions->orderBy('date', 'asc')->get();
    }

    /**
     * Build the shared VAT transaction source used by both B/F and the ledger.
     *
     * IS2313: a saved VAT can be carried by the transaction header, purchase/
     * sell lines, or an is_vat/tax_id flag before regeneration. The ledger must
     * use the same practical fallback rules as the VAT Report table instead of
     * requiring transactions.tax_amount > 0.
     */
    private function transactionQuery(int $businessId, ?string $taxType)
    {
        $types = $this->typesFor($taxType);
        $core = null;

        if (Schema::hasTable('transactions')) {
            $core = DB::table('transactions')
                ->where('transactions.business_id', $businessId)
                ->whereIn('transactions.type', self::TAX_TRANSACTION_TYPES);

            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $core->whereNull('transactions.deleted_at');
            }

            if (Schema::hasColumn('transactions', 'status')) {
                // A saved Purchase entry can legitimately be Pending/Ordered,
                // while Sales/Expenses/settlements normally use Final/Received.
                // IS2322 asks for every saved VAT-bearing source entry, so exclude
                // only clearly non-posted/cancelled states instead of whitelisting
                // two statuses and silently dropping valid Purchase rows.
                $core->where(function ($statusQuery) {
                    $statusQuery->whereNull('transactions.status')
                        ->orWhereNotIn('transactions.status', ['draft', 'cancelled', 'canceled', 'void', 'rejected']);
                });
            }

            if ($types !== null) {
                $core->whereIn('transactions.type', $types);
            }

            $purchaseLineTax = '0';
            if (Schema::hasTable('purchase_lines')
                && Schema::hasColumn('purchase_lines', 'transaction_id')
                && Schema::hasColumn('purchase_lines', 'item_tax')
                && Schema::hasColumn('purchase_lines', 'quantity')) {
                $purchaseTaxSub = DB::table('purchase_lines')
                    ->selectRaw('transaction_id, SUM(COALESCE(item_tax, 0) * COALESCE(quantity, 0)) as line_tax')
                    ->groupBy('transaction_id');

                $core->leftJoinSub($purchaseTaxSub, 'vat_purchase_line_tax', function ($join) {
                    $join->on('transactions.id', '=', 'vat_purchase_line_tax.transaction_id');
                });

                $purchaseLineTax = 'COALESCE(vat_purchase_line_tax.line_tax, 0)';
            }

            $sellLineTax = '0';
            if (Schema::hasTable('transaction_sell_lines')
                && Schema::hasColumn('transaction_sell_lines', 'transaction_id')
                && Schema::hasColumn('transaction_sell_lines', 'item_tax')
                && Schema::hasColumn('transaction_sell_lines', 'quantity')) {
                $sellTaxSub = DB::table('transaction_sell_lines')
                    ->selectRaw('transaction_id, SUM(COALESCE(item_tax, 0) * COALESCE(quantity, 0)) as line_tax')
                    ->groupBy('transaction_id');

                $core->leftJoinSub($sellTaxSub, 'vat_sell_line_tax', function ($join) {
                    $join->on('transactions.id', '=', 'vat_sell_line_tax.transaction_id');
                });

                $sellLineTax = 'COALESCE(vat_sell_line_tax.line_tax, 0)';
            }

            $defaultRate = $this->defaultTaxRate($businessId);
            $rateSql = number_format($defaultRate, 6, '.', '');

            if (Schema::hasTable('tax_rates')
                && Schema::hasColumn('transactions', 'tax_id')) {
                $core->leftJoin('tax_rates as vat_ledger_tax_rate', 'vat_ledger_tax_rate.id', '=', 'transactions.tax_id');
                $rateSql = 'COALESCE(vat_ledger_tax_rate.amount, ' . $rateSql . ')';
            }

            $isVatCondition = Schema::hasColumn('transactions', 'is_vat')
                ? 'COALESCE(transactions.is_vat, 0) = 1'
                : '0 = 1';

            $taxIdCondition = Schema::hasColumn('transactions', 'tax_id')
                ? 'transactions.tax_id IS NOT NULL'
                : '0 = 1';

            $headerTax = Schema::hasColumn('transactions', 'tax_amount')
                ? 'COALESCE(transactions.tax_amount, 0)'
                : '0';

            $finalTotal = Schema::hasColumn('transactions', 'final_total')
                ? 'COALESCE(transactions.final_total, 0)'
                : '0';

            $derivedTax = '(CASE
                WHEN ' . $rateSql . ' > 0 AND ' . $finalTotal . ' > 0
                THEN ' . $finalTotal . '
                     - (' . $finalTotal . ' / (1 + (' . $rateSql . ' / 100)))
                ELSE 0
            END)';

            $effectiveTax = '(CASE
                WHEN ' . $headerTax . ' > 0
                    THEN ' . $headerTax . '
                WHEN transactions.type = "purchase" AND ' . $purchaseLineTax . ' > 0
                    THEN ' . $purchaseLineTax . '
                WHEN transactions.type = "sell" AND ' . $sellLineTax . ' > 0
                    THEN ' . $sellLineTax . '
                WHEN (' . $isVatCondition . ' OR ' . $taxIdCondition . ')
                    THEN ' . $derivedTax . '
                ELSE 0
            END)';

            $noteSql = Schema::hasColumn('transactions', 'transaction_note')
                ? 'transactions.transaction_note'
                : 'NULL';

            $core->select([
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.type',
                    DB::raw($noteSql . ' as transaction_note'),
                ])
                ->addSelect(DB::raw($effectiveTax . ' as vat_effective_tax_amount'))
                ->whereRaw($effectiveTax . ' > 0');
        }

        /*
         * IS2313: POS-New writes final sales to `pos_sales`, not to
         * `transactions`. Add those VAT-bearing sales to the same ledger source.
         * When Output is not requested (Input/Expense filters), skip POS entirely.
         */
        $includePos = $types === null || in_array('sell', $types, true);
        if ($includePos
            && Schema::hasTable('pos_sales')
            && Schema::hasColumn('pos_sales', 'business_id')
            && Schema::hasColumn('pos_sales', 'id')) {

            $posDate = Schema::hasColumn('pos_sales', 'sale_date')
                ? 'COALESCE(pos_sales.sale_date, pos_sales.created_at)'
                : 'pos_sales.created_at';
            $posNote = Schema::hasColumn('pos_sales', 'note')
                ? 'pos_sales.note'
                : 'NULL';
            $posHeaderTax = Schema::hasColumn('pos_sales', 'tax_amount')
                ? 'COALESCE(pos_sales.tax_amount, 0)'
                : '0';

            $posLineTax = '0';
            $pos = DB::table('pos_sales')
                ->where('pos_sales.business_id', $businessId);

            if (Schema::hasTable('pos_sale_lines')
                && Schema::hasColumn('pos_sale_lines', 'tax_amount')) {
                $saleIdColumn = Schema::hasColumn('pos_sale_lines', 'sale_id')
                    ? 'sale_id'
                    : (Schema::hasColumn('pos_sale_lines', 'pos_sale_id') ? 'pos_sale_id' : null);

                if (! empty($saleIdColumn)) {
                    $posTaxSub = DB::table('pos_sale_lines')
                        ->selectRaw($saleIdColumn . ' as sale_id, SUM(COALESCE(tax_amount, 0)) as line_tax')
                        ->groupBy($saleIdColumn);

                    $pos->leftJoinSub($posTaxSub, 'vat_pos_line_tax', function ($join) {
                        $join->on('pos_sales.id', '=', 'vat_pos_line_tax.sale_id');
                    });

                    $posLineTax = 'COALESCE(vat_pos_line_tax.line_tax, 0)';
                }
            }

            $posEffectiveTax = '(CASE
                WHEN ' . $posHeaderTax . ' > 0 THEN ' . $posHeaderTax . '
                WHEN ' . $posLineTax . ' > 0 THEN ' . $posLineTax . '
                ELSE 0
            END)';

            if (Schema::hasColumn('pos_sales', 'status')) {
                $pos->where(function ($statusQuery) {
                    $statusQuery->where('pos_sales.status', 'final')
                        ->orWhereNull('pos_sales.status');
                });
            }

            // Avoid duplicates where an older/future POS build also mirrors the
            // exact invoice into the core transaction table.
            if (Schema::hasTable('transactions')
                && Schema::hasColumn('transactions', 'invoice_no')
                && (Schema::hasColumn('pos_sales', 'invoice_no') || Schema::hasColumn('pos_sales', 'sale_no'))) {
                $posInvoice = Schema::hasColumn('pos_sales', 'invoice_no')
                    ? 'pos_sales.invoice_no'
                    : 'pos_sales.sale_no';

                $pos->whereNotExists(function ($duplicate) use ($businessId, $posInvoice) {
                    $duplicate->select(DB::raw(1))
                        ->from('transactions as vat_pos_core_tx')
                        ->whereRaw('vat_pos_core_tx.invoice_no = ' . $posInvoice)
                        ->where('vat_pos_core_tx.business_id', $businessId)
                        ->where('vat_pos_core_tx.type', 'sell');
                });
            }

            $pos->select([
                    'pos_sales.id',
                    DB::raw($posDate . ' as transaction_date'),
                    DB::raw('"sell" as type'),
                    DB::raw($posNote . ' as transaction_note'),
                    DB::raw($posEffectiveTax . ' as vat_effective_tax_amount'),
                ])
                ->whereRaw($posEffectiveTax . ' > 0');

            if ($core !== null) {
                $core->unionAll($pos);
            } else {
                $core = $pos;
            }
        }

        // No eligible source table exists; return an empty, shape-compatible
        // query so callers do not need special-case branches.
        if ($core === null) {
            return DB::query()
                ->select([
                    DB::raw('NULL as id'),
                    DB::raw('NULL as transaction_date'),
                    DB::raw('NULL as type'),
                    DB::raw('NULL as transaction_note'),
                    DB::raw('0 as vat_effective_tax_amount'),
                ])
                ->whereRaw('1 = 0');
        }

        return $core;
    }

    /**
     * The VAT module already treats the first business tax rate as the default
     * VAT rate on its invoice screens. Use the same guarded fallback when a
     * shared transaction is flagged VAT but has not yet been regenerated.
     */
    private function defaultTaxRate(int $businessId): float
    {
        if (! Schema::hasTable('tax_rates')
            || ! Schema::hasColumn('tax_rates', 'business_id')
            || ! Schema::hasColumn('tax_rates', 'amount')) {
            return 0.0;
        }

        return max(0, (float) DB::table('tax_rates')
            ->where('business_id', $businessId)
            ->where('amount', '>', 0)
            ->orderBy('id')
            ->value('amount'));
    }
}
