<?php

namespace Modules\BeautySaloons\Services\Portal;

use Modules\BeautySaloons\Entities\BeautyAppointment;
use Modules\BeautySaloons\Entities\BeautyGiftVoucher;
use Modules\BeautySaloons\Entities\BeautyLoyaltyTransaction;
use Modules\BeautySaloons\Entities\BeautyPackageSale;

class PortalDashboardService
{
    public function summary(int $customerId): array
    {
        return [
            'upcoming_appointments' => BeautyAppointment::query()->where('customer_id', $customerId)->whereDate('appointment_date', '>=', now()->toDateString())->count(),
            'completed_appointments' => BeautyAppointment::query()->where('customer_id', $customerId)->where('status', 'completed')->count(),
            'active_packages' => class_exists(BeautyPackageSale::class) ? BeautyPackageSale::query()->where('customer_id', $customerId)->where('status', 'active')->count() : 0,
            'active_vouchers' => class_exists(BeautyGiftVoucher::class) ? BeautyGiftVoucher::query()->where('customer_id', $customerId)->where('status', 'active')->count() : 0,
            'loyalty_points' => class_exists(BeautyLoyaltyTransaction::class) ? (float) BeautyLoyaltyTransaction::query()->where('customer_id', $customerId)->sum('points') : 0,
        ];
    }
}
