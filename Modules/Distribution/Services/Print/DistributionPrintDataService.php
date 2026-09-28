<?php

namespace Modules\Distribution\Services\Print;

use Modules\Distribution\Entities\Core\Business;
use Modules\Distribution\Entities\Core\BusinessLocation;
use Modules\Distribution\Entities\Core\System;

class DistributionPrintDataService
{
    public function businessId(): ?int
    {
        return request()->session()->get('user.business_id') ?: session()->get('user.business_id');
    }

    public function business(?int $businessId = null)
    {
        $businessId = $businessId ?: $this->businessId();
        return $businessId ? Business::find($businessId) : null;
    }

    public function location(?int $businessId = null)
    {
        $businessId = $businessId ?: $this->businessId();
        if (! $businessId) {
            return null;
        }

        return BusinessLocation::where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('is_active', 1)->orWhereNull('is_active');
            })
            ->first()
            ?: BusinessLocation::where('business_id', $businessId)->first();
    }

    public function precision(?int $businessId = null): array
    {
        $business = $this->business($businessId);

        return [
            'currency_precision' => ! empty($business->currency_precision)
                ? (int) $business->currency_precision
                : (int) config('constants.currency_precision', 2),
            'quantity_precision' => ! empty($business->quantity_precision)
                ? (int) $business->quantity_precision
                : (int) config('constants.quantity_precision', 2),
        ];
    }

    public function reportFooter(): ?string
    {
        return System::getProperty('admin_reports_footer');
    }
}
