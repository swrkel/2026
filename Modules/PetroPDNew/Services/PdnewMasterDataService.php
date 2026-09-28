<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Small adapter around shared application master data.
 *
 * Petro PD-New never imports another module's models or services.  Shared
 * business locations and contacts are read through this guarded adapter only.
 */
class PdnewMasterDataService
{
    public function locations(int $businessId): Collection
    {
        if (! Schema::hasTable('business_locations')
            || ! Schema::hasColumn('business_locations', 'id')
            || ! Schema::hasColumn('business_locations', 'business_id')) {
            return collect();
        }

        $query = DB::table('business_locations')
            ->where('business_id', $businessId);

        $user = request()->user();
        if ($user && method_exists($user, 'permitted_locations')) {
            $permitted = $user->permitted_locations();
            if ($permitted !== 'all') {
                $ids = collect(is_array($permitted) ? $permitted : [])
                    ->map(static fn ($id) => (int) $id)
                    ->filter(static fn (int $id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                if ($ids === []) {
                    return collect();
                }

                $query->whereIn('id', $ids);
            }
        }

        if (Schema::hasColumn('business_locations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('business_locations', 'is_active')) {
            $query->where('is_active', true);
        }

        $columns = ['id'];
        foreach (['name', 'landmark', 'city'] as $column) {
            if (Schema::hasColumn('business_locations', $column)) {
                $columns[] = $column;
            }
        }

        return $query
            ->orderBy(Schema::hasColumn('business_locations', 'name') ? 'name' : 'id')
            ->get($columns)
            ->map(function ($location): object {
                $name = trim((string) ($location->name ?? ''));
                $location->display_name = $name !== ''
                    ? $name
                    : 'Location #' . $location->id;

                return $location;
            });
    }

    public function customers(int $businessId, int $limit = 1000): Collection
    {
        if (! Schema::hasTable('contacts')
            || ! Schema::hasColumn('contacts', 'id')
            || ! Schema::hasColumn('contacts', 'business_id')) {
            return collect();
        }

        $query = DB::table('contacts')
            ->where('business_id', $businessId);

        if (Schema::hasColumn('contacts', 'type')) {
            $query->whereIn('type', ['customer', 'both']);
        }
        if (Schema::hasColumn('contacts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('contacts', 'active')) {
            $query->where('active', true);
        }
        if (Schema::hasColumn('contacts', 'is_inactive')) {
            $query->where('is_inactive', false);
        }

        $columns = ['id'];
        foreach (['name', 'contact_id', 'mobile', 'supplier_business_name'] as $column) {
            if (Schema::hasColumn('contacts', $column)) {
                $columns[] = $column;
            }
        }

        return $query
            ->orderBy(Schema::hasColumn('contacts', 'name') ? 'name' : 'id')
            ->limit(max(1, min(5000, $limit)))
            ->get($columns)
            ->map(function ($customer): object {
                $name = trim((string) ($customer->name ?? $customer->supplier_business_name ?? ''));
                $parts = [$name !== '' ? $name : 'Customer #' . $customer->id];
                if (! empty($customer->contact_id)) {
                    $parts[] = (string) $customer->contact_id;
                }
                if (! empty($customer->mobile)) {
                    $parts[] = (string) $customer->mobile;
                }
                $customer->display_name = implode(' — ', $parts);

                return $customer;
            });
    }
}
