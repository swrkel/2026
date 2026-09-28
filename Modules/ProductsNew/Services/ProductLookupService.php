<?php

namespace Modules\ProductsNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductLookupService
{
    public function __construct(
        protected ProductsNewTenantGuard $guard,
        protected ProductStatusService $status
    )
    {
    }

    public function formLookups(): array
    {
        return [
            /*
             | Whether to offer "Show in Pumper Dashboard" on the product form.
             |
             | The permission lives in Super Admin > Manage New > Product New and
             | is stored in the business package details. A business that does
             | not run a pumper dashboard has no use for the field.
             |
             | Read here rather than in each controller: formLookups() is what
             | every product form calls, so one place decides it.
            */
            'show_pumper_dashboard_field' => $this->pumperDashboardEnabled(),

            'categories' => $this->categories(false),
            'subCategories' => $this->categories(true),
            'brands' => $this->listFrom('brands', ['id', 'name']),
            'units' => $this->listFrom('units', ['id', 'actual_name', 'short_name']),
            'taxRates' => $this->listFrom('tax_rates', ['id', 'name', 'amount']),
            /*
             * MA-002: how many decimals the calculated figures are shown to.
             *
             * Comes from Settings > Business Settings > Business > Currency
             * Precision, per business - it is 2 on most and 3 on at least one,
             * so it cannot be hard coded.
             */
            'currencyPrecision' => $this->currencyPrecision(),
            'warranties' => $this->listFrom('warranties', ['id', 'name']),
            'locations' => $this->listFrom('business_locations', ['id', 'name']),
            'stores' => $this->stores(),
            'stockAccounts' => $this->stockAccounts(),
            // All current module forms use the shared core product master.
            // Inactive products are excluded by products().
            'productsNew' => $this->products(),
        ];
    }

