<?php

namespace Modules\RiceMill\Services;

use App\Business;
use App\Utils\SidebarPermissionUtil;
use Illuminate\Support\Facades\Route;

class DashboardAccessService
{
    public function __construct(private PermissionAccessService $permissions)
    {
    }

    public function moduleEnabled(int $businessId): bool
    {
        try {
            return SidebarPermissionUtil::isManageSidebarEnabled('ricemill', $businessId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function business(int $businessId, string $companyNumber = ''): ?Business
    {
        $query = Business::query()->where('id', $businessId);
        if ($companyNumber !== '') {
            $query->where('company_number', $companyNumber);
        }

        return $query->first();
    }

    public function canEnter($user, int $businessId): bool
    {
        return $this->permissions->allows($user, 'rice_mill.dashboard.view')
            && $this->managedPageAllows($businessId, 'rice_mill_dashboard');
    }

    public function actions($user, int $businessId): array
    {
        $items = [];

        if ($this->permissions->allows($user, 'rice_mill.paddy_receipt.create')
            && $this->managedPageAllows($businessId, 'rice_mill_receipts_view')) {
            $items[] = $this->item(
                'receive-paddy',
                'Receive Paddy',
                'Weighbridge & paddy receiving',
                'fa fa-truck',
                'rice-mill.receipts.create'
            );
        }

        if ($this->permissions->allows($user, 'rice_mill.production.create')
            && $this->permissions->allows($user, 'rice_mill.production.complete')
            && $this->managedPageAllows($businessId, 'rice_mill_production_entry')) {
            $items[] = $this->item(
                'production',
                'Mill Production',
                'Start and complete milling production',
                'fa fa-cogs',
                'rice-mill.production.create'
            );
        }

        if ($this->permissions->allows($user, 'rice_mill.dispatch.create')
            && $this->managedPageAllows($businessId, 'rice_mill_dispatch_view')) {
            $items[] = $this->item(
                'sales',
                'Sales',
                'Create rice sales / dispatch',
                'fa fa-shopping-cart',
                'rice-mill.dispatch.create'
            );
        }

        if ($this->canReceiveCustomerPayments($businessId)) {
            $route = Route::has('customers.customer_payments.create')
                ? 'customers.customer_payments.create'
                : (Route::has('customers.customer_payments.index') ? 'customers.customer_payments.index' : null);

            if ($route) {
                $items[] = $this->item(
                    'customer-payments',
                    'Receive Customer Payments',
                    'Open Customers payment entry',
                    'fa fa-money',
                    $route
                );
            }
        }

        if ($this->permissions->allows($user, 'rice_mill.paddy_stock.view')
            && $this->managedPageAllows($businessId, 'rice_mill_paddy_stock_view')) {
            $items[] = $this->item(
                'stock-paddy',
                'Paddy Stock',
                'Current paddy stock status',
                'fa fa-leaf',
                'rice-mill.paddy-stock.index',
                'Stock Status'
            );
        }

        if ($this->permissions->allows($user, 'rice_mill.finished_stock.view')
            && $this->managedPageAllows($businessId, 'rice_mill_finished_stock_view')) {
            $items[] = $this->item(
                'stock-rice',
                'Rice Stock',
                'Finished rice stock status',
                'fa fa-cubes',
                'rice-mill.finished-stock.index',
                'Stock Status'
            );
        }

        if ($this->permissions->allows($user, 'rice_mill.packing.view')
            && $this->managedPageAllows($businessId, 'rice_mill_packaging_materials_view')) {
            $items[] = $this->item(
                'stock-consumables',
                'Consumables',
                'Packaging / consumable stock status',
                'fa fa-archive',
                'rice-mill.packaging-materials.index',
                'Stock Status'
            );
        }

        return $items;
    }

    private function canReceiveCustomerPayments(int $businessId): bool
    {
        try {
            if (! SidebarPermissionUtil::isManageSidebarEnabled('customers', $businessId)) {
                return false;
            }

            if (! SidebarPermissionUtil::usesManagedRoleForCurrentUser($businessId)) {
                return true;
            }

            return SidebarPermissionUtil::managedRoleAllowsPermission('umn.module.customers.view', $businessId)
                && SidebarPermissionUtil::managedRoleAllowsPermission('umn.module.customers.edit', $businessId)
                && SidebarPermissionUtil::managedRoleAllowsPermission('umn.page.customers_customer_payments.view', $businessId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Page permission is additional to the module-level View/Edit right for
     * User Management New managed roles. Legacy roles retain their existing
     * Spatie permission behaviour.
     */
    private function managedPageAllows(int $businessId, string $pageKey): bool
    {
        try {
            if (! SidebarPermissionUtil::usesManagedRoleForCurrentUser($businessId)) {
                return true;
            }

            return SidebarPermissionUtil::managedRoleAllowsPermission(
                'umn.page.' . $pageKey . '.view',
                $businessId
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function item(
        string $key,
        string $label,
        string $description,
        string $icon,
        string $route,
        ?string $group = null
    ): array {
        return compact('key', 'label', 'description', 'icon', 'route', 'group');
    }
}
