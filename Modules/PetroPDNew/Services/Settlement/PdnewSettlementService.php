<?php

namespace Modules\PetroPDNew\Services\Settlement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Entities\PdnewSettlementStatusHistory;
use Modules\PetroPDNew\Entities\PdnewSourceImport;
use Modules\PetroPDNew\Services\PdnewAuditService;
use Modules\PetroPDNew\Services\PdnewNumberSequenceService;
use Modules\PetroPDNew\Services\PdnewSettingsService;
use Modules\PetroPDNew\Services\Integration\PoneSettlementReferenceWriter;
use Modules\PetroPDNew\Services\Source\PoneSourceImportService;
use RuntimeException;

class PdnewSettlementService
{
    public function __construct(
        private PoneSourceImportService $imports,
        private PdnewSettlementHydrator $hydrator,
        private PdnewSettlementTotalsService $totals,
        private PdnewReconciliationService $reconciliation,
        private PdnewNumberSequenceService $numbers,
        private PdnewSettingsService $settings,
        private PdnewAuditService $audit,
        private PoneSettlementReferenceWriter $references
    ) {}

    public function createFromShift(
        int $businessId,
        int $shiftId,
        int $userId,
        ?string $settlementDate = null,
        ?string $note = null
    ): PdnewSettlement {
        $existingSettlement = PdnewSettlement::query()
            ->where('business_id', $businessId)
            ->where('pone_shift_id', $shiftId)
            ->first();

        if ($existingSettlement) {
            return $existingSettlement;
        }

        $this->references->assertAvailableForCreation($businessId, $shiftId);
        $source = $this->imports->import($businessId, $shiftId, $userId);

        if ($source->settlement_id) {
            return PdnewSettlement::query()
                ->where('business_id', $businessId)
                ->findOrFail($source->settlement_id);
        }

        return DB::transaction(function () use ($source, $userId, $settlementDate, $note): PdnewSettlement {
            $source = PdnewSourceImport::query()
                ->whereKey($source->id)
                ->where('business_id', $source->business_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($source->settlement_id) {
                return PdnewSettlement::query()->findOrFail($source->settlement_id);
            }

            $this->references->assertAvailableForCreation(
                (int) $source->business_id,
                (int) $source->pone_shift_id,
                true
            );

            $snapshot = $this->imports->currentSnapshot($source);
            $shift = (array) $snapshot['shift'];
            $operator = (array) ($snapshot['operator'] ?? []);
            $scopeSettings = $this->settings->forScope((int) $source->business_id, $source->location_id);
            $number = $this->numbers->next(
                (int) $source->business_id,
                $source->location_id ? (int) $source->location_id : null,
                'settlement',
                (string) ($scopeSettings['settlement_prefix'] ?? 'PDN-SET-')
            );

            $settlement = PdnewSettlement::query()->create([
                'uuid' => (string) Str::uuid(),
                'business_id' => $source->business_id,
                'location_id' => $source->location_id,
                'source_import_id' => $source->id,
                'pone_shift_id' => $source->pone_shift_id,
                'pone_shift_number' => $source->pone_shift_number,
                'pone_operator_profile_id' => $source->pone_operator_profile_id,
                'pone_pd_operator_id' => $source->pone_pd_operator_id,
                'operator_name' => (string) ($operator['display_name'] ?? ('Operator #' . $source->pone_operator_profile_id)),
                'settlement_number' => $number,
                'settlement_date' => $settlementDate ?: now()->toDateString(),
                'source_closed_at' => $source->source_closed_at,
                'status' => 'draft',
                'source_hash' => $source->source_hash,
                'reconciliation_status' => 'pending',
                'notes' => $note,
                'created_by' => $userId,
            ]);

            $source->update(['settlement_id' => $settlement->id, 'import_status' => 'settled']);
            $this->hydrator->hydrate($settlement, $snapshot);
            $settlement = $this->totals->recalculate($settlement);
            $this->recordStatus($settlement, null, 'draft', 'Settlement created from a closed Pumper Dashboard-New shift.', $userId);
            $this->reconciliation->evaluate($settlement);
            $this->audit->log('settlement.created', 'pdnew_settlement', $settlement->id, null, $settlement);

            return $settlement->fresh();
        }, 3);
    }

    public function refreshFromSource(PdnewSettlement $settlement, int $userId): PdnewSettlement
    {
        return DB::transaction(function () use ($settlement, $userId): PdnewSettlement {
            $settlement = $this->lockForUpdate($settlement);
            $this->assertEditable($settlement);

            $source = $this->imports->import(
                (int) $settlement->business_id,
                (int) $settlement->pone_shift_id,
                $userId
            );
            $snapshot = $this->imports->currentSnapshot($source);
            $before = $settlement->getAttributes();

            $settlement->update([
                'source_import_id' => $source->id,
                'source_hash' => $source->source_hash,
                'source_closed_at' => $source->source_closed_at,
            ]);

            $this->hydrator->hydrate($settlement, $snapshot);
            $settlement = $this->totals->recalculate($settlement);
            $this->reconciliation->evaluate($settlement);
            $this->audit->log('settlement.source_refreshed', 'pdnew_settlement', $settlement->id, $before, $settlement);

            return $settlement->fresh();
        }, 3);
    }

    public function update(PdnewSettlement $settlement, array $data): PdnewSettlement
    {
        return DB::transaction(function () use ($settlement, $data): PdnewSettlement {
            $settlement = $this->lockForUpdate($settlement);
            $this->assertEditable($settlement);
            $before = $settlement->getAttributes();

            $settlement->update([
                'settlement_date' => $data['settlement_date'] ?? $settlement->settlement_date,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $settlement->notes,
            ]);

            $this->audit->log('settlement.updated', 'pdnew_settlement', $settlement->id, $before, $settlement);
            return $settlement->fresh();
        }, 3);
    }

    public function cancel(PdnewSettlement $settlement, int $userId, string $reason): PdnewSettlement
    {
        return DB::transaction(function () use ($settlement, $userId, $reason): PdnewSettlement {
            $settlement = $this->lockForUpdate($settlement);

            if ($settlement->status === 'finalized') {
                throw new RuntimeException('A finalized settlement must be reopened before it can be cancelled.');
            }
            if (! in_array((string) $settlement->status, ['draft', 'review', 'approved', 'reopened'], true)) {
                throw new RuntimeException('This settlement cannot be cancelled while its status is ' . $settlement->status . '.');
            }

            $from = (string) $settlement->status;
            $settlement->update([
                'status' => 'cancelled',
                'cancelled_by' => $userId,
                'cancelled_at' => now(),
                'notes' => trim((string) $settlement->notes . "\nCancellation: " . $reason),
            ]);
            $settlement->sourceImport()->update(['import_status' => 'cancelled']);
            $this->recordStatus($settlement, $from, 'cancelled', $reason, $userId);
            $this->audit->log('settlement.cancelled', 'pdnew_settlement', $settlement->id, ['status' => $from], ['status' => 'cancelled']);

            return $settlement->fresh();
        }, 3);
    }


    public function lockForUpdate(PdnewSettlement $settlement): PdnewSettlement
    {
        return PdnewSettlement::query()
            ->where('business_id', $settlement->business_id)
            ->whereKey($settlement->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function assertEditable(PdnewSettlement $settlement): void
    {
        if (! in_array((string) $settlement->status, ['draft', 'reopened'], true)) {
            throw new RuntimeException('Only draft or reopened Petro PD-New settlements can be edited.');
        }
    }

    private function recordStatus(
        PdnewSettlement $settlement,
        ?string $from,
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
}
