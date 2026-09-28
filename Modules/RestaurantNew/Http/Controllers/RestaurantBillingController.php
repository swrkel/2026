<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewBill;
use Modules\RestaurantNew\Entities\RestaurantNewOrder;
use Modules\RestaurantNew\Services\RestaurantBillingService;

class RestaurantBillingController extends Controller
{
    public function __construct(private RestaurantBillingService $billingService)
    {
        $this->middleware(['auth', 'restaurantnew.business.scope']);
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = $request->get('location_id');

        $bills = RestaurantNewBill::with(['payments'])
            ->forBusiness($businessId)
            ->forLocation($locationId ? (int) $locationId : null)
            ->latest('bill_date')
            ->paginate(25);

        return view('restaurantnew::billing.index', compact('bills'));
    }

    public function create(Request $request)
    {
        $order = null;
        if ($request->filled('order_id')) {
            $order = RestaurantNewOrder::with(['lines'])->findOrFail($request->integer('order_id'));
        }

        return view('restaurantnew::billing.create', compact('order'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'service_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'round_off_amount' => ['nullable', 'numeric'],
            'payments' => ['nullable', 'array'],
            'payments.*.payment_method' => ['required_with:payments', 'string', 'max:50'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0'],
        ]);

        $order = RestaurantNewOrder::with(['lines'])->findOrFail((int) $data['order_id']);
        $bill = $this->billingService->createBillFromOrder($order, $data);

        return redirect()->route('restaurantnew.billing.show', $bill->id)->with('status', __('restaurantnew::lang.bill_created'));
    }

    public function show(RestaurantNewBill $bill)
    {
        $bill->load(['lines', 'payments', 'order']);
        return view('restaurantnew::billing.show', compact('bill'));
    }

    public function receipt(RestaurantNewBill $bill)
    {
        $bill->load(['lines', 'payments']);
        return view('restaurantnew::billing.receipt', compact('bill'));
    }

    public function addPayment(Request $request, RestaurantNewBill $bill)
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'account_id' => ['nullable', 'integer'],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->billingService->addPayment($bill, $data);

        return back()->with('status', __('restaurantnew::lang.payment_added'));
    }

    public function void(Request $request, RestaurantNewBill $bill)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->billingService->voidBill($bill, $data['reason']);

        return redirect()->route('restaurantnew.billing.index')->with('status', __('restaurantnew::lang.bill_voided'));
    }

    public function refund(Request $request, RestaurantNewBill $bill)
    {
        $data = $request->validate([
            'refund_method' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->billingService->createRefund($bill, $data);

        return back()->with('status', __('restaurantnew::lang.refund_created'));
    }
}
