<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Ticketing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Invoice;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Http\Requests\Ticketing\CreateInvoiceRequest;
use Modules\AirlineTicketingNew\Services\Ticketing\InvoiceService;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $records = Invoice::query()
            ->forBusiness()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('airlineticketingnew::ticketing.invoices.index', compact('records'));
    }

    public function create(Ticket $ticket)
    {
        abort_unless((int) $ticket->business_id === (int) session('business.id'), 404);

        return view('airlineticketingnew::ticketing.invoices.form', compact('ticket'));
    }

    public function store(CreateInvoiceRequest $request, Ticket $ticket, InvoiceService $service)
    {
        abort_unless((int) $ticket->business_id === (int) session('business.id'), 404);
        $invoice = $service->createFromTicket($ticket, $request->validated());

        return redirect()
            ->route('airline-ticketing-new.invoices.show', $invoice)
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::ticketing.invoice_created')]);
    }

    public function show(Invoice $invoice)
    {
        abort_unless((int) $invoice->business_id === (int) session('business.id'), 404);

        return view('airlineticketingnew::ticketing.invoices.show', compact('invoice'));
    }
}
