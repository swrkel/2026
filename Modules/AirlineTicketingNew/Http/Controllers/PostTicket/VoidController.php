<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\PostTicket;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Entities\TicketVoid;
use Modules\AirlineTicketingNew\Http\Requests\PostTicket\RequestVoidRequest;
use Modules\AirlineTicketingNew\Services\PostTicket\VoidService;

class VoidController extends Controller
{
    public function index()
    {
        $records = TicketVoid::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::post-ticket.voids.index', compact('records'));
    }

    public function create(Ticket $ticket)
    {
        abort_unless((int) $ticket->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::post-ticket.voids.form', compact('ticket'));
    }

    public function store(RequestVoidRequest $request, Ticket $ticket, VoidService $service)
    {
        abort_unless((int) $ticket->business_id === (int) session('business.id'), 404);
        $service->request($ticket, $request->validated());

        return redirect()->route('airline-ticketing-new.voids.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::postticket.void_requested')]);
    }

    public function approve(TicketVoid $void, VoidService $service)
    {
        abort_unless((int) $void->business_id === (int) session('business.id'), 404);
        $service->approve($void);

        return back()->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::postticket.void_approved')]);
    }
}
