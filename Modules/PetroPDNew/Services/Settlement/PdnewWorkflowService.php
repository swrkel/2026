<?php

namespace Modules\PetroPDNew\Services\Settlement;

use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Entities\PdnewDayEndSettlement;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Entities\PdnewSettlementApproval;
use Modules\PetroPDNew\Entities\PdnewSettlementStatusHistory;
use Modules\PetroPDNew\Services\Integration\PdnewOutboxService;
use Modules\PetroPDNew\Services\Integration\PoneSettlementReferenceWriter;
use Modules\PetroPDNew\Services\PdnewAuditService;
use Modules\PetroPDNew\Services\PdnewSettingsService;
use RuntimeException;

class PdnewWorkflowService
{
    public function __construct(
        private PdnewReconciliationService $reconciliation,
        private PdnewSettingsService $settings,
        private PoneSettlementReferenceWriter $references,
        private PdnewPostingService $postings,
        private PdnewOutboxService $outbox,
        private PdnewAuditService $audit
    ) {}

    public function submitForReview(PdnewSettlement $settlement, int $userId, ?string $note = null): PdnewSettlement
    {
        return DB::transaction(function () use ($settlement, $userId, $note): PdnewSettlement {
            $settlement = $this->lockSettlement($settlement);
            $this->requireStatus($settlement, ['draft', 'reopened']);
            $result = $this->reconciliation->evaluate($settlement);
            if (! $result['source_matches']) {
                throw new RuntimeException('The Pumper Dashboard-New source changed. Refresh the settlement before review.');
            }

            return $this->transition($settlement, 'review', 'submit', $userId, $note, [
                'submitted_at' => now(), 'submitted_by' => $userId,
            ]);
        }, 3);
    }

    public function returnToDraft(PdnewSettlement $settlement, int $userId, string $note): PdnewSettlement
    {
        return DB::transaction(function () use ($settlement, $userId, $note): PdnewSettlement {
            $settlement = $this->lockSettlement($settlement);
            $this->requireStatus($settlement, ['review', 'approved']);

            return $this->transition($settlement, 'draft', 'return', $userId, $note, [
                'reviewed_at' => null, 'reviewed_by' => null,
                'approved_at' => null, 'approved_by' => null,
            ]);
        }, 3);
    }

    public function approve(PdnewSettlement $settlement, int $userId, ?string $note = null): PdnewSettlement
    {
        return DB::transaction(function () use ($settlement, $userId, $note): PdnewSettlement {
            $settlement = $this->lockSettlement($settlement);
            $this->requireStatus($settlement, ['review']);
            $result = $this->reconciliation->evaluate($settlement);
            $settlement = $result['settlement'];
            if (! $result['source_matches']) {
                throw new RuntimeException('The source integrity check failed.');
            }
            if ($this->reconciliation->hasBlockingIssues($settlement)) {
                throw new RuntimeException('Resolve all blocking reconciliation issues before approval.');
            }

            return $this->transition($settlement, 'approved', 'approve', $userId, $note, [
                'reviewed_at' => $settlement->reviewed_at ?: now(),
                'reviewed_by' => $settlement->reviewed_by ?: $userId,
                'approved_at' => now(),
                'approved_by' => $userId,
            ]);
        }, 3);
    }

    public function finalize(PdnewSettlement $settlement, int $userId, ?string $note = null): PdnewSettlement
    {
        $settings = $this->settings->forScope(
            (int) $settlement->business_id,
            $settlement->location_id ? (int) $settlement->location_id : null
        );

        $required = ! empty($settings['require_approval']) ? ['approved'] : ['review', 'approved'];
        $this->requireStatus($settlement, $required);

        return DB::transaction(function () use ($settlement, $userId, $note, $settings): PdnewSettlement {
            $settlement = PdnewSettlement::query()
                ->where('business_id', $settlement->business_id)
                ->lockForUpdate()
                ->findOrFail($settlement->id);
            $this->requireStatus($settlement, ! empty($settings['require_approval']) ? ['approved'] : ['review', 'approved']);
            $result = $this->reconciliation->evaluate($settlement);
            $settlement = $result['settlement'];

            if (! $result['source_matches']) {
                throw new RuntimeException('Finalization stopped because the Pumper Dashboard-New source changed.');
            }
            if ($this->reconciliation->hasBlockingIssues($settlement)) {
                throw new RuntimeException('Finalization stopped because blocking reconciliation issues remain.');
            }
            if (! empty($settings['require_zero_variance']) && abs((float) $settlement->variance_amount) >= 0.00005) {
                throw new RuntimeException('Finalization requires a zero settlement variance.');
            }

            $from = (string) $settlement->status;
            $settlement->update(['status' => 'finalizing']);
            $this->references->write($settlement, $userId);
            $posting = $this->postings->prepareForSettlement($settlement, $userId);

            if (abs((float) $posting->total_debit - (float) $posting->total_credit) >= 0.00005) {
                throw new RuntimeException('The Petro PD-New posting batch is not balanced.');
            }

            $settlement->update([
                'status' => 'finalized',
                'finalized_by' => $userId,
                'finalized_at' => now(),
            ]);
            $settlement->sourceImport()->update(['import_status' => 'finalized']);

            $this->approval($settlement, 'finalize', $from, 'finalized', $userId, $note);
            $this->history($settlement, $from, 'finalized', $note, $userId);
            $this->outbox->queue(
                (int) $settlement->business_id,
                'settlement',
                (int) $settlement->id,
                'settlement.finalized',
                ['settlement_number' => $settlement->settlement_number]
            );
            $this->audit->log('settlement.finalized', 'pdnew_settlement', $settlement->id, ['status' => $from], ['status' => 'finalized']);

            return $settlement->fresh();
        }, 3);
    }

