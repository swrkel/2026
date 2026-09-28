<?php

namespace Modules\MyHealthMembers\Http\Controllers\Hospital;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthAppointment;
use Modules\MyHealthMembers\Entities\MyHealthMember;

class MyHealthReceptionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $members = collect();

        if ($search) {
            $members = MyHealthMember::query()
                ->where('member_code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")
                ->orWhere('nic_no', 'like', "%{$search}%")
                ->limit(20)
                ->get();
        }

        $queue = MyHealthAppointment::with(['member', 'doctor', 'room'])
            ->whereDate('appointment_date', now()->toDateString())
            ->orderBy('queue_no')
            ->get();

        return view('myhealthmembers::hospital.reception.index', compact('members', 'queue', 'search'));
    }
}
