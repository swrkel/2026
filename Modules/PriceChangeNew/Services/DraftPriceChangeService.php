<?php

namespace Modules\PriceChangeNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\PriceChangeNew\Entities\PriceChange;
use Modules\PriceChangeNew\Entities\PriceChangeLine;
use Modules\PriceChangeNew\Entities\PriceChangeScope;
use Modules\PriceChangeNew\Entities\PriceChangeScopePrice;

class DraftPriceChangeService
{
    public function __construct(
        private PriceChangeContext $context,
        private ReferenceNumberService $references,
        private ProductLookupService $products,
        private PriceCalculator $calculator,
        private PriceChangeAuditService $audits
    ) {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): PriceChange
    {
        $businessId = $this->context->businessId();

        return DB::transaction(function () use ($businessId, $data): PriceChange {
            $change = PriceChange::query()->create([
                'business_id' => $businessId,
                'reference_no' => $this->references->next($businessId),
                'title' => trim((string) $data['title']),
                'reason' => $data['reason'] ?? null,
                'status' => 'draft',
                'effective_at' => $data['effective_at'] ?? null,
                'stock_price_mode' => $data['stock_price_mode'] ?? 'all_stock',
                'application_scope' => $data['application_scope'] ?? 'business_base',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->replaceDetails($change, $data);
            $this->audits->record($change, 'draft_created', null, 'draft', [
                'line_count' => $change->lines()->count(),
                'scope_count' => $change->scopes()->count(),
                'application_scope' => $change->application_scope,
            ]);

            return $change->fresh(['lines', 'scopes', 'scopePrices']);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function update(PriceChange $change, array $data): PriceChange
    {
        $this->assertDraft($change);

        return DB::transaction(function () use ($change, $data): PriceChange {
            $before = $change->only(['title', 'reason', 'effective_at', 'stock_price_mode', 'application_scope']);

            $change->fill([
                'title' => trim((string) $data['title']),
                'reason' => $data['reason'] ?? null,
                'effective_at' => $data['effective_at'] ?? null,
                'stock_price_mode' => $data['stock_price_mode'] ?? 'all_stock',
                'application_scope' => $data['application_scope'] ?? 'business_base',
                'updated_by' => auth()->id(),
            ])->save();

            PriceChangeScopePrice::query()->where('price_change_id', $change->id)->delete();
            PriceChangeLine::query()->where('price_change_id', $change->id)->delete();
            PriceChangeScope::query()->where('price_change_id', $change->id)->delete();
            $this->replaceDetails($change, $data);

            $this->audits->record($change, 'draft_updated', 'draft', 'draft', [
                'before' => $before,
                'line_count' => $change->lines()->count(),
                'scope_count' => $change->scopes()->count(),
                'application_scope' => $change->application_scope,
            ]);

            return $change->fresh(['lines', 'scopes', 'scopePrices']);
        }, 3);
    }

    public function delete(PriceChange $change): void
    {
        $this->assertDraft($change);

        DB::transaction(function () use ($change): void {
            $this->audits->record($change, 'draft_deleted', 'draft', 'deleted', ['reference_no' => $change->reference_no]);
            $change->deleted_by = auth()->id();
            $change->save();
            $change->delete();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    private function replaceDetails(PriceChange $change, array $data): void
    {
        $locationIds = $this->context->assertLocations((array) ($data['location_ids'] ?? []));
        $locations = DB::table('business_locations')
            ->where('business_id', $change->business_id)
            ->whereIn('id', $locationIds)
            ->get(['id', 'name', 'selling_price_group_id']);

        foreach ($locations as $location) {
            PriceChangeScope::query()->create([
                'price_change_id' => $change->id,
                'business_id' => $change->business_id,
                'location_id' => (int) $location->id,
                'location_name' => (string) $location->name,
            ]);
        }

        $lines = (array) ($data['lines'] ?? []);
        if ($lines === []) {
            throw ValidationException::withMessages(['lines_json' => 'Add at least one product price line.']);
        }

        $usedVariations = [];
        foreach ($lines as $index => $line) {
            $variationId = (int) ($line['variation_id'] ?? 0);
            if ($variationId < 1 || isset($usedVariations[$variationId])) {
                throw ValidationException::withMessages(['lines_json' => 'Every product variation must be valid and can be added only once.']);
            }
            $usedVariations[$variationId] = true;

            $snapshot = $this->products->details($variationId, $locationIds);
            $newSell = $this->calculator->normalize(
                (float) ($line['new_sell_price'] ?? 0),
                (string) ($line['sell_price_basis'] ?? 'inc_tax'),
                (float) $snapshot['tax_rate']
            );

            $newPurchase = null;
            if (array_key_exists('new_purchase_price', $line)
                && $line['new_purchase_price'] !== null
                && $line['new_purchase_price'] !== '') {
                if ($change->application_scope === 'location_price_groups') {
                    throw ValidationException::withMessages([
                        'application_scope' => 'Purchase prices are business-wide. Use Business base price scope when changing a purchase price.',
                    ]);
                }
                $newPurchase = $this->calculator->normalize(
                    (float) $line['new_purchase_price'],
                    (string) ($line['purchase_price_basis'] ?? 'inc_tax'),
                    (float) $snapshot['tax_rate']
                );
            }

            $purchaseExTax = $newPurchase['ex_tax'] ?? (float) $snapshot['current_purchase_price_ex_tax'];
            $newProfit = $this->calculator->profitPercent($purchaseExTax, $newSell['ex_tax']);

            $createdLine = PriceChangeLine::query()->create([
                'price_change_id' => $change->id,
                'business_id' => $change->business_id,
                'line_no' => $index + 1,
                'product_id' => $snapshot['product_id'],
                'variation_id' => $snapshot['variation_id'],
                'product_name' => $snapshot['product_name'],
                'variation_name' => $snapshot['variation_name'],
                'sku' => $snapshot['sku'],
                'tax_id' => $snapshot['tax_id'],
                'tax_name' => $snapshot['tax_name'],
                'tax_rate' => $snapshot['tax_rate'],
                'tax_type' => $snapshot['tax_type'],
                'stock_quantity' => $snapshot['stock_quantity'],
                'current_purchase_price_ex_tax' => $snapshot['current_purchase_price_ex_tax'],
                'current_purchase_price_inc_tax' => $snapshot['current_purchase_price_inc_tax'],
                'current_sell_price_ex_tax' => $snapshot['current_sell_price_ex_tax'],
                'current_sell_price_inc_tax' => $snapshot['current_sell_price_inc_tax'],
                'current_profit_percent' => $snapshot['current_profit_percent'],
                'purchase_price_basis' => $line['purchase_price_basis'] ?? 'inc_tax',
                'new_purchase_price_ex_tax' => $newPurchase['ex_tax'] ?? null,
                'new_purchase_price_inc_tax' => $newPurchase['inc_tax'] ?? null,
                'sell_price_basis' => $line['sell_price_basis'] ?? 'inc_tax',
                'new_sell_price_ex_tax' => $newSell['ex_tax'],
                'new_sell_price_inc_tax' => $newSell['inc_tax'],
                'new_profit_percent' => $newProfit,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            if ($change->application_scope === 'location_price_groups') {
                foreach ($locations as $location) {
                    $priceGroupId = (int) ($location->selling_price_group_id ?? 0);
                    if ($priceGroupId < 1) {
                        throw ValidationException::withMessages([
                            'application_scope' => 'Location "' . $location->name . '" has no default selling price group. Configure it or select Business base price scope.',
                        ]);
                    }
                    $currentGroupPrice = DB::table('variation_group_prices')
                        ->where('variation_id', $variationId)
                        ->where('price_group_id', $priceGroupId)
                        ->value('price_inc_tax');

                    PriceChangeScopePrice::query()->create([
                        'price_change_id' => $change->id,
                        'price_change_line_id' => $createdLine->id,
                        'business_id' => $change->business_id,
                        'location_id' => (int) $location->id,
                        'price_group_id' => $priceGroupId,
                        'current_group_price_inc_tax' => $currentGroupPrice !== null
                            ? round((float) $currentGroupPrice, 8)
                            : $snapshot['current_sell_price_inc_tax'],
                        'new_group_price_inc_tax' => $newSell['inc_tax'],
                    ]);
                }
            }
        }
    }

    private function assertDraft(PriceChange $change): void
    {
        abort_unless($change->status === 'draft', 422, 'Only draft price changes may be edited or deleted.');
    }
}
