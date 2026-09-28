<?php

namespace Modules\PriceChangeNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductLookupService
{
    public function __construct(private PriceChangeContext $context)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function search(string $term): array
    {
        $businessId = $this->context->businessId();
        $term = trim($term);

        $query = DB::table('variations as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
            ->where('p.business_id', $businessId)
            ->where('p.is_inactive', 0)
            ->whereNull('v.deleted_at')
            ->select([
                'p.id as product_id',
                'p.name as product_name',
                'p.type as product_type',
                'v.id as variation_id',
                'v.name as variation_name',
                'v.sub_sku',
                'pv.name as variation_group_name',
            ])
            ->orderBy('p.name')
            ->orderBy('v.name')
            ->limit((int) config('pricechangenew.product_search_limit', 25));

        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
            $query->where(function ($search) use ($like): void {
                $search->where('p.name', 'like', $like)
                    ->orWhere('p.sku', 'like', $like)
                    ->orWhere('v.sub_sku', 'like', $like)
                    ->orWhere('v.name', 'like', $like);
            });
        }

        return $query->get()->map(function ($row): array {
            $parts = [$row->product_name];
            if (! in_array($row->product_type, ['single', 'modifier'], true)) {
                if (! empty($row->variation_group_name)) {
                    $parts[] = $row->variation_group_name;
                }
                if (! empty($row->variation_name) && strtoupper((string) $row->variation_name) !== 'DUMMY') {
                    $parts[] = $row->variation_name;
                }
            }

            return [
                'id' => (int) $row->variation_id,
                'product_id' => (int) $row->product_id,
                'text' => implode(' - ', array_filter($parts)) . ' (' . ($row->sub_sku ?: '-') . ')',
                'sku' => $row->sub_sku,
            ];
        })->all();
    }

    /** @param array<int, int> $locationIds
     *  @return array<string, mixed>
     */
    public function details(int $variationId, array $locationIds): array
    {
        $businessId = $this->context->businessId();
        $locationIds = $this->context->assertLocations($locationIds);

        $row = DB::table('variations as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
            ->leftJoin('tax_rates as tr', function ($join): void {
                $join->on('tr.id', '=', 'p.tax')->whereNull('tr.deleted_at');
            })
            ->where('p.business_id', $businessId)
            ->where('v.id', $variationId)
            ->where('p.is_inactive', 0)
            ->whereNull('v.deleted_at')
            ->select([
                'p.id as product_id', 'p.name as product_name', 'p.type as product_type',
                'p.tax as tax_id', 'p.tax_type',
                'v.id as variation_id', 'v.name as variation_name', 'v.sub_sku',
                'v.default_purchase_price', 'v.dpp_inc_tax',
                'v.default_sell_price', 'v.sell_price_inc_tax',
                'v.profit_percent', 'pv.name as variation_group_name',
                DB::raw('COALESCE(tr.amount, 0) as tax_rate'),
                DB::raw('COALESCE(tr.name, "No Tax") as tax_name'),
            ])
            ->first();

        if (! $row) {
            throw ValidationException::withMessages([
                'lines' => 'The selected product or variation is not available for this business.',
            ]);
        }

        $locations = DB::table('business_locations as bl')
            ->leftJoin('variation_location_details as vld', function ($join) use ($variationId): void {
                $join->on('vld.location_id', '=', 'bl.id')
                    ->where('vld.variation_id', '=', $variationId);
            })
            ->where('bl.business_id', $businessId)
            ->whereIn('bl.id', $locationIds)
            ->select('bl.id', 'bl.name', DB::raw('COALESCE(vld.qty_available, 0) as qty_available'))
            ->orderBy('bl.name')
            ->get();

        $displayParts = [$row->product_name];
        if (! in_array($row->product_type, ['single', 'modifier'], true)) {
            if (! empty($row->variation_group_name)) {
                $displayParts[] = $row->variation_group_name;
            }
            if (! empty($row->variation_name) && strtoupper((string) $row->variation_name) !== 'DUMMY') {
                $displayParts[] = $row->variation_name;
            }
        }

        return [
            'product_id' => (int) $row->product_id,
            'variation_id' => (int) $row->variation_id,
            'product_name' => (string) $row->product_name,
            'variation_name' => implode(' - ', array_filter($displayParts)),
            'sku' => (string) ($row->sub_sku ?: ''),
            'tax_id' => $row->tax_id ? (int) $row->tax_id : null,
            'tax_name' => (string) $row->tax_name,
            'tax_rate' => round((float) $row->tax_rate, 6),
            'tax_type' => (string) ($row->tax_type ?: 'exclusive'),
            'current_purchase_price_ex_tax' => round((float) $row->default_purchase_price, 8),
            'current_purchase_price_inc_tax' => round((float) $row->dpp_inc_tax, 8),
            'current_sell_price_ex_tax' => round((float) $row->default_sell_price, 8),
            'current_sell_price_inc_tax' => round((float) $row->sell_price_inc_tax, 8),
            'current_profit_percent' => round((float) $row->profit_percent, 8),
            'stock_quantity' => round((float) $locations->sum('qty_available'), 4),
            'locations' => $locations->map(fn ($location): array => [
                'id' => (int) $location->id,
                'name' => (string) $location->name,
                'qty_available' => round((float) $location->qty_available, 4),
            ])->all(),
        ];
    }
}
