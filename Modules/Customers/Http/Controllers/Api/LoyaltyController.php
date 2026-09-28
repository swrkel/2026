<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoyaltyController extends BaseDealerApiController
{
    public function index(Request $request)
    {
        $points = 0;
        $history = collect();

        if (Schema::hasTable('customer_portal_reward_points')) {
            $history = DB::table('customer_portal_reward_points')
                ->where('business_id', $this->businessId($request))
                ->where('contact_id', $this->customerId($request))
                ->orderByDesc('created_at')
                ->limit($this->paginateLimit($request, 100, 500))
                ->get();

            $points = (float) $history->sum(function ($row) {
                return (float) ($row->points ?? 0);
            });
        }

        return $this->success([
            'available_points' => $points,
            'history' => $history,
        ]);
    }
}
