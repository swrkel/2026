<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewSaleOrder;
use Modules\RestaurantNew\Entities\RestaurantNewTableOperation;

class RestaurantTableOperationService
{
    public function transferTable(RestaurantNewSaleOrder $order, ?int $fromTableId, int $toTableId, array $meta = []): RestaurantNewSaleOrder
    {
        return DB::transaction(function () use ($order, $fromTableId, $toTableId, $meta) {
            RestaurantNewTableOperation::create([
                'business_id' => $order->business_id,
                'location_id' => $order->location_id,
                'order_id' => $order->id,
                'operation_type' => 'transfer_table',
                'from_table_id' => $fromTableId,
                'to_table_id' => $toTableId,
                'from_waiter_id' => $order->waiter_id,
                'to_waiter_id' => $order->waiter_id,
                'remarks' => $meta['remarks'] ?? null,
                'created_by' => $meta['created_by'] ?? auth()->id(),
            ]);

            $order->update(['table_id' => $toTableId]);
            return $order->fresh('lines');
        });
    }

    public function changeWaiter(RestaurantNewSaleOrder $order, int $toWaiterId, array $meta = []): RestaurantNewSaleOrder
    {
        RestaurantNewTableOperation::create([
            'business_id' => $order->business_id,
            'location_id' => $order->location_id,
            'order_id' => $order->id,
            'operation_type' => 'change_waiter',
            'from_table_id' => $order->table_id,
            'to_table_id' => $order->table_id,
            'from_waiter_id' => $order->waiter_id,
            'to_waiter_id' => $toWaiterId,
            'remarks' => $meta['remarks'] ?? null,
            'created_by' => $meta['created_by'] ?? auth()->id(),
        ]);

        $order->update(['waiter_id' => $toWaiterId]);
        return $order->fresh('lines');
    }

    public function mergeOrders(RestaurantNewSaleOrder $primaryOrder, array $secondaryOrderIds, array $meta = []): RestaurantNewSaleOrder
    {
        return DB::transaction(function () use ($primaryOrder, $secondaryOrderIds, $meta) {
            $secondaryOrders = RestaurantNewSaleOrder::with('lines')->whereIn('id', $secondaryOrderIds)->get();

            foreach ($secondaryOrders as $secondary) {
                foreach ($secondary->lines as $line) {
                    $line->update(['order_id' => $primaryOrder->id]);
                }
                RestaurantNewTableOperation::create([
                    'business_id' => $primaryOrder->business_id,
                    'location_id' => $primaryOrder->location_id,
                    'order_id' => $primaryOrder->id,
                    'operation_type' => 'merge_order',
                    'from_table_id' => $secondary->table_id,
                    'to_table_id' => $primaryOrder->table_id,
                    'remarks' => 'Merged order '.$secondary->order_no.' into '.$primaryOrder->order_no,
                    'created_by' => $meta['created_by'] ?? auth()->id(),
                ]);
                $secondary->update(['status' => 'merged', 'merged_into_order_id' => $primaryOrder->id]);
            }

            $this->recalculateOrder($primaryOrder);
            return $primaryOrder->fresh('lines');
        });
    }

    public function recalculateOrder(RestaurantNewSaleOrder $order): void
    {
        $order->load('lines');
        $subTotal = $order->lines->sum('line_total');
        $grandTotal = max(0, $subTotal - (float)$order->discount_amount + (float)$order->tax_amount + (float)$order->service_charge_amount);
        $order->update(['sub_total' => $subTotal, 'grand_total' => $grandTotal]);
    }
}
