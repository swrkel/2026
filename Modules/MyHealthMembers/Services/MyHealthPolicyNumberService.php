<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthInsurancePolicy;

class MyHealthPolicyNumberService
{
    public function nextNumber(): string
    {
        $lastId = (int) MyHealthInsurancePolicy::query()->max('id');
        return 'MHP-' . str_pad((string) ($lastId + 1), 7, '0', STR_PAD_LEFT);
    }
}