    /**
     * Existing values required by the Products New create/edit wizard.
     */
    public function productFormState(int $productId): array
    {
        $pricing = [
            'single_dpp' => null,
            'single_dpp_inc_tax' => null,
            'profit_percent' => null,
            'profit_basis' => null,
            'single_dsp' => null,
            'single_dsp_inc_tax' => null,
        ];

        if (Schema::hasTable('variations')) {
            $priceColumns = collect([
                'default_purchase_price',
                'dpp_inc_tax',
                'profit_percent',
                'default_sell_price',
                'sell_price_inc_tax',
            ])->filter(fn (string $column): bool => Schema::hasColumn('variations', $column))->values()->all();

            $variation = $priceColumns === []
                ? null
                : DB::table('variations')
                    ->where('product_id', $productId)
                    ->when(
                        Schema::hasColumn('variations', 'deleted_at'),
                        fn ($query) => $query->whereNull('deleted_at')
                    )
                    ->orderBy('id')
                    ->first($priceColumns);

            if ($variation) {
                $pricing = [
                    'single_dpp' => $variation->default_purchase_price ?? null,
                    'single_dpp_inc_tax' => $variation->dpp_inc_tax ?? null,
                    'profit_percent' => $variation->profit_percent ?? null,
                    'profit_basis' => null,
                    'single_dsp' => $variation->default_sell_price ?? null,
                    'single_dsp_inc_tax' => $variation->sell_price_inc_tax ?? null,
                ];
            }
        }

        /*
         * IS2212 - remember "Profit Percentage On" reliably.
         *
         * Some tenant databases were created before products.profit_basis was added,
         * so the form could accept "Tax Inclusive" but clean() correctly discarded
         * the key because the core column did not exist. On the next edit the Blade
         * fallback therefore showed Exclusive.
         *
         * Products New already owns products_new_product_meta.settings, so use that
         * as the compatibility store. If the core column exists we still read it.
         * For an older VAT-inclusive product with no explicit saved basis, Inclusive
         * is the safest compatibility inference and matches the way the current form
         * creates VAT-inclusive products.
         */
        $coreProfitBasis = null;
        $taxType = 'exclusive';

        if (Schema::hasTable('products')) {
            $productColumns = ['tax_type'];

            if (Schema::hasColumn('products', 'profit_basis')) {
                $productColumns[] = 'profit_basis';
            }

            $productRow = DB::table('products')
                ->where('id', $productId)
                ->first($productColumns);

            if ($productRow) {
                $taxType = strtolower(trim((string) ($productRow->tax_type ?? 'exclusive')));
                $candidate = strtolower(trim((string) ($productRow->profit_basis ?? '')));

                if (in_array($candidate, ['exclusive', 'inclusive'], true)) {
                    $coreProfitBasis = $candidate;
                }
            }
        }

        $metaProfitBasis = null;

        if (
            Schema::hasTable('products_new_product_meta')
            && Schema::hasColumn('products_new_product_meta', 'settings')
        ) {
            $metaQuery = DB::table('products_new_product_meta')
                ->where('product_id', $productId);

            if (Schema::hasColumn('products_new_product_meta', 'business_id')) {
                $metaQuery->where('business_id', $this->guard->businessId());
            }

            $settingsRaw = $metaQuery->value('settings');

            if (is_string($settingsRaw) && trim($settingsRaw) !== '') {
                $settings = json_decode($settingsRaw, true);

                if (is_array($settings)) {
                    $candidate = strtolower(trim((string) ($settings['profit_basis'] ?? '')));

                    if (in_array($candidate, ['exclusive', 'inclusive'], true)) {
                        $metaProfitBasis = $candidate;
                    }
                }
            } elseif (is_array($settingsRaw)) {
                $candidate = strtolower(trim((string) ($settingsRaw['profit_basis'] ?? '')));

                if (in_array($candidate, ['exclusive', 'inclusive'], true)) {
                    $metaProfitBasis = $candidate;
                }
            }
        }

        /*
         * IS2215 - resolution order.
         *
         * An explicit module-meta value is strongest because it is written only
         * when the operator actually saves the selector. The core column is next.
         * There is one legacy ambiguity: the original migration gave every old
         * row a DEFAULT 'exclusive'. For a VAT-inclusive product that has no
         * explicit meta value, that default must not make Edit Pricing reopen as
         * Exclusive. Treat the tax type as the compatibility signal in that case.
         *
         * After the product is saved by the corrected code, metaProfitBasis is
         * present and therefore preserves an explicitly chosen Exclusive value
         * even on a VAT-inclusive product.
         */
        if ($metaProfitBasis !== null) {
            $pricing['profit_basis'] = $metaProfitBasis;
        } elseif ($taxType === 'inclusive' && ($coreProfitBasis === null || $coreProfitBasis === 'exclusive')) {
            $pricing['profit_basis'] = 'inclusive';
        } else {
            $pricing['profit_basis'] = $coreProfitBasis ?? 'exclusive';
        }

        $locations = [];
        if (Schema::hasTable('product_locations')) {
            $locations = DB::table('product_locations')
                ->where('product_id', $productId)
                ->pluck('location_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return compact('pricing', 'locations');
    }

    public function productsNew(): Collection
    {
        if (!Schema::hasTable('products')) {
            return collect();
        }

        $columns = array_values(array_filter(
            ['id', 'name', 'sku'],
            fn (string $column): bool => Schema::hasColumn('products', $column)
        ));

        if ($columns === []) {
            return collect();
        }

        $q = DB::table('products')->select($columns);
        if (Schema::hasColumn('products', 'business_id')) {
            $this->guard->applyBusiness($q, 'products.business_id');
        }

        return $q->orderBy(Schema::hasColumn('products', 'name') ? 'name' : 'id')->limit(1000)->get();
    }

    public function products($request = null): Collection
    {
        if (!Schema::hasTable('products')) {
            return collect();
        }

        $columns = array_values(array_filter(
            ['id', 'name', 'sku', 'barcode_type'],
            fn (string $column): bool => Schema::hasColumn('products', $column)
        ));

        if ($columns === []) {
            return collect();
        }

        $q = DB::table('products')->select($columns);
        if (Schema::hasColumn('products', 'business_id')) {
            $this->guard->applyBusiness($q, 'products.business_id');
        }

        $this->status->applyActiveOnly($q, 'products');

        if (Schema::hasColumn('products', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return $q->orderBy(Schema::hasColumn('products', 'name') ? 'name' : 'id')->limit(500)->get();
    }

    protected function categories(bool $subCategoriesOnly): Collection
    {
        if (!Schema::hasTable('categories') || !Schema::hasColumn('categories', 'id')) {
            return collect();
        }

        $columns = ['id'];
        foreach (['name', 'short_code', 'parent_id'] as $column) {
            if (Schema::hasColumn('categories', $column)) {
                $columns[] = $column;
            }
        }

        $query = DB::table('categories')->select($columns);

        if (Schema::hasColumn('categories', 'business_id')) {
            $this->guard->applyBusiness($query, 'categories.business_id');
        }

        if (Schema::hasColumn('categories', 'parent_id')) {
            if ($subCategoriesOnly) {
                $query->whereRaw('COALESCE(parent_id, 0) > 0');
            } else {
                $query->whereRaw('COALESCE(parent_id, 0) = 0');
            }
        } elseif ($subCategoriesOnly) {
            return collect();
        }

        if (Schema::hasColumn('categories', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy(Schema::hasColumn('categories', 'name') ? 'name' : 'id')->get();
    }

    protected function stockAccounts(): Collection
    {
        if (!Schema::hasTable('accounts') || !Schema::hasColumn('accounts', 'id')) {
            return collect();
        }

        $columns = ['id'];
        if (Schema::hasColumn('accounts', 'name')) {
            $columns[] = 'name';
        }

        $query = DB::table('accounts')->select($columns);

        if (Schema::hasColumn('accounts', 'business_id')) {
            $this->guard->applyBusiness($query, 'accounts.business_id');
        }

        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where(function ($where): void {
                $where->whereNull('is_closed')->orWhere('is_closed', 0);
            });
        }

        if (Schema::hasColumn('accounts', 'name')) {
            $query->whereIn('name', ['Stock Account', 'Raw Material Account', 'Finished Goods Account'])
                ->orderBy('name');
        } else {
            $query->orderBy('id');
        }

        return $query->get();
    }

    /**
     * MA-002: the business currency precision, defaulting to 2.
     *
     * Stored as a varchar, so it is cast and clamped rather than trusted -
     * a stray value must not produce a nonsensical number of decimals.
     */
    protected function currencyPrecision(): int
    {
        $value = \DB::table('business')
            ->where('id', ProductsNewTenantGuard::businessId())
            ->value('currency_precision');

        $precision = is_numeric($value) ? (int) $value : 2;

        return max(0, min(6, $precision));
    }

    protected function stores(): Collection
    {
        if (!Schema::hasTable('stores')) {
            return collect();
        }

        $columns = array_values(array_filter(
            ['id', 'name', 'location_id'],
            fn (string $column): bool => Schema::hasColumn('stores', $column)
        ));

        if (!in_array('id', $columns, true) || !in_array('location_id', $columns, true)) {
            return collect();
        }

        $q = DB::table('stores')->select($columns);
        if (Schema::hasColumn('stores', 'business_id')) {
            $this->guard->applyBusiness($q, 'stores.business_id');
        }

        if (Schema::hasColumn('stores', 'status')) {
            $q->where('status', 1);
        }

        return $q->orderBy('location_id')
            ->orderBy(Schema::hasColumn('stores', 'name') ? 'name' : 'id')
            ->get();
    }

    protected function listFrom(string $table, array $columns, array $where = [], bool $subCategoriesOnly = false): Collection
    {
        if (!Schema::hasTable($table)) {
            return collect();
        }

        $availableColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($table, $column)
        ));

        if ($availableColumns === []) {
            return collect();
        }

        $q = DB::table($table)->select($availableColumns);

        if (Schema::hasColumn($table, 'business_id')) {
            $this->guard->applyBusiness($q, $table . '.business_id');
        }

        foreach ($where as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $q->where($column, $value);
            }
        }

        if ($subCategoriesOnly && Schema::hasColumn($table, 'parent_id')) {
            $q->whereRaw('COALESCE(parent_id, 0) > 0');
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        $orderColumn = $availableColumns[1] ?? $availableColumns[0];

        return $q->orderBy($orderColumn)->get();
    }

    /**
     * Is the Product New "Show in Pumper Dashboard" field enabled for this business?
     *
     * IS2193 requires the field to follow the Product New permission in
     * Super Admin > All Businesses > Manage New.  Do not fall back to a
     * different/legacy pumper permission here: if Manage New disables this
     * Product New permission, Add/Edit Product must hide the second-level field.
     */
    protected function pumperDashboardEnabled(): bool
    {
        try {
            $businessId = (int) (session('business.id') ?: session('user.business_id') ?: 0);
            if ($businessId <= 0) {
                return false;
            }

            // Match Manage New: read the currently active approved subscription,
            // not simply the newest approved row (which can be future/expired).
            $subscription = \Modules\Superadmin\Entities\Subscription::active_subscription($businessId);
            if (! $subscription) {
                return false;
            }

            $details = $subscription->package_details;
            if (is_string($details)) {
                $details = json_decode($details, true) ?: [];
            }
            $details = (array) $details;

            $value = $details['products_pumper_dashboard_default'] ?? 0;
            if (is_string($value)) {
                $value = strtolower(trim($value));
            }

            return in_array($value, [1, '1', true, 'true', 'yes', 'on', 'enabled'], true);
        } catch (\Throwable $e) {
            // Keep Product New usable if the optional Superadmin subscription
            // lookup is unavailable; the optional field will simply stay hidden.
            return false;
        }
    }
}
