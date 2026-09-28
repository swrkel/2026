<?php
namespace Modules\AirlineTicketingNew\Services\Mobile;

use Illuminate\Support\Facades\DB;

class MobileActionService
{
    public function pendingActions(int $businessId,int $userId): array
    {
        return [
            'tasks'=>DB::table('atn_operational_tasks')
                ->where('business_id',$businessId)
                ->where('assigned_to',$userId)
                ->where('status','open')
                ->orderBy('due_at')
                ->limit(20)
                ->get(),
            'workflows'=>DB::table('atn_workflow_instances')
                ->where('business_id',$businessId)
                ->where('status','pending')
                ->orderBy('id')
                ->limit(20)
                ->get(),
        ];
    }
}
