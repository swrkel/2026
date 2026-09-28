<?php

namespace Modules\PetroGeneral\Services\UserActivity;

use App\BusinessLocation;
use App\User;

class UserActivityPageService
{
    public function getIndexData(int $businessId): array
    {
        return [
            'business_locations' => BusinessLocation::forDropdown($businessId),
            'users' => User::where('business_id', $businessId)->pluck('username', 'id'),
        ];
    }
}
