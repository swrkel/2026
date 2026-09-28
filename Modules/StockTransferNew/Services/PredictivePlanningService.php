<?php

namespace Modules\StockTransferNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\PredictivePlan;
use Modules\StockTransferNew\Entities\PredictivePlanLine;

class PredictivePlanningService
{
    public function dashboard(array $filters = []): array
    {
        $plans = PredictivePlan::query()
            ->when(!empty($filters['business_id']), fn ($q) => $q->where('business_id', $filters['business_id']))
            ->latest()
            ->paginate(30);

        return [
            'filters' => $filters,
            'plans' => $plans,
            'kpis' => $this->kpis($filters),
        ];
    }

    public function generatePlan(array $data): PredictivePlan
    {
        return DB::transaction(function () use ($data) {
            $plan = PredictivePlan::create([
                'business_id' => $data['business_id'],
                'from_location_id' => $data['from_location_id'] ?? null,
                'to_location_id' => $data['to_location_id'],
                'from_store_id' => $data['from_store_id'] ?? null,
                'to_store_id' => $data['to_store_id'],
                'forecast_days' => $data['forecast_days'],
                'safety_stock_days' => $data['safety_stock_days'] ?? 7,
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
                'generated_by' => auth()->id(),
                'generated_at' => Carbon::now(),
            ]);

            foreach ($this->suggestedLines($data) as $line) {
                $plan->lines()->create($line);
            }

            return $plan;
        });
    }

    public function show(int $planId): array
    {
        $plan = PredictivePlan::with('lines')->findOrFail($planId);
        return ['plan' => $plan, 'lines' => $plan->lines()->orderByDesc('suggested_qty')->paginate(100)];
    }

    public function approveLine(int $lineId, float $approvedQty, ?string $remarks): void
    {
        PredictivePlanLine::whereKey($lineId)->update([
            'approved_qty' => $approvedQty,
            'status' => 'approved',
            'approval_remarks' => $remarks,
            'approved_by' => auth()->id(),
            'approved_at' => Carbon::now(),
        ]);
    }

    public function rejectLine(int $lineId, string $remarks): void
    {
        PredictivePlanLine::whereKey($lineId)->update([
            'status' => 'rejected',
            'approval_remarks' => $remarks,
            'approved_by' => auth()->id(),
            'approved_at' => Carbon::now(),
        ]);
    }

    public function workloadBalance(array $filters = []): array
    {
        $rows = DB::table('stn_transfers')
            ->select('to_location_id', 'to_store_id', DB::raw('count(*) as open_transfers'), DB::raw('coalesce(sum(total_qty),0) as open_qty'))
            ->when(!empty($filters['business_id']), fn ($q) => $q->where('business_id', $filters['business_id']))
            ->whereIn('status', ['approved', 'dispatched', 'in_transit', 'partially_received'])
            ->groupBy('to_location_id', 'to_store_id')
            ->orderByDesc('open_qty')
            ->get();

        return ['filters' => $filters, 'rows' => $rows];
    }

    public function exportCsv(array $filters = []): string
    {
        $plans = PredictivePlan::query()->latest()->limit(500)->get();
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Plan ID', 'Business', 'To Location', 'To Store', 'Forecast Days', 'Safety Days', 'Status', 'Generated At']);
        foreach ($plans as $plan) {
            fputcsv($out, [$plan->id, $plan->business_id, $plan->to_location_id, $plan->to_store_id, $plan->forecast_days, $plan->safety_stock_days, $plan->status, $plan->generated_at]);
        }
        rewind($out);
        return stream_get_contents($out) ?: '';
    }

    protected function suggestedLines(array $data): array
    {
        $forecastDays = (int) $data['forecast_days'];
        $safetyDays = (int) ($data['safety_stock_days'] ?? 7);

        $movement = DB::table('stn_stock_movements')
            ->select('product_id', 'variation_id', DB::raw('avg(abs(qty)) as avg_daily_qty'))
            ->where('business_id', $data['business_id'])
            ->where('location_id', $data['to_location_id'])
            ->where('store_id', $data['to_store_id'])
            ->where('movement_type', 'out')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(max($forecastDays, 7)))
            ->groupBy('product_id', 'variation_id')
            ->limit(250)
            ->get();

        return $movement->map(function ($row) use ($forecastDays, $safetyDays) {
            $required = round((float) $row->avg_daily_qty * ($forecastDays + $safetyDays), 4);
            return [
                'product_id' => $row->product_id,
                'variation_id' => $row->variation_id,
                'average_daily_qty' => $row->avg_daily_qty,
                'forecast_qty' => round((float) $row->avg_daily_qty * $forecastDays, 4),
                'safety_stock_qty' => round((float) $row->avg_daily_qty * $safetyDays, 4),
                'suggested_qty' => $required,
                'approved_qty' => 0,
                'status' => 'pending_review',
            ];
        })->all();
    }

    protected function kpis(array $filters = []): array
    {
        $base = PredictivePlan::query()->when(!empty($filters['business_id']), fn ($q) => $q->where('business_id', $filters['business_id']));
        return [
            'draft_plans' => (clone $base)->where('status', 'draft')->count(),
            'review_lines' => PredictivePlanLine::where('status', 'pending_review')->count(),
            'approved_lines' => PredictivePlanLine::where('status', 'approved')->count(),
            'rejected_lines' => PredictivePlanLine::where('status', 'rejected')->count(),
        ];
    }
}
