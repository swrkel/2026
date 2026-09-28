<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneMeterReading;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Services\Integration\PonePetroPdNewPublisher;

class PoneAssignmentManagementService
{
    public function __construct(
        private PoneSharedMasterDataService $masterData,
        private PonePetroPdNewPublisher $bridge,
        private PoneAssignmentEventService $events,
        private PoneAuditService $audit
    ) {}

    public function add(PoneShift $shift, array $data, int $userId): PonePumpAssignment
    {
        return DB::transaction(function () use ($shift, $data, $userId): PonePumpAssignment {
            if (! $shift->isOpen()) throw ValidationException::withMessages(['shift_id' => __('pumperdashboardnew::lang.shift_not_open')]);
            $pumpId = (int) $data['pump_id'];
            $pump = $this->masterData->pump($shift->business_id, $pumpId);
            if (! $pump || ($shift->location_id && (int) ($pump->location_id ?? 0) !== (int) $shift->location_id)) {
                throw ValidationException::withMessages(['pump_id' => __('pumperdashboardnew::lang.invalid_pump')]);
            }
            $busy = PonePumpAssignment::query()->where('business_id', $shift->business_id)->where('pump_id', $pumpId)
                ->whereIn('status', ['assigned', 'open'])->where('shift_id', '<>', $shift->id)->exists();
            if ($busy) throw ValidationException::withMessages(['pump_id' => __('pumperdashboardnew::lang.pump_already_assigned')]);
            if ($shift->assignments()->where('pump_id', $pumpId)->exists()) {
                throw ValidationException::withMessages(['pump_id' => __('pumperdashboardnew::lang.pump_already_in_shift')]);
            }
            $last = PonePumpAssignment::query()->where('business_id', $shift->business_id)->where('pump_id', $pumpId)
                ->where('status', 'closed')->latest('closed_at')->first();
            $opening = isset($data['opening_meter']) ? round((float) $data['opening_meter'], 6)
                : (float) ($last?->closing_meter ?? $pump->last_meter_reading ?? $pump->pod_last_meter ?? $pump->starting_meter ?? 0);
            $product = ! empty($pump->product_id) ? $this->masterData->product($shift->business_id, (int) $pump->product_id, $shift->location_id) : null;
            $price = isset($data['unit_price']) ? round((float) $data['unit_price'], 6) : (float) ($product->unit_price ?? 0);
            $assignment = $shift->assignments()->create([
                'business_id' => $shift->business_id,
                'location_id' => $shift->location_id,
                'operator_profile_id' => $shift->operator_profile_id,
                'pd_operator_id' => $shift->pd_operator_id,
                'pump_id' => $pumpId,
                'product_id' => $pump->product_id ?? null,
                'opening_meter' => $opening,
                'current_meter' => $opening,
                'unit_price' => $price,
                'status' => 'assigned',
                'assigned_at' => now(),
                'integration_status' => 'pending',
            ]);
            PoneMeterReading::query()->create([
                'shift_id' => $shift->id, 'assignment_id' => $assignment->id,
                'business_id' => $shift->business_id, 'location_id' => $shift->location_id,
                'operator_profile_id' => $shift->operator_profile_id, 'pump_id' => $pumpId,
                'reading_type' => 'opening', 'meter_value' => $opening, 'source' => 'manual',
                'recorded_at' => now(), 'recorded_by' => $userId, 'note' => $data['note'] ?? null,
            ]);
            $this->events->record($assignment, 'assigned', $opening, 0, $data['note'] ?? null, [], $userId);
            $this->bridge->syncAssignment($assignment);
            $this->audit->log('assignment.added', 'pone_pump_assignment', $assignment->id, null, $assignment, $shift->business_id, $shift->location_id, $shift->operator_profile_id, $userId);
            return $assignment->fresh(['events', 'readings']);
        }, 3);
    }

    public function update(PonePumpAssignment $assignment, array $data, int $userId): PonePumpAssignment
    {
        return DB::transaction(function () use ($assignment, $data, $userId): PonePumpAssignment {
            $assignment = PonePumpAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            if ($assignment->status !== 'assigned') {
                throw ValidationException::withMessages(['assignment' => __('pumperdashboardnew::lang.assignment_locked_after_acceptance')]);
            }
            $before = $assignment->toArray();
            $opening = round((float) ($data['opening_meter'] ?? $assignment->opening_meter), 6);
            $price = round((float) ($data['unit_price'] ?? $assignment->unit_price), 6);
            if ($opening < 0 || $price < 0) throw ValidationException::withMessages(['opening_meter' => __('pumperdashboardnew::lang.invalid_meter_or_price')]);
            $assignment->update(['opening_meter' => $opening, 'current_meter' => $opening, 'unit_price' => $price, 'integration_status' => 'pending']);
            PoneMeterReading::query()->where('assignment_id', $assignment->id)->where('reading_type', 'opening')->update(['meter_value' => $opening, 'recorded_by' => $userId, 'note' => $data['note'] ?? null, 'updated_at' => now()]);
            $this->bridge->syncAssignment($assignment);
            $this->audit->log('assignment.updated', 'pone_pump_assignment', $assignment->id, $before, $assignment, $assignment->business_id, $assignment->location_id, $assignment->operator_profile_id, $userId);
            return $assignment->fresh();
        }, 3);
    }

    public function cancel(PonePumpAssignment $assignment, string $reason, int $userId): PonePumpAssignment
    {
        return DB::transaction(function () use ($assignment, $reason, $userId): PonePumpAssignment {
            $assignment = PonePumpAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            if ($assignment->status !== 'assigned') throw ValidationException::withMessages(['assignment' => __('pumperdashboardnew::lang.only_unreceived_assignment_can_cancel')]);
            $before = $assignment->toArray();
            $assignment->update(['status' => 'cancelled', 'closing_note' => trim($reason), 'integration_status' => 'pending']);
            $this->events->record($assignment, 'cancelled', (float) $assignment->current_meter, 0, $reason, [], $userId);
            $this->bridge->syncAssignment($assignment);
            $this->audit->log('assignment.cancelled', 'pone_pump_assignment', $assignment->id, $before, $assignment, $assignment->business_id, $assignment->location_id, $assignment->operator_profile_id, $userId);
            return $assignment->fresh();
        }, 3);
    }
}
