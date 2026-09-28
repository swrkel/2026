<?php

namespace Modules\LeadsNew\Services\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeadsNewTenantScopeService
{
    public function applyBusinessScope(Builder $query): Builder
    {
        $businessId = session('business.id') ?? optional(Auth::user())->business_id;
        if (!empty($businessId)) {
            $query->where('business_id', $businessId);
        }
        return $query;
    }

    public function applyLocationScope(Builder $query, $locationId = null): Builder
    {
        $locationId = $locationId ?: request()->get('location_id');
        if (!empty($locationId)) {
            $query->where('location_id', $locationId);
        }
        return $query;
    }

    public function applyUserScopeWhenRequired(Builder $query, bool $ownOnly = false): Builder
    {
        if ($ownOnly && Auth::check()) {
            $query->where('assigned_to', Auth::id());
        }
        return $query;
    }
}
