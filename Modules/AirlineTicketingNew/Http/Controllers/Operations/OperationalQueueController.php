<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Operations;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\OperationalTask;
use Modules\AirlineTicketingNew\Services\Operations\OperationalQueueService;

class OperationalQueueController extends Controller
{
    public function index()
    {
        $records = OperationalTask::query()->forBusiness()->orderByRaw("FIELD(priority,'urgent','high','normal','low')")->orderBy('due_at')->paginate(50);
        return view('airlineticketingnew::operations.queue.index', compact('records'));
    }

    public function rebuild(OperationalQueueService $service)
    {
        $count = $service->rebuild((int) session('business.id'));
        return back()->with('status', ['success' => 1, 'msg' => $count . ' operational tasks refreshed.']);
    }
}
