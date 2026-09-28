<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyWaitlist;
use Modules\BeautySaloons\Services\AdvancedSchedulerService;

class WaitlistController extends Controller
{
    public function index()
    {
        $waitlists = BeautyWaitlist::orderByDesc('id')->paginate(25);
        return view('beautysaloons::scheduler.waitlist', compact('waitlists'));
    }

    public function store(Request $request, AdvancedSchedulerService $scheduler)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|integer',
            'service_id' => 'nullable|integer',
            'preferred_staff_id' => 'nullable|integer',
            'preferred_date' => 'nullable|date',
            'preferred_start_time' => 'nullable',
            'preferred_end_time' => 'nullable',
            'priority' => 'nullable|string|max:20',
            'note' => 'nullable|string',
        ]);

        $scheduler->addToWaitlist($data);
        return redirect()->back()->with('status', __('beautysaloons::scheduler.waitlist_saved'));
    }

    public function convert($id)
    {
        BeautyWaitlist::where('id', $id)->update(['status' => 'converted', 'converted_at' => now()]);
        return redirect()->back()->with('status', __('beautysaloons::scheduler.waitlist_converted'));
    }
}
