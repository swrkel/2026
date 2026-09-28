<?php

namespace Modules\ReportsOther\Services;

class ReportFormatter
{
    public function __construct(private readonly BusinessSettingsGateway $settings)
    {
    }

    public function amount(float|int|string|null $value, int $businessId): string
    {
        $precision = $this->settings->currencyPrecision($businessId);
        return number_format((float) ($value ?? 0), $precision, '.', ',');
    }
}
