<?php

namespace Modules\CommunicationHub\Services\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BusinessContext
{
    public function businessId(): ?int
    {
        $candidate = session('business.id')
            ?? session('business_id')
            ?? optional(Auth::user())->business_id;

        return $candidate ? (int) $candidate : null;
    }

    public function businessLocationId(): ?int
    {
        $candidate = session('business_location_id')
            ?? session('user.business_location_id')
            ?? session('location_id')
            ?? optional(Auth::user())->business_location_id;

        return $candidate ? (int) $candidate : null;
    }

    public function userId(): ?int
    {
        return Auth::id() ? (int) Auth::id() : null;
    }

    public function locations(): array
    {
        $businessId = $this->businessId();
        if (! $businessId || ! DB::getSchemaBuilder()->hasTable('business_locations')) {
            return [];
        }

        return DB::table('business_locations')
            ->where('business_id', $businessId)
            ->where(function ($query) {
                if (DB::getSchemaBuilder()->hasColumn('business_locations', 'is_active')) {
                    $query->where('is_active', 1)->orWhereNull('is_active');
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
