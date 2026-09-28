<?php

namespace Modules\RestaurantNew\Http\Controllers\Corporate;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Services\Corporate\RestaurantCorporateAccountService;

class RestaurantCorporateAccountController extends Controller
{
    protected RestaurantCorporateAccountService $service;

    public function __construct(RestaurantCorporateAccountService $service)
    {
        $this->middleware(['auth', 'restaurantnew.scope']);
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $accounts = $this->service->accounts($businessId, $request->get('location_id'))->paginate(25);
        return view('restaurantnew::corporate_accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('restaurantnew::corporate_accounts.create');
    }

    public function store(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $data = $request->validate([
            'account_code' => 'required|string|max:50',
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'credit_limit' => 'nullable|numeric',
            'credit_days' => 'nullable|integer',
        ]);
        $data['business_id'] = $businessId;
        $data['created_by'] = auth()->id();
        $this->service->createAccount($data);
        return redirect()->route('restaurant-new.corporate.index')->with('status', __('corporate.account_created'));
    }

    public function invoices()
    {
        return view('restaurantnew::corporate_accounts.invoices');
    }

    public function statement()
    {
        return view('restaurantnew::corporate_accounts.statement');
    }
}
