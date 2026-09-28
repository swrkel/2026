<?php

namespace Modules\SettlementSW\Services;

class SettlementSwReportHelper
{
    public static function exportFileName(string $reportName): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '_', trim($reportName));
        return 'SettlementSW_' . trim($safe ?: 'Report', '_') . '_' . now()->format('Ymd_His');
    }

    public static function dateRangeLabel(?string $startDate, ?string $endDate): string
    {
        if ($startDate && $endDate) {
            return SettlementSwDateFormatter::date($startDate) . ' to ' . SettlementSwDateFormatter::date($endDate);
        }

        return __('settlementsw::lang.all_dates');
    }
}
