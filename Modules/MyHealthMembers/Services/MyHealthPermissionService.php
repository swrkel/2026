<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthBusinessPermission;

class MyHealthPermissionService
{
    public function businessId(): ?int
    {
        return request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
    }

    public function can(string $permission): bool
    {
        $businessId = $this->businessId();

        if (empty($businessId)) {
            return false;
        }

        $record = MyHealthBusinessPermission::where('business_id', $businessId)->first();

        if (empty($record)) {
            return false;
        }

        if (!empty($record->access_expiry_date) && strtotime($record->access_expiry_date) < strtotime(date('Y-m-d'))) {
            return false;
        }

        return (bool) ($record->{$permission} ?? false);
    }
}
