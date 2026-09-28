<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Passengers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\CorporateContact;
use Modules\AirlineTicketingNew\Entities\CorporateCustomer;

class CorporateContactController extends Controller
{
    public function index(CorporateCustomer $corporateCustomer)
    {
        abort_unless((int) $corporateCustomer->business_id === (int) session('business.id'), 404);

        $records = CorporateContact::query()
            ->where('business_id', (int) session('business.id'))
            ->where('corporate_customer_id', $corporateCustomer->id)
            ->latest('id')
            ->get();

        return view('airlineticketingnew::passengers.children.contacts', compact('corporateCustomer', 'records'));
    }

    public function store(Request $request, CorporateCustomer $corporateCustomer)
    {
        abort_unless((int) $corporateCustomer->business_id === (int) session('business.id'), 404);

        $data = $request->validate([
            'name' => ['required','string','max:150'],
            'designation' => ['nullable','string','max:100'],
            'department' => ['nullable','string','max:100'],
            'email' => ['nullable','email','max:150'],
            'phone' => ['nullable','string','max:40'],
            'alternate_phone' => ['nullable','string','max:40'],
            'is_active' => ['nullable','boolean'],
            'is_primary' => ['nullable','boolean']
        ]);

        $data['business_id'] = (int) session('business.id');
        $data['corporate_customer_id'] = $corporateCustomer->id;
        $data['is_active'] = $request->boolean('is_active');
        if ($request->has('is_primary')) {
            $data['is_primary'] = $request->boolean('is_primary');
        }

        CorporateContact::query()->create($data);

        return back()->with('status', [
            'success' => 1,
            'msg' => __('airlineticketingnew::profiles.saved_successfully'),
        ]);
    }
}
