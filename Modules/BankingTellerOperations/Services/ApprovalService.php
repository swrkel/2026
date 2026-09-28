<?php

namespace Modules\BankingTellerOperations\Services;

class ApprovalService
{
    public function request(array $data): \Modules\BankingTellerOperations\Entities\SupervisorApproval
    {
        $data['approval_no'] = $data['approval_no'] ?? app(TellerNumberService::class)->next('AP');
        $data['status'] = $data['status'] ?? 'pending';
        return \Modules\BankingTellerOperations\Entities\SupervisorApproval::create($data);
    }
}
