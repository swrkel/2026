<?php
namespace Modules\TeaEstateManagement\Services;

use Illuminate\Support\Facades\Auth;

class TenantContextService
{
    public function businessId(): int
    {
        $id = (int) (session('user.business_id') ?: session('business.id'));
        abort_if($id <= 0, 403, 'Business context is not available.');
        return $id;
    }
    public function userId(): int
    {
        $id = (int) optional(Auth::user())->id;
        abort_if($id <= 0, 401);
        return $id;
    }
}
