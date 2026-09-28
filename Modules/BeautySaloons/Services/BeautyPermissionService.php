<?php

namespace Modules\BeautySaloons\Services;

class BeautyPermissionService
{
    public static function permissions(): array
    {
        return [
            'beauty_saloons.view', 'beauty_saloons.create', 'beauty_saloons.update', 'beauty_saloons.delete',
            'beauty_saloons.appointments.view', 'beauty_saloons.appointments.create', 'beauty_saloons.appointments.update', 'beauty_saloons.appointments.cancel',
            'beauty_saloons.customers.view', 'beauty_saloons.services.view', 'beauty_saloons.staff.view',
            'beauty_saloons.sales.view', 'beauty_saloons.payments.create', 'beauty_saloons.reports.view', 'beauty_saloons.settings',
        ];
    }
}
