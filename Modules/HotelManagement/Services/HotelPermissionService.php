<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\Auth;

class HotelPermissionService
{
    public function modulePrefix(): string
    {
        return 'hotel.';
    }

    public function all(): array
    {
        return include __DIR__.'/../Permissions/permissions.php';
    }

    public function can(string $permission): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'can') && $user->can($permission)) {
            return true;
        }

        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
            return true;
        }

        if (isset($user->permissions) && is_iterable($user->permissions)) {
            foreach ($user->permissions as $item) {
                $name = is_string($item) ? $item : ($item->name ?? $item->permission ?? null);
                if ($name === $permission) {
                    return true;
                }
            }
        }

        return false;
    }

    public function visibleMenu(): array
    {
        $menu = config('hotelmanagement_menu', include __DIR__.'/../Config/menu.php');
        $sections = [];

        foreach (($menu['sections'] ?? []) as $section) {
            if (empty($section['permission']) || $this->can($section['permission']) || $this->can('hotel.view')) {
                $sections[] = $section;
            }
        }

        $menu['sections'] = $sections;
        return $menu;
    }
}
