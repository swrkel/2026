<?php

namespace Modules\Vat\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S664 (item 4): the VAT ledger, computed from the VAT module's OWN tables.
 *
 *
 * WHY THIS EXISTS
 *
 * VatReportController used App\Utils\ContactUtil::getCustomerTaxLedger(), which
 * reads the CORE `transactions` table:
 *
 *     transactions.tax_amount > 0
 *     AND type IN ('purchase','sell','expense','vat_penalty')
 *     AND (type IN ('sell','vat_penalty') OR is_vat = 1)
 *
 * The VAT module does not write to `transactions`. Its invoices live in
 * vat_invoices / vat_invoices_2, its purchases in vat_purchases, its expenses
 * in vat_expenses. A separate bridge (vat_invoice_to_transaction_settings and
 * _history) can copy invoices across, but on the supplied database BOTH bridge
 * tables are empty and `transactions` contains ZERO rows with tax_amount > 0 -
 * while vat_invoices_2 holds 58 invoices. That is why the report was blank.
 *
 * More importantly, some businesses are licensed only for VAT module pages and
 * never post into `transactions` at all. For those the core query can never
 * return anything, so reading core is structurally wrong, not just empty here.
 *
 *
 * DOUBLE COUNTING
 *
 * Where the bridge IS enabled, a VAT invoice exists twice: once in
 * vat_invoices_2 and once as a copy in `transactions`. This service counts the
 * VAT tables only, so each invoice is counted exactly once no matter whether
 * the bridge is on. The bridge remains what it was meant to be - a feed into
 * accounting - rather than the VAT report's data source.
 *
 *
 * SEPARATION
 *
 * Every table read here is owned by the VAT module, except `contacts`, which is
 * shared and is read directly rather than through a core model. That keeps this
 * working when the Contact module is retired in favour of Customers, since both
 * use the same `contacts` table.
 *
 * Each query is guarded with Schema::hasTable so a business without a given VAT
 * feature contributes nothing instead of erroring.
 */
class VatLedgerService
{
    /**
     * Signed VAT balance carried forward before $startDate.
     *
     * Output tax (sales) increases what is owed; input tax (purchases and
     * expenses) reduces it; payments already made reduce it further. Mirrors the
     * arithmetic of the core getCustomerTaxBf() it replaces.
     */
    public function balanceBroughtForward(int $businessId, string $startDate, string $minimumDate): float
    {
        $outputTax = $this->sumInvoiceTax($businessId, null, $startDate, $minimumDate, true);
        $inputTax = $this->sumPurchaseTax($businessId, null, $startDate, $minimumDate, true)
            + $this->sumExpenseTax($businessId, null, $startDate, $minimumDate, true);

        $balance = $outputTax - $inputTax;

        $balance += $this->sumOpeningBalances($businessId, null, $startDate, $minimumDate, true);
        $balance -= $this->sumPayments($businessId, null, $startDate, $minimumDate, true);

        return round($balance, 2);
    }

    /**
     * Ledger lines between $startDate and $endDate, oldest first.
     *
     * Shape matches what report_details.blade.php already expects from the core
     * method: id, date, type, amount, transaction_note. The `type` values are
     * kept identical too ('sell', 'purchase', 'expense', 'vat_payment',
     * 'input_ob', 'output_ob') so the view needs no change.
     */
    public function ledger(int $businessId, string $startDate, string $endDate, string $minimumDate): Collection
    {
        $lines = collect()
            ->merge($this->invoiceLines($businessId, $startDate, $endDate, $minimumDate))
            ->merge($this->purchaseLines($businessId, $startDate, $endDate, $minimumDate))
            ->merge($this->expenseLines($businessId, $startDate, $endDate, $minimumDate))
            ->merge($this->paymentLines($businessId, $startDate, $endDate, $minimumDate))
            ->merge($this->openingBalanceLines($businessId, $startDate, $endDate, $minimumDate));

        return $lines
            ->sortBy(fn ($row) => (string) $row->date)
            ->values();
    }

    /**
     * Totals for the Input / Output / Expense tax headings.
     */
    public function taxTotals(int $businessId, string $startDate, string $endDate, string $minimumDate): array
    {
        $output = $this->sumInvoiceTax($businessId, $startDate, $endDate, $minimumDate);
        $purchase = $this->sumPurchaseTax($businessId, $startDate, $endDate, $minimumDate);
        $expense = $this->sumExpenseTax($businessId, $startDate, $endDate, $minimumDate);

        return [
            'output_tax' => round($output, 2),
            'input_tax' => round($purchase, 2),
            'expense_tax' => round($expense, 2),
            'total_paid' => round($this->sumPayments($businessId, $startDate, $endDate, $minimumDate), 2),
            'balance' => round($output - $purchase - $expense, 2),
        ];
    }

