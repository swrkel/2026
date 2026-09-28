<?php

namespace Modules\Vat\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S664: how many transactions a VAT prefix is responsible for.
 *
 * The ticket asks for the same rule on three screens - VAT Invoice prefixes,
 * VAT Invoice 2 prefixes, and User to Invoice Prefix:
 *
 *   - Edit and Delete disabled while the prefix has related transactions.
 *   - Enabled again once those transactions are deleted.
 *
 * Counting LIVE rather than storing a flag is what makes the second half work
 * by itself. Nothing has to be reset when the last invoice is removed from
 * List VAT Invoice 2 - the count simply returns to zero on the next page load
 * and the buttons come back.
 *
 * Both invoice tables store the prefix's ID in their `prefix` column (see
 * VatInvoiceController::store and VatInvoice2Controller), so the lookup is a
 * direct match rather than a text comparison.
 */
class VatPrefixUsageService
{
    /**
     * Invoices raised against a VAT Invoice (type 1) prefix.
     */
    public function invoiceCount(int $prefixId, int $businessId): int
    {
        return $this->countIn('vat_invoices', $prefixId, $businessId);
    }

    /**
     * Invoices raised against a VAT Invoice 2 prefix.
     */
    public function invoice2Count(int $prefixId, int $businessId): int
    {
        return $this->countIn('vat_invoices_2', $prefixId, $businessId);
    }

    /**
     * For User to Invoice Prefix rows, which point at BOTH a VAT Invoice prefix
     * and a VAT Invoice 2 prefix. The assignment is in use if either side has
     * transactions behind it.
     */
    public function userPrefixCount(?int $prefixId, ?int $prefixId2, int $businessId): int
    {
        $total = 0;

        if (! empty($prefixId)) {
            $total += $this->invoiceCount((int) $prefixId, $businessId);
        }

        if (! empty($prefixId2)) {
            $total += $this->invoice2Count((int) $prefixId2, $businessId);
        }

        return $total;
    }

    /**
     * Count rows in $table whose `prefix` column matches, scoped to the business.
     *
     * Guarded on both table and column: this module is deployed against several
     * schema versions, and a missing table must disable the buttons' usage check
     * rather than break the whole datatable with an SQL error.
     */
    private function countIn(string $table, int $prefixId, int $businessId): int
    {
        if ($prefixId <= 0 || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'prefix')) {
            return 0;
        }

        $query = DB::table($table)->where('prefix', $prefixId);

        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        // Soft-deleted invoices are not "related transactions" any more - the
        // ticket's re-enable rule depends on deletions actually releasing the
        // prefix.
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }

    /**
     * The shared markup for one action menu.
     *
     * Built here so the three screens cannot drift apart in wording or
     * behaviour. $count > 0 renders disabled buttons carrying the reason;
     * otherwise the live Edit and Delete entries are returned.
     */
    public function actionMenuHtml(string $editUrl, string $deleteUrl, int $count, string $editContainer = '.fuel_tank_modal'): string
    {
        /*
         * S664: vat-prefix-action-group / vat-prefix-action-menu are the hooks
         * the shared script uses to lift an open menu out to <body>.
         *
         * Edit and Delete were ALWAYS rendered on these screens - the reported
         * "not showing" was the menu being clipped by the scrollable table
         * wrapper, not missing markup.
         */
        $html = '<div class="btn-group vat-prefix-action-group">
                <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
            . __('messages.actions') . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right vat-prefix-action-menu" role="menu">';

        if ($count > 0) {
            $reason = e(sprintf(
                'This prefix is used by %d transaction%s.',
                $count,
                $count === 1 ? '' : 's'
            ));

            $html .= '<li class="disabled"><a href="#" onclick="return false;" style="cursor:not-allowed; opacity:.55;" title="' . $reason . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
            $html .= '<li class="disabled"><a href="#" onclick="return false;" style="cursor:not-allowed; opacity:.55;" title="' . $reason . '"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>';
            $html .= '<li role="separator" class="divider"></li>';
            $html .= '<li><a href="#" onclick="return false;" style="cursor:default; white-space:normal; font-size:11px; color:#999;">' . $reason . '</a></li>';
        } else {
            $html .= '<li><a href="#" data-href="' . $editUrl . '" class="btn-modal" data-container="' . e($editContainer) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
            $html .= '<li><a href="#" data-href="' . $deleteUrl . '" class="delete_task"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>';
        }

        return $html . '</ul></div>';
    }
}
