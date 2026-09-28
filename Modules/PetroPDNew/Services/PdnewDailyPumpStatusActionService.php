<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Services\PoneAdminShiftService;
use Modules\PumperDashboardNew\Services\PoneAssignmentManagementService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class PdnewDailyPumpStatusActionService
{
    public function __construct(
        private PoneAdminShiftService $shiftService,
        private PoneAssignmentManagementService $assignmentService,
        private PoneSharedMasterDataService $masterData
    ) {}

    /** @return array<string, mixed> */
    public function assignForm(int $businessId, ?int $locationId): array
    {
        $busyOperatorIds = PoneShift::query()
            ->where('business_id', $businessId)
            ->whereIn('status', ['open', 'closing'])
            ->pluck('operator_profile_id');

        $operators = PonePdOperator::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->where('login_enabled', true)
            ->when($locationId, function ($query, int $location): void {
                $query->where(function ($scope) use ($location): void {
                    $scope->where('location_id', $location)->orWhereNull('location_id');
                });
            })
            ->when($busyOperatorIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $busyOperatorIds))
            ->orderBy('display_name')
            ->get();

        $busyPumpIds = PonePumpAssignment::query()
            ->where('business_id', $businessId)
            ->whereIn('status', ['assigned', 'open'])
            ->pluck('pump_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $pumps = $this->masterData->pumps($businessId, $locationId)
            ->reject(fn (object $pump): bool => in_array((int) $pump->id, $busyPumpIds, true))
            ->values();

        return [
            'operators' => $operators,
            'pumps' => $pumps,
            'locations' => $this->masterData->locations($businessId),
            'active_location_id' => $locationId,
        ];
    }

    public function createShift(int $businessId, ?int $activeLocationId, array $data, int $userId): PoneShift
    {
        if ($activeLocationId) {
            $data['location_id'] = $activeLocationId;
        }

        $pumpIds = collect($data['pump_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $busyPumpIds = PonePumpAssignment::query()
            ->where('business_id', $businessId)
            ->whereIn('pump_id', $pumpIds->all())
            ->whereIn('status', ['assigned', 'open'])
            ->pluck('pump_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($busyPumpIds !== []) {
            throw ValidationException::withMessages([
                'pump_ids' => 'One or more selected pumps have already been assigned to an open shift. Refresh the page and select only available pumps.',
            ]);
        }

        return $this->shiftService->create($businessId, $data, $userId);
    }

    public function assignment(int $businessId, ?int $locationId, int $assignmentId): PonePumpAssignment
    {
        return PonePumpAssignment::query()
            ->whereKey($assignmentId)
            ->where('business_id', $businessId)
            ->when($locationId, fn ($query, int $location) => $query->where('location_id', $location))
            ->with('shift')
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    public function assignmentForm(PonePumpAssignment $assignment): array
    {
        $pump = $this->masterData->pump(
            (int) $assignment->business_id,
            (int) $assignment->pump_id,
            $assignment->location_id ? (int) $assignment->location_id : null
        );

        return [
            'assignment' => $assignment,
            'pump' => $pump,
        ];
    }

    public function updateAssignment(PonePumpAssignment $assignment, array $data, int $userId): PonePumpAssignment
    {
        if ($assignment->status !== 'assigned' || $assignment->accepted_at || $assignment->confirmed_at) {
            throw ValidationException::withMessages([
                'assignment' => 'The assignment cannot be edited after the pump operator has received it.',
            ]);
        }

        return $this->assignmentService->update($assignment, $data, $userId);
    }

    public function cancelAssignment(PonePumpAssignment $assignment, string $reason, int $userId): PonePumpAssignment
    {
        if ($assignment->status !== 'assigned' || $assignment->accepted_at || $assignment->confirmed_at) {
            throw ValidationException::withMessages([
                'assignment' => 'Only an unreceived pump assignment can be cancelled.',
            ]);
        }

        return $this->assignmentService->cancel($assignment, $reason, $userId);
    }
}
