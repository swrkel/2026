<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PoneSharedMasterDataService
{
    public function business(int $businessId): ?object
    {
        if (! Schema::hasTable('business') || ! Schema::hasColumn('business', 'id')) return null;

        return DB::table('business')->where('id', $businessId)->first();
    }

    public function location(?int $locationId, ?int $businessId = null): ?object
    {
        if (! $locationId || ! Schema::hasTable('business_locations') || ! Schema::hasColumn('business_locations', 'id')) return null;

        $query = DB::table('business_locations')->where('id', $locationId);
        if ($businessId && Schema::hasColumn('business_locations', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        return $query->first();
    }

    public function locations(int $businessId): Collection
    {
        if (! Schema::hasTable('business_locations') || ! Schema::hasColumn('business_locations', 'business_id')) return collect();

        $query = DB::table('business_locations')->where('business_id', $businessId);
        $this->whereActive($query, 'business_locations');

        return $query->orderBy(Schema::hasColumn('business_locations', 'name') ? 'name' : 'id')->get();
    }

    public function pumps(int $businessId, ?int $locationId = null): Collection
    {
        if (! Schema::hasTable('pumps') || ! Schema::hasColumn('pumps', 'id')) return collect();

        $query = DB::table('pumps as p');
        if (Schema::hasColumn('pumps', 'business_id')) $query->where('p.business_id', $businessId);
        if ($locationId && Schema::hasColumn('pumps', 'location_id')) $query->where('p.location_id', $locationId);
        $this->whereActive($query, 'pumps', 'p');

        $joinedProducts = Schema::hasTable('products')
            && Schema::hasColumn('pumps', 'product_id')
            && Schema::hasColumn('products', 'id');
        if ($joinedProducts) {
            $query->leftJoin('products as pr', 'pr.id', '=', 'p.product_id');
        }

        $select = [];
        foreach (['id', 'business_id', 'location_id', 'product_id', 'pump_name', 'pump_no', 'fuel_type', 'starting_meter', 'last_meter_reading', 'pod_starting_meter', 'pod_last_meter'] as $column) {
            $select[] = Schema::hasColumn('pumps', $column)
                ? 'p.' . $column
                : DB::raw('NULL AS `' . $column . '`');
        }
        $select[] = $joinedProducts && Schema::hasColumn('products', 'name')
            ? DB::raw('pr.`name` AS `product_name`')
            : DB::raw('NULL AS `product_name`');

        $query->select($select);
        if (Schema::hasColumn('pumps', 'pump_no')) $query->orderBy('p.pump_no');
        if (Schema::hasColumn('pumps', 'pump_name')) $query->orderBy('p.pump_name');
        else $query->orderBy('p.id');

        return $query->get();
    }

    public function pump(int $businessId, int $pumpId, ?int $locationId = null): ?object
    {
        return $this->pumps($businessId, $locationId)->firstWhere('id', $pumpId);
    }

    public function products(int $businessId, ?int $locationId = null, ?string $search = null, int $limit = 200): Collection
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'id')) return collect();

        $query = DB::table('products as p');
        if (Schema::hasColumn('products', 'business_id')) $query->where('p.business_id', $businessId);
        if (Schema::hasColumn('products', 'is_inactive')) $query->where('p.is_inactive', 0);
        $this->whereActive($query, 'products', 'p');

        $hasVariations = Schema::hasTable('variations')
            && Schema::hasColumn('variations', 'product_id')
            && Schema::hasColumn('products', 'id');
        if ($hasVariations) {
            $query->leftJoin('variations as v', function ($join): void {
                $join->on('v.product_id', '=', 'p.id');
                if (Schema::hasColumn('variations', 'deleted_at')) $join->whereNull('v.deleted_at');
            });
        }

        $hasLocationStock = $locationId
            && $hasVariations
            && Schema::hasTable('variation_location_details')
            && Schema::hasColumn('variation_location_details', 'variation_id')
            && Schema::hasColumn('variation_location_details', 'location_id')
            && Schema::hasColumn('variations', 'id');
        if ($hasLocationStock) {
            $query->leftJoin('variation_location_details as vld', function ($join) use ($locationId): void {
                $join->on('vld.variation_id', '=', 'v.id')->where('vld.location_id', $locationId);
            });
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $first = true;
                foreach (['name', 'sku'] as $column) {
                    if (! Schema::hasColumn('products', $column)) continue;
                    $first
                        ? $inner->where('p.' . $column, 'like', '%' . $search . '%')
                        : $inner->orWhere('p.' . $column, 'like', '%' . $search . '%');
                    $first = false;
                }
                if ($first) $inner->whereRaw('1 = 0');
            });
        }

        $nameExpression = Schema::hasColumn('products', 'name') ? 'p.`name`' : "CONCAT('Product ', p.`id`)";
        $skuExpression = Schema::hasColumn('products', 'sku') ? 'p.`sku`' : 'NULL';
        $unitPriceParts = [];
        if ($hasVariations && Schema::hasColumn('variations', 'sell_price_inc_tax')) $unitPriceParts[] = 'v.`sell_price_inc_tax`';
        if ($hasVariations && Schema::hasColumn('variations', 'default_sell_price')) $unitPriceParts[] = 'v.`default_sell_price`';
        $unitPriceExpression = $unitPriceParts
            ? 'MAX(CAST(COALESCE(' . implode(', ', $unitPriceParts) . ', 0) AS DECIMAL(22,6)))'
            : '0';
        $quantityExpression = $hasLocationStock && Schema::hasColumn('variation_location_details', 'qty_available')
            ? 'MAX(COALESCE(vld.`qty_available`, 0))'
            : '0';

        $query->selectRaw('p.`id`, ' . $nameExpression . ' AS `name`, ' . $skuExpression . ' AS `sku`, ' . $unitPriceExpression . ' AS `unit_price`, ' . $quantityExpression . ' AS `quantity_available`')
            ->groupBy('p.id');
        if (Schema::hasColumn('products', 'name')) $query->groupBy('p.name');
        if (Schema::hasColumn('products', 'sku')) $query->groupBy('p.sku');
        if (Schema::hasColumn('products', 'name')) $query->orderBy('p.name');
        else $query->orderBy('p.id');

        return $query->limit(max(1, min(10000, $limit)))->get();
    }

    public function product(int $businessId, int $productId, ?int $locationId = null): ?object
    {
        return $this->products($businessId, $locationId, null, 10000)->firstWhere('id', $productId);
    }

    public function stores(int $businessId, ?int $locationId = null): Collection
    {
        if (! Schema::hasTable('stores') || ! Schema::hasColumn('stores', 'id')) return collect();

        $query = DB::table('stores');
        if (Schema::hasColumn('stores', 'business_id')) $query->where('business_id', $businessId);
        if ($locationId && Schema::hasColumn('stores', 'location_id')) $query->where('location_id', $locationId);
        $this->whereActive($query, 'stores');

        $columns = ['id'];
        foreach (['name', 'location_id', 'business_id'] as $column) {
            if (Schema::hasColumn('stores', $column)) $columns[] = $column;
        }

        return $query->orderBy(Schema::hasColumn('stores', 'name') ? 'name' : 'id')->get($columns);
    }

    public function store(int $businessId, int $storeId, ?int $locationId = null): ?object
    {
        return $this->stores($businessId, $locationId)->firstWhere('id', $storeId);
    }

    public function tanks(int $businessId, ?int $locationId = null): Collection
    {
        if (! Schema::hasTable('fuel_tanks') || ! Schema::hasColumn('fuel_tanks', 'id')) return collect();

        $query = DB::table('fuel_tanks');
        if (Schema::hasColumn('fuel_tanks', 'business_id')) $query->where('business_id', $businessId);
        if ($locationId && Schema::hasColumn('fuel_tanks', 'location_id')) $query->where('location_id', $locationId);
        $this->whereActive($query, 'fuel_tanks');

        return $query->orderBy(Schema::hasColumn('fuel_tanks', 'fuel_tank_number') ? 'fuel_tank_number' : 'id')->get();
    }

    public function tank(int $businessId, int $tankId, ?int $locationId = null): ?object
    {
        return $this->tanks($businessId, $locationId)->firstWhere('id', $tankId);
    }

    public function customers(int $businessId, ?string $search = null, int $limit = 200, ?int $locationId = null): Collection
    {
        return $this->contacts($businessId, ['customer', 'both'], $search, $limit, $locationId);
    }

    public function customer(int $businessId, int $customerId, ?int $locationId = null): ?object
    {
        return $this->customers($businessId, null, 10000, $locationId)->firstWhere('id', $customerId);
    }

    public function suppliers(int $businessId, ?string $search = null, int $limit = 200, ?int $locationId = null): Collection
    {
        return $this->contacts($businessId, ['supplier', 'both'], $search, $limit, $locationId);
    }

    public function supplier(int $businessId, int $supplierId, ?int $locationId = null): ?object
    {
        return $this->suppliers($businessId, null, 10000, $locationId)->firstWhere('id', $supplierId);
    }

    public function accounts(int $businessId, ?string $type = null): Collection
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasColumn('accounts', 'id')) return collect();

        $query = DB::table('accounts');
        if (Schema::hasColumn('accounts', 'business_id')) $query->where('business_id', $businessId);
        if (Schema::hasColumn('accounts', 'is_closed')) $query->where('is_closed', 0);
        if (Schema::hasColumn('accounts', 'is_main_account')) $query->where('is_main_account', 0);
        $this->whereActive($query, 'accounts');
        if ($type && Schema::hasColumn('accounts', 'account_type')) $query->where('account_type', $type);

        $columns = ['id'];
        foreach (['name', 'account_number', 'account_type', 'business_id'] as $column) {
            if (Schema::hasColumn('accounts', $column)) $columns[] = $column;
        }

        return $query->orderBy(Schema::hasColumn('accounts', 'name') ? 'name' : 'id')->get($columns);
    }

    public function account(int $businessId, int $accountId, ?string $type = null): ?object
    {
        return $this->accounts($businessId, $type)->firstWhere('id', $accountId);
    }

    public function users(int $businessId): Collection
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'id')) return collect();

        $query = DB::table('users');
        if (Schema::hasColumn('users', 'business_id')) {
            $query->where('business_id', $businessId);
        } else {
            $candidateIds = $this->operatorUserIds($businessId);
            if ($candidateIds === []) return collect();
            $query->whereIn('id', $candidateIds);
        }
        $this->whereActive($query, 'users');
        if (Schema::hasColumn('users', 'deleted_at')) $query->whereNull('deleted_at');

        $columns = ['id'];
        foreach (['username', 'first_name', 'last_name', 'surname', 'business_id', 'pump_operator_id'] as $column) {
            if (Schema::hasColumn('users', $column)) $columns[] = $column;
        }

        return $query->orderBy(Schema::hasColumn('users', 'first_name') ? 'first_name' : 'id')->get($columns);
    }

    public function operator(int $businessId, int $pdOperatorId): ?object
    {
        if (! Schema::hasTable('pump_operators') || ! Schema::hasColumn('pump_operators', 'id')) return null;

        $query = DB::table('pump_operators')->where('id', $pdOperatorId);
        if (Schema::hasColumn('pump_operators', 'business_id')) $query->where('business_id', $businessId);
        $this->whereActive($query, 'pump_operators');

        return $query->first();
    }

    private function contacts(int $businessId, array $types, ?string $search, int $limit, ?int $locationId): Collection
    {
        if (! Schema::hasTable('contacts') || ! Schema::hasColumn('contacts', 'id')) return collect();

        $query = DB::table('contacts');
        if (Schema::hasColumn('contacts', 'business_id')) $query->where('business_id', $businessId);
        if (Schema::hasColumn('contacts', 'type')) $query->whereIn('type', $types);
        if ($locationId) {
            foreach (['location_id', 'business_location_id'] as $column) {
                if (! Schema::hasColumn('contacts', $column)) continue;
                $query->where(function ($inner) use ($column, $locationId): void {
                    $inner->where($column, $locationId)->orWhereNull($column);
                });
                break;
            }
        }
        $this->whereActive($query, 'contacts');
        if (Schema::hasColumn('contacts', 'deleted_at')) $query->whereNull('deleted_at');

        $search = trim((string) $search);
        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $first = true;
                foreach (['name', 'contact_id', 'mobile'] as $column) {
                    if (! Schema::hasColumn('contacts', $column)) continue;
                    $first
                        ? $inner->where($column, 'like', '%' . $search . '%')
                        : $inner->orWhere($column, 'like', '%' . $search . '%');
                    $first = false;
                }
                if ($first) $inner->whereRaw('1 = 0');
            });
        }

        $columns = ['id'];
        foreach (['name', 'contact_id', 'mobile', 'business_id', 'location_id', 'business_location_id'] as $column) {
            if (Schema::hasColumn('contacts', $column)) $columns[] = $column;
        }

        return $query->orderBy(Schema::hasColumn('contacts', 'name') ? 'name' : 'id')
            ->limit(max(1, min(10000, $limit)))->get($columns);
    }

    private function operatorUserIds(int $businessId): array
    {
        $ids = [];
        if (Schema::hasTable('pone_pd_operators') && Schema::hasColumn('pone_pd_operators', 'user_id')) {
            $query = DB::table('pone_pd_operators')->whereNotNull('user_id');
            if (Schema::hasColumn('pone_pd_operators', 'business_id')) $query->where('business_id', $businessId);
            $ids = array_merge($ids, $query->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        }
        if (Schema::hasTable('pump_operators') && Schema::hasColumn('pump_operators', 'user_id')) {
            $query = DB::table('pump_operators')->whereNotNull('user_id');
            if (Schema::hasColumn('pump_operators', 'business_id')) $query->where('business_id', $businessId);
            $ids = array_merge($ids, $query->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function whereActive(Builder $query, string $table, ?string $alias = null): void
    {
        $prefix = $alias ? $alias . '.' : '';
        if (Schema::hasColumn($table, 'status')) {
            $statusColumn = $alias ? '`' . $alias . '`.`status`' : '`status`';
            $query->whereRaw("LOWER(TRIM(CAST({$statusColumn} AS CHAR))) IN ('1','active')");
        }
        if (Schema::hasColumn($table, 'is_active')) $query->where($prefix . 'is_active', 1);
        if (Schema::hasColumn($table, 'active')) $query->where($prefix . 'active', 1);
    }
}
