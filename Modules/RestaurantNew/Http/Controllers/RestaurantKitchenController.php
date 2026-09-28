<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenTicket;
use Modules\RestaurantNew\Entities\RestaurantNewOrder;
use Modules\RestaurantNew\Services\RestaurantKitchenService;

class RestaurantKitchenController extends Controller
{
    protected RestaurantKitchenService $kitchenService;

    public function __construct(RestaurantKitchenService $kitchenService)
    {
        $this->kitchenService = $kitchenService;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('business_location_id');
        $sectionId = $request->get('kitchen_section_id');
        $tickets = $this->kitchenService->queue($businessId, $locationId, $sectionId);

        return view('restaurantnew::kitchen.index', compact('tickets', 'locationId', 'sectionId'));
    }

    public function queue(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        return response()->json([
            'tickets' => $this->kitchenService->queue($businessId, $request->get('business_location_id'), $request->get('kitchen_section_id')),
        ]);
    }

    public function createFromOrder(Request $request, RestaurantNewOrder $order)
    {
        $this->authorizeBusiness($request, $order->business_id);
        $tickets = $this->kitchenService->createTicketsForOrder($order, (int) auth()->id());
        return response()->json(['success' => true, 'tickets' => $tickets]);
    }

    public function updateStatus(Request $request, RestaurantNewKitchenTicket $ticket)
    {
        $this->authorizeBusiness($request, $ticket->business_id);
        $validated = $request->validate([
            'status' => 'required|in:new,printed,preparing,completed,cancelled',
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        return response()->json([
            'success' => true,
            'ticket' => $this->kitchenService->updateTicketStatus($ticket, $validated['status'], (int) auth()->id(), $validated['cancel_reason'] ?? null),
        ]);
    }

    public function print(RestaurantNewKitchenTicket $ticket)
    {
        $ticket->load('lines');
        return view('restaurantnew::kitchen.print', compact('ticket'));
    }

    protected function authorizeBusiness(Request $request, int $businessId): void
    {
        abort_unless((int) $request->session()->get('user.business_id') === $businessId, 403);
    }
}
