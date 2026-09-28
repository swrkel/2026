<?php

namespace Modules\Membership\Entities;

/**
 * Membership-owned contact/member reference model.
 *
 * Uses the existing contacts table for ERP compatibility. Membership code
 * should refer to this class instead of App\Contact where possible.
 */
class MembershipContact extends \App\Contact
{
    protected $table = 'contacts';
}
