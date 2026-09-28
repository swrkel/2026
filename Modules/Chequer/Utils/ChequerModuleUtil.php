<?php

namespace Modules\Chequer\Utils;

class ChequerModuleUtil
{
    public function isSubscribed($business_id): bool
    {
        return !empty($business_id);
    }

    public function expiredResponse()
    {
        return response()->json(['success' => false, 'msg' => 'Subscription not active.'], 403);
    }

    public function hasThePermissionInSubscription($business_id, $permission): bool
    {
        return !empty($business_id);
    }
}
