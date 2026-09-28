<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Services\Source\PoneSourceReader;

class DashboardController extends PdnewController
{
    public function index(PoneSourceReader $sources)
    {
        $businessId = $this->context->businessId();
        $locationId = $this->context->locationId();

        $available = $sources->closedShiftQuery($businessId, $locationId)
            ->whereNull('i.settlement_id')
            ->whereNull('r.settlement_no')
            ->count();

        $base = PdnewSettlement::query()->forBusiness($businessId)->forLocation($locationId);

        $issueQuery = DB::table('pdnew_reconciliation_issues as issue')
            ->join('pdnew_settlements as settlement', function ($join): void {
                $join->on('settlement.id', '=', 'issue.settlement_id')
                    ->on('settlement.business_id', '=', 'issue.business_id');
            })
            ->where('issue.business_id', $businessId)
            ->where('issue.status', 'open');

        $integrationQuery = DB::table('pdnew_integration_outbox as outbox')
            ->where('outbox.business_id', $businessId)
            ->whereIn('outbox.status', ['pending', 'failed']);

        if ($locationId) {
            $issueQuery->where('settlement.location_id', $locationId);
            $this->scopeIntegrationToLocation($integrationQuery, $locationId, 'outbox');
        }

        $cards = [
            'available_shifts' => $available,
            'draft_settlements' => (clone $base)->whereIn('status', ['draft', 'reopened'])->count(),
            'review_settlements' => (clone $base)->whereIn('status', ['review', 'approved'])->count(),
            'finalized_today' => (clone $base)->where('status', 'finalized')->whereDate('finalized_at', now()->toDateString())->count(),
            'open_issues' => $issueQuery->count(),
            'pending_integration' => $integrationQuery->count(),
        ];

        $totals = (clone $base)
            ->whereDate('settlement_date', now()->toDateString())
            ->selectRaw('COALESCE(SUM(expected_total),0) expected, COALESCE(SUM(received_total),0) received, COALESCE(SUM(variance_amount),0) variance')
            ->first();

        $recent = (clone $base)->orderByDesc('id')->limit(10)->get();

        return view('petropdnew::dashboard', compact('cards', 'totals', 'recent'));
    }

    private function scopeIntegrationToLocation($query, int $locationId, string $alias): void
    {
        $query->where(function ($scope) use ($locationId, $alias): void {
            $scope->where(function ($settlementJob) use ($locationId, $alias): void {
                $settlementJob->where($alias . '.aggregate_type', 'settlement')
                    ->whereExists(function ($source) use ($locationId, $alias): void {
                        $source->selectRaw('1')
                            ->from('pdnew_settlements as scoped_settlement')
                            ->whereColumn('scoped_settlement.id', $alias . '.aggregate_id')
                            ->where('scoped_settlement.location_id', $locationId);
                    });
            })->orWhere(function ($dayEndJob) use ($locationId, $alias): void {
                $dayEndJob->where($alias . '.aggregate_type', 'day_end')
                    ->whereExists(function ($source) use ($locationId, $alias): void {
                        $source->selectRaw('1')
                            ->from('pdnew_day_ends as scoped_day_end')
                            ->whereColumn('scoped_day_end.id', $alias . '.aggregate_id')
                            ->where('scoped_day_end.location_id', $locationId);
                    });
            });
        });
    }
}
