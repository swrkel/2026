<?php
namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\MinStockRule;
use Modules\StockTransferNew\Entities\ReplenishmentProposal;

class StockTransferReplenishmentService
{
    public function rules(int $businessId)
    {
        return MinStockRule::where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('location_id')
            ->orderBy('store_id')
            ->get();
    }

    public function createRule(array $data, int $userId): MinStockRule
    {
        return MinStockRule::updateOrCreate([
            'business_id' => $data['business_id'],
            'location_id' => $data['location_id'],
            'store_id' => $data['store_id'] ?? null,
            'product_id' => $data['product_id'],
            'variation_id' => $data['variation_id'] ?? null,
        ], array_merge($data, ['updated_by' => $userId, 'created_by' => $userId]));
    }

    public function generateProposals(int $businessId, int $userId): int
    {
        $count = 0;
        foreach ($this->rules($businessId) as $rule) {
            $currentQty = $this->currentQty($rule);
            if ($currentQty >= (float)$rule->min_qty) {
                continue;
            }
            $proposedQty = $rule->preferred_transfer_qty ?: max((float)$rule->reorder_qty, (float)$rule->min_qty - $currentQty);
            ReplenishmentProposal::create([
                'business_id' => $businessId,
                'rule_id' => $rule->id,
                'product_id' => $rule->product_id,
                'variation_id' => $rule->variation_id,
                'from_location_id' => $rule->source_location_id,
                'from_store_id' => $rule->source_store_id,
                'to_location_id' => $rule->location_id,
                'to_store_id' => $rule->store_id,
                'current_qty' => $currentQty,
                'min_qty' => $rule->min_qty,
                'proposed_qty' => $proposedQty,
                'status' => 'open',
                'generated_by' => $userId,
            ]);
            $count++;
        }
        return $count;
    }

    protected function currentQty(MinStockRule $rule): float
    {
        if (!DB::getSchemaBuilder()->hasTable('stn_stock_balances')) {
            return 0.0;
        }
        return (float) DB::table('stn_stock_balances')
            ->where('business_id', $rule->business_id)
            ->where('location_id', $rule->location_id)
            ->where('store_id', $rule->store_id)
            ->where('product_id', $rule->product_id)
            ->when($rule->variation_id, fn($q) => $q->where('variation_id', $rule->variation_id))
            ->value('qty_on_hand');
    }

    public function openProposals(int $businessId)
    {
        return ReplenishmentProposal::where('business_id', $businessId)
            ->where('status', 'open')
            ->latest()
            ->get();
    }
}
