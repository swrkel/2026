<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Ticketing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Reservation;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Http\Requests\Ticketing\IssueTicketRequest;
use Modules\AirlineTicketingNew\Services\Ticketing\TicketIssueService;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $records = Ticket::query()
            ->forBusiness()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('ticket_no', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('currency_code', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('airlineticketingnew::ticketing.tickets.index', compact('records'));
    }

    public function create(Reservation $reservation)
    {
        abort_unless((int) $reservation->business_id === (int) session('business.id'), 404);
        abort_unless(in_array($reservation->status, ['reserved','confirmed','on_hold'], true), 422);

        $reservation->load(['segments','passengers']);

        return view('airlineticketingnew::ticketing.tickets.form', compact('reservation'));
    }

    public function store(
        IssueTicketRequest $request,
        Reservation $reservation,
        TicketIssueService $service
    ) {
        abort_unless((int) $reservation->business_id === (int) session('business.id'), 404);
        $ticket = $service->issue($reservation->load(['segments','passengers']), $request->validated());

        return redirect()
            ->route('airline-ticketing-new.tickets.show', $ticket)
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::ticketing.ticket_issued')]);
    }

    public function show(Ticket $ticket)
    {
        abort_unless((int) $ticket->business_id === (int) session('business.id'), 404);
        $ticket->load('segments');

        return view('airlineticketingnew::ticketing.tickets.show', compact('ticket'));
    }
}
