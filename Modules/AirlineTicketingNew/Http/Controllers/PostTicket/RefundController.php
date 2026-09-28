<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\PostTicket;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Refund;

class RefundController extends Controller
{
    public function index()
    {
        $records = Refund::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::post-ticket.refunds.index', compact('records'));
    }

    public function show(Refund $refund)
    {
        abort_unless((int) $refund->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::post-ticket.refunds.show', compact('refund'));
    }
}
