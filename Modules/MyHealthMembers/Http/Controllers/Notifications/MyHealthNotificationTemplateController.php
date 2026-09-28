<?php

namespace Modules\MyHealthMembers\Http\Controllers\Notifications;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthNotificationTemplate;

class MyHealthNotificationTemplateController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::notifications.templates.index', [
            'templates' => MyHealthNotificationTemplate::latest('id')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('myhealthmembers::notifications.templates.form', [
            'template' => new MyHealthNotificationTemplate(),
        ]);
    }

    public function store(Request $request)
    {
        MyHealthNotificationTemplate::create($this->validated($request));

        return redirect()->route('myhealth.notifications.templates.index')->with('status', 'Template created successfully.');
    }

    public function edit(MyHealthNotificationTemplate $template)
    {
        return view('myhealthmembers::notifications.templates.form', compact('template'));
    }

    public function update(Request $request, MyHealthNotificationTemplate $template)
    {
        $template->update($this->validated($request));

        return redirect()->route('myhealth.notifications.templates.index')->with('status', 'Template updated successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:191'],
            'channel' => ['required', 'in:sms,email,whatsapp'],
            'subject' => ['nullable', 'string', 'max:191'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
