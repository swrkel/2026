<?php

namespace Modules\HotelManagement\Reports;

class GuestHistoryReport
{
    public function title(): string
    {
        return str_replace('Report', ' Report', class_basename(static::class));
    }
}
