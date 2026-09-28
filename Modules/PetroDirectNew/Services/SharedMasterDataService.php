<?php

namespace Modules\PetroDirectNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroDirectNew\Support\BusinessContext;

class SharedMasterDataService
{
    private array $columnCache = [];

    public function __construct(private BusinessContext $context) {}

    private function tableColumns(string $table): array
    {
        if (!array_key_exists($table, $this->columnCache)) {
            $this->columnCache[$table] = Schema::hasTable($table)
                ? array_flip(Schema::getColumnListing($table))
                : [];
        }
        return $this->columnCache[$table];
    }

    private function columns(string $table, array $preferred): array
    {
        $available = $this->tableColumns($table);
        return array_values(array_filter($preferred, fn ($column) => isset($available[$column])));
    }

    public function locations(): Collection
    {
        $available = $this->tableColumns('business_locations');
        if ($available === []) return collect();
        $query = DB::table('business_locations')->where('business_id', $this->context->requireBusiness());
        $permitted = $this->context->permittedLocationIds();
        if ($permitted !== []) $query->whereIn('id', $permitted);
        $columns = $this->columns('business_locations', ['id','name','location_id']);
        return $query->orderBy(isset($available['name']) ? 'name' : 'id')->get($columns ?: ['id']);
    }

    public function products(): Collection
    {
        $available = $this->tableColumns('products');
        if ($available === []) return collect();
        $columns = $this->columns('products', ['id','name','sku']);
        $q = DB::table('products');
        if (isset($available['business_id'])) $q->where('business_id', $this->context->requireBusiness());
        return $q->orderBy(isset($available['name']) ? 'name' : 'id')->limit(2000)->get($columns ?: ['id']);
    }

    public function contacts(): Collection
    {
        $available = $this->tableColumns('contacts');
        if ($available === []) return collect();
        $columns = $this->columns('contacts', ['id','name','contact_id','mobile']);
        $q = DB::table('contacts');
        if (isset($available['business_id'])) $q->where('business_id', $this->context->requireBusiness());
        if (isset($available['is_inactive'])) $q->where('is_inactive', 0);
        return $q->orderBy(isset($available['name']) ? 'name' : 'id')->limit(3000)->get($columns ?: ['id']);
    }

    public function accounts(): Collection
    {
        $available = $this->tableColumns('accounts');
        if ($available === []) return collect();
        $columns = $this->columns('accounts', ['id','name','account_number']);
        $q = DB::table('accounts');
        if (isset($available['business_id'])) $q->where('business_id', $this->context->requireBusiness());
        return $q->orderBy(isset($available['name']) ? 'name' : 'id')->get($columns ?: ['id']);
    }

    public function users(): Collection
    {
        $available = $this->tableColumns('users');
        if ($available === []) return collect();
        $columns = $this->columns('users', ['id','first_name','last_name','username','email']);
        $q = DB::table('users');
        if (isset($available['business_id'])) $q->where('business_id', $this->context->requireBusiness());
        $order = isset($available['first_name']) ? 'first_name' : (isset($available['username']) ? 'username' : 'id');
        return $q->orderBy($order)->get($columns ?: ['id']);
    }

    public function searchUsers(string $search = '', int $limit = 30): Collection
    {
        $available = $this->tableColumns('users');
        if ($available === []) return collect();

        $columns = $this->columns('users', ['id','first_name','last_name','username','email']);
        $query = DB::table('users');
        if (isset($available['business_id'])) {
            $query->where('business_id', $this->context->requireBusiness());
        }

        $search = trim($search);
        if ($search !== '') {
            $searchable = array_values(array_filter(
                ['first_name', 'last_name', 'username', 'email'],
                fn ($column) => isset($available[$column])
            ));
            if ($searchable !== []) {
                $query->where(function ($builder) use ($search, $searchable) {
                    foreach ($searchable as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $builder->{$method}($column, 'like', '%' . $search . '%');
                    }
                });
            }
        }

        $order = isset($available['first_name']) ? 'first_name' : (isset($available['username']) ? 'username' : 'id');
        return $query->orderBy($order)->limit(max(1, min($limit, 50)))->get($columns ?: ['id']);
    }
}
