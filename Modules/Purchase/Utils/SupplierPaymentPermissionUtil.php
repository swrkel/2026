<?php

namespace Modules\Purchase\Utils;

class SupplierPaymentPermissionUtil
{
    public static function all(): array
    {
        return [
            'purchase.payment.view',
            'purchase.payment.create',
            'purchase.payment.edit',
            'purchase.payment.delete',
            'purchase.payment.print',
        ];
    }
}
