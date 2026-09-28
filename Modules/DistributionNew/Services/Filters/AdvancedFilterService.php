<?php

namespace Modules\DistributionNew\Services\Filters;

class AdvancedFilterService
{
    public function apply($query, array $filters)
    {
        foreach (['business_id', 'business_location_id', 'status', 'sales_rep_id', 'vehicle_id', 'route_id', 'customer_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate($filters['date_field'] ?? 'created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate($filters['date_field'] ?? 'created_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
