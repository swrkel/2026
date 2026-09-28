<?php

namespace Modules\Membership\Entities;

/**
 * Membership-owned user reference model.
 *
 * This wrapper keeps Membership code referencing module entities instead of
 * importing App\User directly. It still maps to the ERP users table for
 * compatibility with the existing multi-tenant authentication system.
 */
class MembershipUser extends \App\User
{
    protected $table = 'users';
}
