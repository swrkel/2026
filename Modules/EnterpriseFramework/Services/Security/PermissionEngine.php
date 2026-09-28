<?php

namespace Modules\EnterpriseFramework\Services\Security;

class PermissionEngine
{
    public function can(string $permission): bool
    {
        return auth()->check() && (method_exists(auth()->user(), 'can') ? auth()->user()->can($permission) : true);
    }
}
