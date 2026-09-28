<?php

namespace Modules\StockTakingNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MasterDataBridgeService
{
    public function locations(int $businessId): array
    {
        if (! Schema::hasTable('business_locations')) {
            return [];
        }

        $query = DB::table('business_locations')->where('business_id', $businessId);
        $permitted = app(TenantScopeService::class)->permittedLocations();
        if ($permitted !== 'all') {
            $query->whereIn('id', $permitted ?: [-1]);
        }
        if (Schema::hasColumn('business_locations', 'is_active')) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('name')->pluck('name', 'id')->all();
    }

    public function stores(int $businessId, ?int $locationId = null): array
    {
        if (! Schema::hasTable('stores')) {
            return [];
        }

        $query = DB::table('stores')->where('business_id', $businessId);
        $locationColumn = Schema::hasColumn('stores', 'business_location_id')
            ? 'business_location_id'
            : (Schema::hasColumn('stores', 'location_id') ? 'location_id' : null);

        $permitted = app(TenantScopeService::class)->permittedLocations();
        if ($locationColumn && $permitted !== 'all') {
            $query->whereIn($locationColumn, $permitted ?: [-1]);
        }
        if ($locationId && $locationColumn) {
            $query->where($locationColumn, $locationId);
        }
        if (Schema::hasColumn('stores', 'is_active')) {
            $query->where('is_active', 1);
        } elseif (Schema::hasColumn('stores', 'status')) {
            $query->where('status', 1);
        }

        return $query->orderBy('name')->pluck('name', 'id')->all();
    }

    public function users(int $businessId): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $query = DB::table('users')->where('business_id', $businessId);
        if (Schema::hasColumn('users', 'status')) {
            $query->where('status', 'active');
        }
        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $columns = ['id'];
        foreach (['first_name', 'last_name', 'username'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return $query->orderBy(Schema::hasColumn('users', 'first_name') ? 'first_name' : 'id')
            ->get($columns)
            ->mapWithKeys(function ($user): array {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                return [$user->id => ($name ?: ($user->username ?? ('User #' . $user->id)))];
            })
            ->all();
    }


    public function locationIsValid(int $businessId, int $locationId): bool
    {
        return array_key_exists($locationId, $this->locations($businessId));
    }

    public function storeIsValid(int $businessId, int $locationId, int $storeId): bool
    {
        return array_key_exists($storeId, $this->stores($businessId, $locationId));
    }

    public function categories(int $businessId): array
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }
        $query = DB::table('categories')->where('business_id', $businessId);
        if (Schema::hasColumn('categories', 'category_type')) {
            $query->where(function ($nested): void {
                $nested->whereNull('category_type')->orWhere('category_type', 'product');
            });
        }
        if (Schema::hasColumn('categories', 'parent_id')) {
            $query->whereNull('parent_id');
        }
        return $query->orderBy('name')->pluck('name', 'id')->all();
    }

    public function brands(int $businessId): array
    {
        if (! Schema::hasTable('brands')) {
            return [];
        }
        return DB::table('brands')->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')->all();
    }


    public function categoryIsValid(int $businessId, int $categoryId): bool
    {
        return array_key_exists($categoryId, $this->categories($businessId));
    }

    public function brandIsValid(int $businessId, int $brandId): bool
    {
        return array_key_exists($brandId, $this->brands($businessId));
    }

    public function locationName(?int $id): string
    {
        return $id && Schema::hasTable('business_locations')
            ? (string) (DB::table('business_locations')->where('id', $id)->value('name') ?: 'Location #' . $id)
            : 'All Locations';
    }

    public function storeName(?int $id): string
    {
        return $id && Schema::hasTable('stores')
            ? (string) (DB::table('stores')->where('id', $id)->value('name') ?: 'Store #' . $id)
            : 'All Stores';
    }
}
