<?php

namespace Modules\BankingMicrofinance\Services;

use Illuminate\Support\Facades\DB;

class NumberGenerator
{
    public function next(string $table, string $column, string $prefix): string
    {
        $latest = DB::table($table)->where($column, 'like', $prefix . '-%')->orderByDesc('id')->value($column);
        $number = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $number = ((int) $matches[1]) + 1;
        }
        return $prefix . '-' . str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
