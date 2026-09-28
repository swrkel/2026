<?php

namespace Modules\Product\Utils;

class ProductPermissionUtil
{
    public function can(string $permission): bool
    {
        $user = auth()->user();

        if (empty($user)) {
            return false;
        }

        if (method_exists($user, 'can')) {
            return $user->can($permission) || $user->can('superadmin') || $user->can('admin');
        }

        return false;
    }

    public function any(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function abortUnless(string $permission): void
    {
        abort_unless($this->can($permission), 403, __('product::lang.unauthorized'));
    }

    public function all(): array
    {
        return config('product_permissions.permissions', []);
    }
}
