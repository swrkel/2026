<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\FinanceNotification;

class FinanceNotificationController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $query = FinanceNotification::where('business_id', $business_id)
            ->with(['location', 'user'])
            ->latest();

        if (!empty($request->location_id) && $request->location_id != 'all') {
            $query->where('location_id', $request->location_id);
        }

        if (!empty($request->priority)) {
            $query->where('priority', $request->priority);
        }

        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }

        $notifications = $query->paginate(25);

        return view('finance::notifications.index')
            ->with(compact(
                'notifications',
                'locations'
            ));
    }
}