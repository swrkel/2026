<?php

namespace Modules\ProductsNew\Services\Settings;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductSettingLookupService
{
    public function businessId(): int
    {
        return (int) (session('business.id')
            ?? request()->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? 0);
    }

    public function categories(array $filters = [])
    {
        if (!Schema::hasTable('categories')) {
            return new LengthAwarePaginator([], 0, 25, 1, ['path' => request()->url(), 'query' => request()->query()]);
        }

        $columns = Schema::getColumnListing('categories');
        $query = DB::table('categories as cat');

        if (in_array('parent_id', $columns, true)) {
            $query->leftJoin('categories as parent', 'cat.parent_id', '=', 'parent.id');
        }
        if (Schema::hasTable('accounts') && in_array('cogs_account_id', $columns, true)) {
            $query->leftJoin('accounts as cogs', 'cat.cogs_account_id', '=', 'cogs.id');
        }
        if (Schema::hasTable('accounts') && in_array('sales_income_account_id', $columns, true)) {
            $query->leftJoin('accounts as sales', 'cat.sales_income_account_id', '=', 'sales.id');
        }
        if (Schema::hasTable('products_new_category_profiles')) {
            $query->leftJoin('products_new_category_profiles as pncp', 'pncp.category_id', '=', 'cat.id');
        }

        if (in_array('business_id', $columns, true)) {
            $query->where('cat.business_id', $this->businessId());
        }
        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('cat.deleted_at');
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($where) use ($search, $columns): void {
                $where->where('cat.name', 'like', '%' . $search . '%');
                if (in_array('short_code', $columns, true)) {
                    $where->orWhere('cat.short_code', 'like', '%' . $search . '%');
                }
                if (in_array('parent_id', $columns, true)) {
                    $where->orWhere('parent.name', 'like', '%' . $search . '%');
                }
            });
        }

        $type = (string) ($filters['type'] ?? '');
        if ($type !== '' && in_array('parent_id', $columns, true)) {
            if ($type === 'category') {
                $query->whereRaw('COALESCE(cat.parent_id, 0) = 0');
            } elseif ($type === 'subcategory') {
                $query->whereRaw('COALESCE(cat.parent_id, 0) > 0');
            }
        }

        $vatExempted = (string) ($filters['vat_exempted'] ?? '');
        if ($vatExempted !== '' && in_array('vat_exempted', $columns, true)) {
            $query->where('cat.vat_exempted', $vatExempted);
        }

        $select = [
            'cat.id',
            'cat.name',
            $this->columnOrRaw($columns, 'short_code', "''", 'short_code'),
            $this->columnOrRaw($columns, 'parent_id', '0', 'parent_id'),
            $this->columnOrRaw($columns, 'cogs_account_id', 'NULL', 'cogs_account_id'),
            $this->columnOrRaw($columns, 'sales_income_account_id', 'NULL', 'sales_income_account_id'),
            $this->columnOrRaw($columns, 'add_related_account', 'NULL', 'add_related_account'),
            $this->columnOrRaw($columns, 'weight_excess_loss_applicable', '0', 'weight_excess_loss_applicable'),
            $this->columnOrRaw($columns, 'vat_exempted', "'No'", 'vat_exempted'),
            $this->columnOrRaw($columns, 'vat_based_on', "'sale_price'", 'vat_based_on'),
            $this->columnOrRaw($columns, 'apply_vat_on', "'on_product_sub_category_settings'", 'apply_vat_on'),
        ];

        $select[] = in_array('parent_id', $columns, true)
            ? DB::raw("COALESCE(parent.name, '') as parent_name")
            : DB::raw("'' as parent_name");
        $select[] = Schema::hasTable('accounts') && in_array('cogs_account_id', $columns, true)
            ? DB::raw("COALESCE(cogs.name, '') as cogs_name")
            : DB::raw("'' as cogs_name");
        $select[] = Schema::hasTable('accounts') && in_array('sales_income_account_id', $columns, true)
            ? DB::raw("COALESCE(sales.name, '') as sales_income_name")
            : DB::raw("'' as sales_income_name");
        $select[] = Schema::hasTable('products_new_category_profiles')
            ? DB::raw('COALESCE(pncp.category_code_is_hsn, 0) as category_code_is_hsn')
            : DB::raw('0 as category_code_is_hsn');

        return $query->select($select)
            ->orderByRaw('COALESCE(cat.parent_id, 0) ASC')
            ->orderBy('cat.name')
            ->paginate(25)
            ->appends($filters);
    }

    public function parentCategories(): Collection
    {
        if (!Schema::hasTable('categories')) {
            return collect();
        }

        $columns = Schema::getColumnListing('categories');
        $query = DB::table('categories')->select(array_values(array_intersect(['id', 'name', 'short_code'], $columns)));

        if (in_array('business_id', $columns, true)) {
            $query->where('business_id', $this->businessId());
        }
        if (in_array('parent_id', $columns, true)) {
            $query->whereRaw('COALESCE(parent_id, 0) = 0');
        }
        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('name')->get();
    }

    public function cogsAccounts(): Collection
    {
        return $this->accounts(['COGS', 'Cost of Goods', 'Cost Of Goods']);
    }

    public function salesIncomeAccounts(): Collection
    {
        return $this->accounts(['Sales', 'Income']);
    }

    public function brands(array $filters = [])
    {
        return DB::table('brands')
            ->where('business_id', $this->businessId())
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            })
            ->orderBy('name')
            ->paginate(25);
    }

    public function units(array $filters = [])
    {
        return DB::table('units')
            ->where('business_id', $this->businessId())
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($q) use ($filters) {
                $q->where(function ($qq) use ($filters) {
                    $qq->where('actual_name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('short_name', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->orderBy('actual_name')
            ->paginate(25);
    }

    public function variations(array $filters = [])
    {
        return DB::table('variation_templates')
            ->where('business_id', $this->businessId())
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            })
            ->orderBy('name')
            ->paginate(25);
    }

    private function accounts(array $needles): Collection
    {
        if (!Schema::hasTable('accounts') || !Schema::hasColumn('accounts', 'id')) {
            return collect();
        }

        $columns = Schema::getColumnListing('accounts');
        $select = array_values(array_intersect(['id', 'name'], $columns));
        if (!in_array('name', $select, true)) {
            return collect();
        }

        $base = DB::table('accounts')->select($select);
        if (in_array('business_id', $columns, true)) {
            $base->where('business_id', $this->businessId());
        }
        if (in_array('is_closed', $columns, true)) {
            $base->where(function ($where): void {
                $where->whereNull('is_closed')->orWhere('is_closed', 0);
            });
        }

        $filtered = clone $base;
        $filtered->where(function ($where) use ($needles): void {
            foreach ($needles as $index => $needle) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $where->{$method}('name', 'like', '%' . $needle . '%');
            }
        });

        $accounts = $filtered->orderBy('name')->get();
        return $accounts->isNotEmpty() ? $accounts : $base->orderBy('name')->get();
    }

    private function columnOrRaw(array $columns, string $column, string $fallback, string $alias)
    {
        return in_array($column, $columns, true)
            ? 'cat.' . $column
            : DB::raw($fallback . ' as ' . $alias);
    }
}
