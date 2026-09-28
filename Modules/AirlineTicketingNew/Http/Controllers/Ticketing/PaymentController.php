<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Ticketing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Invoice;
use Modules\AirlineTicketingNew\Entities\Payment;
use Modules\AirlineTicketingNew\Http\Requests\Ticketing\ReceivePaymentRequest;
use Modules\AirlineTicketingNew\Services\Ticketing\PaymentService;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $records = Payment::query()
            ->forBusiness()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('payment_no', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('airlineticketingnew::ticketing.payments.index', compact('records'));
    }

    public function create(Invoice $invoice)
    {
        abort_unless((int) $invoice->business_id === (int) session('business.id'), 404);
        abort_if((float) $invoice->due_total <= 0, 422);

        return view('airlineticketingnew::ticketing.payments.form', compact('invoice'));
    }

    public function store(
        ReceivePaymentRequest $request,
        Invoice $invoice,
        PaymentService $service
    ) {
        abort_unless((int) $invoice->business_id === (int) session('business.id'), 404);
        $payment = $service->receive($invoice, $request->validated());

        return redirect()
            ->route('airline-ticketing-new.payments.show', $payment)
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::ticketing.payment_received')]);
    }

    public function show(Payment $payment)
    {
        abort_unless((int) $payment->business_id === (int) session('business.id'), 404);

        return view('airlineticketingnew::ticketing.payments.show', compact('payment'));
    }
}
