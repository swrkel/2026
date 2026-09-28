<?php
namespace Modules\AirlineTicketingNew\Services\Security;

use Modules\AirlineTicketingNew\Entities\PermissionProfile;

class PermissionProfileService
{
    public function permissions(int $businessId,string $profileCode): array
    {
        return PermissionProfile::query()
            ->where('business_id',$businessId)
            ->where('profile_code',$profileCode)
            ->where('is_active',true)
            ->value('permissions_json') ?? [];
    }

    public function allows(int $businessId,string $profileCode,string $permission): bool
    {
        return in_array($permission,$this->permissions($businessId,$profileCode),true);
    }
}
