<?php
namespace Modules\StockTransferNew\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TenantScopeService
{
    public function businessId(?Request $request = null): ?int
    {
        $request = $request ?: request();
        return (int)($request->session()->get('user.business_id') ?? $request->input('business_id') ?? 0) ?: null;
    }

    public function apply(Builder $query, ?Request $request = null, array $columns = []): Builder
    {
        $request = $request ?: request();
        $businessColumn = $columns['business_id'] ?? 'business_id';
        $locationColumn = $columns['location_id'] ?? 'location_id';
        $fromStoreColumn = $columns['from_store_id'] ?? 'from_store_id';
        $toStoreColumn = $columns['to_store_id'] ?? 'to_store_id';

        if ($businessId = $this->businessId($request)) {
            $query->where($businessColumn, $businessId);
        }
        if ($request->filled('location_id')) {
            $query->where($locationColumn, (int)$request->input('location_id'));
        }
        if ($request->filled('store_id')) {
            $storeId = (int)$request->input('store_id');
            $query->where(function($q) use ($fromStoreColumn, $toStoreColumn, $storeId) {
                $q->where($fromStoreColumn, $storeId)->orWhere($toStoreColumn, $storeId);
            });
        }
        return $query;
    }
}
