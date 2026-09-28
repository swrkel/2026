<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneUnloadStock;
use Modules\PumperDashboardNew\Services\Integration\PonePetroPdNewPublisher;

class PoneUnloadStockService
{
    public function __construct(
        private PoneContextService $context,
        private PoneNumberSequenceService $numbers,
        private PoneSharedMasterDataService $masterData,
        private PonePetroPdNewPublisher $bridge,
        private PoneAuditService $audit
    ) {}

    public function create(array $data): PoneUnloadStock
    {
        return DB::transaction(function () use ($data): PoneUnloadStock {
            $shift = $this->context->shift();
            $rows = $this->normaliseLines($data['lines'] ?? [], $shift->business_id, $shift->location_id);
            if ($rows === []) {
                throw ValidationException::withMessages(['lines' => __('pumperdashboardnew::lang.unload_lines_required')]);
            }
            $supplierId = ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
            $storeId = $this->validatedStoreId($shift->business_id, $shift->location_id, $data['store_id'] ?? null);
            if ($supplierId && ! $this->masterData->supplier($shift->business_id, $supplierId, $shift->location_id)) {
                throw ValidationException::withMessages(['supplier_id' => __('pumperdashboardnew::lang.invalid_supplier')]);
            }

            $unload = PoneUnloadStock::query()->create([
                'uuid' => Str::uuid()->toString(),
                'shift_id' => $shift->id,
                'business_id' => $shift->business_id,
                'location_id' => $shift->location_id,
                'operator_profile_id' => $shift->operator_profile_id,
                'pd_operator_id' => $shift->pd_operator_id,
                'store_id' => $storeId,
                'supplier_id' => $supplierId,
                'receipt_number' => $this->numbers->next($shift->business_id, $shift->location_id, 'unload'),
                'bill_number' => $data['bill_number'] ?? null,
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'unloaded_at' => $data['unloaded_at'] ?? now(),
                'total_quantity' => round(array_sum(array_column($rows, 'quantity')), 6),
                'total_amount' => round(array_sum(array_column($rows, 'amount')), 4),
                'status' => 'confirmed',
                'note' => $data['note'] ?? null,
                'integration_status' => 'pending',
                'created_by' => $this->context->userId(),
            ]);
            $this->replaceLines($unload, $rows);
            $fresh = $unload->fresh('lines');
            $this->bridge->syncUnloadStock($fresh);
            $this->audit->log('unload_stock.created', 'pone_unload_stock', $unload->id, null, $fresh);
            return $fresh;
        }, 3);
    }

    public function update(int $id, array $data): PoneUnloadStock
    {
        return DB::transaction(function () use ($id, $data): PoneUnloadStock {
            $shift = $this->context->shift();
            $unload = PoneUnloadStock::query()->whereKey($id)->where('shift_id', $shift->id)
                ->where('business_id', $shift->business_id)->with('lines')->lockForUpdate()->firstOrFail();
            if ($unload->status !== 'confirmed') {
                throw ValidationException::withMessages(['unload' => __('pumperdashboardnew::lang.only_confirmed_unload_editable')]);
            }
            $reason = trim((string) ($data['edit_reason'] ?? ''));
            if ($reason === '') {
                throw ValidationException::withMessages(['edit_reason' => __('pumperdashboardnew::lang.edit_reason_required')]);
            }
            $rows = $this->normaliseLines($data['lines'] ?? [], $shift->business_id, $shift->location_id);
            if ($rows === []) {
                throw ValidationException::withMessages(['lines' => __('pumperdashboardnew::lang.unload_lines_required')]);
            }
            $supplierId = array_key_exists('supplier_id', $data)
                ? (! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null)
                : $unload->supplier_id;
            $storeId = $this->validatedStoreId(
                $shift->business_id,
                $shift->location_id,
                array_key_exists('store_id', $data) ? $data['store_id'] : $unload->store_id
            );
            if ($supplierId && ! $this->masterData->supplier($shift->business_id, $supplierId, $shift->location_id)) {
                throw ValidationException::withMessages(['supplier_id' => __('pumperdashboardnew::lang.invalid_supplier')]);
            }
            $before = $unload->toArray();
            $unload->update([
                'store_id' => $storeId,
                'supplier_id' => $supplierId,
                'bill_number' => $data['bill_number'] ?? null,
                'supplier_reference' => $data['supplier_reference'] ?? null,
                'unloaded_at' => $data['unloaded_at'] ?? $unload->unloaded_at,
                'total_quantity' => round(array_sum(array_column($rows, 'quantity')), 6),
                'total_amount' => round(array_sum(array_column($rows, 'amount')), 4),
                'note' => trim((string) ($data['note'] ?? '')) . "\nEdit reason: {$reason}",
                'edited_by' => $this->context->userId(),
                'edited_at' => now(),
                'integration_status' => 'pending',
            ]);
            $this->bridge->retireSourceLinks('unload_stock_line', $unload->lines->pluck('id')->all());
            $this->replaceLines($unload, $rows);
            $fresh = $unload->fresh('lines');
            $this->bridge->syncUnloadStock($fresh);
            $this->audit->log('unload_stock.updated', 'pone_unload_stock', $unload->id, $before, $fresh);
            return $fresh;
        }, 3);
    }

