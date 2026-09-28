<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyNotificationLog;
use Modules\BeautySaloons\Entities\BeautyNotificationSetting;
use Modules\BeautySaloons\Entities\BeautyNotificationTemplate;
use Modules\BeautySaloons\Services\BeautyNotificationService;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $logs = BeautyNotificationLog::query()
            ->when($request->channel, fn ($q, $v) => $q->where('channel', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest('id')
            ->paginate(25);

        return view('beautysaloons::notifications.index', compact('logs'));
    }

    public function templates()
    {
        $templates = BeautyNotificationTemplate::latest('id')->paginate(25);
        return view('beautysaloons::notifications.templates.index', compact('templates'));
    }

    public function createTemplate()
    {
        return view('beautysaloons::notifications.templates.create');
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:191',
            'category' => 'nullable|string|max:100',
            'channel' => 'required|in:sms,email,push,whatsapp,in_app',
            'language' => 'nullable|string|max:10',
            'subject' => 'nullable|string|max:191',
            'body' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['business_id'] = session('business.id');
        $data['language'] = $data['language'] ?? 'en';
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['created_by'] = auth()->id();

        BeautyNotificationTemplate::create($data);

        return redirect()->route('beautysaloons.notifications.templates')->with('status', __('beautysaloons::notifications.template_saved'));
    }

    public function settings()
    {
        $setting = BeautyNotificationSetting::firstOrCreate(['business_id' => session('business.id')]);
        return view('beautysaloons::notifications.settings', compact('setting'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'sms_enabled' => 'nullable|boolean',
            'email_enabled' => 'nullable|boolean',
            'push_enabled' => 'nullable|boolean',
            'whatsapp_enabled' => 'nullable|boolean',
            'appointment_reminder_hours' => 'nullable|integer|min:1|max:168',
            'max_retry_count' => 'nullable|integer|min:0|max:10',
            'sms_sender_name' => 'nullable|string|max:50',
            'email_from_name' => 'nullable|string|max:100',
            'email_from_address' => 'nullable|email|max:191',
        ]);

        foreach (['sms_enabled','email_enabled','push_enabled','whatsapp_enabled'] as $field) {
            $data[$field] = (bool) ($data[$field] ?? false);
        }

        BeautyNotificationSetting::updateOrCreate(['business_id' => session('business.id')], $data + ['updated_by' => auth()->id()]);

        return back()->with('status', __('beautysaloons::notifications.settings_saved'));
    }

    public function test(Request $request, BeautyNotificationService $service)
    {
        $data = $request->validate([
            'recipient' => 'required|string|max:191',
            'channel' => 'required|in:sms,email,push,whatsapp,in_app',
            'message' => 'required|string',
        ]);

        $service->queueFromTemplate('manual_test', $data['channel'], $data['recipient'], [], ['message' => $data['message']]);

        return back()->with('status', __('beautysaloons::notifications.test_queued'));
    }
}
