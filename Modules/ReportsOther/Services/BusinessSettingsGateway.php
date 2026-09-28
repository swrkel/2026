<?php

namespace Modules\ReportsOther\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessSettingsGateway
{
    public function currencyPrecision(int $businessId): int
    {
        $cfg = config('reportsother.business_settings');
        $fallback = max(0, min(8, (int) ($cfg['fallback_precision'] ?? 2)));
        $table = $cfg['table'];
        $idColumn = $cfg['id_column'];
        $precisionColumn = $cfg['currency_precision_column'];

        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $precisionColumn)) {
            return $fallback;
        }

        $value = DB::table($table)->where($idColumn, $businessId)->value($precisionColumn);
        return is_numeric($value) ? max(0, min(8, (int) $value)) : $fallback;
    }
    public function financialYearStartMonth(int $businessId): int
    {
        $cfg = config('reportsother.business_settings');
        $fallback = max(1, min(12, (int) ($cfg['fallback_fy_start_month'] ?? 1)));
        $table = $cfg['table'];
        $idColumn = $cfg['id_column'];
        $column = $cfg['fy_start_month_column'] ?? 'fy_start_month';

        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return $fallback;
        }

        $value = DB::table($table)->where($idColumn, $businessId)->value($column);
        return is_numeric($value) ? max(1, min(12, (int) $value)) : $fallback;
    }

}
