<?php

namespace Modules\DistributionNew\Services\Loading;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Utils\DisnewNumberUtil;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class DisnewLoadingPlanService
{
    public function create(array $data, array $lines = []): int
    {
        return DB::transaction(function () use ($data, $lines) {
            $businessId = DisnewTenantUtil::businessId();
            $locationId = DisnewTenantUtil::locationId();
            $planId = DB::table('disnew_loading_plans')->insertGetId([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'plan_no' => DisnewNumberUtil::next($businessId, 'loading_plan', 'DLP'),
                'plan_date' => $data['plan_date'] ?? now()->toDateString(),
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'driver_id' => $data['driver_id'] ?? null,
                'sales_rep_id' => $data['sales_rep_id'] ?? null,
                'status' => 'draft',
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($lines as $line) {
                $plannedQty = (float)($line['planned_qty'] ?? $line['qty'] ?? 0);
                if ($plannedQty <= 0) { continue; }
                DB::table('disnew_loading_plan_lines')->insert([
                    'loading_plan_id' => $planId,
                    'sales_order_id' => $line['sales_order_id'] ?? null,
                    'sales_order_line_id' => $line['sales_order_line_id'] ?? null,
                    'product_id' => $line['product_id'],
                    'product_name' => $line['product_name'] ?? null,
                    'ordered_qty' => $line['ordered_qty'] ?? $plannedQty,
                    'planned_qty' => $plannedQty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return $planId;
        });
    }

    public function approve(int $planId): void
    {
        DB::table('disnew_loading_plans')->where('id', $planId)->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function convertToLoading(int $planId): int
    {
        return DB::transaction(function () use ($planId) {
            $plan = DB::table('disnew_loading_plans')->where('id', $planId)->lockForUpdate()->first();
            abort_if(!$plan, 404, 'Loading plan not found.');
            abort_if(!in_array($plan->status, ['draft', 'approved'], true), 422, 'Loading plan already converted or closed.');
            $loadingId = DB::table('disnew_loadings')->insertGetId([
                'loading_plan_id' => $planId,
                'business_id' => $plan->business_id,
                'location_id' => $plan->location_id,
                'store_id' => request('store_id'),
                'loading_no' => DisnewNumberUtil::next($plan->business_id, 'loading', 'DL'),
                'vehicle_id' => $plan->vehicle_id,
                'driver_id' => $plan->driver_id,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $lines = DB::table('disnew_loading_plan_lines')->where('loading_plan_id', $planId)->get();
            foreach ($lines as $line) {
                DB::table('disnew_loading_lines')->insert([
                    'loading_id' => $loadingId,
                    'loading_plan_line_id' => $line->id,
                    'sales_order_id' => $line->sales_order_id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product_name,
                    'planned_qty' => $line->planned_qty,
                    'qty' => $line->planned_qty,
                    'loaded_qty' => $line->planned_qty,
                    'variance_qty' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('disnew_loading_plans')->where('id', $planId)->update(['status' => 'converted_to_loading', 'updated_at' => now()]);
            return $loadingId;
        });
    }
}
