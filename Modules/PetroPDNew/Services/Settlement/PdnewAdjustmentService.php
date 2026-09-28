<?php

namespace Modules\PetroPDNew\Services\Settlement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Entities\PdnewSettlementAdjustment;
use Modules\PetroPDNew\Services\PdnewAuditService;
use Modules\PetroPDNew\Services\PdnewNumberSequenceService;
use RuntimeException;

class PdnewAdjustmentService
{
    private const FIELDS = [
        'meter_sales_total', 'other_sales_total', 'source_payments_total',
        'manual_payments_total', 'expected_total', 'received_total',
    ];

    public function __construct(
        private PdnewSettlementService $settlements,
        private PdnewSettlementTotalsService $totals,
        private PdnewReconciliationService $reconciliation,
        private PdnewNumberSequenceService $numbers,
        private PdnewAuditService $audit
    ) {}

    public function request(PdnewSettlement $settlement, array $data, int $userId): PdnewSettlementAdjustment
    {
        $field = (string) $data['field_name'];
        if (! in_array($field, self::FIELDS, true)) {
            throw new RuntimeException('The selected amount cannot be adjusted.');
        }

        return DB::transaction(function () use ($settlement, $data, $userId, $field): PdnewSettlementAdjustment {
            $settlement = $this->settlements->lockForUpdate($settlement);
            $this->settlements->assertEditable($settlement);

            $number = $this->numbers->next(
                (int) $settlement->business_id,
                $settlement->location_id ? (int) $settlement->location_id : null,
                'adjustment',
                'PDN-ADJ-'
            );

            $adjustment = PdnewSettlementAdjustment::query()->create([
                'uuid' => (string) Str::uuid(),
                'business_id' => $settlement->business_id,
                'settlement_id' => $settlement->id,
                'adjustment_number' => $number,
                'adjustment_type' => (string) ($data['adjustment_type'] ?? 'replace'),
                'field_name' => $field,
                'current_amount' => (float) $settlement->{$field},
                'requested_amount' => $data['requested_amount'],
                'approved_amount' => 0,
                'reason' => (string) $data['reason'],
                'status' => 'requested',
                'requested_by' => $userId,
                'requested_at' => now(),
                'metadata' => $data['metadata'] ?? [],
            ]);

            $this->reconciliation->evaluate($settlement);
            $this->audit->log('adjustment.requested', 'pdnew_settlement_adjustment', $adjustment->id, null, $adjustment);

            return $adjustment->fresh();
        }, 3);
    }

    public function approve(PdnewSettlementAdjustment $adjustment, int $userId, ?float $amount, ?string $note): PdnewSettlementAdjustment
    {
        return DB::transaction(function () use ($adjustment, $userId, $amount, $note): PdnewSettlementAdjustment {
            [$settlement, $adjustment] = $this->lockPair($adjustment);
            $this->settlements->assertEditable($settlement);

            if ($adjustment->status !== 'requested') {
                throw new RuntimeException('Only a requested adjustment can be approved.');
            }

            $adjustment->update([
                'status' => 'approved',
                'approved_amount' => $amount ?? (float) $adjustment->requested_amount,
                'approved_by' => $userId,
                'approved_at' => now(),
                'decision_note' => $note,
            ]);

            $this->totals->recalculate($settlement);
            $this->reconciliation->evaluate($settlement);
            $this->audit->log('adjustment.approved', 'pdnew_settlement_adjustment', $adjustment->id, null, $adjustment);

            return $adjustment->fresh();
        }, 3);
    }

    public function reject(PdnewSettlementAdjustment $adjustment, int $userId, string $note): PdnewSettlementAdjustment
    {
        return DB::transaction(function () use ($adjustment, $userId, $note): PdnewSettlementAdjustment {
            [$settlement, $adjustment] = $this->lockPair($adjustment);
            $this->settlements->assertEditable($settlement);

            if ($adjustment->status !== 'requested') {
                throw new RuntimeException('Only a requested adjustment can be rejected.');
            }

            $adjustment->update([
                'status' => 'rejected',
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'decision_note' => $note,
            ]);

            $this->reconciliation->evaluate($settlement);
            $this->audit->log('adjustment.rejected', 'pdnew_settlement_adjustment', $adjustment->id, null, $adjustment);

            return $adjustment->fresh();
        }, 3);
    }

    /**
     * Lock parent first, then child. All settlement financial writers use this
     * order so finalization cannot race an adjustment decision.
     *
     * @return array{0:PdnewSettlement,1:PdnewSettlementAdjustment}
     */
    private function lockPair(PdnewSettlementAdjustment $adjustment): array
    {
        $settlement = PdnewSettlement::query()
            ->where('business_id', $adjustment->business_id)
            ->whereKey($adjustment->settlement_id)
            ->lockForUpdate()
            ->firstOrFail();
        $adjustment = PdnewSettlementAdjustment::query()
            ->where('business_id', $settlement->business_id)
            ->where('settlement_id', $settlement->id)
            ->whereKey($adjustment->id)
            ->lockForUpdate()
            ->firstOrFail();

        return [$settlement, $adjustment];
    }
}
