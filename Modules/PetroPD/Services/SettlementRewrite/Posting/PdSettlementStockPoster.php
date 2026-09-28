<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stock / COGS / sales-income posting seam for PD settlement rewrite.
 */
class PdSettlementStockPoster
{
    public function post(array $context): void
    {
        $settlementNo = $context['settlement_no'] ?? null;
        $businessId = $context['business_id'] ?? null;
        $meterRows = $context['meter_rows'] ?? [];
        $otherSalesRows = $context['other_sales_rows'] ?? [];

        if (empty($settlementNo) || empty($businessId)) {
            Log::warning('PD rewrite stock posting skipped: missing settlement/business', [
                'settlement_no' => $settlementNo,
                'business_id' => $businessId,
            ]);
            return;
        }

        $linkedSell = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where(function ($query) use ($settlementNo) {
                $query->where('invoice_no', $settlementNo)
                    ->orWhere('ref_no', $settlementNo)
                    ->orWhere('additional_notes', 'like', '%' . $settlementNo . '%');
            })
            ->exists();

        Log::info('PD rewrite stock posting context ready', [
            'settlement_no' => $settlementNo,
            'linked_sell_transaction_exists' => $linkedSell,
            'meter_rows' => count($meterRows),
            'other_sales_rows' => count($otherSalesRows),
            'meter_sales_total' => $context['meter_sales_total'] ?? 0,
            'other_sales_total' => $context['other_sales_total'] ?? 0,
        ]);
    }
}
