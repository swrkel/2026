<?php

namespace Modules\Vat\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Customers offered by the VAT module's own screens.
 *
 * WHY THIS EXISTS
 *
 *   The VAT report built its customer dropdown with
 *       App\Contact::customersDropdown(...)
 *   which is core code, and which reads only the shared `contacts` table.
 *
 *   This module has its own `vat_contacts`, carrying its own `vat_no` - and VAT
 *   customers are identified by their VAT number, so those records are the ones
 *   the module is actually about. But a business running the Customers module
 *   keeps its customers in `contacts`, and those must appear here too, or the
 *   report cannot be run for them.
 *
 *   So the list is the union of the two, and neither source is imported as core
 *   code: both tables are read through the query builder by name.
 *
 * HOW THE CUSTOMERS MODULE IS DETECTED
 *
 *   Through the module manager, not through App\Utils\ModuleUtil. That manager
 *   is part of the module system itself rather than the application's own code,
 *   so depending on it does not tie this module to the core. The approach
 *   mirrors ExpensesNew\Services\ModuleAvailabilityService, including asking
 *   has() before isEnabled() - nwidart's isEnabled() throws for an unknown
 *   module, and reporting that exception fills the tenant log with
 *   "Module [X] does not exist!" on every page load.
 *
 * WHY BOTH SOURCES RATHER THAN ONE
 *
 *   A business may have VAT contacts that were never in `contacts`, and
 *   customers in `contacts` that have no VAT record yet. Showing only one source
 *   would silently hide part of the customer base, and which part would depend
 *   on how that business was set up. Merged, with duplicates removed, the
 *   dropdown is complete either way.
 */
class VatCustomerListService
{
    /**
     * id => label, for a customer dropdown.
     *
     * Keys are prefixed so the two sources cannot collide: `vat_contacts` id 7
     * and `contacts` id 7 are different records. A caller that only needs the
     * numeric id can pass $prefixKeys as false, which is the shape the VAT
     * report's existing dropdown expects.
     *
     * @return \Illuminate\Support\Collection<string|int, string>
     */
    public function customers(int $businessId, bool $prependNone = false, bool $appendId = true)
    {
        $customers = collect();

        foreach ($this->vatContacts($businessId, $appendId) as $id => $label) {
            $customers->put($id, $label);
        }

        /*
         * Only when the Customers module is present. Without it those records are
         * not this business's customer list and should not be offered.
         */
        if ($this->customersModuleEnabled()) {
            foreach ($this->coreContacts($businessId, $appendId) as $id => $label) {
                // vat_contacts wins a clash: it is the record carrying the VAT number.
                if (! $customers->has($id)) {
                    $customers->put($id, $label);
                }
            }
        }

        $customers = $customers->sort(SORT_NATURAL | SORT_FLAG_CASE);

        if ($prependNone) {
            $customers = $customers->prepend(__('lang_v1.none'), '');
        }

        return $customers;
    }

    /**
     * The module's own VAT contacts.
     *
     * Keyed by the linked contact_id where there is one, so a customer held in
     * both tables appears once. Falling back to the vat_contacts id keeps a
     * VAT-only contact selectable.
     */
    protected function vatContacts(int $businessId, bool $appendId): array
    {
        if (! Schema::hasTable('vat_contacts')) {
            return [];
        }

        $rows = DB::table('vat_contacts')
            ->where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->where('active', 1)
            ->select(['id', 'contact_id', 'name', 'vat_no'])
            ->get();

        $customers = [];

        foreach ($rows as $row) {
            $key = ! empty($row->contact_id) ? (int) $row->contact_id : (int) $row->id;

            $customers[$key] = $this->label($row->name, $appendId ? $row->vat_no : null);
        }

        return $customers;
    }

    /**
     * Customers from the shared contacts table, for a business running the
     * Customers module.
     *
     * Reproduces what App\Contact::customersDropdown() returned - active
     * contacts of type customer or both - without importing it. `contact_id`
     * there is the customer's reference code, not a foreign key, which is why it
     * is used as the label suffix rather than as the array key.
     */
    protected function coreContacts(int $businessId, bool $appendId): array
    {
        if (! Schema::hasTable('contacts')) {
            return [];
        }

        $query = DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->where('active', 1);

        if (Schema::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $rows = $query->select(['id', 'name', 'contact_id'])->get();

        $customers = [];

        foreach ($rows as $row) {
            $customers[(int) $row->id] = $this->label($row->name, $appendId ? $row->contact_id : null);
        }

        return $customers;
    }

    /**
     * "Name (reference)", or just the name when there is no reference.
     */
    protected function label(?string $name, ?string $reference): string
    {
        $name = trim((string) $name);
        $reference = trim((string) $reference);

        return $reference === '' ? $name : $name . ' (' . $reference . ')';
    }

    /**
     * Is the Customers module available to this installation?
     */
    public function customersModuleEnabled(): bool
    {
        try {
            if (! app()->bound('modules')) {
                return false;
            }

            $manager = app('modules');

            // Asked first: isEnabled() throws for an unknown module, and
            // reporting that writes "Module [Customers] does not exist!" to the
            // tenant log on every page load.
            if (method_exists($manager, 'has') && ! $manager->has('Customers')) {
                return false;
            }

            if (method_exists($manager, 'isEnabled')) {
                return (bool) $manager->isEnabled('Customers');
            }

            if (method_exists($manager, 'find')) {
                $module = $manager->find('Customers');

                if ($module && method_exists($module, 'isEnabled')) {
                    return (bool) $module->isEnabled();
                }
            }
        } catch (Throwable $exception) {
            // An installation without the module manager is not an error here.
        }

        return false;
    }
}
