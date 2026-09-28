<?php
namespace Modules\Purchase\Utils;

class PurchaseApprovalUtil
{
    public function canApprove(array $permissions): bool
    {
        return in_array('purchase.approve', $permissions);
    }
}
