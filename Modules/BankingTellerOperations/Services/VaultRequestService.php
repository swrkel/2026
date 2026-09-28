<?php

namespace Modules\BankingTellerOperations\Services;

class VaultRequestService
{
    public function create(array $data): \Modules\BankingTellerOperations\Entities\VaultRequest
    {
        $data['request_no'] = $data['request_no'] ?? app(TellerNumberService::class)->next('VR');
        $data['status'] = $data['status'] ?? 'pending';
        return \Modules\BankingTellerOperations\Entities\VaultRequest::create($data);
    }
}
