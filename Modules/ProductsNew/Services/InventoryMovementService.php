<?php

namespace Modules\ProductsNew\Services;

use App\BusinessLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewInventoryMovement;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class InventoryMovementService
{
    public function __construct(
        protected ProductsNewTenantGuard $guard,
        protected ProductTimelineService $timeline,
        protected ProductStatusService $status,
        protected ProductsNewFinanceStockService $finance
    ) {}

    public function query(array $filters = [])
    {
        $q = DB::table('products_new_inventory_movements as m')
            ->leftJoin('products as p', 'p.id', '=', 'm.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'm.variation_id')
            ->leftJoin('business_locations as l', 'l.id', '=', 'm.location_id')
            ->select('m.*', 'p.name as product_name', 'p.sku', 'v.name as variation_name', 'l.name as location_name')
            ->orderByDesc('m.movement_date')
            ->orderByDesc('m.id');

        $this->guard->applyBusiness($q, 'm.business_id');

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $q->where(fn ($query) => $query
                ->where('p.name', 'like', $search)
                ->orWhere('p.sku', 'like', $search)
                ->orWhere('m.reference_no', 'like', $search));
        }

        if (!empty($filters['location_id'])) {
            $q->where('m.location_id', $filters['location_id']);
        }
        if (!empty($filters['movement_type'])) {
            $q->where('m.movement_type', $filters['movement_type']);
        }
        if (!empty($filters['from_date'])) {
            $q->whereDate('m.movement_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $q->whereDate('m.movement_date', '<=', $filters['to_date']);
        }

        return $q;
    }

    public function record(array $data): ProductsNewInventoryMovement
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->guard->businessId();
            $this->status->assertActiveProductId((int) $data['product_id']);

            $qty = (float) ($data['qty'] ?? 0);
            $unitCost = (float) ($data['unit_cost'] ?? 0);

            $movementPayload = [
                'business_id' => $businessId,
                'product_id' => $data['product_id'],
                'variation_id' => $data['variation_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'movement_type' => $data['movement_type'],
                'movement_date' => $this->normaliseMovementDateTime($data['movement_date'] ?? now()),
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => round($qty * $unitCost, 4),
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ];

            // Some newer tenant schemas may include store_id in the movement table.
            if (
                !empty($data['store_id'])
                && Schema::hasTable('products_new_inventory_movements')
                && Schema::hasColumn('products_new_inventory_movements', 'store_id')
            ) {
                $movementPayload['store_id'] = (int) $data['store_id'];
            }

            $movement = ProductsNewInventoryMovement::create($movementPayload);

            if (!empty($data['variation_id']) && !empty($data['location_id'])) {
                $sign = $this->movementSign((string) $data['movement_type']);
                $delta = $sign * $qty;

                $productVariationId = !empty($data['product_variation_id'])
                    ? (int) $data['product_variation_id']
                    : $this->productVariationId((int) $data['variation_id']);

                $this->adjustLocationStock(
                    (int) $data['product_id'],
                    $productVariationId,
                    (int) $data['variation_id'],
                    (int) $data['location_id'],
                    $delta
                );

                if (!empty($data['store_id'])) {
                    $this->adjustStoreStock(
                        (int) $data['product_id'],
                        $productVariationId,
                        (int) $data['variation_id'],
                        (int) $data['store_id'],
                        $delta
                    );
                }
            }

            // Mirror only Products New opening-stock values into the standard
            // Finance ledger. This does not change stock quantity a second time.
            $this->finance->mirrorOpeningStockMovement($movement, $data);

            $this->timeline->log(
                (int) $movement->product_id,
                'inventory_movement',
                [
                    'title' => 'Inventory movement recorded',
                    'type' => $movement->movement_type,
                    'qty' => $movement->qty,
                    'reference_no' => $movement->reference_no,
                    'store_id' => $data['store_id'] ?? null,
                ]
            );

            return $movement;
        });
    }

    public function locations()
    {
        return BusinessLocation::getDropdownCollection($this->guard->businessId());
    }

    public function products()
    {
        $query = DB::table('products')
            ->where('business_id', $this->guard->businessId());

        $this->status->applyActiveOnly($query, 'products');

        return $query
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'sku']);
    }

    protected function normaliseMovementDateTime($value): Carbon
    {
        $raw = $value;
        $date = Carbon::parse($value ?: now());
        if (is_string($raw) && ! str_contains(trim($raw), ':')) {
            $now = now();
            $date->setTime($now->hour, $now->minute, $now->second);
        }
        return $date;
    }

    protected function movementSign(string $movementType): int
    {
        return in_array(
            $movementType,
            ['opening_stock', 'opening_stock_adjustment_in', 'stock_in', 'adjustment_in', 'return_in', 'transfer_in'],
            true
        ) ? 1 : -1;
    }

    protected function productVariationId(int $variationId): int
    {
        if (!Schema::hasTable('variations')) {
            return 0;
        }

        return (int) DB::table('variations')
            ->where('id', $variationId)
            ->value('product_variation_id');
    }

    protected function adjustLocationStock(
        int $productId,
        int $productVariationId,
        int $variationId,
        int $locationId,
        float $delta
    ): void {
        if (!Schema::hasTable('variation_location_details')) {
            return;
        }

        // Lock the parent variation first. This also serialises the "row does
        // not exist yet" case, preventing two concurrent movements from both
        // inserting the same variation/location stock identity.
        if (Schema::hasTable('variations')) {
            DB::table('variations')->where('id', $variationId)->lockForUpdate()->value('id');
        }

        $existingRows = DB::table('variation_location_details')
            ->where('variation_id', $variationId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        $now = now();

        if ($existingRows->isNotEmpty()) {
            $canonical = $existingRows->first();
            $currentQty = (float) $existingRows->sum(fn ($row) => (float) ($row->qty_available ?? 0));

            DB::table('variation_location_details')
                ->where('id', $canonical->id)
                ->update($this->schemaPayload('variation_location_details', [
                    'qty_available' => $currentQty + $delta,
                    'updated_at' => $now,
                ]));

            // Exact duplicate physical stock rows represent one logical stock
            // identity. Consolidate them into the oldest row as part of the same
            // locked transaction so they cannot keep multiplying quantities.
            $duplicateIds = $existingRows->pluck('id')->filter(fn ($id) => (int) $id !== (int) $canonical->id)->all();
            if ($duplicateIds !== []) {
                DB::table('variation_location_details')->whereIn('id', $duplicateIds)->delete();
            }

            return;
        }

        $payload = $this->schemaPayload('variation_location_details', [
            'product_id' => $productId,
            'product_variation_id' => $productVariationId,
            'variation_id' => $variationId,
            'location_id' => $locationId,
            'qty_available' => $delta,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('variation_location_details')->insert($payload);
    }

    protected function adjustStoreStock(
        int $productId,
        int $productVariationId,
        int $variationId,
        int $storeId,
        float $delta
    ): void {
        if (!Schema::hasTable('variation_store_details')) {
            return;
        }

        if (Schema::hasTable('variations')) {
            DB::table('variations')->where('id', $variationId)->lockForUpdate()->value('id');
        }

        $existingRows = DB::table('variation_store_details')
            ->where('variation_id', $variationId)
            ->where('store_id', $storeId)
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        $now = now();

        if ($existingRows->isNotEmpty()) {
            $canonical = $existingRows->first();
            $currentQty = (float) $existingRows->sum(fn ($row) => (float) ($row->qty_available ?? 0));

            DB::table('variation_store_details')
                ->where('id', $canonical->id)
                ->update($this->schemaPayload('variation_store_details', [
                    'qty_available' => $currentQty + $delta,
                    'updated_at' => $now,
                ]));

            $duplicateIds = $existingRows->pluck('id')->filter(fn ($id) => (int) $id !== (int) $canonical->id)->all();
            if ($duplicateIds !== []) {
                DB::table('variation_store_details')->whereIn('id', $duplicateIds)->delete();
            }

            return;
        }

        $payload = $this->schemaPayload('variation_store_details', [
            'product_id' => $productId,
            'product_variation_id' => $productVariationId,
            'variation_id' => $variationId,
            'store_id' => $storeId,
            'qty_available' => $delta,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('variation_store_details')->insert($payload);
    }

    protected function schemaPayload(string $table, array $payload): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_filter(
            $payload,
            fn (string $key): bool => isset($columns[$key]),
            ARRAY_FILTER_USE_KEY
        );
    }
}
