<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Ticketing;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Receipt;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt)
    {
        abort_unless((int) $receipt->business_id === (int) session('business.id'), 404);

        return view('airlineticketingnew::ticketing.receipts.show', compact('receipt'));
    }

    public function print(Receipt $receipt)
    {
        abort_unless((int) $receipt->business_id === (int) session('business.id'), 404);

        return view('airlineticketingnew::ticketing.receipts.print', compact('receipt'));
    }
}
