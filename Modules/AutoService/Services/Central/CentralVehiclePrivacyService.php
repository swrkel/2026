<?php

namespace Modules\AutoService\Services\Central;

use Illuminate\Support\Collection;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicleServiceRecord;

class CentralVehiclePrivacyService
{
    /**
     * Owner view is allowed to see cost totals, invoice reference and business name after OTP verification.
     */
    public function ownerRecords($records): Collection
    {
        return collect($records)->map(function (AutoServiceCentralVehicleServiceRecord $record) {
            return [
                'service_date' => $record->service_date,
                'job_no' => $record->job_no,
                'invoice_no' => $record->invoice_no,
                'mileage' => $record->mileage,
                'business_name' => $record->business_name ?: $record->tenant_key,
                'service_type' => $record->service_type,
                'complaints' => $record->complaints,
                'diagnosis' => $record->diagnosis,
                'work_done' => $record->work_done,
                'parts_used' => $this->safePartsList($record->parts_used),
                'oils_used' => $this->safePartsList($record->oils_used),
                'labour_total' => (float) $record->labour_total,
                'parts_total' => (float) $record->parts_total,
                'oil_total' => (float) $record->oil_total,
                'discount_total' => (float) $record->discount_total,
                'tax_total' => (float) $record->tax_total,
                'grand_total' => (float) $record->grand_total,
            ];
        });
    }

    /**
     * Workshop view must never disclose previous workshop identity, contact details, invoice details or prices.
     */
    public function workshopRecords($records): Collection
    {
        return collect($records)->map(function (AutoServiceCentralVehicleServiceRecord $record) {
            return [
                'service_date' => $record->service_date,
                'mileage' => $record->mileage,
                'service_type' => $record->service_type,
                'complaints' => $record->complaints,
                'diagnosis' => $record->diagnosis,
                'work_done' => $record->work_done,
                'parts_used' => $this->safePartsList($record->parts_used),
                'oils_used' => $this->safePartsList($record->oils_used),
                'source' => 'Previous service record',
            ];
        });
    }

    public function safePartsList($items): array
    {
        return collect($items ?: [])->map(function ($item) {
            $row = is_array($item) ? $item : (array) $item;
            return array_filter([
                'product_name' => $row['product_name'] ?? $row['name'] ?? $row['description'] ?? null,
                'description' => $row['description'] ?? null,
                'sku' => $row['sku'] ?? null,
                'brand' => $row['brand'] ?? null,
                'part_no' => $row['part_no'] ?? $row['part_number'] ?? null,
                'quantity' => $row['quantity'] ?? $row['qty'] ?? null,
                'unit' => $row['unit'] ?? $row['unit_name'] ?? null,
                'oil_grade' => $row['oil_grade'] ?? null,
            ], function ($value) { return $value !== null && $value !== ''; });
        })->values()->all();
    }
}
