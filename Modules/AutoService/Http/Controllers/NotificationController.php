<?php
namespace Modules\AutoService\Http\Controllers;

use Modules\AutoService\Entities\AutoServiceNotificationLog;

class NotificationController extends AutoServiceBaseController
{
    public function index()
    {
        $logs = AutoServiceNotificationLog::orderByDesc('id')->paginate(50);
        return view('autoservice::notifications.index', compact('logs'));
    }

    public function markSent($id)
    {
        AutoServiceNotificationLog::where('id', $id)->update(['status'=>'sent', 'sent_at'=>now()]);
        return back()->with('status', __('Marked as sent'));
    }
}
