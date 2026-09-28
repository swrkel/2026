<?php
namespace Modules\StockTransferNew\Services;

use Modules\StockTransferNew\Entities\ExportQueue;

class LargeExportService
{
    public function queue(string $reportType, array $filters = [], ?int $businessId = null): ExportQueue
    {
        return ExportQueue::create([
            'business_id' => $businessId,
            'report_type' => $reportType,
            'filters' => $filters,
            'status' => 'queued',
            'requested_by' => auth()->id(),
        ]);
    }

    public function recent(?int $businessId = null)
    {
        $query = ExportQueue::query()->latest('id')->limit(50);
        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        return $query->get();
    }
}
