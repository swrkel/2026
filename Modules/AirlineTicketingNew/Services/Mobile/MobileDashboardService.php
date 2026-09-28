<?php
namespace Modules\AirlineTicketingNew\Services\Mobile;

use Illuminate\Support\Facades\DB;

class MobileDashboardService
{
    public function summary(int $businessId, ?int $userId = null): array
    {
        return [
            'today_reservations' => DB::table('atn_reservations')
                ->where('business_id', $businessId)
                ->whereDate('reservation_date', today())
                ->count(),
            'today_tickets' => DB::table('atn_tickets')
                ->where('business_id', $businessId)
                ->whereDate('issue_date', today())
                ->count(),
            'open_tasks' => DB::table('atn_operational_tasks')
                ->where('business_id', $businessId)
                ->when($userId, fn ($q) => $q->where('assigned_to', $userId))
                ->where('status', 'open')
                ->count(),
            'pending_workflows' => DB::table('atn_workflow_instances')
                ->where('business_id', $businessId)
                ->where('status', 'pending')
                ->count(),
        ];
    }
}
