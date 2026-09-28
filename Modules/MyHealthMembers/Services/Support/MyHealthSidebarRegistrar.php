<?php

namespace Modules\MyHealthMembers\Services\Support;

use Illuminate\Support\Facades\Route;

class MyHealthSidebarRegistrar
{
    public static function menu(): array
    {
        $menu = config('myhealthmembers_menu', []);
        $menu['module_key'] = $menu['module_key'] ?? 'myhealthmembers';
        $menu['module_name'] = $menu['module_name'] ?? 'My Health';
        $menu['label'] = $menu['label'] ?? $menu['module_name'];
        $menu['icon'] = $menu['icon'] ?? 'fa fa-heartbeat';
        $menu['route'] = $menu['route'] ?? 'myhealth.members.index';
        $menu['url'] = self::routeUrl($menu['route'], '/myhealth/members');
        $menu['permission'] = $menu['permission'] ?? null;
        $menu['order'] = $menu['order'] ?? 45;

        $items = [];
        foreach (($menu['items'] ?? []) as $item) {
            $routeName = $item['route'] ?? null;
            $item['url'] = $routeName ? self::routeUrl($routeName, $item['url'] ?? '#') : ($item['url'] ?? '#');
            $items[] = $item;
        }
        $menu['items'] = $items;

        return $menu;
    }

    public static function sidebarPartial(): string
    {
        return 'myhealthmembers::partials.sidebar';
    }

    protected static function routeUrl(?string $routeName, string $fallback): string
    {
        if ($routeName && Route::has($routeName)) {
            return route($routeName);
        }

        return url($fallback);
    }
}
