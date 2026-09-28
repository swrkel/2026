<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyVoucherSeries;

class VoucherSeriesService
{
    public function nextNumber(BeautyVoucherSeries $series): string
    {
        $next = ((int) $series->last_number) + 1;
        $series->update(['last_number' => $next]);
        return $series->prefix . str_pad((string) $next, (int) $series->padding, '0', STR_PAD_LEFT);
    }
}
