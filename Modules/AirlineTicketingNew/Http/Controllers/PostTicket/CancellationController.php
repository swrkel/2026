<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\PostTicket;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Entities\TicketCancellation;
use Modules\AirlineTicketingNew\Http\Requests\PostTicket\ApproveRefundRequest;
use Modules\AirlineTicketingNew\Http\Requests\PostTicket\RequestCancellationRequest;
use Modules\AirlineTicketingNew\Services\PostTicket\CancellationRefundService;

class CancellationController extends Controller
{
    public function index()
    {
        $records = TicketCancellation::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::post-ticket.cancellations.index', compact('records'));
    }

    public function create(Ticket $ticket)
    {
        abort_unless((int) $ticket->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::post-ticket.cancellations.form', compact('ticket'));
    }

    public function store(RequestCancellationRequest $request, Ticket $ticket, CancellationRefundService $service)
    {
        abort_unless((int) $ticket->business_id === (int) session('business.id'), 404);
        $service->requestCancellation($ticket, $request->validated());

        return redirect()->route('airline-ticketing-new.cancellations.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::postticket.cancellation_requested')]);
    }

    public function approve(ApproveRefundRequest $request, TicketCancellation $cancellation, CancellationRefundService $service)
    {
        abort_unless((int) $cancellation->business_id === (int) session('business.id'), 404);
        $service->approveAndCreateRefund($cancellation, $request->validated());

        return back()->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::postticket.refund_created')]);
    }
}
