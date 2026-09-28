<?php
namespace Modules\AirlineTicketingNew\Support\Performance;

class PaginatedQuery
{
    public static function size(?int $requested, int $default = 50, int $maximum = 200): int
    {
        return min($maximum, max(1, $requested ?: $default));
    }
}
