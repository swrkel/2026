<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\LeadsNew\Services\LeadsNewNotificationCenterService;

class LeadsNewNotificationCenterController extends Controller
{
    public function index(LeadsNewNotificationCenterService $service)
    {
        $businessId = request()->session()->get('user.business_id');
        $notifications = $service->unread($businessId, (int) auth()->id());
        return view('leadsnew::notifications.index', compact('notifications'));
    }
}
