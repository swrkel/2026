<?php
namespace Modules\Purchase\Utils;

class PurchaseUtil
{
    public function businessId()
    {
        return session('user.business_id');
    }
}
