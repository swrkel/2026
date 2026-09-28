<?php

namespace Modules\RestaurantNew\Http\Controllers\GiftVoucher;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewGiftVoucher;
use Modules\RestaurantNew\Services\GiftVoucher\RestaurantGiftVoucherService;

class RestaurantGiftVoucherController extends Controller
{
    protected RestaurantGiftVoucherService $service;

    public function __construct(RestaurantGiftVoucherService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $vouchers = $this->service->list($request->all())->paginate(25);
        return view('restaurantnew::gift_vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        return view('restaurantnew::gift_vouchers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => ['required', 'integer'],
            'business_location_id' => ['nullable', 'integer'],
            'voucher_no' => ['required', 'string', 'max:40'],
            'voucher_type' => ['required', 'string', 'max:30'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_mobile' => ['nullable', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'issue_amount' => ['required', 'numeric', 'min:0'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date'],
        ]);
        $data['created_by'] = auth()->id();

        $this->service->issue($data);
        return redirect()->route('restaurant-new.gift-vouchers.index')->with('status', __('restaurantnew::gift_voucher.issued_success'));
    }

    public function show(RestaurantNewGiftVoucher $gift_voucher)
    {
        $gift_voucher->load('transactions');
        return view('restaurantnew::gift_vouchers.show', ['voucher' => $gift_voucher]);
    }

    public function redeem(Request $request, RestaurantNewGiftVoucher $gift_voucher)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.0001'], 'note' => ['nullable', 'string']]);
        $this->service->redeem($gift_voucher, (float) $data['amount'], ['note' => $data['note'] ?? null, 'created_by' => auth()->id()]);
        return back()->with('status', __('restaurantnew::gift_voucher.redeemed_success'));
    }
}
