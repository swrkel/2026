<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\Ingredient;
use Modules\RestaurantNew\Entities\InventoryBalance;
use Modules\RestaurantNew\Entities\StockMovement;
use Modules\RestaurantNew\Entities\StockTransfer;
use Modules\RestaurantNew\Entities\StockTransferLine;

class StockTransferService
{
    public function __construct(
        private TenantScopeService $scope,
        private NumberService $numbers,
        private AuditService $audit
    ) {}

    public function create(array $data): StockTransfer
    {
        $businessId = $this->scope->businessId();
        $from = (int) $data['from_location_id'];
        $to = (int) $data['to_location_id'];

        $this->scope->assertLocationAccess($from);
        $this->scope->assertLocationAccess($to);
        if ($from === $to) {
            throw ValidationException::withMessages([
                'to_location_id' => 'Source and destination locations must differ.',
            ]);
        }

        return DB::transaction(function () use ($data, $businessId, $from, $to) {
            $transfer = StockTransfer::withoutGlobalScopes()->create([
                'business_id' => $businessId,
                'from_location_id' => $from,
                'to_location_id' => $to,
                'transfer_no' => $this->numbers->next($businessId, 'stock_transfer', 'TRF-'),
                'transfer_date' => $data['transfer_date'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['lines'] as $index => $row) {
                $ingredient = Ingredient::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->whereKey((int) $row['ingredient_id'])
                    ->where('is_active', true)
                    ->first();
                if (! $ingredient) {
                    throw ValidationException::withMessages([
                        "lines.$index.ingredient_id" => 'Invalid ingredient.',
                    ]);
                }

                StockTransferLine::withoutGlobalScopes()->create([
                    'business_id' => $businessId,
                    'stock_transfer_id' => $transfer->id,
                    'ingredient_id' => $ingredient->id,
                    'requested_qty' => (float) $row['quantity'],
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            return $transfer->fresh('lines');
        }, 3);
    }

    public function dispatch(StockTransfer $transfer): StockTransfer
    {
        $this->assertTransferBusiness($transfer);
        $this->scope->assertLocationAccess((int) $transfer->from_location_id);

        return DB::transaction(function () use ($transfer) {
            $locked = StockTransfer::withoutGlobalScopes()
                ->where('business_id', $this->scope->businessId())
                ->whereKey($transfer->id)
                ->lockForUpdate()
                ->with('lines')
                ->firstOrFail();

            $this->scope->assertLocationAccess((int) $locked->from_location_id);
            if (in_array($locked->status, ['dispatched', 'received'], true)) {
                return $locked;
            }
            if ($locked->status !== 'draft') {
                throw ValidationException::withMessages([
                    'transfer' => 'Only draft transfers can be dispatched.',
                ]);
            }

            foreach ($locked->lines as $line) {
                $balance = InventoryBalance::withoutGlobalScopes()
                    ->where('business_id', $locked->business_id)
                    ->where('location_id', $locked->from_location_id)
                    ->where('ingredient_id', $line->ingredient_id)
                    ->lockForUpdate()
                    ->first();
                $quantity = (float) $line->requested_qty;

                if (! $balance || (float) $balance->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'stock' => 'Insufficient source stock for ingredient #'.$line->ingredient_id.'.',
                    ]);
                }

                $cost = (float) $balance->average_cost;
                $balance->decrement('quantity', $quantity);
                $line->update(['dispatched_qty' => $quantity, 'unit_cost' => $cost]);

                StockMovement::withoutGlobalScopes()->firstOrCreate(
                    [
                        'source_type' => 'restnew_transfer_out',
                        'source_id' => $line->id,
                        'ingredient_id' => $line->ingredient_id,
                    ],
                    [
                        'business_id' => $locked->business_id,
                        'location_id' => $locked->from_location_id,
                        'movement_type' => 'transfer_out',
                        'quantity' => -$quantity,
                        'unit_cost' => $cost,
                        'value' => -$quantity * $cost,
                        'reference_no' => $locked->transfer_no,
                        'created_by' => auth()->id(),
                    ]
                );
            }

            $locked->update([
                'status' => 'dispatched',
                'dispatched_at' => now(),
                'dispatched_by' => auth()->id(),
            ]);
            $this->audit->record(
                'stock_transfer.dispatched',
                'stock_transfer',
                $locked->id,
                [],
                ['transfer_no' => $locked->transfer_no]
            );

            return $locked->fresh('lines');
        }, 3);
    }

    public function receive(StockTransfer $transfer): StockTransfer
    {
        $this->assertTransferBusiness($transfer);
        $this->scope->assertLocationAccess((int) $transfer->to_location_id);

        return DB::transaction(function () use ($transfer) {
            $locked = StockTransfer::withoutGlobalScopes()
                ->where('business_id', $this->scope->businessId())
                ->whereKey($transfer->id)
                ->lockForUpdate()
                ->with('lines')
                ->firstOrFail();

            $this->scope->assertLocationAccess((int) $locked->to_location_id);
            if ($locked->status === 'received') {
                return $locked;
            }
            if ($locked->status !== 'dispatched') {
                throw ValidationException::withMessages([
                    'transfer' => 'Dispatch the transfer before receiving it.',
                ]);
            }

            foreach ($locked->lines as $line) {
                $quantity = (float) $line->dispatched_qty;
                $cost = (float) $line->unit_cost;
                $balance = InventoryBalance::withoutGlobalScopes()
                    ->where('business_id', $locked->business_id)
                    ->where('location_id', $locked->to_location_id)
                    ->where('ingredient_id', $line->ingredient_id)
                    ->lockForUpdate()
                    ->first();

                if (! $balance) {
                    $balance = InventoryBalance::withoutGlobalScopes()->create([
                        'business_id' => $locked->business_id,
                        'location_id' => $locked->to_location_id,
                        'ingredient_id' => $line->ingredient_id,
                        'quantity' => 0,
                        'average_cost' => $cost,
                    ]);
                }

                $oldQuantity = (float) $balance->quantity;
                $newQuantity = $oldQuantity + $quantity;
                $newCost = $newQuantity > 0
                    ? (($oldQuantity * (float) $balance->average_cost) + ($quantity * $cost)) / $newQuantity
                    : $cost;

                $balance->update(['quantity' => $newQuantity, 'average_cost' => $newCost]);
                $line->update(['received_qty' => $quantity]);

                StockMovement::withoutGlobalScopes()->firstOrCreate(
                    [
                        'source_type' => 'restnew_transfer_in',
                        'source_id' => $line->id,
                        'ingredient_id' => $line->ingredient_id,
                    ],
                    [
                        'business_id' => $locked->business_id,
                        'location_id' => $locked->to_location_id,
                        'movement_type' => 'transfer_in',
                        'quantity' => $quantity,
                        'unit_cost' => $cost,
                        'value' => $quantity * $cost,
                        'reference_no' => $locked->transfer_no,
                        'created_by' => auth()->id(),
                    ]
                );
            }

            $locked->update([
                'status' => 'received',
                'received_at' => now(),
                'received_by' => auth()->id(),
            ]);
            $this->audit->record(
                'stock_transfer.received',
                'stock_transfer',
                $locked->id,
                [],
                ['transfer_no' => $locked->transfer_no]
            );

            return $locked->fresh('lines');
        }, 3);
    }

    private function assertTransferBusiness(StockTransfer $transfer): void
    {
        abort_unless(
            (int) $transfer->business_id === (int) $this->scope->businessId(),
            404
        );
    }
}
