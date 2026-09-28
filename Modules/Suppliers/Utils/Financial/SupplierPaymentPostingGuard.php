<?php

namespace Modules\Suppliers\Utils\Financial;

use Modules\Suppliers\Entities\SupplierAccountTransaction;

class SupplierPaymentPostingGuard
{
    public static function alreadyPosted(int $businessId, string $referenceNo, string $sourceType): bool
    {
        return SupplierAccountTransaction::query()
            ->where('business_id', $businessId)
            ->where('ref_no', $referenceNo)
            ->where('type', $sourceType)
            ->exists();
    }
}
