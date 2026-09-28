<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthClaimSettlement;

class MyHealthClaimSettlementNumberService
{
    public function nextNumber(): string
    {
        $lastId = (int) MyHealthClaimSettlement::query()->max('id');
        return 'MHS-' . date('Ymd') . '-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
    }
}
