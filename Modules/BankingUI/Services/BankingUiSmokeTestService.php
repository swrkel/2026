<?php

namespace Modules\BankingUI\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class BankingUiSmokeTestService
{
    public function routeChecks(): array
    {
        $expected = config('banking_ui_smoke.routes', []);
        return collect($expected)->map(function ($routeName) {
            return [
                'key' => $routeName,
                'status' => Route::has($routeName) ? 'pass' : 'fail',
                'message' => Route::has($routeName) ? 'Route registered' : 'Route missing',
            ];
        })->values()->all();
    }

    public function permissionChecks(): array
    {
        $expected = config('banking_ui_smoke.permissions', []);
        return collect($expected)->map(function ($permission) {
            $exists = DB::table('permissions')->where('name', $permission)->exists();
            return [
                'key' => $permission,
                'status' => $exists ? 'pass' : 'fail',
                'message' => $exists ? 'Permission seeded' : 'Permission missing',
            ];
        })->values()->all();
    }

    public function sidebarChecks(): array
    {
        return collect(config('banking_ui_smoke.menus', []))->map(function ($menuKey) {
            return [
                'key' => $menuKey,
                'status' => 'review',
                'message' => 'Verify this menu is visible for the assigned Banking tester role.',
            ];
        })->values()->all();
    }

    public function releaseChecklist(): array
    {
        return [
            ['item' => 'Banking parent menu visible', 'status' => 'review'],
            ['item' => 'Core Banking child menus visible', 'status' => 'review'],
            ['item' => 'Microfinance child menus visible', 'status' => 'review'],
            ['item' => 'Insurance child menus visible', 'status' => 'review'],
            ['item' => 'Reports toolbar visible', 'status' => 'review'],
            ['item' => 'Role permissions assigned', 'status' => 'review'],
            ['item' => 'No 404 from visible Banking menu items', 'status' => 'review'],
            ['item' => 'No unauthorized menus shown to restricted roles', 'status' => 'review'],
        ];
    }
}
