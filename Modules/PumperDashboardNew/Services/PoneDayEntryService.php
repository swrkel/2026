<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneDayEntry;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;
use Modules\PumperDashboardNew\Services\Integration\PonePetroPdNewPublisher;

class PoneDayEntryService
{
    public function __construct(
        private PoneContextService $context,
        private PonePetroPdNewPublisher $bridge,
        private PoneAuditService $audit
    ) {}

    public function create(array $data): PoneDayEntry
    {
        return DB::transaction(function () use ($data): PoneDayEntry {
            $shift = $this->context->shift();
            $attributes = $this->attributes($data, $shift->id, $shift->business_id, $shift->location_id);
            $entry = PoneDayEntry::query()->create($attributes + [
                'shift_id' => $shift->id,
                'business_id' => $shift->business_id,
                'location_id' => $shift->location_id,
                'operator_profile_id' => $shift->operator_profile_id,
                'pd_operator_id' => $shift->pd_operator_id,
                'settlement_no' => $shift->settlement_no,
                'status' => 'active',
                'integration_status' => 'pending',
                'created_by' => $this->context->userId(),
            ]);
            $this->bridge->syncDayEntry($entry);
            $this->audit->log('day_entry.created', 'pone_day_entry', $entry->id, null, $entry);
            return $entry->fresh();
        }, 3);
    }

    public function update(int $id, array $data): PoneDayEntry
    {
        return DB::transaction(function () use ($id, $data): PoneDayEntry {
            $shift = $this->context->shift();
            $entry = PoneDayEntry::query()->whereKey($id)->where('shift_id', $shift->id)
                ->where('business_id', $shift->business_id)->lockForUpdate()->firstOrFail();
            if ($entry->status !== 'active') throw ValidationException::withMessages(['entry' => __('pumperdashboardnew::lang.only_active_day_entry_editable')]);
            $reason = trim((string) ($data['edit_reason'] ?? ''));
            if ($reason === '') throw ValidationException::withMessages(['edit_reason' => __('pumperdashboardnew::lang.edit_reason_required')]);
            $before = $entry->toArray();
            $entry->update($this->attributes($data, $shift->id, $shift->business_id, $shift->location_id) + [
                'settlement_no' => $data['settlement_no'] ?? $entry->settlement_no ?? $shift->settlement_no,
                'note' => trim((string) ($data['note'] ?? '')) . "\nEdit reason: {$reason}",
                'edited_by' => $this->context->userId(), 'edited_at' => now(), 'integration_status' => 'pending',
            ]);
            $this->bridge->syncDayEntry($entry->fresh());
            $this->audit->log('day_entry.updated', 'pone_day_entry', $entry->id, $before, $entry);
            return $entry->fresh();
        }, 3);
    }

    public function void(int $id, string $reason): PoneDayEntry
    {
        return DB::transaction(function () use ($id, $reason): PoneDayEntry {
            $shift = $this->context->shift();
            $entry = PoneDayEntry::query()->whereKey($id)->where('shift_id', $shift->id)
                ->where('business_id', $shift->business_id)->lockForUpdate()->firstOrFail();
            $reason = trim($reason);
            if ($reason === '') throw ValidationException::withMessages(['reason' => __('pumperdashboardnew::lang.void_reason_required')]);
            if ($entry->status === 'void') return $entry;
            $before = $entry->toArray();
            $entry->update(['status' => 'void', 'note' => $reason, 'voided_by' => $this->context->userId(), 'voided_at' => now(), 'integration_status' => 'pending']);
            $this->bridge->voidDayEntry($entry->fresh());
            $this->audit->log('day_entry.voided', 'pone_day_entry', $entry->id, $before, $entry);
            return $entry->fresh();
        }, 3);
    }

    private function attributes(array $data, int $shiftId, int $businessId, ?int $locationId): array
    {
        $assignment = null;
        if (! empty($data['assignment_id'])) {
            $assignment = PonePumpAssignment::query()->whereKey((int) $data['assignment_id'])
                ->where('shift_id', $shiftId)->where('business_id', $businessId)->firstOrFail();
        }
        $quantity = round((float) ($data['quantity'] ?? 0), 6);
        $testing = round((float) ($data['testing_quantity'] ?? 0), 6);
        $amount = round((float) ($data['amount'] ?? 0), 4);
        if ($quantity < 0 || $testing < 0 || $amount < 0) {
            throw ValidationException::withMessages(['quantity' => __('pumperdashboardnew::lang.quantity_must_not_be_negative')]);
        }
        $startingMeter = isset($data['starting_meter']) && $data['starting_meter'] !== ''
            ? round((float) $data['starting_meter'], 6)
            : ($assignment?->opening_meter !== null ? round((float) $assignment->opening_meter, 6) : null);
        $closingMeter = isset($data['closing_meter']) && $data['closing_meter'] !== ''
            ? round((float) $data['closing_meter'], 6)
            : ($assignment?->current_meter !== null ? round((float) $assignment->current_meter, 6) : null);
        if ($startingMeter !== null && $closingMeter !== null && $closingMeter < $startingMeter) {
            throw ValidationException::withMessages(['closing_meter' => __('pumperdashboardnew::lang.closing_meter_below_starting')]);
        }
        return [
            'assignment_id' => $assignment?->id,
            'pump_id' => $assignment?->pump_id,
            'entry_type' => $data['entry_type'],
            'reference_no' => $data['reference_no'] ?? null,
            'quantity' => $quantity,
            'amount' => $amount,
            'starting_meter' => $startingMeter,
            'closing_meter' => $closingMeter,
            'testing_quantity' => $testing,
            'entry_at' => $data['entry_at'] ?? now(),
            'note' => $data['note'] ?? null,
        ];
    }
}
