<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\CustomerReferenceFuelType;

/**
 * Task 8046 - builds the Fuel Type dropdown.
 *
 * WHAT THE SPEC ASKS FOR
 *   "All the Product subcategories to show which are linked with the Fuel
 *    product Category", plus a system default option "Not Known".
 *
 * HOW THE FUEL CATEGORY IS IDENTIFIED
 *   Product categories live in the shared `categories` table, where a
 *   sub-category is a row whose parent_id points at its parent category. There
 *   is no flag on that table saying "this one is fuel", so the parent has to be
 *   identified some other way.
 *
 *   This service resolves it in three steps, first match wins:
 *
 *     1. config('customers.fuel_category_id') - an explicit id. Use this on any
 *        tenant whose fuel category is not literally called "Fuel".
 *     2. config('customers.fuel_category_names') - a list of names to match
 *        case-insensitively. Defaults to Fuel / Fuels / Fuel Products.
 *     3. Nothing matched - the dropdown contains only "Not Known".
 *
 *   Step 3 is a deliberate outcome rather than an error. A tenant that does not
 *   sell fuel should still be able to record vehicle references, and the page
 *   must not break because a category is missing. The UI tells the user why the
 *   list is short instead of silently showing an empty dropdown.
 *
 * SCHEMA TOLERANCE
 *   Every column touched here is checked with Schema::hasColumn first. The
 *   Customers module already installs onto tenants at different schema
 *   versions, so a hard reference to a column that may not exist would take the
 *   whole page down.
 */
class CustomerReferenceFuelTypeService
{
    protected const CATEGORY_TABLE = 'categories';

    /**
     * Per-request cache. The dropdown is rendered on the list page, in the add
     * popup and in the filter bar, so the same lookup is asked for repeatedly
     * within one request.
     */
    protected array $cache = [];

    /**
     * Options for a select control, keyed by posted value.
     *
     * Always contains the "Not Known" default as the first entry.
     *
     * @return array<string, string>
     */
    public function options(int $businessId): array
    {
        $options = [CustomerReferenceFuelType::NOT_KNOWN_VALUE => CustomerReferenceFuelType::NOT_KNOWN_LABEL];

        foreach ($this->subCategories($businessId) as $row) {
            $options[(string) $row->id] = $row->name;
        }

        return $options;
    }

    /**
     * The product sub-categories under the Fuel category.
     *
     * @return \Illuminate\Support\Collection
     */
    public function subCategories(int $businessId)
    {
        $key = 'sub_' . $businessId;
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $empty = collect();

        if (! Schema::hasTable(self::CATEGORY_TABLE) || ! Schema::hasColumn(self::CATEGORY_TABLE, 'parent_id')) {
            return $this->cache[$key] = $empty;
        }

        $parentId = $this->fuelCategoryId($businessId);
        if (empty($parentId)) {
            return $this->cache[$key] = $empty;
        }

        $query = DB::table(self::CATEGORY_TABLE)
            ->where('parent_id', $parentId)
            ->select('id', 'name');

        $this->applyCommonCategoryConstraints($query, $businessId);

        return $this->cache[$key] = $query->orderBy('name')->get();
    }

    /**
     * Resolve the id of the Fuel product category, or null.
     */
    public function fuelCategoryId(int $businessId): ?int
    {
        $key = 'parent_' . $businessId;
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $configured = config('customers.fuel_category_id');
        if (! empty($configured) && is_numeric($configured)) {
            return $this->cache[$key] = (int) $configured;
        }

        if (! Schema::hasTable(self::CATEGORY_TABLE)) {
            return $this->cache[$key] = null;
        }

        $names = config('customers.fuel_category_names', ['Fuel', 'Fuels', 'Fuel Products']);
        if (! is_array($names) || empty($names)) {
            $names = ['Fuel'];
        }

        $query = DB::table(self::CATEGORY_TABLE)->select('id');

        // Top-level categories only: a sub-category cannot itself be the parent
        // we are looking for.
        if (Schema::hasColumn(self::CATEGORY_TABLE, 'parent_id')) {
            $query->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            });
        }

        $query->where(function ($q) use ($names) {
            foreach ($names as $name) {
                $q->orWhereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $name))]);
            }
        });

        $this->applyCommonCategoryConstraints($query, $businessId);

        $row = $query->first();

        return $this->cache[$key] = $row ? (int) $row->id : null;
    }

    /**
     * Display name for a stored fuel_type_id.
     *
     * Returns null when the id no longer resolves, which lets the caller fall
     * back to the name snapshot stored on the reference row.
     */
    public function nameFor(int $businessId, ?int $fuelTypeId): ?string
    {
        if (empty($fuelTypeId)) {
            return null;
        }

        $match = $this->subCategories($businessId)->firstWhere('id', $fuelTypeId);

        return $match ? $match->name : null;
    }

    /**
     * Is a posted fuel type id actually one of the Fuel sub-categories?
     *
     * Used by validation so a crafted request cannot attach an arbitrary
     * category id - or another tenant's category - to a reference.
     */
    public function isValidFuelTypeId(int $businessId, $fuelTypeId): bool
    {
        if (! is_numeric($fuelTypeId)) {
            return false;
        }

        return $this->subCategories($businessId)->contains('id', (int) $fuelTypeId);
    }

    /**
     * True when the tenant has a usable Fuel category configured.
     *
     * The views use this to explain an otherwise puzzling one-item dropdown.
     */
    public function isConfigured(int $businessId): bool
    {
        return $this->subCategories($businessId)->isNotEmpty();
    }

    /**
     * Tenant scoping and soft-delete handling shared by both lookups.
     *
     * `categories` is business-scoped in this ERP, so without the business_id
     * filter one tenant would see another tenant's fuel types.
     */
    protected function applyCommonCategoryConstraints($query, int $businessId): void
    {
        if (Schema::hasColumn(self::CATEGORY_TABLE, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        if (Schema::hasColumn(self::CATEGORY_TABLE, 'category_type')) {
            /*
             * NULL counts as a product category.
             *
             * category_type distinguishes product categories from other kinds,
             * but it is only populated on tenants where something has written
             * it. Rows created before the column existed - or through screens
             * that never set it - carry NULL while still being product
             * categories in every practical sense. Filtering on
             * category_type = 'product' alone therefore hides a perfectly
             * valid Fuel category, which is exactly what happened on the first
             * tenant this shipped to.
             */
            $query->where(function ($categoryTypeQuery) {
                $categoryTypeQuery->where('category_type', 'product')
                    ->orWhereNull('category_type');
            });
        }

        if (Schema::hasColumn(self::CATEGORY_TABLE, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
    }
}
