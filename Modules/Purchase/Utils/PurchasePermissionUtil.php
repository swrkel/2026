<?php

namespace Modules\Purchase\Utils;

class PurchasePermissionUtil
{
    public static function grouped(): array
    {
        return config('purchase.permissions', require module_path('Purchase', 'Config/permissions.php'));
    }

    public static function all(): array
    {
        return collect(static::grouped())->flatten()->values()->all();
    }

    public static function sidebar(): array
    {
        return [
            'dashboard' => 'purchase.dashboard.view',
            'entries' => 'purchase.entry.view',
            'orders' => 'purchase.order.view',
            'returns' => 'purchase.return.view',
            'bills' => 'purchase.bill.view',
            'supplier_payments' => 'purchase.supplier_payment.view',
            'reports' => 'purchase.report.view',
            'settings' => 'purchase.settings.view',
        ];
    }
}
