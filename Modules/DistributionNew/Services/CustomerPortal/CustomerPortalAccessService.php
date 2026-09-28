<?php

namespace Modules\DistributionNew\Services\CustomerPortal;

use Modules\DistributionNew\Entities\CustomerPortal\CustomerPortalUser;

class CustomerPortalAccessService
{
    public function currentPortalUser(int $businessId, int $userId): ?CustomerPortalUser
    {
        return CustomerPortalUser::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->first();
    }

    public function assertCan(CustomerPortalUser $portalUser, string $ability): void
    {
        $map = [
            'place_order' => 'can_place_order',
            'view_invoice' => 'can_view_invoice',
            'view_statement' => 'can_view_statement',
            'request_return' => 'can_request_return',
            'raise_complaint' => 'can_raise_complaint',
        ];
        $field = $map[$ability] ?? null;
        abort_if(!$field || !$portalUser->{$field}, 403, __('distributionnew::customer_portal.access_denied'));
    }
}
