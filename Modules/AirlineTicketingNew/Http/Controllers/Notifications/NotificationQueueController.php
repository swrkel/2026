<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Notifications;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\NotificationLog;
use Modules\AirlineTicketingNew\Services\Notifications\NotificationDispatchService;

class NotificationQueueController extends Controller
{
    public function index()
    {
        $records = NotificationLog::query()->forBusiness()->latest('id')->paginate(50);
        return view('airlineticketingnew::notifications.queue.index', compact('records'));
    }

    public function dispatch(NotificationDispatchService $service)
    {
        $count = $service->dispatchQueued((int)session('business.id'));
        return back()->with('status', ['success' => 1, 'msg' => $count . ' notifications prepared for the communication bridge.']);
    }
}
