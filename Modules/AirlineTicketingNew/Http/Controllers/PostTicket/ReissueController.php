<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\PostTicket;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Entities\TicketReissue;
use Modules\AirlineTicketingNew\Http\Requests\PostTicket\RequestReissueRequest;
use Modules\AirlineTicketingNew\Services\PostTicket\ReissueService;

class ReissueController extends Controller
{
    public function index(Request $request)
    {
        $records = TicketReissue::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::post-ticket.reissues.index', compact('records'));
    }

    public function create(Ticket $ticket)
    {
        abort_unless((int) $ticket->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::post-ticket.reissues.form', compact('ticket'));
    }

    public function store(RequestReissueRequest $request, Ticket $ticket, ReissueService $service)
    {
        abort_unless((int) $ticket->business_id === (int) session('business.id'), 404);
        $record = $service->request($ticket, $request->validated());

        return redirect()->route('airline-ticketing-new.reissues.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::postticket.reissue_requested')]);
    }
}
