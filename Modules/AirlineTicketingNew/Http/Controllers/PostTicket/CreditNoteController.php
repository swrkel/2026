<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\PostTicket;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\CreditNote;

class CreditNoteController extends Controller
{
    public function index()
    {
        $records = CreditNote::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::post-ticket.credit-notes.index', compact('records'));
    }

    public function show(CreditNote $creditNote)
    {
        abort_unless((int) $creditNote->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::post-ticket.credit-notes.show', compact('creditNote'));
    }
}
