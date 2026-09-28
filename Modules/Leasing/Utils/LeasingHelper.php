<?php

namespace Modules\Leasing\Utils;

class LeasingHelper
{
    public static function statuses()
    {
        return ['active', 'redeemed', 'renewed', 'insuranceed', 'closed', 'cancelled'];
    }
}