    public function reopen(PdnewSettlement $settlement, int $userId, string $reason): PdnewSettlement
    {
        $this->requireStatus($settlement, ['finalized']);

        if (PdnewDayEndSettlement::query()->where('settlement_id', $settlement->id)->exists()) {
            throw new RuntimeException('A settlement included in Day End cannot be reopened.');
        }

        $settings = $this->settings->forScope(
            (int) $settlement->business_id,
            $settlement->location_id ? (int) $settlement->location_id : null
        );
        if (empty($settings['allow_reopen'])) {
            throw new RuntimeException('Settlement reopening is disabled in Petro PD-New settings.');
        }

        return DB::transaction(function () use ($settlement, $userId, $reason): PdnewSettlement {
            $settlement = PdnewSettlement::query()
                ->where('business_id', $settlement->business_id)
                ->lockForUpdate()
                ->findOrFail($settlement->id);
            $this->requireStatus($settlement, ['finalized']);
            if (PdnewDayEndSettlement::query()->where('settlement_id', $settlement->id)->exists()) {
                throw new RuntimeException('A settlement included in Day End cannot be reopened.');
            }
            $this->references->remove($settlement);
            $this->postings->reverseForSettlement($settlement, $userId);
            $from = (string) $settlement->status;
            $settlement->update([
                'status' => 'reopened',
                'finalized_by' => null,
                'finalized_at' => null,
            ]);
            $settlement->sourceImport()->update(['import_status' => 'settled']);
            $this->approval($settlement, 'reopen', $from, 'reopened', $userId, $reason);
            $this->history($settlement, $from, 'reopened', $reason, $userId);
            $this->audit->log('settlement.reopened', 'pdnew_settlement', $settlement->id, ['status' => $from], ['status' => 'reopened']);

            return $settlement->fresh();
        }, 3);
    }

    private function lockSettlement(PdnewSettlement $settlement): PdnewSettlement
    {
        return PdnewSettlement::query()
            ->where('business_id', $settlement->business_id)
            ->whereKey($settlement->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function transition(
        PdnewSettlement $settlement,
        string $to,
        string $action,
        int $userId,
        ?string $note,
        array $extra = []
    ): PdnewSettlement {
        $from = (string) $settlement->status;
        $settlement->update(array_merge(['status' => $to], $extra));
        $this->approval($settlement, $action, $from, $to, $userId, $note);
        $this->history($settlement, $from, $to, $note, $userId);
        $this->audit->log('settlement.' . $action, 'pdnew_settlement', $settlement->id, ['status' => $from], ['status' => $to]);

        return $settlement->fresh();
    }

    private function approval(
        PdnewSettlement $settlement,
        string $action,
        string $from,
        string $to,
        int $userId,
        ?string $note
    ): void {
        PdnewSettlementApproval::query()->create([
            'business_id' => $settlement->business_id,
            'settlement_id' => $settlement->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'acted_by' => $userId,
            'acted_at' => now(),
            'metadata' => [],
        ]);
    }

    private function history(
        PdnewSettlement $settlement,
        string $from,
        string $to,
        ?string $reason,
        int $userId
    ): void {
        PdnewSettlementStatusHistory::query()->create([
            'business_id' => $settlement->business_id,
            'settlement_id' => $settlement->id,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'changed_by' => $userId,
            'changed_at' => now(),
            'metadata' => [],
        ]);
    }

    private function requireStatus(PdnewSettlement $settlement, array $allowed): void
    {
        if (! in_array((string) $settlement->status, $allowed, true)) {
            throw new RuntimeException(
                'This action is not allowed while the settlement status is ' . $settlement->status . '.'
            );
        }
    }
}
