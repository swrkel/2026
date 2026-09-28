<?php

namespace Modules\MyHealthMembers\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class MyHealthNotificationCentreController extends Controller
{
    public function index()
    {
        $templates = collect();
        if (DB::getSchemaBuilder()->hasTable('myhealth_notification_templates')) {
            $templates = DB::table('myhealth_notification_templates')->orderBy('name')->get();
        }

        return view('myhealthmembers::admin.notifications.index', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        if (DB::getSchemaBuilder()->hasTable('myhealth_notification_templates')) {
            DB::table('myhealth_notification_templates')->updateOrInsert(
                ['name' => $request->input('name')],
                [
                    'channel' => $request->input('channel'),
                    'subject' => $request->input('subject'),
                    'body' => $request->input('body'),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return back()->with('status', 'Notification template saved successfully.');
    }
}
