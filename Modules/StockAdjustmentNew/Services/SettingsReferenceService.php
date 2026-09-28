<?php

namespace Modules\StockAdjustmentNew\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsReferenceService
{
    /** @var array<string, array<int, string>> */
    private array $columns = [];

    /**
     * @return array<int, array{id:int,name:string,parent_id:?int}>
     */
    public function categories(?int $businessId): array
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }

        $id = $this->firstColumn('categories', ['id', 'category_id']);
        $name = $this->firstColumn('categories', ['name', 'category_name', 'title']);
        $business = $this->firstColumn('categories', ['business_id']);
        $parent = $this->firstColumn('categories', ['parent_id', 'parent_category_id']);
        $deleted = $this->firstColumn('categories', ['deleted_at']);

        if ($id === null || $name === null) {
            return [];
        }

        $query = DB::table('categories');
        $this->applyBusiness($query, $business, $businessId);

        if ($deleted !== null) {
            $query->whereNull($deleted);
        }

        $rows = $query->select([
            $id . ' as id',
            $name . ' as name',
            $parent !== null ? $parent . ' as parent_id' : DB::raw('NULL as parent_id'),
        ])->whereNotNull($name)
            ->whereRaw('TRIM(' . $name . ") <> ''")
            ->orderBy($name)
            ->get();

        // Older tenant databases do not use one consistent category activity
        // flag. Keep the direct tenant query permissive so valid categories do
        // not disappear from the mapping and adjustment forms.
        if ($rows->isEmpty() && class_exists('App\\Category') && method_exists('App\\Category', 'forDropdown')) {
            try {
                $dropdown = \App\Category::forDropdown($businessId);
                if ($dropdown instanceof \Illuminate\Support\Collection) {
                    $rows = $dropdown->map(static function ($label, $key) {
                        return (object) [
                            'id' => $key,
                            'name' => $label,
                            'parent_id' => null,
                        ];
                    })->values();
                }
            } catch (\Throwable $exception) {
                // Direct tenant-table query remains the primary path.
            }
        }

        return $rows->map(static function ($row): array {
            $parentId = $row->parent_id !== null ? (int) $row->parent_id : null;

            return [
                'id' => (int) $row->id,
                'name' => trim((string) $row->name),
                'parent_id' => $parentId !== null && $parentId > 0 ? $parentId : null,
            ];
        })->filter(static fn (array $row): bool => $row['id'] > 0 && $row['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string,group_id:?int,code:string}>
     */
    public function accounts(?int $businessId): array
    {
        $table = $this->firstTable(['accounts', 'finance_accounts']);
        if ($table === null) {
            return [];
        }

        $id = $this->firstColumn($table, ['id', 'account_id']);
        $name = $this->firstColumn($table, ['name', 'account_name', 'title']);
        $code = $this->firstColumn($table, ['account_number', 'account_code', 'code']);
        $business = $this->firstColumn($table, ['business_id']);
        $group = $this->firstColumn($table, ['account_group_id', 'group_id', 'account_type_id']);
        $active = $this->firstColumn($table, ['is_active', 'active', 'status']);
        $deleted = $this->firstColumn($table, ['deleted_at']);

        if ($id === null || $name === null) {
            return [];
        }

        $query = DB::table($table);
        $this->applyBusiness($query, $business, $businessId);
        $this->applyActive($query, $active);
        if ($deleted !== null) {
            $query->whereNull($deleted);
        }

        return $query->select([
            $id . ' as id',
            $name . ' as name',
            $group !== null ? $group . ' as group_id' : DB::raw('NULL as group_id'),
            $code !== null ? $code . ' as code' : DB::raw("'' as code"),
        ])->orderBy($name)->get()->map(static function ($row): array {
            return [
                'id' => (int) $row->id,
                'name' => trim((string) $row->name),
                'group_id' => $row->group_id !== null ? (int) $row->group_id : null,
                'code' => trim((string) ($row->code ?? '')),
            ];
        })->filter(static fn (array $row): bool => $row['id'] > 0 && $row['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function accountGroups(?int $businessId): array
    {
        $table = $this->firstTable(['account_groups', 'finance_account_groups', 'account_types']);
        if ($table === null) {
            return [];
        }

        $id = $this->firstColumn($table, ['id', 'account_group_id', 'account_type_id']);
        $name = $this->firstColumn($table, ['name', 'group_name', 'account_type', 'title']);
        $business = $this->firstColumn($table, ['business_id']);
        $active = $this->firstColumn($table, ['is_active', 'active', 'status']);
        $deleted = $this->firstColumn($table, ['deleted_at']);

        if ($id === null || $name === null) {
            return [];
        }

        $query = DB::table($table);
        $this->applyBusiness($query, $business, $businessId);
        $this->applyActive($query, $active);
        if ($deleted !== null) {
            $query->whereNull($deleted);
        }

        return $query->select([$id . ' as id', $name . ' as name'])
            ->orderBy($name)
            ->get()
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'name' => trim((string) $row->name),
            ])->filter(static fn (array $row): bool => $row['id'] > 0 && $row['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * Return business locations that can be selected by the current user.
     * A null permitted list means all business locations are allowed.
     *
     * @param array<int, int>|null $permittedLocationIds
     * @return array<int, array{id:int,name:string}>
     */
    public function locations(?int $businessId, ?array $permittedLocationIds = null): array
    {
        $table = $this->firstTable(['business_locations', 'locations']);
        if ($table === null) {
            return [];
        }

        $id = $this->firstColumn($table, ['id', 'location_id']);
        $name = $this->firstColumn($table, ['name', 'location_name', 'title']);
        $business = $this->firstColumn($table, ['business_id']);
        $active = $this->firstColumn($table, ['is_active', 'active', 'status']);
        $deleted = $this->firstColumn($table, ['deleted_at']);

        if ($id === null || $name === null) {
            return [];
        }

        if (is_array($permittedLocationIds) && $permittedLocationIds === []) {
            return [];
        }

        $query = DB::table($table);
        $this->applyBusiness($query, $business, $businessId);
        $this->applyActive($query, $active);
        if ($deleted !== null) {
            $query->whereNull($deleted);
        }
        if (is_array($permittedLocationIds)) {
            $query->whereIn($id, $permittedLocationIds);
        }

        return $query->select([$id . ' as id', $name . ' as name'])
            ->whereNotNull($name)
            ->orderBy($name)
            ->get()
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'name' => trim((string) $row->name),
            ])->filter(static fn (array $row): bool => $row['id'] > 0 && $row['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string,location_id:?int}>
     */
    public function stores(?int $businessId): array
    {
        $table = $this->firstTable(['stores', 'business_stores', 'store_locations']);
        if ($table === null) {
            return [];
        }

        $id = $this->firstColumn($table, ['id', 'store_id']);
        $name = $this->firstColumn($table, ['name', 'store_name', 'title']);
        $business = $this->firstColumn($table, ['business_id']);
        $location = $this->firstColumn($table, ['location_id', 'business_location_id']);
        $active = $this->firstColumn($table, ['is_active', 'active', 'status']);
        $deleted = $this->firstColumn($table, ['deleted_at']);

        if ($id === null || $name === null) {
            return [];
        }

        $query = DB::table($table);
        $this->applyBusiness($query, $business, $businessId);
        $this->applyActive($query, $active);
        if ($deleted !== null) {
            $query->whereNull($deleted);
        }

        return $query->select([
            $id . ' as id',
            $name . ' as name',
            $location !== null ? $location . ' as location_id' : DB::raw('NULL as location_id'),
        ])->whereNotNull($name)
            ->orderBy($name)
            ->get()
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'name' => trim((string) $row->name),
                'location_id' => $row->location_id !== null ? (int) $row->location_id : null,
            ])->filter(static fn (array $row): bool => $row['id'] > 0 && $row['name'] !== '')
            ->values()
            ->all();
    }

    public function locationBelongsToBusiness(int $locationId, int $businessId, ?array $permittedLocationIds = null): bool
    {
        foreach ($this->locations($businessId, $permittedLocationIds) as $location) {
            if ((int) $location['id'] === $locationId) {
                return true;
            }
        }

        return false;
    }

    public function storeBelongsToBusiness(int $storeId, int $businessId, ?int $locationId = null): bool
    {
        foreach ($this->stores($businessId) as $store) {
            if ((int) $store['id'] !== $storeId) {
                continue;
            }

            return $locationId === null
                || $store['location_id'] === null
                || (int) $store['location_id'] === $locationId;
        }

        return false;
    }

    public function categoryBelongsToBusiness(int $categoryId, int $businessId): bool
    {
        return collect($this->categories($businessId))->contains(
            static fn (array $category): bool => (int) $category['id'] === $categoryId
        );
    }

    public function accountBelongsToBusiness(int $accountId, int $businessId): bool
    {
        return collect($this->accounts($businessId))->contains(
            static fn (array $account): bool => (int) $account['id'] === $accountId
        );
    }

    public function accountGroupBelongsToBusiness(int $groupId, int $businessId): bool
    {
        return collect($this->accountGroups($businessId))->contains(
            static fn (array $group): bool => (int) $group['id'] === $groupId
        );
    }

    private function applyBusiness(Builder $query, ?string $column, ?int $businessId): void
    {
        if ($column !== null && $businessId !== null) {
            $query->where($column, $businessId);
        }
    }

    private function applyActive(Builder $query, ?string $column): void
    {
        if ($column === null) {
            return;
        }

        if ($column === 'status') {
            $query->where(function (Builder $builder) use ($column): void {
                $builder->whereNull($column)
                    ->orWhereIn($column, ['active', 'Active', 'enabled', 'Enabled', '1', 1]);
            });
            return;
        }

        $query->where(function (Builder $builder) use ($column): void {
            $builder->whereNull($column)->orWhere($column, 1);
        });
    }

    private function firstTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function firstColumn(string $table, array $candidates): ?string
    {
        if (! isset($this->columns[$table])) {
            $this->columns[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $this->columns[$table], true)) {
                return $candidate;
            }
        }

        return null;
    }
}
