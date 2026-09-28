<?php
namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewOrderLifecycle;
use Modules\DistributionNew\Models\DisnewSalesOrder;

class DisnewOrderLifecycleService
{
    public const STATUSES = ['draft','pending_approval','approved','loading','partially_loaded','dispatched','delivered','partially_invoiced','fully_invoiced','closed','cancelled'];

    public function changeStatus(DisnewSalesOrder $order, string $toStatus, ?int $userId = null, ?string $remarks = null): DisnewSalesOrder
    {
        if (! in_array($toStatus, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid Distribution New order status.');
        }
        return DB::transaction(function () use ($order, $toStatus, $userId, $remarks) {
            $from = $order->status;
            $order->status = $toStatus;
            $order->save();
            DisnewOrderLifecycle::create([
                'business_id' => $order->business_id,
                'location_id' => $order->location_id ?? null,
                'sales_order_id' => $order->id,
                'from_status' => $from,
                'to_status' => $toStatus,
                'changed_by' => $userId,
                'changed_at' => now(),
                'remarks' => $remarks,
            ]);
            return $order;
        });
    }

    public function canEdit(DisnewSalesOrder $order, array $permissions = []): bool
    {
        if (in_array($order->status, ['closed','cancelled','fully_invoiced'], true)) {
            return in_array('distributionnew.sales_order.force_edit', $permissions, true);
        }
        return in_array('distributionnew.sales_order.edit', $permissions, true);
    }
}
