<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Services\Integration\PonePetroPdNewPublisher;

class PoneShiftService
{
    public function __construct(
        private PoneContextService $context,
        private PoneSettingsService $settings,
        private PoneShiftTotalsService $totals,
        private PoneOperatorLedgerService $ledger,
        private PonePetroPdNewPublisher $bridge,
        private PoneAuditService $audit
    ) {}

    public function close(?string $note = null): PoneShift
    {
        $shift = DB::transaction(function () use ($note): PoneShift {
            $shift = PoneShift::query()->whereKey($this->context->shiftId())
                ->where('business_id', $this->context->businessId())
                ->where('operator_profile_id', $this->context->operatorProfileId())
                ->lockForUpdate()->firstOrFail();
            if ($shift->status === 'closed') return $shift;

            $config = $this->settings->get($shift->business_id, $shift->location_id);
            $openPumps = $shift->assignments()->whereNotIn('status', ['closed', 'cancelled'])->count();
            if ($openPumps > 0 && ! ($config['allow_close_with_open_pumps'] ?? false)) {
                throw ValidationException::withMessages(['shift' => trans_choice('pumperdashboardnew::lang.open_pumps_block_close', $openPumps, ['count' => $openPumps])]);
            }
            if (($config['require_collection_before_close'] ?? false)
                && ! $shift->collections()->where('status', 'confirmed')->exists()) {
                throw ValidationException::withMessages(['collection' => __('pumperdashboardnew::lang.collection_required_before_close')]);
            }
            $before = $shift->toArray();
            $shift->update([
                'status' => 'closing',
                'notes' => trim((string) $note) !== '' ? trim((string) $note) : $shift->notes,
                'integration_status' => 'pending',
            ]);
            $shift = $this->totals->refresh($shift);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('shift.closing', 'pone_shift', $shift->id, $before, $shift);
            return $shift->fresh();
        }, 3);

        $synced = $this->bridge->closeShift($shift);
        $config = $this->settings->get($shift->business_id, $shift->location_id);
        if (! $synced && ($config['require_clean_sync_before_close'] ?? true)
            && ! ($config['allow_close_with_pending_sync'] ?? false)) {
            $shift->forceFill(['status' => 'open'])->saveQuietly();
            throw ValidationException::withMessages(['integration' => __('pumperdashboardnew::lang.integration_must_succeed_before_close')]);
        }

        $shift = DB::transaction(function () use ($shift, $synced): PoneShift {
            $locked = PoneShift::query()->whereKey($shift->id)->lockForUpdate()->firstOrFail();
            $before = $locked->toArray();
            $locked->update([
                'status' => 'closed', 'closed_at' => now(), 'closed_by' => $this->context->userId(),
                'integration_status' => $synced ? 'synced' : 'failed',
            ]);
            $locked = $this->totals->refresh($locked);
            $this->ledger->synchronizeShift($locked);
            $this->audit->log('shift.closed', 'pone_shift', $locked->id, $before, $locked);
            return $locked->fresh();
        }, 3);

        $this->context->setShift(null);
        return $shift;
    }
}
