<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Passengers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\CorporateCustomer;
use Modules\AirlineTicketingNew\Http\Requests\Passengers\SaveCorporateCustomerRequest;
use Modules\AirlineTicketingNew\Services\Passengers\ProfileNumberService;

class CorporateCustomerController extends Controller
{
    public function index(Request $request)
    {
        $records = CorporateCustomer::query()
            ->forBusiness()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('customer_no', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('airlineticketingnew::corporate-customers.index', compact('records'));
    }

    public function create()
    {
        return view('airlineticketingnew::corporate-customers.form', ['record' => new CorporateCustomer()]);
    }

    public function store(SaveCorporateCustomerRequest $request, ProfileNumberService $numberService)
    {
        $businessId = (int) session('business.id');

        CorporateCustomer::query()->create(array_merge($request->validated(), [
            'business_id' => $businessId,
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
            'customer_no' => $numberService->next($businessId, 'corporate_customer', 'CORP'),
        ]));

        return redirect()->route('airline-ticketing-new.corporate-customers.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::profiles.saved_successfully')]);
    }

    public function edit(CorporateCustomer $corporateCustomer)
    {
        abort_unless((int) $corporateCustomer->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::corporate-customers.form', ['record' => $corporateCustomer]);
    }

    public function update(SaveCorporateCustomerRequest $request, CorporateCustomer $corporateCustomer)
    {
        abort_unless((int) $corporateCustomer->business_id === (int) session('business.id'), 404);
        $corporateCustomer->fill($request->validated())->save();

        return redirect()->route('airline-ticketing-new.corporate-customers.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::profiles.saved_successfully')]);
    }
}
