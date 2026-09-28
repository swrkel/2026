<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthInsuranceClaim;

class MyHealthClaimNumberService
{
    public function nextNumber(): string
    {
        $lastId = (int) MyHealthInsuranceClaim::query()->max('id');
        return 'MHC-' . str_pad((string) ($lastId + 1), 7, '0', STR_PAD_LEFT);
    }
}
