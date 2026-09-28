<?php

namespace Modules\LeadsNew\Services\Dashboard;

use Modules\LeadsNew\Models\LeadsNewLead;
use Modules\LeadsNew\Models\LeadsNewOpportunity;

class LeadsNewDashboardV2Service
{
    public function summary(array $filters = []): array
    {
        $leadQuery = LeadsNewLead::query();
        $opportunityQuery = LeadsNewOpportunity::query();

        if (!empty($filters['business_id'])) {
            $leadQuery->where('business_id', $filters['business_id']);
            $opportunityQuery->where('business_id', $filters['business_id']);
        }
        if (!empty($filters['location_id'])) {
            $leadQuery->where('location_id', $filters['location_id']);
            $opportunityQuery->where('location_id', $filters['location_id']);
        }

        return [
            'total_leads' => (clone $leadQuery)->count(),
            'today_leads' => (clone $leadQuery)->whereDate('created_at', now()->toDateString())->count(),
            'monthly_leads' => (clone $leadQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'open_opportunities' => (clone $opportunityQuery)->whereNull('closed_at')->count(),
            'pipeline_value' => (clone $opportunityQuery)->whereNull('closed_at')->sum('expected_value'),
        ];
    }
}
