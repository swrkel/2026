<?php

namespace Modules\BankingTellerOperations\Services;

class TellerSlipService
{
    public function create(array $data): \Modules\BankingTellerOperations\Entities\TellerSlip
    {
        $data['slip_no'] = $data['slip_no'] ?? app(TellerNumberService::class)->next('TS');
        $data['status'] = $data['status'] ?? 'draft';
        return \Modules\BankingTellerOperations\Entities\TellerSlip::create($data);
    }

    public function approve(\Modules\BankingTellerOperations\Entities\TellerSlip $slip, int $userId): \Modules\BankingTellerOperations\Entities\TellerSlip
    {
        $slip->status = 'approved';
        $slip->approved_by = $userId;
        $slip->approved_at = now();
        $slip->save();
        return $slip;
    }
}
