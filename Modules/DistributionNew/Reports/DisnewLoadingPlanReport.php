<?php

namespace Modules\DistributionNew\Reports;

use Illuminate\Support\Facades\DB;

class DisnewLoadingPlanReport
{
    public function rows(int $businessId, array $filters = [])
    {
        return DB::table('disnew_loading_plans')
            ->where('business_id', $businessId)
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn($q, $from) => $q->whereDate('plan_date', '>=', $from))
            ->when($filters['to'] ?? null, fn($q, $to) => $q->whereDate('plan_date', '<=', $to))
            ->orderByDesc('id')
            ->get();
    }
}
