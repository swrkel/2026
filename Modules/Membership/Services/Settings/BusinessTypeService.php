<?php

namespace Modules\Membership\Services\Settings;

use Modules\Membership\Entities\MembershipBusinessName;
use Modules\Membership\Entities\MembershipBusinessType;
use Modules\Membership\Entities\MembershipMember;
use Modules\Membership\Entities\MembershipPointSetting;

class BusinessTypeService
{
    public function isInUse(MembershipBusinessType $businessType): bool
    {
        return MembershipPointSetting::where('membership_business_type_id', $businessType->id)->exists()
            || MembershipBusinessName::where('membership_business_type_id', $businessType->id)->exists()
            || MembershipMember::where('membership_business_type_id', $businessType->id)->exists();
    }
}
