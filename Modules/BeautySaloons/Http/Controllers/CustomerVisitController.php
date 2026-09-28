<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyCustomerProfile;
use Modules\BeautySaloons\Services\BeautyCustomerVisitService;

class CustomerVisitController extends Controller
{
    public function store(Request $request, $customerId, BeautyCustomerVisitService $service)
    {
        $customer = BeautyCustomerProfile::findOrFail($customerId);
        $data = $request->all();
        $data['customer_profile_id'] = $customer->id;
        $service->addNote($data);
        return back()->with('status', ['success' => 1, 'msg' => __('beautysaloons::customers.visit_note_added')]);
    }
}
