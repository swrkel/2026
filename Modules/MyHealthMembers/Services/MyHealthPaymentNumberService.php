<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthBillingPayment;

class MyHealthPaymentNumberService
{
    public function nextNumber(): string
    {
        $lastId = (int) MyHealthBillingPayment::query()->max('id');
        return 'MHP-' . date('Ymd') . '-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
    }
}
