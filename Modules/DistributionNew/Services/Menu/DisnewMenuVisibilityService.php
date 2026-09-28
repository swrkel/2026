<?php

namespace Modules\DistributionNew\Services\Menu;

class DisnewMenuVisibilityService
{
    public function items(): array
    {
        return [
            ['key' => 'dashboard', 'route' => 'distributionnew.dashboard', 'permission' => 'distributionnew.view'],
            ['key' => 'sales_orders', 'route' => 'distributionnew.sales-orders.index', 'permission' => 'distributionnew.sales_order.view'],
            ['key' => 'sales_invoices', 'route' => 'distributionnew.sales-invoices.index', 'permission' => 'distributionnew.sales_invoice.view'],
            ['key' => 'loading', 'route' => 'distributionnew.loading.index', 'permission' => 'distributionnew.loading.view'],
            ['key' => 'unloading', 'route' => 'distributionnew.unloading.index', 'permission' => 'distributionnew.unloading.view'],
            ['key' => 'vehicles', 'route' => 'distributionnew.vehicles.index', 'permission' => 'distributionnew.vehicle.view'],
            ['key' => 'reports', 'route' => 'distributionnew.reports.index', 'permission' => 'distributionnew.reports.view'],
            ['key' => 'audit', 'route' => 'distributionnew.audit.index', 'permission' => 'distributionnew.audit.view'],
        ];
    }
}
