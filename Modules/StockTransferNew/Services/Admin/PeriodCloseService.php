<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\PeriodClose;
use Modules\StockTransferNew\Entities\PeriodCloseLine;

class PeriodCloseService
{
    public function summary(int $businessId, ?int $locationId, ?int $storeId, int $year, int $month): array
    {
        $range = $this->range($year, $month);
        $query = DB::table('stn_transfers')->where('business_id', $businessId)
            ->whereBetween('created_at', [$range['from'], $range['to']]);

        if ($locationId) {
            $query->where(function ($q) use ($locationId) {
                $q->where('from_location_id', $locationId)->orWhere('to_location_id', $locationId);
            });
        }
        if ($storeId) {
            $query->where(function ($q) use ($storeId) {
                $q->where('from_store_id', $storeId)->orWhere('to_store_id', $storeId);
            });
        }

        $rows = (clone $query)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total','status')->toArray();
        $variance = (clone $query)->where(function ($q) {
            $q->where('has_variance', 1)->orWhere('variance_status', 'open');
        })->count();

        return [
            'total_transfers' => array_sum($rows),
            'pending_transfers' => ($rows['draft'] ?? 0) + ($rows['requested'] ?? 0) + ($rows['pending_approval'] ?? 0),
            'in_transit_transfers' => ($rows['dispatched'] ?? 0) + ($rows['in_transit'] ?? 0),
            'variance_transfers' => $variance,
            'by_status' => $rows,
        ];
    }

    public function preview(int $businessId, ?int $locationId, ?int $storeId, int $year, int $month): array
    {
        $summary = $this->summary($businessId, $locationId, $storeId, $year, $month);
        $issues = [];

        if ($summary['pending_transfers'] > 0) {
            $issues[] = ['issue_type'=>'pending_transfer','severity'=>'warning','issue_message'=>'There are transfers still waiting for approval or request completion.','action_required'=>'Complete or cancel pending transfers before closing.'];
        }
        if ($summary['in_transit_transfers'] > 0) {
            $issues[] = ['issue_type'=>'in_transit_transfer','severity'=>'critical','issue_message'=>'There are transfers dispatched but not received.','action_required'=>'Receive or investigate in-transit transfers before closing.'];
        }
        if ($summary['variance_transfers'] > 0) {
            $issues[] = ['issue_type'=>'open_variance','severity'=>'critical','issue_message'=>'There are transfers with open shortages/excess quantities.','action_required'=>'Reconcile variances before closing.'];
        }

        return compact('summary','issues');
    }

    public function lockPeriod(int $businessId, ?int $locationId, ?int $storeId, int $year, int $month, int $userId, ?string $remarks = null): PeriodClose
    {
        return DB::transaction(function () use ($businessId, $locationId, $storeId, $year, $month, $userId, $remarks) {
            $preview = $this->preview($businessId, $locationId, $storeId, $year, $month);
            $summary = $preview['summary'];
            $status = count($preview['issues']) ? 'blocked' : 'locked';

            $close = PeriodClose::updateOrCreate([
                'business_id'=>$businessId,
                'location_id'=>$locationId,
                'store_id'=>$storeId,
                'period_year'=>$year,
                'period_month'=>$month,
            ], [
                'status'=>$status,
                'total_transfers'=>$summary['total_transfers'],
                'pending_transfers'=>$summary['pending_transfers'],
                'in_transit_transfers'=>$summary['in_transit_transfers'],
                'variance_transfers'=>$summary['variance_transfers'],
                'locked_by'=>$status === 'locked' ? $userId : null,
                'locked_at'=>$status === 'locked' ? now() : null,
                'remarks'=>$remarks,
                'updated_by'=>$userId,
            ]);

            PeriodCloseLine::where('period_close_id', $close->id)->delete();
            foreach ($preview['issues'] as $issue) {
                $close->lines()->create($issue);
            }

            return $close->load('lines');
        });
    }

    public function reopen(PeriodClose $close, int $userId, ?string $remarks = null): PeriodClose
    {
        $close->update([
            'status'=>'reopened',
            'reopened_by'=>$userId,
            'reopened_at'=>now(),
            'remarks'=>$remarks ?: $close->remarks,
            'updated_by'=>$userId,
        ]);
        return $close->fresh('lines');
    }

    private function range(int $year, int $month): array
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        return ['from'=>$from->copy()->startOfDay(), 'to'=>$from->copy()->endOfMonth()->endOfDay()];
    }
}
