<?php

namespace Modules\AirlineTicketingNew\Services\Ticketing;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\AirlineTicketingNew\Entities\Invoice;
use Modules\AirlineTicketingNew\Entities\Payment;
use Modules\AirlineTicketingNew\Entities\PaymentAllocation;
use Modules\AirlineTicketingNew\Entities\Receipt;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class PaymentService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function receive(Invoice $invoice, array $data): Payment
    {
        if ((float) $data['amount'] <= 0 || (float) $data['amount'] > (float) $invoice->due_total) {
            throw ValidationException::withMessages([
                'amount' => __('airlineticketingnew::ticketing.invalid_payment_amount'),
            ]);
        }

        return DB::transaction(function () use ($invoice, $data): Payment {
            $payment = Payment::query()->create([
                'business_id' => $invoice->business_id,
                'business_location_id' => $invoice->business_location_id,
                'store_id' => $invoice->store_id,
                'payment_no' => $this->numbers->next(
                    $invoice->business_id,
                    $invoice->business_location_id,
                    $invoice->store_id,
                    'payment',
                    'ATP'
                ),
                'payment_date' => $data['payment_date'],
                'reservation_id' => $invoice->reservation_id,
                'invoice_id' => $invoice->id,
                'customer_type' => $invoice->customer_type,
                'corporate_customer_id' => $invoice->corporate_customer_id,
                'passenger_id' => $invoice->passenger_id,
                'payment_method' => $data['payment_method'],
                'payment_account' => $data['payment_account'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'currency_code' => $invoice->currency_code,
                'exchange_rate' => $invoice->exchange_rate,
                'amount' => $data['amount'],
                'base_amount' => round((float) $data['amount'] * (float) $invoice->exchange_rate, 4),
                'status' => 'posted',
                'remarks' => $data['remarks'] ?? null,
                'received_by' => auth()->id(),
            ]);

            PaymentAllocation::query()->create([
                'business_id' => $invoice->business_id,
                'business_location_id' => $invoice->business_location_id,
                'store_id' => $invoice->store_id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'reservation_id' => $invoice->reservation_id,
                'allocated_amount' => $data['amount'],
            ]);

            $newPaid = round((float) $invoice->paid_total + (float) $data['amount'], 4);
            $newDue = max(0, round((float) $invoice->grand_total - $newPaid, 4));

            $invoice->update([
                'paid_total' => $newPaid,
                'due_total' => $newDue,
                'status' => $newDue <= 0 ? 'paid' : 'partially_paid',
            ]);

            Receipt::query()->create([
                'business_id' => $invoice->business_id,
                'business_location_id' => $invoice->business_location_id,
                'store_id' => $invoice->store_id,
                'receipt_no' => $this->numbers->next(
                    $invoice->business_id,
                    $invoice->business_location_id,
                    $invoice->store_id,
                    'receipt',
                    'ATRC'
                ),
                'receipt_date' => $data['payment_date'],
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'reservation_id' => $invoice->reservation_id,
                'currency_code' => $invoice->currency_code,
                'amount' => $data['amount'],
                'status' => 'issued',
                'remarks' => $data['remarks'] ?? null,
            ]);

            return $payment->fresh();
        });
    }
}
