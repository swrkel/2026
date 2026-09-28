<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewKdsQueueItem;
use Modules\RestaurantNew\Entities\RestaurantNewKdsScreen;
use Modules\RestaurantNew\Services\EnterpriseKdsService;

class EnterpriseKdsController extends Controller
{
    protected EnterpriseKdsService $service;

    public function __construct(EnterpriseKdsService $service)
    {
        $this->service = $service;
    }

    public function board(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $locationId = $request->get('location_id');
        $section = $request->get('section');
        $queue = $this->service->dashboardQueue($businessId, $locationId, $section);
        $screens = RestaurantNewKdsScreen::where('business_id', $businessId)->where('is_active', true)->orderBy('screen_name')->get();
        return view('restaurantnew::enterprise_kds.board', compact('queue', 'screens', 'section'));
    }

    public function queueJson(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $queue = $this->service->dashboardQueue($businessId, $request->get('location_id'), $request->get('section'))
            ->map(function ($item) {
                $item->timer = $this->service->timerSummary($item);
                return $item;
            });
        return response()->json(['data' => $queue]);
    }

    public function pushItem(Request $request)
    {
        $data = $request->validate([
            'business_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'order_id' => 'required|integer',
            'order_item_id' => 'nullable|integer',
            'kot_id' => 'nullable|integer',
            'order_no' => 'nullable|string|max:80',
            'item_name' => 'required|string|max:191',
            'quantity' => 'nullable|numeric',
            'order_type' => 'nullable|string|max:40',
            'kitchen_section' => 'nullable|string|max:80',
            'priority' => 'nullable|string|max:40',
            'expected_prep_minutes' => 'nullable|integer',
            'kitchen_note' => 'nullable|string',
        ]);
        $data['business_id'] = $data['business_id'] ?? $request->session()->get('user.business_id');
        $item = $this->service->pushOrderItem($data);
        return response()->json(['success' => true, 'item' => $item]);
    }

    public function changeStatus(Request $request, $id)
    {
        $item = RestaurantNewKdsQueueItem::findOrFail($id);
        $data = $request->validate(['status' => 'required|string|max:50', 'remarks' => 'nullable|string']);
        $item = $this->service->changeStatus($item, $data['status'], $data['remarks'] ?? null);
        return response()->json(['success' => true, 'item' => $item, 'timer' => $this->service->timerSummary($item)]);
    }

    public function reassignChef(Request $request, $id)
    {
        $item = RestaurantNewKdsQueueItem::findOrFail($id);
        $item = $this->service->reassignChef($item, $request->get('chef_id'));
        return response()->json(['success' => true, 'item' => $item]);
    }

    public function screens(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $screens = RestaurantNewKdsScreen::where('business_id', $businessId)->latest('id')->paginate(25);
        return view('restaurantnew::enterprise_kds.screens', compact('screens'));
    }

    public function storeScreen(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $data = $request->validate([
            'screen_name' => 'required|string|max:120',
            'screen_code' => 'required|string|max:80',
            'location_id' => 'nullable|integer',
            'kitchen_section' => 'nullable|string|max:80',
            'sound_enabled' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);
        $data['business_id'] = $businessId;
        RestaurantNewKdsScreen::create($data);
        return back()->with('status', __('restaurantnew::lang.kds_screen_saved'));
    }
}
