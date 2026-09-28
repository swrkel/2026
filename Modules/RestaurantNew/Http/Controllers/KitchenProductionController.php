<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenQueue;
use Modules\RestaurantNew\Services\RestaurantKitchenProductionService;

class KitchenProductionController extends Controller
{
    protected RestaurantKitchenProductionService $service;

    public function __construct(RestaurantKitchenProductionService $service)
    {
        $this->service = $service;
    }

    public function board(Request $request)
    {
        $businessId = (int) session('business.id');
        $queues = $this->service->boardData($businessId, $request->location_id, $request->kitchen_section_id);
        return view('restaurantnew::kitchen.production-board', compact('queues'));
    }

    public function data(Request $request)
    {
        $businessId = (int) session('business.id');
        return response()->json(['data' => $this->service->boardData($businessId, $request->location_id, $request->kitchen_section_id)]);
    }

    public function preparing(RestaurantNewKitchenQueue $queue)
    {
        return response()->json(['success' => true, 'queue' => $this->service->markPreparing($queue)]);
    }

    public function ready(RestaurantNewKitchenQueue $queue)
    {
        return response()->json(['success' => true, 'queue' => $this->service->markReady($queue)]);
    }

    public function served(RestaurantNewKitchenQueue $queue)
    {
        return response()->json(['success' => true, 'queue' => $this->service->markServed($queue)]);
    }

    public function priority(Request $request, RestaurantNewKitchenQueue $queue)
    {
        $request->validate(['priority' => 'required|in:low,normal,high,urgent']);
        return response()->json(['success' => true, 'queue' => $this->service->changePriority($queue, $request->priority, $request->notes)]);
    }
}
