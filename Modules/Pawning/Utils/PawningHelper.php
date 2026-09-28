<?php

namespace Modules\Pawning\Utils;

class PawningHelper
{
    public static function statuses()
    {
        return ['active', 'redeemed', 'renewed', 'auctioned', 'closed', 'cancelled'];
    }
}
