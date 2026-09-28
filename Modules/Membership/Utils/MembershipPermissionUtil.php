<?php

namespace Modules\Membership\Utils;

use Illuminate\Support\Facades\Auth;

class MembershipPermissionUtil
{
    public function can(string $permission): bool
    {
        $user = Auth::user();
        return !empty($user) && method_exists($user, 'can') && $user->can($permission);
    }

    public function authorize(string $permission): void
    {
        if (!$this->can($permission)) {
            abort(403, 'Unauthorized Access');
        }
    }
}
