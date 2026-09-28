<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\StockTransfer;
use Modules\StockTransferNew\Utilities\StockTransferStatus;

class StockTransferInventoryService
{
    public function __construct(protected StockTransferProductBridgeService $productsBridge) {}

    public function dispatch(StockTransfer $transfer, int $userId = null): void
    {
        DB::transaction(function () use ($transfer, $userId) {
            $transfer->load('lines');
            foreach ($transfer->lines as $line) {
                $qty = (float)$line->qty_dispatched;
                if ($qty <= 0) {
                    continue;
                }
                $this->writeStockMovement($transfer, $line, 'out', $qty, $userId);
                $this->productsBridge->adjustStandardStock($line->variation_id, $transfer->from_location_id, $transfer->from_store_id, -$qty);
                $this->productsBridge->recordProductsNewMovement([
                    'business_id' => $transfer->business_id,
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'location_id' => $transfer->from_location_id,
                    'movement_type' => 'stock_transfer_out',
                    'qty' => $qty,
                    'unit_cost' => $line->unit_cost,
                    'reference_no' => $transfer->transfer_no,
                    'created_by' => $userId,
                ]);
                $this->adjustBalance($transfer->business_id, $transfer->from_location_id, $transfer->from_store_id, $line, -$qty, $qty, $userId);
            }

            $transfer->update([
                'status' => StockTransferStatus::IN_TRANSIT,
                'dispatched_at' => now(),
                'dispatched_by' => $userId,
                'dispatch_note_no' => $transfer->dispatch_note_no ?: 'DN-' . $transfer->transfer_no,
            ]);
        });
    }

    public function receive(StockTransfer $transfer, array $receivedLines, int $userId = null): void
    {
        DB::transaction(function () use ($transfer, $receivedLines, $userId) {
            $transfer->load('lines');
            foreach ($transfer->lines as $line) {
                $receivedQty = (float)($receivedLines[$line->id] ?? $line->qty_dispatched);
                $dispatchedQty = (float)$line->qty_dispatched;
                $line->update([
                    'qty_received' => $receivedQty,
                    'short_qty' => max(0, $dispatchedQty - $receivedQty),
                    'excess_qty' => max(0, $receivedQty - $dispatchedQty),
                ]);
                if ($receivedQty > 0) {
                    $this->writeStockMovement($transfer, $line, 'in', $receivedQty, $userId);
                    $this->productsBridge->adjustStandardStock($line->variation_id, $transfer->to_location_id, $transfer->to_store_id, $receivedQty);
                    $this->productsBridge->recordProductsNewMovement([
                        'business_id' => $transfer->business_id,
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'location_id' => $transfer->to_location_id,
                        'movement_type' => 'stock_transfer_in',
                        'qty' => $receivedQty,
                        'unit_cost' => $line->unit_cost,
                        'reference_no' => $transfer->transfer_no,
                        'created_by' => $userId,
                    ]);
                    $this->adjustBalance($transfer->business_id, $transfer->to_location_id, $transfer->to_store_id, $line, $receivedQty, 0, $userId);
                }
                if ($dispatchedQty > 0) {
                    $this->reduceTransit($transfer->business_id, $transfer->from_location_id, $transfer->from_store_id, $line, $dispatchedQty, $userId);
                }
            }

            $transfer->update([
                'status' => StockTransferStatus::RECEIVED,
                'received_at' => now(),
                'received_by' => $userId,
                'receive_note_no' => $transfer->receive_note_no ?: 'RN-' . $transfer->transfer_no,
            ]);
        });
    }

    protected function writeStockMovement(StockTransfer $transfer, $line, string $direction, float $quantity, ?int $userId): void
    {
        DB::table('stnew_stock_movements')->insert([
            'business_id' => $transfer->business_id,
            'stock_transfer_id' => $transfer->id,
            'stock_transfer_line_id' => $line->id,
            'product_id' => $line->product_id,
            'variation_id' => $line->variation_id,
            'from_location_id' => $transfer->from_location_id,
            'to_location_id' => $transfer->to_location_id,
            'from_store_id' => $transfer->from_store_id,
            'to_store_id' => $transfer->to_store_id,
            'movement_type' => $direction,
            'quantity' => $quantity,
            'unit_cost' => $line->unit_cost,
            'total_cost' => $quantity * (float)$line->unit_cost,
            'reference_no' => $transfer->transfer_no,
            'movement_date' => now(),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function adjustBalance(int $businessId, $locationId, $storeId, $line, float $onHandDelta, float $transitDelta, ?int $userId): void
    {
        $keys = [
            'business_id' => $businessId,
            'business_location_id' => $locationId,
            'store_id' => $storeId,
            'product_id' => $line->product_id,
            'variation_id' => $line->variation_id,
        ];
        $existing = DB::table('stnew_stock_balances')->where($keys)->first();
        if ($existing) {
            DB::table('stnew_stock_balances')->where('id', $existing->id)->update([
                'qty_on_hand' => DB::raw('qty_on_hand + (' . $onHandDelta . ')'),
                'qty_in_transit' => DB::raw('qty_in_transit + (' . $transitDelta . ')'),
                'last_unit_cost' => $line->unit_cost,
                'last_movement_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }
        DB::table('stnew_stock_balances')->insert(array_merge($keys, [
            'qty_on_hand' => $onHandDelta,
            'qty_in_transit' => $transitDelta,
            'last_unit_cost' => $line->unit_cost,
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    protected function reduceTransit(int $businessId, $locationId, $storeId, $line, float $qty, ?int $userId): void
    {
        DB::table('stnew_stock_balances')
            ->where('business_id', $businessId)
            ->where('business_location_id', $locationId)
            ->where('store_id', $storeId)
            ->where('product_id', $line->product_id)
            ->where('variation_id', $line->variation_id)
            ->update([
                'qty_in_transit' => DB::raw('GREATEST(qty_in_transit - ' . $qty . ', 0)'),
                'last_movement_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
