<?php

namespace Modules\BankingInsurance\Services;

use Illuminate\Support\Facades\DB;

class NumberGenerator
{
    public function next($business_id, $table, $column, $prefix)
    {
        $last = DB::table($table)
            ->where('business_id', $business_id)
            ->where($column, 'like', $prefix . '-%')
            ->orderBy('id', 'desc')
            ->value($column);

        $next = 1;
        if (!empty($last) && preg_match('/(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }
        return $prefix . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
