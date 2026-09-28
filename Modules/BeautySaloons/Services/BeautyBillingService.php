<?php

namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyBill;
use Modules\BeautySaloons\Entities\BeautyBillLine;
use Modules\BeautySaloons\Entities\BeautyBillPayment;

class BeautyBillingService
{
    public function calculate(array $lines, float $discount = 0): array
    {
        $subTotal = 0;
        foreach ($lines as $line) {
            $qty = (float)($line['quantity'] ?? 1);
            $price = (float)($line['unit_price'] ?? 0);
            $subTotal += $qty * $price;
        }
        $net = max($subTotal - $discount, 0);
        return ['sub_total' => $subTotal, 'discount_amount' => $discount, 'tax_amount' => 0, 'net_total' => $net];
    }

    public function checkout(array $payload): BeautyBill
    {
        return DB::transaction(function () use ($payload) {
            $totals = $this->calculate($payload['lines'] ?? [], (float)($payload['discount_amount'] ?? 0));
            $paid = collect($payload['payments'] ?? [])->sum(fn ($p) => (float)($p['amount'] ?? 0));

            $bill = BeautyBill::create(array_merge($totals, [
                'business_id' => $payload['business_id'] ?? null,
                'business_location_id' => $payload['business_location_id'] ?? null,
                'bill_no' => $payload['bill_no'] ?? $this->nextBillNo(),
                'bill_date' => $payload['bill_date'] ?? now()->toDateString(),
                'customer_id' => $payload['customer_id'] ?? null,
                'appointment_id' => $payload['appointment_id'] ?? null,
                'paid_amount' => $paid,
                'balance_amount' => max($totals['net_total'] - $paid, 0),
                'payment_status' => $paid >= $totals['net_total'] ? 'paid' : ($paid > 0 ? 'partial' : 'due'),
                'status' => 'final',
                'created_by' => $payload['created_by'] ?? null,
            ]));

            foreach ($payload['lines'] ?? [] as $line) {
                BeautyBillLine::create(array_merge($line, ['bill_id' => $bill->id]));
            }
            foreach ($payload['payments'] ?? [] as $payment) {
                BeautyBillPayment::create(array_merge($payment, ['bill_id' => $bill->id]));
            }
            return $bill;
        });
    }

    public function nextBillNo(): string
    {
        return 'BSB-' . now()->format('Ymd') . '-' . str_pad((string)(BeautyBill::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
