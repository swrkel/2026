<?php

namespace Modules\BeautySaloons\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Portal\PortalCustomerSessionService;

class PortalVoucherController extends Controller
{
    public function index(PortalCustomerSessionService $session)
    {
        $customer = $session->customer();
        return view('beautysaloons::portal.simple', ['customer' => $customer, 'title' => str_replace('Portal', '', class_basename(static::class))]);
    }

    public function edit(PortalCustomerSessionService $session)
    {
        $customer = $session->customer();
        return view('beautysaloons::portal.profile', compact('customer'));
    }

    public function update(Request $request, PortalCustomerSessionService $session)
    {
        $customer = $session->customer();
        $customer->update($request->only(['name', 'mobile', 'email', 'address']));
        return back()->with('status', 'Profile updated successfully.');
    }
}
