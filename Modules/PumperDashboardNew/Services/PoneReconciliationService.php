<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneExcessCommission;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Entities\PoneShortageRecovery;

class PoneReconciliationService
{
    public function __construct(
        private PoneContextService $context,
        private PoneNumberSequenceService $numbers,
        private PoneOperatorLedgerService $ledger,
        private PoneAuditService $audit
    ) {}

    public function recoverShortage(array $data): PoneShortageRecovery
    {
        return $this->recoverForShift($this->context->shift(true), $data, $this->context->userId());
    }

    public function recoverForShift(PoneShift $shift, array $data, int $userId): PoneShortageRecovery
    {
        return DB::transaction(function () use ($shift, $data, $userId): PoneShortageRecovery {
            $shift = PoneShift::query()->whereKey($shift->id)->lockForUpdate()->firstOrFail();
            $amount = round((float) ($data['amount'] ?? 0), 4);
            $available = $this->availableShortageForShift($shift);
            if ($amount <= 0 || $amount > $available + 0.00005) {
                throw ValidationException::withMessages(['amount' => __('pumperdashboardnew::lang.recovery_exceeds_shortage', ['amount' => number_format($available, 4)])]);
            }
            $row = PoneShortageRecovery::query()->create([
                'shift_id' => $shift->id, 'business_id' => $shift->business_id, 'location_id' => $shift->location_id,
                'operator_profile_id' => $shift->operator_profile_id, 'pd_operator_id' => $shift->pd_operator_id,
                'recovery_number' => $this->numbers->next($shift->business_id, $shift->location_id, 'recovery'),
                'recovery_date' => $data['recovery_date'] ?? now()->toDateString(), 'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash', 'reference_no' => $data['reference_no'] ?? null,
                'note' => $data['note'] ?? null, 'status' => 'confirmed', 'created_by' => $userId,
            ]);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('shortage.recovered', 'pone_shortage_recovery', $row->id, null, $row,
                $shift->business_id, $shift->location_id, $shift->operator_profile_id, $userId);
            return $row->fresh();
        }, 3);
    }

    public function createExcessCommission(array $data): PoneExcessCommission
    {
        return $this->commissionForShift($this->context->shift(true), $data, $this->context->userId());
    }

    public function commissionForShift(PoneShift $shift, array $data, int $userId): PoneExcessCommission
    {
        return DB::transaction(function () use ($shift, $data, $userId): PoneExcessCommission {
            $shift = PoneShift::query()->whereKey($shift->id)->lockForUpdate()->firstOrFail();
            $available = $this->availableExcessForShift($shift);
            $type = (string) ($data['commission_type'] ?? 'percentage');
            $rate = round((float) ($data['commission_rate'] ?? 0), 6);
            $amount = $type === 'fixed' ? $rate : round($available * $rate / 100, 4);
            if ($available <= 0 || $rate <= 0 || $amount <= 0 || $amount > $available + 0.00005) {
                throw ValidationException::withMessages(['commission_rate' => __('pumperdashboardnew::lang.invalid_excess_commission')]);
            }
            $row = PoneExcessCommission::query()->create([
                'shift_id' => $shift->id, 'business_id' => $shift->business_id, 'location_id' => $shift->location_id,
                'operator_profile_id' => $shift->operator_profile_id, 'pd_operator_id' => $shift->pd_operator_id,
                'commission_number' => $this->numbers->next($shift->business_id, $shift->location_id, 'commission'),
                'commission_date' => $data['commission_date'] ?? now()->toDateString(),
                'base_excess_amount' => $available, 'commission_type' => $type, 'commission_rate' => $rate,
                'commission_amount' => $amount, 'note' => $data['note'] ?? null, 'status' => 'confirmed', 'created_by' => $userId,
            ]);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('excess.commission_created', 'pone_excess_commission', $row->id, null, $row,
                $shift->business_id, $shift->location_id, $shift->operator_profile_id, $userId);
            return $row->fresh();
        }, 3);
    }

    public function voidRecovery(int $id, string $reason): PoneShortageRecovery
    {
        return $this->voidRecoveryForBusiness($id, $reason, $this->context->businessId(), $this->context->userId(), $this->context->shift(true)->id);
    }

    public function voidRecoveryForBusiness(int $id, string $reason, int $businessId, int $userId, ?int $shiftId = null): PoneShortageRecovery
    {
        return DB::transaction(function () use ($id, $reason, $businessId, $userId, $shiftId): PoneShortageRecovery {
            $row = PoneShortageRecovery::query()->whereKey($id)->where('business_id', $businessId)
                ->when($shiftId, fn ($q) => $q->where('shift_id', $shiftId))->lockForUpdate()->firstOrFail();
            $reason = trim($reason);
            if ($reason === '') throw ValidationException::withMessages(['reason' => __('pumperdashboardnew::lang.void_reason_required')]);
            $before = $row->toArray();
            $row->update(['status' => 'void', 'note' => $reason, 'voided_by' => $userId, 'voided_at' => now()]);
            if ($row->shift) $this->ledger->synchronizeShift($row->shift);
            $this->audit->log('shortage.recovery_voided', 'pone_shortage_recovery', $row->id, $before, $row,
                $row->business_id, $row->location_id, $row->operator_profile_id, $userId);
            return $row->fresh();
        }, 3);
    }

    public function voidCommission(int $id, string $reason): PoneExcessCommission
    {
        return $this->voidCommissionForBusiness($id, $reason, $this->context->businessId(), $this->context->userId(), $this->context->shift(true)->id);
    }

    public function voidCommissionForBusiness(int $id, string $reason, int $businessId, int $userId, ?int $shiftId = null): PoneExcessCommission
    {
        return DB::transaction(function () use ($id, $reason, $businessId, $userId, $shiftId): PoneExcessCommission {
            $row = PoneExcessCommission::query()->whereKey($id)->where('business_id', $businessId)
                ->when($shiftId, fn ($q) => $q->where('shift_id', $shiftId))->lockForUpdate()->firstOrFail();
            $reason = trim($reason);
            if ($reason === '') throw ValidationException::withMessages(['reason' => __('pumperdashboardnew::lang.void_reason_required')]);
            $before = $row->toArray();
            $row->update(['status' => 'void', 'note' => $reason, 'voided_by' => $userId, 'voided_at' => now()]);
            if ($row->shift) $this->ledger->synchronizeShift($row->shift);
            $this->audit->log('excess.commission_voided', 'pone_excess_commission', $row->id, $before, $row,
                $row->business_id, $row->location_id, $row->operator_profile_id, $userId);
            return $row->fresh();
        }, 3);
    }

    public function availableShortage(int $shiftId): float
    {
        $shift = $this->context->shift(true);
        return (int) $shift->id === $shiftId ? $this->availableShortageForShift($shift) : 0;
    }

    public function availableShortageForShift(PoneShift $shift): float
    {
        $recovered = (float) PoneShortageRecovery::query()->where('shift_id', $shift->id)->where('status', 'confirmed')->sum('amount');
        return max(0, round((float) $shift->shortage_amount - $recovered, 4));
    }

    public function availableExcess(int $shiftId): float
    {
        $shift = $this->context->shift(true);
        return (int) $shift->id === $shiftId ? $this->availableExcessForShift($shift) : 0;
    }

    public function availableExcessForShift(PoneShift $shift): float
    {
        $commissioned = (float) PoneExcessCommission::query()->where('shift_id', $shift->id)->where('status', 'confirmed')->sum('commission_amount');
        return max(0, round((float) $shift->excess_amount - $commissioned, 4));
    }
}