    public function void(int $id, string $reason): PoneUnloadStock
    {
        return DB::transaction(function () use ($id, $reason): PoneUnloadStock {
            $shift = $this->context->shift();
            $unload = PoneUnloadStock::query()->whereKey($id)->where('shift_id', $shift->id)
                ->where('business_id', $shift->business_id)->with('lines')->lockForUpdate()->firstOrFail();
            $reason = trim($reason);
            if ($reason === '') throw ValidationException::withMessages(['reason' => __('pumperdashboardnew::lang.void_reason_required')]);
            if ($unload->status === 'void') return $unload;
            $before = $unload->toArray();
            $unload->update([
                'status' => 'void', 'note' => $reason, 'voided_by' => $this->context->userId(),
                'voided_at' => now(), 'integration_status' => 'pending',
            ]);
            $fresh = $unload->fresh('lines');
            $this->bridge->voidUnloadStock($fresh);
            $this->audit->log('unload_stock.voided', 'pone_unload_stock', $unload->id, $before, $fresh);
            return $fresh;
        }, 3);
    }

    private function validatedStoreId(int $businessId, ?int $locationId, mixed $storeId): ?int
    {
        $storeId = $storeId === null || $storeId === '' ? null : (int) $storeId;
        if ($storeId && ! $this->masterData->store($businessId, $storeId, $locationId)) {
            throw ValidationException::withMessages(['store_id' => __('pumperdashboardnew::lang.invalid_store')]);
        }
        return $storeId;
    }

    private function normaliseLines(array $lines, int $businessId, ?int $locationId): array
    {
        $rows = [];
        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $product = $productId ? $this->masterData->product($businessId, $productId, $locationId) : null;
            $quantity = round((float) ($line['quantity'] ?? 0), 6);
            $unitCost = round((float) ($line['unit_cost'] ?? 0), 6);
            if (! $product || $quantity <= 0 || $unitCost < 0) continue;
            $tankId = ! empty($line['tank_id']) ? (int) $line['tank_id'] : null;
            if ($tankId && ! $this->masterData->tank($businessId, $tankId, $locationId)) {
                throw ValidationException::withMessages(['lines' => __('pumperdashboardnew::lang.invalid_tank')]);
            }
            $rows[] = [
                'product_id' => $productId,
                'tank_id' => $tankId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'amount' => round($quantity * $unitCost, 4),
                'dip_reading' => isset($line['dip_reading']) && $line['dip_reading'] !== '' ? round((float) $line['dip_reading'], 6) : null,
                'current_stock' => isset($line['current_stock']) && $line['current_stock'] !== '' ? round((float) $line['current_stock'], 6) : null,
            ];
        }
        return $rows;
    }

    private function replaceLines(PoneUnloadStock $unload, array $rows): void
    {
        $unload->lines()->delete();
        foreach ($rows as $row) $unload->lines()->create($row);
    }
}
