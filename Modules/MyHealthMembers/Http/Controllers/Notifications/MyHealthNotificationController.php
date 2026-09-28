<?php

namespace Modules\MyHealthMembers\Http\Controllers\Notifications;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthNotification;
use Modules\MyHealthMembers\Services\Notifications\MyHealthNotificationService;

class MyHealthNotificationController extends Controller
{
    public function index(Request $request, MyHealthNotificationService $service)
    {
        return view('myhealthmembers::notifications.index', [
            'summary' => $service->dashboard(),
            'notifications' => $service->list($request->only(['status', 'channel', 'member'])),
            'filters' => $request->only(['status', 'channel', 'member']),
        ]);
    }

    public function create()
    {
        return view('myhealthmembers::notifications.create');
    }

    public function store(Request $request, MyHealthNotificationService $service)
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'channel' => ['required', 'in:sms,email,whatsapp'],
            'subject' => ['nullable', 'string', 'max:191'],
            'message' => ['required', 'string'],
            'purpose' => ['nullable', 'string', 'max:100'],
        ]);

        $service->queueForMember((int) $data['member_id'], $data['channel'], $data['subject'] ?? '', $data['message'], $data['purpose'] ?? 'manual');

        return redirect()->route('myhealth.notifications.index')->with('status', 'Notification queued successfully.');
    }

    public function markSent(MyHealthNotification $notification, MyHealthNotificationService $service)
    {
        $service->markSent($notification);

        return back()->with('status', 'Notification marked as sent.');
    }

    public function markFailed(Request $request, MyHealthNotification $notification, MyHealthNotificationService $service)
    {
        $service->markFailed($notification, $request->input('failure_reason'));

        return back()->with('status', 'Notification marked as failed.');
    }
}
