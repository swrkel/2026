<?php

namespace Modules\Vat\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separation step 2 (see document 5-18, "Suggested sequence").
 *
 * Brings two pieces of VAT logic out of App\Utils\TransactionUtil and into the
 * module that owns them:
 *
 *   - __getVatEffectiveDate(), which reads vat_settings - a VAT-OWNED table.
 *     Core had no business knowing about it.
 *   - getInputTax() / getOutputTax() / getExpenseTax(), which are VAT
 *     arithmetic by definition.
 *
 * Both were classified Category A in the inventory: behaviour that can be
 * internalised with no shared-state consequence.
 *
 *
 * WHAT CHANGES, AND WHAT DELIBERATELY DOES NOT
 *
 * The effective-date rule is reproduced exactly: the latest ACTIVE vat_settings
 * row for the business, by effective_date then id. Same row the core method
 * selected, so no screen shifts its reporting window.
 *
 * The tax figures are computed from VAT-OWNED tables, matching the decision
 * already made in VatLedgerService (document 5-17): businesses licensed only
 * for VAT module pages never post into core `transactions`, so computing from
 * core cannot work for them. On the supplied database core `transactions` held
 * zero rows with tax_amount > 0 while vat_invoices_2 held 58 invoices.
 *
 * The return shape is kept as ['total_tax' => float, ...] because
 * VatController already treats these results as arrays with that key, and the
 * VAT summary screens read it directly. Changing the shape would have meant
 * touching every consumer for no benefit.
 *
 * Every table is guarded with Schema::hasTable so a business without a given
 * VAT feature contributes zero rather than erroring.
 */
class VatTaxCalculationService
{
    /**
     * The VAT effective date for a business: nothing before this counts.
     *
     * Replaces TransactionUtil::__getVatEffectiveDate(). Returns null when the
     * business has no active VAT settings, which callers already handle by
     * leaving the requested start date alone.
     */
    public function effectiveDate(int $businessId): ?string
    {
        if (! Schema::hasTable('vat_settings')) {
            return null;
        }

        $query = DB::table('vat_settings')->where('business_id', $businessId);

        // status = 1 is the active settings row. Guarded because at least one
        // deployment predates the column.
        if (Schema::hasColumn('vat_settings', 'status')) {
            $query->where('status', 1);
        }

        $row = $query
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first(['effective_date']);

        return $row->effective_date ?? null;
    }

    /**
     * Apply the effective date to a requested start date.
     *
     * Every call site repeated this same three-line clamp; centralising it means
     * they cannot drift apart.
     */
    public function clampStartDate(int $businessId, ?string $startDate): ?string
    {
        $effectiveDate = $this->effectiveDate($businessId);

        if (empty($effectiveDate) || empty($startDate)) {
            return $startDate;
        }

        return strtotime($startDate) < strtotime($effectiveDate) ? $effectiveDate : $startDate;
    }

    /**
     * Input tax - VAT paid on purchases.
     */
    public function inputTax(int $businessId, ?string $startDate, ?string $endDate, $locationId = null): array
    {
        $total = $this->sum('vat_purchases', 'invoice_date', 'vat_amount', $businessId, $startDate, $endDate, $locationId);

        return ['total_tax' => round($total, 2)];
    }

    /**
     * Output tax - VAT charged on customer invoices.
     *
     * Summed across all three invoice tables, the same set VatLedgerService and
     * the Customer VAT Schedule already treat as output-tax sources.
     */
    public function outputTax(int $businessId, ?string $startDate, ?string $endDate, $locationId = null): array
    {
        $total = 0.0;

        foreach (['vat_invoices', 'vat_invoices_2', 'fleet_vat_invoices_2'] as $table) {
            $total += $this->sum($table, 'date', 'tax_amount', $businessId, $startDate, $endDate, $locationId);
        }

        return ['total_tax' => round($total, 2)];
    }

    /**
     * Expense tax - VAT on recorded expenses.
     */
    public function expenseTax(int $businessId, ?string $startDate, ?string $endDate, $locationId = null): array
    {
        $total = $this->sum('vat_expenses', 'transaction_date', 'tax_amount', $businessId, $startDate, $endDate, $locationId);

        return ['total_tax' => round($total, 2)];
    }

    /**
     * Sum one tax column over a date window, scoped to business and location.
     *
     * location_id is applied only where the table actually carries it -
     * vat_purchases has no location column, so a location filter must not
     * silently return zero there.
     */
    private function sum(string $table, string $dateColumn, string $amountColumn, int $businessId, ?string $startDate, ?string $endDate, $locationId = null): float
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $dateColumn)
            || ! Schema::hasColumn($table, $amountColumn)) {
            return 0.0;
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (! empty($locationId) && Schema::hasColumn($table, 'location_id')) {
            $query->where('location_id', $locationId);
        }

        if (! empty($startDate)) {
            $query->whereDate($dateColumn, '>=', $startDate);
        }

        if (! empty($endDate)) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }

        return (float) $query->sum($amountColumn);
    }
}
