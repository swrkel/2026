<?php

namespace Modules\ProductsNew\Services\Report;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductReportFilterService
{
    public function filters(array $request): array
    {
        $requestedBusinessId = isset($request['business_id']) && $request['business_id'] !== ''
            ? (int) $request['business_id']
            : null;

        return [
            'business_id' => ProductsNewTenantGuard::businessId($requestedBusinessId),
            'location_id' => $request['location_id'] ?? null,
            'category_id' => $request['category_id'] ?? null,
            'brand_id' => $request['brand_id'] ?? null,
            'date_from' => $request['date_from'] ?? null,
            'date_to' => $request['date_to'] ?? null,
            'keyword' => $request['keyword'] ?? null,
        ];
    }

    public function locations($businessId)
    {
        if (! Schema::hasTable('business_locations')) {
            return collect();
        }

        $query = DB::table('business_locations')
            ->where('business_id', $businessId);

        if (Schema::hasColumn('business_locations', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('name')->pluck('name', 'id');
    }
}
