<?php

namespace Modules\PetroPDNew\Services\Settlement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PetroPDNew\Entities\PdnewPostingBatch;
use Modules\PetroPDNew\Entities\PdnewPostingLine;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Services\PdnewNumberSequenceService;

class PdnewPostingService
{
    public function __construct(private PdnewNumberSequenceService $numbers) {}

    public function prepareForSettlement(PdnewSettlement $settlement, int $userId): PdnewPostingBatch
    {
        return DB::transaction(function () use ($settlement, $userId): PdnewPostingBatch {
            $existing = PdnewPostingBatch::query()
                ->where('business_id', $settlement->business_id)
                ->where('settlement_id', $settlement->id)
                ->whereIn('status', ['prepared', 'posted'])
                ->lockForUpdate()
                ->first();

            if ($existing && $this->matchesSettlement($existing, $settlement)) {
                return $existing->fresh('lines');
            }

            if ($existing) {
                $existing->update([
                    'status' => 'reversed',
                    'reversed_by' => $userId,
                    'reversed_at' => now(),
                ]);
            }

            $number = $this->numbers->next(
                (int) $settlement->business_id,
                $settlement->location_id ? (int) $settlement->location_id : null,
                'posting',
                'PDN-PST-'
            );
            $shortage = (float) $settlement->source_shortage_total
                + (float) $settlement->manual_shortage_total;
            $excess = (float) $settlement->source_excess_total
                + (float) $settlement->manual_excess_total;

            $batch = PdnewPostingBatch::query()->create([
                'uuid' => (string) Str::uuid(),
                'business_id' => $settlement->business_id,
                'location_id' => $settlement->location_id,
                'settlement_id' => $settlement->id,
                'batch_number' => $number,
                'posting_date' => $settlement->settlement_date,
                'status' => 'prepared',
                'total_debit' => 0,
                'total_credit' => 0,
                'metadata' => [
                    'source' => 'petro_pd_new',
                    'settlement_number' => $settlement->settlement_number,
                    'source_hash' => $settlement->source_hash,
                    'expected_total' => (float) $settlement->expected_total,
                    'received_total' => (float) $settlement->received_total,
                    'classified_shortage_total' => $shortage,
                    'classified_excess_total' => $excess,
                    'operational_variance_amount' => (float) $settlement->operational_variance_amount,
                    'unresolved_variance_amount' => (float) $settlement->variance_amount,
                ],
                'created_by' => $userId,
            ]);

            $line = 1;
            if ((float) $settlement->received_total !== 0.0) {
                $this->line(
                    $batch,
                    $line++,
                    'PDNEW-CLEARING',
                    'Normal settlement collections',
                    (float) $settlement->received_total,
                    0
                );
            }
            if ($shortage !== 0.0) {
                $this->line(
                    $batch,
                    $line++,
                    'PDNEW-SHORTAGE',
                    'Classified settlement shortage',
                    $shortage,
                    0
                );
            }
            if ((float) $settlement->meter_sales_total !== 0.0) {
                $this->line(
                    $batch,
                    $line++,
                    'PDNEW-METER-SALES',
                    'Meter sales',
                    0,
                    (float) $settlement->meter_sales_total
                );
            }
            if ((float) $settlement->other_sales_total !== 0.0) {
                $this->line(
                    $batch,
                    $line++,
                    'PDNEW-OTHER-SALES',
                    'Other sales',
                    0,
                    (float) $settlement->other_sales_total
                );
            }

            $expectedAdjustment = (float) ($settlement->expected_adjustments_total ?? 0);
            if ($expectedAdjustment > 0) {
                $this->line(
                    $batch,
                    $line++,
                    'PDNEW-EXPECTED-ADJUSTMENT',
                    'Approved expected amount adjustment',
                    0,
                    $expectedAdjustment
                );
            } elseif ($expectedAdjustment < 0) {
                $this->line(
                    $batch,
                    $line++,
                    'PDNEW-EXPECTED-ADJUSTMENT',
                    'Approved expected amount adjustment',
                    abs($expectedAdjustment),
                    0
                );
            }

            if ($excess !== 0.0) {
                $this->line(
                    $batch,
                    $line++,
                    'PDNEW-EXCESS',
                    'Classified settlement excess',
                    0,
                    $excess
                );
            }

            $debit = (float) $batch->lines()->sum('debit');
            $credit = (float) $batch->lines()->sum('credit');
            $balanced = abs($debit - $credit) < 0.00005;
            $batch->update([
                'total_debit' => $debit,
                'total_credit' => $credit,
                'status' => $balanced ? 'posted' : 'prepared',
                'posted_by' => $balanced ? $userId : null,
                'posted_at' => $balanced ? now() : null,
            ]);

            return $batch->fresh('lines');
        }, 3);
    }

    public function reverseForSettlement(PdnewSettlement $settlement, int $userId): void
    {
        PdnewPostingBatch::query()
            ->where('business_id', $settlement->business_id)
            ->where('settlement_id', $settlement->id)
            ->whereIn('status', ['prepared', 'posted'])
            ->update([
                'status' => 'reversed',
                'reversed_by' => $userId,
                'reversed_at' => now(),
            ]);
    }

    private function matchesSettlement(
        PdnewPostingBatch $batch,
        PdnewSettlement $settlement
    ): bool {
        $metadata = (array) $batch->metadata;
        $shortage = (float) $settlement->source_shortage_total
            + (float) $settlement->manual_shortage_total;
        $excess = (float) $settlement->source_excess_total
            + (float) $settlement->manual_excess_total;

        return hash_equals(
                (string) ($metadata['source_hash'] ?? ''),
                (string) $settlement->source_hash
            )
            && $this->sameAmount($metadata['expected_total'] ?? null, $settlement->expected_total)
            && $this->sameAmount($metadata['received_total'] ?? null, $settlement->received_total)
            && $this->sameAmount($metadata['classified_shortage_total'] ?? null, $shortage)
            && $this->sameAmount($metadata['classified_excess_total'] ?? null, $excess)
            && $this->sameAmount($metadata['unresolved_variance_amount'] ?? null, $settlement->variance_amount);
    }

    private function sameAmount(mixed $left, mixed $right): bool
    {
        return $left !== null && abs((float) $left - (float) $right) < 0.00005;
    }

    private function line(
        PdnewPostingBatch $batch,
        int $lineNo,
        string $code,
        string $description,
        float $debit,
        float $credit
    ): void {
        PdnewPostingLine::query()->create([
            'business_id' => $batch->business_id,
            'posting_batch_id' => $batch->id,
            'line_no' => $lineNo,
            'account_code' => $code,
            'description' => $description,
            'debit' => round($debit, 4),
            'credit' => round($credit, 4),
            'metadata' => [],
        ]);
    }
}
