<?php

namespace Modules\BeautySaloons\Reports;

class BeautyReportsBIManifest
{
    public static function reports(): array
    {
        return [
            'Appointment Register', 'Daily Sales', 'Service Sales', 'Product Sales', 'Staff Commission',
            'Customer Visit History', 'Membership & Packages', 'Wallet Liability', 'Gift Vouchers',
            'Loyalty Points', 'Inventory Movement', 'Payment Collection', 'Branch Performance'
        ];
    }
}
