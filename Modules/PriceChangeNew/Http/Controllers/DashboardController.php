<?php

namespace Modules\PriceChangeNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\PriceChangeNew\Entities\PriceChange;
use Modules\PriceChangeNew\Services\PriceChangeContext;

class DashboardController extends Controller
{
    public function __construct(private PriceChangeContext $context)
    {
    }

    public function index()
    {
        $businessId = $this->context->businessId();
        $counts = PriceChange::query()
            ->forBusiness($businessId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentChanges = PriceChange::query()
            ->forBusiness($businessId)
            ->withCount('lines')
            ->with('scopes:id,price_change_id,location_name')
            ->latest('id')
            ->limit(8)
            ->get();

        $dueCount = PriceChange::query()
            ->forBusiness($businessId)
            ->whereIn('status', ['approved', 'scheduled', 'failed', 'partial'])
            ->where(function ($q): void {
                $q->whereNull('effective_at')->orWhere('effective_at', '<=', now());
            })->count();

        return view('pricechangenew::dashboard.index', [
            'counts' => $counts,
            'dueCount' => $dueCount,
            'recentChanges' => $recentChanges,
            'canViewChanges' => $this->context->canAny(['pricechangenew.changes.view']),
            'canCreateChanges' => $this->context->canAny(['pricechangenew.changes.create']),
            'canViewApprovals' => $this->context->canAny(['pricechangenew.approvals.view', 'pricechangenew.approvals.approve', 'pricechangenew.approvals.reject']),
            'canApplyChanges' => $this->context->canAny(['pricechangenew.changes.apply']),
            'canViewReports' => $this->context->canAny(['pricechangenew.reports.view']),
            'canManageSettings' => $this->context->canAny(['pricechangenew.settings.manage']),
        ]);
    }
}
