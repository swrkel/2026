<?php

namespace Modules\LeadsNew\Services\Performance;

use Illuminate\Database\Eloquent\Builder;

class LeadsNewQueryOptimizer
{
    public function listDefaults(Builder $query): Builder
    {
        return $query->orderByDesc('id')->limit(request()->get('limit', 25));
    }

    public function dateRange(Builder $query, ?string $from, ?string $to, string $column = 'created_at'): Builder
    {
        if ($from) {
            $query->whereDate($column, '>=', $from);
        }
        if ($to) {
            $query->whereDate($column, '<=', $to);
        }
        return $query;
    }
}
