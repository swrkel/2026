<?php

namespace Modules\Membership\Utils;

use Carbon\Carbon;

class MembershipDateUtil
{
    public function dbDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    public function dbDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i');
    }

    public function today(): string
    {
        return now()->format('Y-m-d');
    }
}
