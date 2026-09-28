<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneOtherSale;
use Modules\PumperDashboardNew\Services\Integration\PonePetroPdNewPublisher;

class PoneOtherSaleService
{
    public function __construct(
        private PoneContextService $context,
        private PoneNumberSequenceService $numbers,
        private PoneSharedMasterDataService $masterData,
        private PoneShiftTotalsService $totals,
        private PoneOperatorLedgerService $ledger,
        private PonePetroPdNewPublisher $bridge,
        private PoneAuditService $audit
    ) {}

    public function create(array $data): PoneOtherSale
    {
        return DB::transaction(function () use ($data): PoneOtherSale {
            $shift = $this->context->shift();
            $rows = $this->normaliseLines($data['lines'] ?? [], $shift->business_id, $shift->location_id);
            if ($rows === []) throw ValidationException::withMessages(['lines' => __('pumperdashboardnew::lang.sale_lines_required')]);
            [$gross, $discount, $net] = $this->totalsFor($rows);
            $storeId = $this->validatedStoreId($shift->business_id, $shift->location_id, $data['store_id'] ?? null);
            $customerId = $this->validatedCustomerId($shift->business_id, $shift->location_id, $data['customer_id'] ?? null);
            $sale = PoneOtherSale::query()->create([
                'uuid' => Str::uuid()->toString(),
                'shift_id' => $shift->id,
                'business_id' => $shift->business_id,
                'location_id' => $shift->location_id,
                'operator_profile_id' => $shift->operator_profile_id,
                'pd_operator_id' => $shift->pd_operator_id,
                'store_id' => $storeId,
                'customer_id' => $customerId,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'collection_form_no' => $data['collection_form_no'] ?? $shift->collection_form_no,
                'sale_number' => $this->numbers->next($shift->business_id, $shift->location_id, 'other_sale'),
                'sale_at' => $data['sale_at'] ?? now(),
                'gross_amount' => $gross,
                'discount_amount' => $discount,
                'net_amount' => $net,
                'status' => 'confirmed',
                'note' => $data['note'] ?? null,
                'integration_status' => 'pending',
                'created_by' => $this->context->userId(),
            ]);
            $this->replaceLines($sale, $rows);
            $fresh = $sale->fresh('lines');
            $this->bridge->syncOtherSale($fresh);
            $shift = $this->totals->refresh($shift);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('other_sale.created', 'pone_other_sale', $sale->id, null, $fresh);
            return $fresh;
        }, 3);
    }

    public function update(int $saleId, array $data): PoneOtherSale
    {
        return DB::transaction(function () use ($saleId, $data): PoneOtherSale {
            $shift = $this->context->shift();
            $sale = PoneOtherSale::query()->whereKey($saleId)->where('shift_id', $shift->id)
                ->where('business_id', $shift->business_id)->with('lines')->lockForUpdate()->firstOrFail();
            if ($sale->status !== 'confirmed') throw ValidationException::withMessages(['sale' => __('pumperdashboardnew::lang.only_confirmed_sale_editable')]);
            $reason = trim((string) ($data['edit_reason'] ?? ''));
            if ($reason === '') throw ValidationException::withMessages(['edit_reason' => __('pumperdashboardnew::lang.edit_reason_required')]);
            $rows = $this->normaliseLines($data['lines'] ?? [], $shift->business_id, $shift->location_id);
            if ($rows === []) throw ValidationException::withMessages(['lines' => __('pumperdashboardnew::lang.sale_lines_required')]);
            [$gross, $discount, $net] = $this->totalsFor($rows);
            $storeId = $this->validatedStoreId($shift->business_id, $shift->location_id, array_key_exists('store_id', $data) ? $data['store_id'] : $sale->store_id);
            $customerId = $this->validatedCustomerId($shift->business_id, $shift->location_id, array_key_exists('customer_id', $data) ? $data['customer_id'] : $sale->customer_id);
            $before = $sale->toArray();
            $sale->update([
                'store_id' => $storeId,
                'customer_id' => $customerId,
                'payment_method' => $data['payment_method'] ?? $sale->payment_method,
                'collection_form_no' => $data['collection_form_no'] ?? $sale->collection_form_no,
                'sale_at' => $data['sale_at'] ?? $sale->sale_at,
                'gross_amount' => $gross,
                'discount_amount' => $discount,
                'net_amount' => $net,
                'note' => trim(($data['note'] ?? '') . ($data['note'] ?? '' ? "\n" : '') . 'Edit reason: ' . $reason),
                'edited_by' => $this->context->userId(),
                'edited_at' => now(),
                'integration_status' => 'pending',
            ]);
            $this->bridge->retireSourceLinks('other_sale_line', $sale->lines->pluck('id')->all());
            $this->replaceLines($sale, $rows);
            $fresh = $sale->fresh('lines');
            $this->bridge->syncOtherSale($fresh);
            $shift = $this->totals->refresh($shift);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('other_sale.updated', 'pone_other_sale', $sale->id, $before, $fresh);
            return $fresh;
        }, 3);
    }

