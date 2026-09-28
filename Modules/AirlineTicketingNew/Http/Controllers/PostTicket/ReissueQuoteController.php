<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\PostTicket;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Services\PostTicket\AdvancedReissueService;

class ReissueQuoteController extends Controller
{
    public function store(Request $request, Ticket $ticket, AdvancedReissueService $service)
    {
        abort_unless((int)$ticket->business_id === (int)session('business.id'), 404);

        $data = $request->validate([
            'fare_difference' => ['nullable','numeric'],
            'tax_difference' => ['nullable','numeric'],
            'penalty_amount' => ['nullable','numeric','min:0'],
            'service_fee' => ['nullable','numeric','min:0'],
            'details_json' => ['nullable','array'],
        ]);

        $service->quote($ticket, $data);

        return back()->with('status', ['success' => 1, 'msg' => 'Reissue quote created successfully.']);
    }
}