    /* ------------------------------------------------------------------ *
     * Output tax - customer invoices
     * ------------------------------------------------------------------ */

    /**
     * The invoice tables that make up output tax, with their date columns.
     *
     * fleet_vat_invoices_2 is included because the Customer VAT Schedule already
     * treats it as a VAT invoice source; it is guarded like the rest, so a
     * business without the fleet feature simply contributes nothing.
     */
    private function invoiceSources(): array
    {
        return [
            ['table' => 'vat_invoices', 'date' => 'date', 'tax' => 'tax_amount', 'no' => 'customer_bill_no'],
            ['table' => 'vat_invoices_2', 'date' => 'date', 'tax' => 'tax_amount', 'no' => 'customer_bill_no'],
            ['table' => 'fleet_vat_invoices_2', 'date' => 'date', 'tax' => 'tax_amount', 'no' => 'customer_bill_no'],
        ];
    }

    private function sumInvoiceTax(int $businessId, ?string $startDate, string $endOrStart, string $minimumDate, bool $before = false): float
    {
        $total = 0.0;

        foreach ($this->invoiceSources() as $source) {
            $query = $this->dateScoped($source['table'], $source['date'], $businessId, $startDate, $endOrStart, $minimumDate, $before);

            if ($query === null) {
                continue;
            }

            $total += (float) $query->sum($source['tax']);
        }

        return $total;
    }

    private function invoiceLines(int $businessId, string $startDate, string $endDate, string $minimumDate): Collection
    {
        $lines = collect();

        foreach ($this->invoiceSources() as $source) {
            $query = $this->dateScoped($source['table'], $source['date'], $businessId, $startDate, $endDate, $minimumDate);

            if ($query === null) {
                continue;
            }

            $noColumn = Schema::hasColumn($source['table'], $source['no']) ? $source['no'] : 'id';

            $rows = $query
                ->where($source['tax'], '>', 0)
                ->get(['id', $source['date'] . ' as date', $source['tax'] . ' as amount', $noColumn . ' as ref_no']);

            foreach ($rows as $row) {
                // 'sell' matches the type the view already renders as output tax.
                $lines->push($this->line($row->id, $row->date, 'sell', $row->amount, 'VAT Invoice ' . $row->ref_no));
            }
        }

        return $lines;
    }

    /* ------------------------------------------------------------------ *
     * Input tax - purchases
     * ------------------------------------------------------------------ */

    private function sumPurchaseTax(int $businessId, ?string $startDate, string $endOrStart, string $minimumDate, bool $before = false): float
    {
        $query = $this->dateScoped('vat_purchases', 'invoice_date', $businessId, $startDate, $endOrStart, $minimumDate, $before);

        return $query === null ? 0.0 : (float) $query->sum('vat_amount');
    }

    private function purchaseLines(int $businessId, string $startDate, string $endDate, string $minimumDate): Collection
    {
        $query = $this->dateScoped('vat_purchases', 'invoice_date', $businessId, $startDate, $endDate, $minimumDate);

        if ($query === null) {
            return collect();
        }

        return $query
            ->where('vat_amount', '>', 0)
            ->get(['id', 'invoice_date as date', 'vat_amount as amount', 'invoice_no'])
            ->map(fn ($row) => $this->line($row->id, $row->date, 'purchase', $row->amount, 'VAT Purchase ' . $row->invoice_no));
    }

    /* ------------------------------------------------------------------ *
     * Input tax - expenses
     * ------------------------------------------------------------------ */

    private function sumExpenseTax(int $businessId, ?string $startDate, string $endOrStart, string $minimumDate, bool $before = false): float
    {
        $query = $this->dateScoped('vat_expenses', 'transaction_date', $businessId, $startDate, $endOrStart, $minimumDate, $before);

        return $query === null ? 0.0 : (float) $query->sum('tax_amount');
    }

