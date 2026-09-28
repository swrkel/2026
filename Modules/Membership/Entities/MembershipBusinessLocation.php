<?php

namespace Modules\Membership\Entities;

/**
 * Membership-owned business location reference model.
 *
 * Keeps Membership module code module-scoped while remaining compatible with
 * the ERP business_locations table.
 */
class MembershipBusinessLocation extends \App\BusinessLocation
{
    protected $table = 'business_locations';
}
