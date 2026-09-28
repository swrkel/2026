<?php

namespace Modules\Membership\Entities;

/**
 * Membership-owned business reference model.
 *
 * Uses the existing business table while keeping Membership services and
 * entities independent from direct App\Business imports.
 */
class MembershipBusiness extends \App\Business
{
    protected $table = 'business';
}
