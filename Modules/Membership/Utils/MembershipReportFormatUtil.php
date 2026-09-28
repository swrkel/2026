<?php

namespace Modules\Membership\Utils;

use App\Business;

class MembershipReportFormatUtil
{
    public function currencyPrecision(?int $businessId): int
    {
        if (empty($businessId)) {
            return 2;
        }

        $business = Business::find($businessId);
        return (int) ($business->currency_precision ?? 2);
    }

    public function date($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    public function amount($value, ?int $businessId = null): string
    {
        return number_format((float) ($value ?? 0), $this->currencyPrecision($businessId), '.', ',');
    }

    public function number($value, int $precision = 0): string
    {
        return number_format((float) ($value ?? 0), $precision, '.', ',');
    }
}
