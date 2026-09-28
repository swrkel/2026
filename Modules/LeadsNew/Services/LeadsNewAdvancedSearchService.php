<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LeadsNewAdvancedSearchService
{
    public function query(int $businessId, array $filters): Builder
    {
        $query = DB::table('leads_new_leads')->where('business_id', $businessId);

        foreach (['location_id', 'status_id', 'source_id', 'assigned_to', 'territory_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($inner) use ($q) {
                $inner->where('lead_no', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('company_name', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('lead_date', [$filters['date_from'], $filters['date_to']]);
        }

        return $query->latest('id');
    }
}
