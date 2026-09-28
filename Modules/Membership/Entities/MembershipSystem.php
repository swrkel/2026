<?php

namespace Modules\Membership\Entities;

/**
 * Membership-owned system setting reference model.
 *
 * Used for report footer/settings reads without importing App\System in views.
 */
class MembershipSystem extends \App\System
{
    protected $table = 'system';
}
