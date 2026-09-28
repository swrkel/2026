<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyNotificationLog;

class NotificationReportController extends Controller
{
    public function delivery(Request $request)
    {
        $query = BeautyNotificationLog::query()
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));

        $summary = [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'retry' => (clone $query)->where('status', 'retry')->count(),
        ];

        $logs = $query->latest('id')->paginate(50);

        return view('beautysaloons::notifications.reports.delivery', compact('summary', 'logs'));
    }
}
