<?php

namespace Modules\DistributionNew\Services\Reports;

use Modules\DistributionNew\Models\DisnewExportLog;

class DisnewExportLogService
{
    public function log(?int $businessId, ?int $locationId, string $reportKey, string $exportType, array $filters, int $recordCount, ?int $userId): DisnewExportLog
    {
        return DisnewExportLog::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'report_key' => $reportKey,
            'export_type' => $exportType,
            'filter_payload' => $filters,
            'record_count' => $recordCount,
            'created_by' => $userId,
        ]);
    }
}
