<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyStaffCommission;

class BeautyCommissionService
{
    public function calculate(int $staffId, ?int $serviceId, float $amount): float
    {
        $rule = BeautyStaffCommission::where('staff_id', $staffId)
            ->where(function ($q) use ($serviceId) {
                $q->whereNull('service_id');
                if ($serviceId) {
                    $q->orWhere('service_id', $serviceId);
                }
            })
            ->where('is_active', 1)
            ->orderByRaw('service_id IS NULL ASC')
            ->first();

        if (!$rule) {
            return 0.0;
        }

        return $rule->commission_type === 'fixed'
            ? (float) $rule->commission_value
            : round($amount * ((float) $rule->commission_value / 100), 4);
    }
}
