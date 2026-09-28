<?php

namespace Modules\Membership\Utils;

use Modules\Membership\Entities\MembershipBusiness;
use Modules\Membership\Entities\MembershipBusinessLocation;
use Modules\Membership\Entities\MembershipContact;
use Modules\Membership\Entities\MembershipSystem;
use Modules\Membership\Entities\MembershipUser;

class MembershipModelMap
{
    public static function user(): string
    {
        return MembershipUser::class;
    }

    public static function business(): string
    {
        return MembershipBusiness::class;
    }

    public static function contact(): string
    {
        return MembershipContact::class;
    }

    public static function location(): string
    {
        return MembershipBusinessLocation::class;
    }

    public static function system(): string
    {
        return MembershipSystem::class;
    }
}
