<?php

namespace Modules\BankingTellerOperations\Services;

class ReportService
{
    public function summary(): array
    {
        return [
            'open_drawers' => \Modules\BankingTellerOperations\Entities\TellerDrawer::where('status', 'open')->count(),
            'today_slips' => \Modules\BankingTellerOperations\Entities\TellerSlip::whereDate('created_at', now()->toDateString())->count(),
            'pending_approvals' => \Modules\BankingTellerOperations\Entities\SupervisorApproval::where('status', 'pending')->count(),
        ];
    }
}
