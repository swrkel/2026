<?php

namespace Modules\AirlineTicketingNew\Services\Settlements;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\SupplierPayment;
use Modules\AirlineTicketingNew\Entities\SupplierSettlement;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class SupplierPaymentService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function pay(SupplierSettlement $settlement, array $data): SupplierPayment
    {
        return DB::transaction(function () use ($settlement, $data): SupplierPayment {
            $payment = SupplierPayment::query()->create([
                'business_id' => $settlement->business_id,
                'business_location_id' => $settlement->business_location_id,
                'store_id' => $settlement->store_id,
                'payment_no' => $this->numbers->next(
                    $settlement->business_id,
                    $settlement->business_location_id,
                    $settlement->store_id,
                    'supplier_payment',
                    'ATSP'
                ),
                'supplier_id' => $settlement->supplier_id,
                'settlement_id' => $settlement->id,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'payment_account' => $data['payment_account'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'currency_code' => $settlement->currency_code,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'amount' => $data['amount'],
                'base_amount' => round((float)$data['amount'] * (float)($data['exchange_rate'] ?? 1), 4),
                'status' => 'posted',
                'remarks' => $data['remarks'] ?? null,
                'approved_by' => auth()->id(),
                'paid_by' => auth()->id(),
            ]);

            $paid = round((float)$settlement->paid_amount + (float)$data['amount'], 4);
            $due = max(0, round((float)$settlement->net_payable - $paid, 4));

            $settlement->update([
                'paid_amount' => $paid,
                'due_amount' => $due,
                'status' => $due <= 0 ? 'paid' : 'partially_paid',
            ]);

            return $payment;
        });
    }
}
