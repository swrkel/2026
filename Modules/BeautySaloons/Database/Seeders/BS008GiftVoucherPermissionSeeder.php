<?php

namespace Modules\BeautySaloons\Database\Seeders;

use Illuminate\Database\Seeder;

class BS008GiftVoucherPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'beauty_saloons.gift_vouchers.view',
            'beauty_saloons.gift_vouchers.create',
            'beauty_saloons.gift_vouchers.update',
            'beauty_saloons.gift_vouchers.delete',
            'beauty_saloons.gift_vouchers.sell',
            'beauty_saloons.gift_vouchers.redeem',
            'beauty_saloons.gift_vouchers.reports',
        ];
        // Hook into your ERP permission utility/table here during installation.
    }
}
