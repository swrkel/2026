<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewBill;
use Modules\RestaurantNew\Entities\RestaurantNewBillLine;
use Modules\RestaurantNew\Entities\RestaurantNewBillPayment;
use Modules\RestaurantNew\Entities\RestaurantNewOrder;
use Modules\RestaurantNew\Entities\RestaurantNewRefund;

class RestaurantBillingService
{
    public function createBillFromOrder(RestaurantNewOrder $order, array $payload): RestaurantNewBill
    {
        return DB::transaction(function () use ($order, $payload) {
            $businessId = (int) $order->business_id;
            $locationId = (int) $order->location_id;

            $subtotal = $this->calculateOrderSubtotal($order);
            $discountAmount = (float) Arr::get($payload, 'discount_amount', 0);
            $taxAmount = (float) Arr::get($payload, 'tax_amount', 0);
            $serviceCharge = (float) Arr::get($payload, 'service_charge_amount', 0);
            $roundOff = (float) Arr::get($payload, 'round_off_amount', 0);
            $grandTotal = $subtotal - $discountAmount + $taxAmount + $serviceCharge + $roundOff;

            $bill = RestaurantNewBill::create([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'restaurant_new_order_id' => $order->id,
                'bill_no' => $payload['bill_no'] ?? $this->nextBillNumber($businessId, $locationId),
                'bill_date' => Carbon::parse($payload['bill_date'] ?? now()),
                'customer_id' => $payload['customer_id'] ?? $order->customer_id ?? null,
                'cashier_id' => $payload['cashier_id'] ?? auth()->id(),
                'waiter_id' => $payload['waiter_id'] ?? $order->waiter_id ?? null,
                'subtotal' => $subtotal,
                'discount_type' => $payload['discount_type'] ?? 'fixed',
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'service_charge_amount' => $serviceCharge,
                'round_off_amount' => $roundOff,
                'grand_total' => $grandTotal,
                'paid_total' => 0,
                'balance_due' => $grandTotal,
                'change_amount' => 0,
                'payment_status' => 'due',
                'bill_status' => 'open',
                'notes' => $payload['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($order->lines ?? [] as $line) {
                RestaurantNewBillLine::create([
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'restaurant_new_bill_id' => $bill->id,
                    'restaurant_new_order_line_id' => $line->id,
                    'menu_item_id' => $line->menu_item_id ?? null,
                    'item_name' => $line->item_name ?? $line->name ?? 'Menu Item',
                    'variant_name' => $line->variant_name ?? null,
                    'quantity' => $line->quantity ?? 1,
                    'unit_price' => $line->unit_price ?? 0,
                    'discount_amount' => $line->discount_amount ?? 0,
                    'tax_amount' => $line->tax_amount ?? 0,
                    'service_charge_amount' => $line->service_charge_amount ?? 0,
                    'line_total' => $line->line_total ?? (($line->quantity ?? 1) * ($line->unit_price ?? 0)),
                    'line_status' => 'active',
                    'created_by' => auth()->id(),
                ]);
            }

            if (! empty($payload['payments'])) {
                foreach ($payload['payments'] as $payment) {
                    $this->addPayment($bill, $payment);
                }
            }

            return $bill->fresh(['lines', 'payments']);
        });
    }

    public function addPayment(RestaurantNewBill $bill, array $payment): RestaurantNewBillPayment
    {
        return DB::transaction(function () use ($bill, $payment) {
            $row = RestaurantNewBillPayment::create([
                'business_id' => $bill->business_id,
                'location_id' => $bill->location_id,
                'restaurant_new_bill_id' => $bill->id,
                'payment_date' => Carbon::parse($payment['payment_date'] ?? now()),
                'payment_method' => $payment['payment_method'] ?? 'cash',
                'account_id' => $payment['account_id'] ?? null,
                'reference_no' => $payment['reference_no'] ?? null,
                'card_type' => $payment['card_type'] ?? null,
                'card_last_four' => $payment['card_last_four'] ?? null,
                'amount' => (float) ($payment['amount'] ?? 0),
                'payment_status' => 'posted',
                'note' => $payment['note'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->refreshBillPaymentStatus($bill->fresh());

            return $row;
        });
    }

    public function voidBill(RestaurantNewBill $bill, string $reason): RestaurantNewBill
    {
        $bill->update([
            'bill_status' => 'void',
            'payment_status' => 'void',
            'void_reason' => $reason,
            'voided_by' => auth()->id(),
            'voided_at' => now(),
        ]);

        return $bill->fresh();
    }

    public function createRefund(RestaurantNewBill $bill, array $payload): RestaurantNewRefund
    {
        return RestaurantNewRefund::create([
            'business_id' => $bill->business_id,
            'location_id' => $bill->location_id,
            'restaurant_new_bill_id' => $bill->id,
            'refund_no' => $payload['refund_no'] ?? $this->nextRefundNumber((int) $bill->business_id, (int) $bill->location_id),
            'refund_date' => Carbon::parse($payload['refund_date'] ?? now()),
            'refund_method' => $payload['refund_method'] ?? 'cash',
            'amount' => (float) ($payload['amount'] ?? 0),
            'reason' => $payload['reason'] ?? null,
            'status' => 'posted',
            'approved_by' => $payload['approved_by'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    public function refreshBillPaymentStatus(RestaurantNewBill $bill): RestaurantNewBill
    {
        $paid = (float) $bill->payments()->where('payment_status', 'posted')->sum('amount');
        $balance = max(((float) $bill->grand_total) - $paid, 0);
        $change = max($paid - ((float) $bill->grand_total), 0);

        $bill->update([
            'paid_total' => $paid,
            'balance_due' => $balance,
            'change_amount' => $change,
            'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'due'),
            'bill_status' => $balance <= 0 ? 'closed' : $bill->bill_status,
        ]);

        return $bill->fresh(['payments']);
    }

    private function calculateOrderSubtotal(RestaurantNewOrder $order): float
    {
        if (method_exists($order, 'lines')) {
            return (float) $order->lines()->sum('line_total');
        }

        return (float) ($order->subtotal ?? $order->total_amount ?? 0);
    }

    private function nextBillNumber(int $businessId, int $locationId): string
    {
        $next = RestaurantNewBill::where('business_id', $businessId)->where('location_id', $locationId)->count() + 1;
        return 'RNB-' . date('ymd') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function nextRefundNumber(int $businessId, int $locationId): string
    {
        $next = RestaurantNewRefund::where('business_id', $businessId)->where('location_id', $locationId)->count() + 1;
        return 'RNR-' . date('ymd') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
