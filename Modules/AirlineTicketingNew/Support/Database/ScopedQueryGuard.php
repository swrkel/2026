<?php

namespace Modules\AirlineTicketingNew\Support\Database;

use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

class ScopedQueryGuard
{
    public function ensureBusinessScope(Builder $query, int $businessId): Builder
    {
        if ($businessId <= 0) {
            throw new InvalidArgumentException('A valid business scope is required.');
        }

        return $query->where($query->from . '.business_id', $businessId);
    }
}
