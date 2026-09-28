<?php

namespace Modules\MembershipNew\app\Menu;

class MembershipNewMenu
{
    /**
     * Compatibility adapter for ERP menu discovery.
     * NavigationRegistry is the single source so host/sidebar renderers cannot
     * drift into a different page list.
     */
    public static function items(): array
    {
        $sidebar = MembershipNewNavigationRegistry::sidebar();

        return [[
            'label' => $sidebar['module'] ?? 'Membership-New',
            'route' => $sidebar['route'] ?? 'membership-new.dashboard',
            'permission' => $sidebar['permission'] ?? 'membership_new.dashboard.view',
            'icon' => $sidebar['icon'] ?? 'fa fa-id-card',
            'children' => array_map(static function (array $item): array {
                return [
                    'label' => $item['title'] ?? '',
                    'route' => $item['route'] ?? '',
                    'permission' => $item['permission'] ?? '',
                ];
            }, $sidebar['items'] ?? []),
        ]];
    }
}
