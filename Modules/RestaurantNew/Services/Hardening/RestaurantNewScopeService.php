<?php

namespace Modules\RestaurantNew\Services\Hardening;

use Illuminate\Database\Eloquent\Builder;
use Modules\RestaurantNew\Entities\RestaurantNewTenantScopeLog;

class RestaurantNewScopeService
{
    public function businessId(): ?int
    {
        return session('business.id') ?: optional(auth()->user())->business_id;
    }

    public function locationId(): ?int
    {
        return session('business_location_id') ?: session('location_id');
    }

    public function applyBusinessScope(Builder $query, ?int $businessId = null): Builder
    {
        $businessId = $businessId ?: $this->businessId();
        if ($businessId) {
            $query->where($query->getModel()->getTable() . '.business_id', $businessId);
        }
        return $query;
    }

    public function applyLocationScope(Builder $query, ?int $locationId = null): Builder
    {
        $locationId = $locationId ?: $this->locationId();
        if ($locationId) {
            $query->where(function ($q) use ($query, $locationId) {
                $table = $query->getModel()->getTable();
                $q->where($table . '.location_id', $locationId)->orWhereNull($table . '.location_id');
            });
        }
        return $query;
    }

    public function logScopeDecision(string $operation, string $status, ?string $reason = null, array $payload = []): void
    {
        RestaurantNewTenantScopeLog::create([
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'user_id' => optional(auth()->user())->id,
            'route_name' => optional(request()->route())->getName(),
            'operation' => $operation,
            'status' => $status,
            'reason' => $reason,
            'payload' => $payload,
        ]);
    }
}
