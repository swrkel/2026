<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthBillingInvoice;

class MyHealthInvoiceNumberService
{
    public function nextNumber(): string
    {
        $lastId = (int) MyHealthBillingInvoice::query()->max('id');
        return 'MHI-' . date('Ymd') . '-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
    }
}