    private function expenseLines(int $businessId, string $startDate, string $endDate, string $minimumDate): Collection
    {
        $query = $this->dateScoped('vat_expenses', 'transaction_date', $businessId, $startDate, $endDate, $minimumDate);

        if ($query === null) {
            return collect();
        }

        return $query
            ->where('tax_amount', '>', 0)
            ->get(['id', 'transaction_date as date', 'tax_amount as amount', 'ref_no'])
            ->map(fn ($row) => $this->line($row->id, $row->date, 'expense', $row->amount, 'VAT Expense ' . $row->ref_no));
    }

    /* ------------------------------------------------------------------ *
     * Payments and opening balances - already VAT-module tables
     * ------------------------------------------------------------------ */

    private function sumPayments(int $businessId, ?string $startDate, string $endOrStart, string $minimumDate, bool $before = false): float
    {
        $query = $this->dateScoped('vat_payments', 'date', $businessId, $startDate, $endOrStart, $minimumDate, $before);

        return $query === null ? 0.0 : (float) $query->sum('amount');
    }

    private function paymentLines(int $businessId, string $startDate, string $endDate, string $minimumDate): Collection
    {
        $query = $this->dateScoped('vat_payments', 'date', $businessId, $startDate, $endDate, $minimumDate);

        if ($query === null) {
            return collect();
        }

        return $query
            ->get(['id', 'date', 'amount', 'note'])
            ->map(fn ($row) => $this->line($row->id, $row->date, 'vat_payment', $row->amount, $row->note));
    }

    private function sumOpeningBalances(int $businessId, ?string $startDate, string $endOrStart, string $minimumDate, bool $before = false): float
    {
        $query = $this->dateScoped('vat_payable_to_accounts', 'created_at', $businessId, $startDate, $endOrStart, $minimumDate, $before);

        if ($query === null) {
            return 0.0;
        }

        $row = $query->selectRaw(
            "COALESCE(SUM(CASE WHEN type = 'vat_payable_account' THEN amount ELSE 0 END), 0) AS output_ob,"
            . " COALESCE(SUM(CASE WHEN type = 'vat_receivable_account' THEN amount ELSE 0 END), 0) AS input_ob"
        )->first();

        /*
         * NOTE: the core method this replaces read $obs->ouput_ob - a typo for
         * output_ob - so the payable side silently contributed NOTHING to the
         * brought-forward balance. Spelled correctly here, which means the
         * opening balance can differ from the old report where these rows exist.
         * That is a correction, not a regression.
         */
        return (float) ($row->output_ob ?? 0) - (float) ($row->input_ob ?? 0);
    }

    private function openingBalanceLines(int $businessId, string $startDate, string $endDate, string $minimumDate): Collection
    {
        $query = $this->dateScoped('vat_payable_to_accounts', 'created_at', $businessId, $startDate, $endDate, $minimumDate);

        if ($query === null) {
            return collect();
        }

        return $query
            ->get(['id', 'created_at as date', 'amount', 'note', 'type'])
            ->map(function ($row) {
                $type = $row->type === 'vat_receivable_account' ? 'input_ob' : 'output_ob';

                return $this->line($row->id, $row->date, $type, $row->amount, $row->note);
            });
    }

    /* ------------------------------------------------------------------ *
     * Shared helpers
     * ------------------------------------------------------------------ */

    /**
     * Build a business- and date-scoped query, or null when the table is absent.
     *
     * $before = true asks for everything strictly BEFORE $endOrStart, which is
     * how the brought-forward figure is taken. Otherwise $startDate..$endOrStart
     * inclusive.
     *
     * $minimumDate is the VAT effective date: nothing before it counts, which is
     * the same rule the core method applied.
     */
    private function dateScoped(string $table, string $dateColumn, int $businessId, ?string $startDate, string $endOrStart, string $minimumDate, bool $before = false)
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $dateColumn)) {
            return null;
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (! empty($minimumDate)) {
            $query->whereDate($dateColumn, '>=', $minimumDate);
        }

        if ($before) {
            $query->whereDate($dateColumn, '<', $endOrStart);
        } else {
            if (! empty($startDate)) {
                $query->whereDate($dateColumn, '>=', $startDate);
            }

            $query->whereDate($dateColumn, '<=', $endOrStart);
        }

        return $query;
    }

    /**
     * One ledger line in the shape the existing view already renders.
     */
    private function line($id, $date, string $type, $amount, ?string $note): object
    {
        return (object) [
            'id' => $id,
            'date' => $date,
            'type' => $type,
            'amount' => (float) $amount,
            'transaction_note' => $note,
            'product_id' => null,
            'quantity' => null,
        ];
    }
}
