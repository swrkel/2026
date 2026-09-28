<?php

namespace Modules\PetroPDNew\Services\Settlement;

use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Entities\PdnewReconciliationIssue;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Services\Source\PoneSourceImportService;

class PdnewReconciliationService
{
    public function __construct(
        private PdnewSettlementTotalsService $totals,
        private PoneSourceImportService $imports
    ) {}

    public function evaluate(PdnewSettlement $settlement): array
    {
        return DB::transaction(function () use ($settlement): array {
            $settlement = PdnewSettlement::query()
                ->where('business_id', $settlement->business_id)
                ->whereKey($settlement->id)
                ->lockForUpdate()
                ->firstOrFail();

            $settlement = $this->totals->recalculate($settlement);
            $source = $settlement->sourceImport()->firstOrFail();
            $verification = $this->imports->verify($source);

            $openKeys = [];

            if (! $verification['matches']) {
                $openKeys[] = 'source_hash_mismatch';
                $this->issue($settlement, 'source_hash_mismatch', 'source_integrity', 'error',
                    'Pumper Dashboard-New records changed after the settlement snapshot was imported.', 0, 0, 0);
            }

            if (abs((float) $settlement->variance_amount) >= 0.00005) {
                $openKeys[] = 'payment_variance';
                $this->issue(
                    $settlement,
                    'payment_variance',
                    'payment_variance',
                    'error',
                    'Expected sales and accounted settlement amounts are not balanced after applying separately classified shortage and excess.',
                    (float) $settlement->expected_total,
                    (float) $settlement->received_total
                        + (float) $settlement->source_shortage_total
                        + (float) $settlement->manual_shortage_total
                        - (float) $settlement->source_excess_total
                        - (float) $settlement->manual_excess_total,
                    (float) $settlement->variance_amount
                );
            }

            $unconfirmedCredit = $settlement->creditSales()->where('confirmed', false)->count();
            if ($unconfirmedCredit > 0) {
                $openKeys[] = 'unconfirmed_credit_sales';
                $this->issue($settlement, 'unconfirmed_credit_sales', 'credit_confirmation', 'error',
                    $unconfirmedCredit . ' credit sale(s) do not have completed Pumper Dashboard-New confirmations.',
                    $unconfirmedCredit, 0, $unconfirmedCredit);
            }

            $invalidMeters = $settlement->pumps()
                ->whereColumn('closing_meter', '<', 'opening_meter')
                ->count();
            if ($invalidMeters > 0) {
                $openKeys[] = 'invalid_closing_meters';
                $this->issue($settlement, 'invalid_closing_meters', 'meter_integrity', 'error',
                    $invalidMeters . ' pump assignment(s) have a closing meter below the opening meter.',
                    0, $invalidMeters, $invalidMeters);
            }

            $pendingAdjustments = $settlement->adjustments()->where('status', 'requested')->count();
            if ($pendingAdjustments > 0) {
                $openKeys[] = 'pending_adjustments';
                $this->issue($settlement, 'pending_adjustments', 'adjustment', 'error',
                    $pendingAdjustments . ' amount adjustment request(s) are awaiting a decision and must be approved or rejected before finalization.',
                    0, $pendingAdjustments, $pendingAdjustments);
            }

            PdnewReconciliationIssue::query()
                ->where('settlement_id', $settlement->id)
                ->where('status', 'open')
                ->when($openKeys !== [], fn ($query) => $query->whereNotIn('issue_key', $openKeys))
                ->update([
                    'status' => 'resolved',
                    'resolution_note' => 'Automatically resolved by a later reconciliation.',
                    'resolved_by' => null,
                    'resolved_at' => now(),
                ]);

            return [
                'settlement' => $settlement->fresh(),
                'issues' => PdnewReconciliationIssue::query()
                    ->where('settlement_id', $settlement->id)
                    ->orderByRaw("FIELD(severity, 'error', 'warning', 'info')")
                    ->orderBy('id')
                    ->get(),
                'source_matches' => $verification['matches'],
            ];
        }, 3);
    }

    public function resolve(PdnewReconciliationIssue $issue, int $userId, string $note): PdnewReconciliationIssue
    {
        return DB::transaction(function () use ($issue, $userId, $note): PdnewReconciliationIssue {
            PdnewSettlement::query()
                ->where('business_id', $issue->business_id)
                ->whereKey($issue->settlement_id)
                ->lockForUpdate()
                ->firstOrFail();
            $issue = PdnewReconciliationIssue::query()
                ->where('business_id', $issue->business_id)
                ->where('settlement_id', $issue->settlement_id)
                ->whereKey($issue->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($issue->status !== 'open') {
                return $issue;
            }

            $issue->update([
                'status' => 'resolved',
                'resolution_note' => $note,
                'resolved_by' => $userId,
                'resolved_at' => now(),
            ]);

            return $issue->fresh();
        }, 3);
    }

    public function hasBlockingIssues(PdnewSettlement $settlement): bool
    {
        return PdnewReconciliationIssue::query()
            ->where('settlement_id', $settlement->id)
            ->where('status', 'open')
            ->where('severity', 'error')
            ->exists();
    }

    private function issue(
        PdnewSettlement $settlement,
        string $key,
        string $type,
        string $severity,
        string $description,
        float $expected,
        float $actual,
        float $difference
    ): void {
        PdnewReconciliationIssue::query()->updateOrCreate(
            ['settlement_id' => $settlement->id, 'issue_key' => $key],
            [
                'business_id' => $settlement->business_id,
                'issue_type' => $type,
                'severity' => $severity,
                'description' => $description,
                'expected_amount' => $expected,
                'actual_amount' => $actual,
                'difference_amount' => $difference,
                'status' => 'open',
                'resolution_note' => null,
                'resolved_by' => null,
                'resolved_at' => null,
            ]
        );
    }
}
