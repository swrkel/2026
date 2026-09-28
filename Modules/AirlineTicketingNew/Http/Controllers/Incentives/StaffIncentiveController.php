<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Incentives;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\StaffIncentive;
use Modules\AirlineTicketingNew\Entities\Ticket;
use Modules\AirlineTicketingNew\Services\Incentives\StaffIncentiveService;

class StaffIncentiveController extends Controller
{
    public function index()
    {
        $records = StaffIncentive::query()->forBusiness()->latest('id')->paginate(25);
        return view('airlineticketingnew::incentives.index', compact('records'));
    }

    public function store(Request $request, Ticket $ticket, StaffIncentiveService $service)
    {
        abort_unless((int)$ticket->business_id === (int)session('business.id'), 404);

        $data = $request->validate([
            'user_id' => ['required','integer'],
            'invoice_id' => ['nullable','integer'],
            'incentive_date' => ['required','date'],
            'calculation_type' => ['required','in:percentage,fixed'],
            'basis_amount' => ['required','numeric','min:0'],
            'rate' => ['required','numeric','min:0'],
            'remarks' => ['nullable','string'],
        ]);

        $service->create($ticket, $data);

        return back()->with('status', ['success' => 1, 'msg' => 'Staff incentive created successfully.']);
    }
}
