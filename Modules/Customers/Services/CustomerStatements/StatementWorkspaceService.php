<?php

namespace Modules\Customers\Services\CustomerStatements;

use App\BusinessLocation;
use App\Contact;
use App\CustomerStatement;
use App\CustomerStatementLogo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class StatementWorkspaceService
{
    public function filters(int $businessId): array
    {
        $locations = BusinessLocation::where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id');

        $customers = Contact::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->where('is_default', 0)
            ->orderBy('name')
            ->pluck('name', 'id');

        $logos = collect();
        if (class_exists(CustomerStatementLogo::class)) {
            $logos = CustomerStatementLogo::where('business_id', $businessId)
                ->orderByDesc('id')
                ->pluck('image_name', 'id');
        }

        $defaultLocationId = session('user.business_location_id')
            ?: session('business_location_id')
            ?: $locations->keys()->first();

        $defaultLocationId = $defaultLocationId !== null ? (string) $defaultLocationId : null;

        return compact('locations', 'customers', 'logos', 'defaultLocationId');
    }

    public function latestAllowedStartDate(int $businessId, int $customerId): ?string
    {
        if (!Schema::hasTable('customer_statements')) {
            return null;
        }

        $lastDate = CustomerStatement::where('business_id', $businessId)
            ->where('customer_id', $customerId)
            ->max('date_to');

        return $lastDate ? date('Y-m-d', strtotime($lastDate . ' +1 day')) : null;
    }
}
