<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\TransferForecast;
use Modules\StockTransferNew\Entities\ReplenishmentSuggestion;

class TransferForecastService
{
    public function dashboard(int $businessId, array $filters = []): array
    {
        $forecastQuery = TransferForecast::query()->where('business_id', $businessId);
        $suggestionQuery = ReplenishmentSuggestion::query()->where('business_id', $businessId);

        foreach (['business_location_id', 'store_id', 'product_id', 'priority', 'status'] as $field) {
            if (!empty($filters[$field])) {
                $forecastQuery->where($field, $filters[$field]);
                $suggestionQuery->where($field, $filters[$field]);
            }
        }

        return [
            'open_forecasts' => (clone $forecastQuery)->whereIn('status', ['draft', 'review'])->count(),
            'critical_suggestions' => (clone $suggestionQuery)->where('priority', 'critical')->where('status', 'open')->count(),
            'total_suggested_qty' => (float) (clone $suggestionQuery)->where('status', 'open')->sum('suggested_qty'),
            'recent_forecasts' => $forecastQuery->latest('id')->limit(20)->get(),
            'suggestions' => $suggestionQuery->latest('id')->limit(50)->get(),
        ];
    }

    public function generateSuggestions(int $businessId, array $filters = []): int
    {
        $minimumRules = DB::table('stn_minimum_stock_rules')
            ->where('business_id', $businessId)
            ->when(!empty($filters['business_location_id']), fn ($q) => $q->where('business_location_id', $filters['business_location_id']))
            ->when(!empty($filters['store_id']), fn ($q) => $q->where('store_id', $filters['store_id']))
            ->get();

        $created = 0;
        foreach ($minimumRules as $rule) {
            $currentStock = (float) DB::table('stn_stock_balances')
                ->where('business_id', $businessId)
                ->where('business_location_id', $rule->business_location_id)
                ->where('store_id', $rule->store_id)
                ->where('product_id', $rule->product_id)
                ->value('qty_on_hand');

            $minimumStock = (float) ($rule->minimum_qty ?? 0);
            if ($currentStock >= $minimumStock) {
                continue;
            }

            $maximumStock = (float) ($rule->maximum_qty ?? ($minimumStock * 2));
            $suggestedQty = max(0, $maximumStock - $currentStock);

            ReplenishmentSuggestion::create([
                'business_id' => $businessId,
                'business_location_id' => $rule->business_location_id,
                'store_id' => $rule->store_id,
                'from_location_id' => $rule->preferred_from_location_id ?? null,
                'from_store_id' => $rule->preferred_from_store_id ?? null,
                'product_id' => $rule->product_id,
                'current_stock' => $currentStock,
                'minimum_stock' => $minimumStock,
                'maximum_stock' => $maximumStock,
                'suggested_qty' => $suggestedQty,
                'reason' => 'Current stock is below configured minimum stock.',
                'priority' => $currentStock <= 0 ? 'critical' : 'normal',
                'status' => 'open',
                'created_by' => auth()->id(),
            ]);
            $created++;
        }

        return $created;
    }

    public function approveSuggestion(ReplenishmentSuggestion $suggestion): void
    {
        $suggestion->update(['status' => 'approved']);
    }

    public function closeSuggestion(ReplenishmentSuggestion $suggestion, string $reason = null): void
    {
        $suggestion->update(['status' => 'closed', 'reason' => trim(($suggestion->reason ?? '') . ' ' . ($reason ?? ''))]);
    }
}
