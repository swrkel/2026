<?php

namespace Modules\PetroDirectNew\Services;

use Carbon\Carbon;
use Modules\PetroDirectNew\Entities\PdirectnewAssignment;
use Modules\PetroDirectNew\Entities\PdirectnewDailyCollection;
use Modules\PetroDirectNew\Entities\PdirectnewOperator;
use Modules\PetroDirectNew\Entities\PdirectnewSettlement;
use Modules\PetroDirectNew\Entities\PdirectnewShift;
use Modules\PetroDirectNew\Support\BusinessContext;

class DashboardService
{
    public function __construct(private BusinessContext $context) {}

    public function summary(?int $locationId = null): array
    {
        $businessId = $this->context->requireBusiness();
        $applyScope = function ($query) use ($businessId, $locationId) {
            $query->where('business_id', $businessId);
            if ($locationId) $query->where('location_id', $locationId);
            return $query;
        };
        $today = Carbon::today();

        $settlements = $applyScope(PdirectnewSettlement::query());
        $todaySettlements = (clone $settlements)->whereDate('transaction_date', $today)->get();

        return [
            'active_operators' => $applyScope(PdirectnewOperator::query())->where('is_active', 1)->count(),
            'open_shifts' => $applyScope(PdirectnewShift::query())->where('status', 'open')->count(),
            'open_assignments' => $applyScope(PdirectnewAssignment::query())->whereIn('status', ['assigned','received'])->count(),
            'draft_settlements' => (clone $settlements)->whereIn('status', ['draft','reopened'])->count(),
            'finalized_today' => $todaySettlements->where('status', 'finalized')->count(),
            'expected_today' => (float) $todaySettlements->sum('expected_total'),
            'received_today' => (float) $todaySettlements->sum('received_total'),
            'variance_today' => (float) $todaySettlements->sum('variance'),
            'collections_today' => (float) $applyScope(PdirectnewDailyCollection::query())->whereDate('collection_date', $today)->sum('grand_total'),
            'recent_settlements' => (clone $settlements)->with('operator')->latest('id')->limit(8)->get(),
        ];
    }
}