    public function void(int $saleId, ?string $reason = null): PoneOtherSale
    {
        return DB::transaction(function () use ($saleId, $reason): PoneOtherSale {
            $shift = $this->context->shift();
            $sale = PoneOtherSale::query()->whereKey($saleId)->where('shift_id', $shift->id)
                ->where('business_id', $shift->business_id)->with('lines')->lockForUpdate()->firstOrFail();
            $reason = trim((string) $reason);
            if ($reason === '') throw ValidationException::withMessages(['reason' => __('pumperdashboardnew::lang.void_reason_required')]);
            if ($sale->status === 'void') return $sale;
            $before = $sale->toArray();
            $sale->update([
                'status' => 'void', 'note' => $reason, 'voided_by' => $this->context->userId(),
                'voided_at' => now(), 'integration_status' => 'pending',
            ]);
            $fresh = $sale->fresh('lines');
            $this->bridge->voidOtherSale($fresh);
            $shift = $this->totals->refresh($shift);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('other_sale.voided', 'pone_other_sale', $sale->id, $before, $fresh);
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

    private function validatedCustomerId(int $businessId, ?int $locationId, mixed $customerId): ?int
    {
        $customerId = $customerId === null || $customerId === '' ? null : (int) $customerId;
        if ($customerId && ! $this->masterData->customer($businessId, $customerId, $locationId)) {
            throw ValidationException::withMessages(['customer_id' => __('pumperdashboardnew::lang.invalid_customer')]);
        }
        return $customerId;
    }

    private function replaceLines(PoneOtherSale $sale, array $rows): void
    {
        $sale->lines()->delete();
        foreach ($rows as $row) {
            unset($row['gross']);
            $sale->lines()->create($row);
        }
    }

    private function totalsFor(array $rows): array
    {
        return [round(array_sum(array_column($rows, 'gross')), 4), round(array_sum(array_column($rows, 'discount_amount')), 4), round(array_sum(array_column($rows, 'amount')), 4)];
    }

    private function normaliseLines(array $lines, int $businessId, ?int $locationId): array
    {
        $rows = [];
        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $product = $productId > 0 ? $this->masterData->product($businessId, $productId, $locationId) : null;
            if (! $product) continue;
            $quantity = round((float) ($line['quantity'] ?? 0), 6);
            $unitPrice = round((float) ($line['unit_price'] ?? $product->unit_price ?? 0), 6);
            $discount = round((float) ($line['discount_amount'] ?? 0), 4);
            $gross = round($quantity * $unitPrice, 4);
            $amount = round($gross - $discount, 4);
            if ($quantity <= 0 || $unitPrice < 0 || $amount <= 0) continue;
            $rows[] = [
                'product_id' => $productId, 'quantity' => $quantity, 'unit_price' => $unitPrice,
                'discount_amount' => $discount, 'amount' => $amount,
                'balance_stock_snapshot' => $product->quantity_available ?? null, 'gross' => $gross,
            ];
        }
        return $rows;
    }
}
