<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Transactions;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Quotation;
use Modules\AirlineTicketingNew\Entities\Reservation;
use Modules\AirlineTicketingNew\Http\Requests\Transactions\ChangeReservationStatusRequest;
use Modules\AirlineTicketingNew\Http\Requests\Transactions\ConvertQuotationRequest;
use Modules\AirlineTicketingNew\Services\Transactions\ReservationService;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $records = Reservation::query()
            ->forBusiness()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('reservation_no', 'like', "%{$search}%")
                        ->orWhere('pnr_code', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('airlineticketingnew::transactions.reservations.index', compact('records'));
    }

    public function convert(ConvertQuotationRequest $request, Quotation $quotation, ReservationService $service)
    {
        abort_unless((int) $quotation->business_id === (int) session('business.id'), 404);
        abort_if($quotation->status === 'converted', 422, __('airlineticketingnew::transactions.already_converted'));

        $reservation = $service->createFromQuotation($quotation->load('segments'), $request->validated());

        return redirect()
            ->route('airline-ticketing-new.reservations.show', $reservation)
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::transactions.reservation_created')]);
    }

    public function show(Reservation $reservation)
    {
        abort_unless((int) $reservation->business_id === (int) session('business.id'), 404);
        $reservation->load(['segments', 'passengers', 'statusHistory']);

        return view('airlineticketingnew::transactions.reservations.show', compact('reservation'));
    }

    public function changeStatus(
        ChangeReservationStatusRequest $request,
        Reservation $reservation,
        ReservationService $service
    ) {
        abort_unless((int) $reservation->business_id === (int) session('business.id'), 404);
        $service->changeStatus($reservation, $request->validated('status'), $request->validated('reason'));

        return back()->with('status', [
            'success' => 1,
            'msg' => __('airlineticketingnew::transactions.status_updated'),
        ]);
    }
}
