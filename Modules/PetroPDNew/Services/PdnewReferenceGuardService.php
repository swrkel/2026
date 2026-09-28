<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PdnewReferenceGuardService
{

    public function assertLocation(int $businessId, ?int $locationId): void
    {
        if (! $locationId) {
            return;
        }

        if (Schema::hasTable('business_locations')) {
            $query = DB::table('business_locations')
                ->where('id', $locationId)
                ->where('business_id', $businessId);

            if (Schema::hasColumn('business_locations', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if (! $query->exists()) {
                throw new RuntimeException(
                    'The selected location does not belong to the active business.'
                );
            }

            return;
        }

        $known = DB::table('pone_pd_operators')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->exists()
            || DB::table('pone_shifts')
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->exists();

        if (! $known) {
            throw new RuntimeException(
                'The selected location is not available to Petro PD-New.'
            );
        }
    }

    public function assertCustomer(int $businessId, ?int $customerId): void
    {
        if (! $customerId) {
            return;
        }

        if (! Schema::hasTable('contacts')) {
            throw new RuntimeException(
                'The customer master table is unavailable in this tenant database.'
            );
        }

        $query = DB::table('contacts')
            ->where('id', $customerId)
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

        if (! $query->exists()) {
            throw new RuntimeException(
                'The selected customer is inactive or does not belong to the active business.'
            );
        }
    }
}
