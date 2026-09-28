<?php

namespace Modules\Membership\Entities;

class PrefixStartingNumber extends MembershipSetting
{
    /**
     * Standalone membership setting entity for Prefix & Starting Numbers.
     * Uses the existing membership_settings table to avoid database changes.
     */
    protected $table = 'membership_settings';
}
