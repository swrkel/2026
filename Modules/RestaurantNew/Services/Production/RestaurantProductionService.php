<?php

namespace Modules\RestaurantNew\Services\Production;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewBatchProduction;
use Modules\RestaurantNew\Entities\RestaurantNewBranchDistribution;
use Modules\RestaurantNew\Entities\RestaurantNewProductionPlan;
use Modules\RestaurantNew\Entities\RestaurantNewProductionPlanItem;
use Modules\RestaurantNew\Entities\RestaurantNewSemiFinishedItem;

class RestaurantProductionService
{
    public function createPlan(array $data, array $items = []): RestaurantNewProductionPlan
    {
        return DB::transaction(function () use ($data, $items) {
            $plan = RestaurantNewProductionPlan::create($data);
            foreach ($items as $item) {
                $item['production_plan_id'] = $plan->id;
                RestaurantNewProductionPlanItem::create($item);
            }
            return $plan->fresh();
        });
    }

    public function approvePlan(RestaurantNewProductionPlan $plan, int $userId): RestaurantNewProductionPlan
    {
        $plan->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
        return $plan->fresh();
    }

    public function startBatch(array $data): RestaurantNewBatchProduction
    {
        $data['started_at'] = $data['started_at'] ?? now();
        $data['status'] = $data['status'] ?? 'in_progress';
        return RestaurantNewBatchProduction::create($data);
    }

    public function completeBatch(RestaurantNewBatchProduction $batch, array $yieldPayload = []): RestaurantNewBatchProduction
    {
        $input = (float)($yieldPayload['input_cost'] ?? $batch->input_cost ?? 0);
        $output = (float)($yieldPayload['output_cost'] ?? $batch->output_cost ?? 0);
        $wastage = max($input - $output, 0);
        $batch->update([
            'status' => 'completed',
            'completed_at' => now(),
            'input_cost' => $input,
            'output_cost' => $output,
            'wastage_cost' => $wastage,
            'yield_payload' => $yieldPayload,
        ]);
        return $batch->fresh();
    }

    public function createSemiFinishedItem(array $data): RestaurantNewSemiFinishedItem
    {
        return RestaurantNewSemiFinishedItem::create($data);
    }

    public function distributeToBranch(array $data): RestaurantNewBranchDistribution
    {
        $data['status'] = $data['status'] ?? 'draft';
        return RestaurantNewBranchDistribution::create($data);
    }

    public function dashboard(int $businessId, ?int $locationId = null): array
    {
        return [
            'open_plans' => RestaurantNewProductionPlan::where('business_id', $businessId)->when($locationId, fn($q) => $q->where('location_id', $locationId))->whereIn('status', ['draft','approved'])->count(),
            'running_batches' => RestaurantNewBatchProduction::where('business_id', $businessId)->when($locationId, fn($q) => $q->where('location_id', $locationId))->where('status', 'in_progress')->count(),
            'completed_batches_today' => RestaurantNewBatchProduction::where('business_id', $businessId)->when($locationId, fn($q) => $q->where('location_id', $locationId))->whereDate('completed_at', today())->count(),
            'pending_distributions' => RestaurantNewBranchDistribution::where('business_id', $businessId)->where('status', 'draft')->count(),
        ];
    }
}
