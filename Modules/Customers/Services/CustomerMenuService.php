<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\Route;

/**
 * Customers-owned menu resolver.
 *
 * CUS319: The Customers sidebar/System menu now reads the same Super Admin
 * package page switches that are saved from All Business -> Manage. This fixes
 * the issue where pages were enabled but did not appear under Login -> System
 * -> Customers Module.
 */
class CustomerMenuService
{
    protected $permissionService;

    public function __construct(CustomerPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function moduleMenu(): array
    {
        $baseMenu = [
            $this->item('Dashboard', 'customers.dashboard', 'fa fa-dashboard', 'dashboard'),
            $this->item('Customer Register', 'customers.index', 'fa fa-users', 'view'),
            $this->item('Add Customer', 'customers.create', 'fa fa-plus', 'create'),
            $this->packagePageItem('customers_customer_payment_bulk'),
            $this->item('Customer Reports', 'customers.reports.index', 'fa fa-bar-chart', 'reports'),
            $this->item('Customer Ledger', 'customers.reports.ledger', 'fa fa-book', 'reports'),
            $this->item('Customer Statement', 'customers.reports.statement', 'fa fa-file-text-o', 'reports'),
            $this->item('Master Data', 'customers.master.index', 'fa fa-cogs', 'settings'),
            $this->item('Workflow & Approvals', 'customers.workflow.index', 'fa fa-check-square-o', 'approve'),
            $this->item('Customer Settings', 'customers.settings.index', 'fa fa-sliders', 'settings'),
        ];

        return $this->uniqueMenuItems(array_filter(array_merge(
            $baseMenu,
            $this->enabledPackagePageMenu()
        )));
    }

    public function quickActions(): array
    {
        return array_values(array_filter([
            $this->item('Open Register', 'customers.index', 'fa fa-users', 'view'),
            $this->item('Add Customer', 'customers.create', 'fa fa-plus', 'create'),
            $this->item('Ledger Report', 'customers.reports.ledger', 'fa fa-book', 'reports'),
            $this->item('Statement Report', 'customers.reports.statement', 'fa fa-file-text-o', 'reports'),
            $this->item('Balance Report', 'customers.reports.balance', 'fa fa-balance-scale', 'reports'),
            $this->item('Master Data', 'customers.master.index', 'fa fa-cogs', 'settings'),
        ]));
    }

    /**
     * Menu items controlled by Super Admin -> All Business -> Manage ->
     * Customers Module individual page switches.
     */
    public function enabledPackagePageMenu(): array
    {
        $items = [];

        foreach (CustomerModulePageRegistry::pages() as $key => $page) {
            if (! $this->permissionService->pageEnabled($key)) {
                continue;
            }

            $ability = $page['ability'] ?? 'view';
            if (! $this->permissionService->allows($ability)) {
                continue;
            }

            $url = CustomerModulePageRegistry::resolveUrl($page);
            if (empty($url)) {
                continue;
            }

            $items[] = [
                'label' => $page['label'],
                'route' => $page['route'] ?? null,
                'icon' => $page['icon'] ?? 'fa fa-circle-o',
                'ability' => $ability,
                'url' => $url,
                'active' => ! empty($page['route']) && Route::has($page['route'])
                    ? (request()->routeIs($page['route']) || request()->routeIs($page['route'] . '.*'))
                    : request()->is(ltrim(parse_url($url, PHP_URL_PATH) ?: '', '/') . '*'),
                'package_key' => $key,
            ];
        }

        return $items;
    }

    /**
     * Resolve a registered Customers page in a fixed primary menu position.
     */
    protected function packagePageItem(string $pageKey): ?array
    {
        $page = CustomerModulePageRegistry::pages()[$pageKey] ?? null;

        if (empty($page) || ! $this->permissionService->pageEnabled($pageKey)) {
            return null;
        }

        $ability = $page['ability'] ?? 'view';
        if (! $this->permissionService->allows($ability)) {
            return null;
        }

        $url = CustomerModulePageRegistry::resolveUrl($page);
        if (empty($url)) {
            return null;
        }

        $route = $page['route'] ?? null;

        return [
            'label' => $page['label'],
            'route' => $route,
            'icon' => $page['icon'] ?? 'fa fa-circle-o',
            'ability' => $ability,
            'url' => $url,
            'active' => ! empty($route) && Route::has($route)
                ? (request()->routeIs($route) || request()->routeIs($route . '.*'))
                : request()->is(ltrim(parse_url($url, PHP_URL_PATH) ?: '', '/') . '*'),
            'package_key' => $pageKey,
        ];
    }

    /**
     * The same route can be present in the fixed menu and package-page list.
     * Keep the first item so only one Bulk Payment link is rendered.
     */
    protected function uniqueMenuItems(array $items): array
    {
        $unique = [];

        foreach ($items as $item) {
            if (empty($item) || empty($item['url'])) {
                continue;
            }

            $key = ! empty($item['route'])
                ? 'route:' . $item['route']
                : 'url:' . rtrim(parse_url($item['url'], PHP_URL_PATH) ?: $item['url'], '/');

            if (! array_key_exists($key, $unique)) {
                $unique[$key] = $item;
            }
        }

        return array_values($unique);
    }

    protected function item(string $label, string $route, string $icon, string $ability): ?array
    {
        if (! Route::has($route)) {
            return null;
        }

        if (! $this->permissionService->allows($ability)) {
            return null;
        }

        return [
            'label' => $label,
            'route' => $route,
            'icon' => $icon,
            'ability' => $ability,
            'url' => route($route),
            'active' => request()->routeIs($route) || request()->routeIs($route . '.*'),
        ];
    }
}
