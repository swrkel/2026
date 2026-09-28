<?php

namespace Modules\PumperDashboardNew\Services;

use Modules\PumperDashboardNew\Entities\PoneAssignmentEvent;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;

class PoneAssignmentEventService
{
    public function record(
        PonePumpAssignment $assignment,
        string $eventType,
        ?float $meterValue = null,
        float $testingQuantity = 0,
        ?string $note = null,
        array $metadata = [],
        ?int $userId = null
    ): PoneAssignmentEvent {
        return PoneAssignmentEvent::query()->create([
            'assignment_id' => $assignment->id,
            'shift_id' => $assignment->shift_id,
            'business_id' => $assignment->business_id,
            'operator_profile_id' => $assignment->operator_profile_id,
            'event_type' => $eventType,
            'meter_value' => $meterValue,
            'testing_quantity' => $testingQuantity,
            'note' => $note,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
            'created_by' => $userId ?: (int) auth()->id() ?: null,
        ]);
    }
}
