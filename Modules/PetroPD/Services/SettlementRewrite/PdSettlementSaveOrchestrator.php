<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Facades\DB;

class PdSettlementSaveOrchestrator
{
    public function __construct(
        protected PdSettlementSourceRepository $sources,
        protected PdSettlementTotalsService $totals,
        protected PdSettlementSourceMarker $marker,
        protected PdSettlementPostingBridge $postingBridge
    ) {
    }

    /**
     * Finalize one closed shift into one settlement source-of-truth flow.
     *
     * This method is intentionally isolated so the old controller can be replaced step-by-step.
     */
    public function finalize(int $businessId, int $settlementId, string $settlementNo, int $shiftId, ?int $pumpOperatorId = null): array
    {
        return DB::transaction(function () use ($businessId, $settlementId, $settlementNo, $shiftId, $pumpOperatorId) {
            $snapshot = $this->sources->sourceSnapshot($businessId, $shiftId, $pumpOperatorId);
            $totals = $this->totals->calculate($snapshot);

            $this->postingBridge->post([
                'business_id' => $businessId,
                'settlement_id' => $settlementId,
                'settlement_no' => $settlementNo,
                'shift_id' => $shiftId,
                'pump_operator_id' => $pumpOperatorId,
                'snapshot' => $snapshot,
                'totals' => $totals,
            ]);

            $this->marker->markAsSettled($businessId, $shiftId, $settlementId, $settlementNo);

            return [
                'snapshot' => $snapshot,
                'totals' => $totals,
            ];
        });
    }
}
