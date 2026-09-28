<?php

namespace Modules\DistributionNew\Services\ProductionCompletion;

use Illuminate\Support\Facades\DB;

class VisitPlanService
{
    public function createPlan(array $data, array $customers): int
    {
        $planId = DB::table('disnew_visit_plans')->insertGetId([
            'business_id' => $data['business_id'],
            'location_id' => $data['location_id'] ?? null,
            'sales_rep_id' => $data['sales_rep_id'],
            'route_id' => $data['route_id'] ?? null,
            'visit_date' => $data['visit_date'],
            'status' => 'planned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (array_values($customers) as $index => $customerId) {
            DB::table('disnew_visit_plan_lines')->insert([
                'visit_plan_id' => $planId,
                'customer_id' => $customerId,
                'sequence_no' => $index + 1,
                'visit_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $planId;
    }
}
