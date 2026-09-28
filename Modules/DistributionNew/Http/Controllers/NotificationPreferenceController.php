<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Models\DisnewNotificationPreference;
use Modules\DistributionNew\Services\Notifications\NotificationPreferenceService;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) session('business.id', $request->get('business_id'));
        $preferences = DisnewNotificationPreference::where('business_id', $businessId)->orderBy('event_key')->get();

        return view('distributionnew::settings.notification-preferences', compact('preferences'));
    }

    public function store(Request $request, NotificationPreferenceService $service)
    {
        $businessId = (int) session('business.id', $request->get('business_id'));
        $service->save($businessId, $request->input('event_key'), $request->all());

        return redirect()->back()->with('status', __('distributionnew::lang.notification_preference_saved'));
    }
}
