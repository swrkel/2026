<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneMeterReading;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;
use Modules\PumperDashboardNew\Services\Integration\PonePetroPdNewPublisher;

class PonePumpService
{
    public function __construct(
        private PoneContextService $context,
        private PoneSharedMasterDataService $masterData,
        private PoneShiftTotalsService $totals,
        private PoneOperatorLedgerService $ledger,
        private PoneAssignmentEventService $events,
        private PonePetroPdNewPublisher $bridge,
        private PoneAuditService $audit
    ) {}

    public function accept(int $assignmentId, ?string $note = null): PonePumpAssignment
    {
        return DB::transaction(function () use ($assignmentId, $note): PonePumpAssignment {
            $assignment = $this->assignment($assignmentId, true);
            if ($assignment->status === 'closed') return $assignment;
            $before = $assignment->toArray();
            $assignment->update([
                'status' => 'open',
                'accepted_at' => $assignment->accepted_at ?: now(),
                'accepted_by' => $assignment->accepted_by ?: $this->context->userId(),
                'integration_status' => 'pending',
            ]);
            $this->events->record($assignment, 'accepted', (float) $assignment->current_meter, 0, $note, [], $this->context->userId());
            $this->bridge->syncAssignment($assignment);
            $this->audit->log('pump.accepted', 'pone_pump_assignment', $assignment->id, $before, $assignment);
            return $assignment->fresh(['events']);
        }, 3);
    }

    public function confirm(int $assignmentId, ?string $note = null): PonePumpAssignment
    {
        return DB::transaction(function () use ($assignmentId, $note): PonePumpAssignment {
            $assignment = $this->assignment($assignmentId, true);
            if (! $assignment->accepted_at) throw ValidationException::withMessages(['assignment' => __('pumperdashboardnew::lang.receive_before_confirm')]);
            if ($assignment->confirmed_at) return $assignment;
            $before = $assignment->toArray();
            $assignment->update([
                'status' => 'open',
                'confirmed_at' => now(),
                'confirmed_by' => $this->context->userId(),
                'integration_status' => 'pending',
            ]);
            $this->events->record($assignment, 'confirmed', (float) $assignment->current_meter, 0, $note, [], $this->context->userId());
            $this->bridge->syncAssignment($assignment);
            $this->audit->log('pump.confirmed', 'pone_pump_assignment', $assignment->id, $before, $assignment);
            return $assignment->fresh(['events']);
        }, 3);
    }

    public function recordCurrent(int $assignmentId, float $meter, float $testingQuantity = 0, ?float $unitPrice = null, ?string $note = null): PonePumpAssignment
    {
        return DB::transaction(function () use ($assignmentId, $meter, $testingQuantity, $unitPrice, $note): PonePumpAssignment {
            $assignment = $this->assignment($assignmentId, true);
            $this->requireConfirmed($assignment);
            $this->validateReading($assignment, $meter, $testingQuantity, false);
            $before = $assignment->toArray();
            $price = $this->price($assignment, $unitPrice);
            $sold = max(0, $meter - (float) $assignment->opening_meter - $testingQuantity);
            $assignment->update([
                'status' => 'open',
                'current_meter' => $meter,
                'testing_quantity' => $testingQuantity,
                'sold_quantity' => $sold,
                'unit_price' => $price,
                'amount' => round($sold * $price, 4),
                'integration_status' => 'pending',
            ]);
            $this->reading($assignment, 'current', $meter, $testingQuantity, $note);
            $this->events->record($assignment, 'current_meter', $meter, $testingQuantity, $note, [], $this->context->userId());
            $this->bridge->syncAssignment($assignment);
            $shift = $this->totals->refresh($assignment->shift);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('meter.current_recorded', 'pone_pump_assignment', $assignment->id, $before, $assignment);
            return $assignment->fresh(['events', 'readings']);
        }, 3);
    }

