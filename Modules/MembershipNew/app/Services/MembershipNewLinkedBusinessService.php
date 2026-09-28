<?php

namespace Modules\MembershipNew\app\Services;

use Modules\MembershipNew\app\Models\MembershipNewLinkedBusiness;

class MembershipNewLinkedBusinessService
{
    public function activeLinkedBusinessIds(int $mainBusinessId): array
    {
        return MembershipNewLinkedBusiness::where('business_id', $mainBusinessId)
            ->where('is_active', 1)
            ->pluck('linked_business_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
