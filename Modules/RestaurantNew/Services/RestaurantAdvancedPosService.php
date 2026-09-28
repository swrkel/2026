<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewHeldOrder;
use Modules\RestaurantNew\Entities\RestaurantNewMultiPayment;
use Modules\RestaurantNew\Entities\RestaurantNewSaleOrder;
use Modules\RestaurantNew\Entities\RestaurantNewSplitBill;
use Modules\RestaurantNew\Entities\RestaurantNewSplitBillLine;

class RestaurantAdvancedPosService
{
    public function holdOrder(RestaurantNewSaleOrder $order, string $reason = null): RestaurantNewHeldOrder
    {
        $order->load('lines');
        $held = RestaurantNewHeldOrder::create([
            'business_id' => $order->business_id,
            'location_id' => $order->location_id,
            'order_id' => $order->id,
            'hold_no' => 'HOLD-' . now()->format('YmdHis') . '-' . $order->id,
            'reason' => $reason,
            'snapshot' => $order->toArray(),
            'status' => 'held',
            'held_by' => auth()->id(),
            'held_at' => now(),
        ]);
        $order->update(['status' => 'held']);
        return $held;
    }

    public function resumeOrder(RestaurantNewHeldOrder $heldOrder): RestaurantNewSaleOrder
    {
        $heldOrder->update(['status' => 'resumed', 'resumed_by' => auth()->id(), 'resumed_at' => now()]);
        $order = RestaurantNewSaleOrder::findOrFail($heldOrder->order_id);
        $order->update(['status' => 'received']);
        return $order->fresh('lines');
    }

    public function splitBill(RestaurantNewSaleOrder $order, array $bills): array
    {
        return DB::transaction(function () use ($order, $bills) {
            $created = [];
            foreach ($bills as $index => $bill) {
                $split = RestaurantNewSplitBill::create([
                    'business_id' => $order->business_id,
                    'location_id' => $order->location_id,
                    'order_id' => $order->id,
                    'split_no' => $order->order_no . '-S' . ($index + 1),
                    'guest_name' => $bill['guest_name'] ?? null,
                    'seat_no' => $bill['seat_no'] ?? null,
                    'sub_total' => 0,
                    'discount_amount' => (float)($bill['discount_amount'] ?? 0),
                    'tax_amount' => (float)($bill['tax_amount'] ?? 0),
                    'service_charge_amount' => (float)($bill['service_charge_amount'] ?? 0),
                    'grand_total' => 0,
                    'payment_status' => 'pending',
                    'created_by' => auth()->id(),
                ]);

                $subTotal = 0;
                foreach (($bill['lines'] ?? []) as $line) {
                    $qty = (float)($line['quantity'] ?? 0);
                    $price = (float)($line['unit_price'] ?? 0);
                    $lineTotal = $qty * $price;
                    $subTotal += $lineTotal;
                    RestaurantNewSplitBillLine::create([
                        'split_bill_id' => $split->id,
                        'order_line_id' => $line['order_line_id'] ?? null,
                        'menu_item_name' => $line['menu_item_name'] ?? 'Item',
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'line_total' => $lineTotal,
                    ]);
                }
                $grandTotal = max(0, $subTotal - $split->discount_amount + $split->tax_amount + $split->service_charge_amount);
                $split->update(['sub_total' => $subTotal, 'grand_total' => $grandTotal]);
                $created[] = $split->fresh('lines');
            }
            $order->update(['is_split_bill' => 1]);
            return $created;
        });
    }

    public function recordMultiplePayments(RestaurantNewSaleOrder $order, array $payments): array
    {
        return DB::transaction(function () use ($order, $payments) {
            $created = [];
            foreach ($payments as $payment) {
                $created[] = RestaurantNewMultiPayment::create([
                    'business_id' => $order->business_id,
                    'location_id' => $order->location_id,
                    'order_id' => $order->id,
                    'split_bill_id' => $payment['split_bill_id'] ?? null,
                    'payment_method' => $payment['payment_method'] ?? 'cash',
                    'account_id' => $payment['account_id'] ?? null,
                    'amount' => (float)($payment['amount'] ?? 0),
                    'reference_no' => $payment['reference_no'] ?? null,
                    'card_type' => $payment['card_type'] ?? null,
                    'paid_at' => $payment['paid_at'] ?? now(),
                    'created_by' => auth()->id(),
                    'remarks' => $payment['remarks'] ?? null,
                ]);
            }
            $paid = RestaurantNewMultiPayment::where('order_id', $order->id)->sum('amount');
            $status = $paid >= (float)$order->grand_total ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
            $order->update(['paid_amount' => $paid, 'payment_status' => $status]);
            return $created;
        });
    }
}