    public function close(int $assignmentId, float $meter, float $testingQuantity = 0, ?float $unitPrice = null, ?string $note = null): PonePumpAssignment
    {
        return DB::transaction(function () use ($assignmentId, $meter, $testingQuantity, $unitPrice, $note): PonePumpAssignment {
            $assignment = $this->assignment($assignmentId, true);
            if ($assignment->status === 'closed') return $assignment;
            $this->requireConfirmed($assignment);
            $this->validateReading($assignment, $meter, $testingQuantity, true);
            $before = $assignment->toArray();
            $price = $this->price($assignment, $unitPrice);
            $sold = max(0, $meter - (float) $assignment->opening_meter - $testingQuantity);
            $assignment->update([
                'status' => 'closed',
                'current_meter' => $meter,
                'closing_meter' => $meter,
                'testing_quantity' => $testingQuantity,
                'sold_quantity' => $sold,
                'unit_price' => $price,
                'amount' => round($sold * $price, 4),
                'closing_note' => $note,
                'closed_at' => now(),
                'closed_by' => $this->context->userId(),
                'integration_status' => 'pending',
            ]);
            $this->reading($assignment, 'closing', $meter, $testingQuantity, $note);
            $this->events->record($assignment, 'closed', $meter, $testingQuantity, $note, ['sold_quantity' => $sold, 'amount' => round($sold * $price, 4)], $this->context->userId());
            $this->bridge->syncAssignment($assignment);
            $shift = $this->totals->refresh($assignment->shift);
            $this->ledger->synchronizeShift($shift);
            $this->audit->log('pump.closed', 'pone_pump_assignment', $assignment->id, $before, $assignment);
            return $assignment->fresh(['events', 'readings']);
        }, 3);
    }

    public function assignment(int $id, bool $lock = false): PonePumpAssignment
    {
        $shift = $this->context->shift();
        $query = PonePumpAssignment::query()->whereKey($id)->where('shift_id', $shift->id)
            ->where('business_id', $this->context->businessId())
            ->where('operator_profile_id', $this->context->operatorProfileId());
        if ($lock) $query->lockForUpdate();
        return $query->firstOrFail();
    }

    private function requireConfirmed(PonePumpAssignment $assignment): void
    {
        if (! $assignment->accepted_at || ! $assignment->confirmed_at) {
            throw ValidationException::withMessages(['assignment' => __('pumperdashboardnew::lang.pump_must_be_received_and_confirmed')]);
        }
    }

    private function validateReading(PonePumpAssignment $assignment, float $meter, float $testingQuantity, bool $closing): void
    {
        $errors = [];
        if ($meter < (float) $assignment->opening_meter) $errors['meter'][] = __('pumperdashboardnew::lang.meter_below_opening');
        if (! $closing && $meter < (float) $assignment->current_meter) $errors['meter'][] = __('pumperdashboardnew::lang.meter_cannot_decrease');
        if ($testingQuantity < 0) $errors['testing_quantity'][] = __('pumperdashboardnew::lang.testing_must_be_positive');
        if (($meter - (float) $assignment->opening_meter) < $testingQuantity) $errors['testing_quantity'][] = __('pumperdashboardnew::lang.testing_exceeds_meter_difference');
        if ($errors) throw ValidationException::withMessages($errors);
    }

    private function price(PonePumpAssignment $assignment, ?float $unitPrice): float
    {
        if ($unitPrice !== null && $unitPrice >= 0) return round($unitPrice, 6);
        if ((float) $assignment->unit_price > 0) return (float) $assignment->unit_price;
        $product = $assignment->product_id ? $this->masterData->product($assignment->business_id, $assignment->product_id, $assignment->location_id) : null;
        return (float) ($product->unit_price ?? 0);
    }

    private function reading(PonePumpAssignment $assignment, string $type, float $meter, float $testing, ?string $note): void
    {
        PoneMeterReading::query()->create([
            'shift_id' => $assignment->shift_id,
            'assignment_id' => $assignment->id,
            'business_id' => $assignment->business_id,
            'location_id' => $assignment->location_id,
            'operator_profile_id' => $assignment->operator_profile_id,
            'pump_id' => $assignment->pump_id,
            'reading_type' => $type,
            'meter_value' => $meter,
            'testing_quantity' => $testing,
            'source' => 'manual',
            'recorded_at' => now(),
            'recorded_by' => $this->context->userId(),
            'note' => $note,
        ]);
    }
}
