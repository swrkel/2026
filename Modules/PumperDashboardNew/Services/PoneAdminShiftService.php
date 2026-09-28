<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneMeterReading;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Services\Integration\PonePetroPdNewPublisher;

class PoneAdminShiftService
{
    public function __construct(
        private PoneNumberSequenceService $numbers,
        private PoneSharedMasterDataService $masterData,
        private PonePetroPdNewPublisher $bridge,
        private PoneAssignmentEventService $events,
        private PoneOperatorLedgerService $ledger,
        private PoneAuditService $audit
    ) {}

    public function create(int $businessId, array $data, int $userId): PoneShift
    {
        return DB::transaction(function () use ($businessId, $data, $userId): PoneShift {
            $profile = PonePdOperator::query()->whereKey((int) $data['operator_profile_id'])
                ->where('business_id', $businessId)->where('status', 'active')->firstOrFail();
            $locationId = ! empty($data['location_id']) ? (int) $data['location_id'] : $profile->location_id;
            $existing = PoneShift::query()->where('business_id', $businessId)
                ->where('operator_profile_id', $profile->id)->whereIn('status', ['open', 'closing'])->exists();
            if ($existing) {
                throw ValidationException::withMessages(['operator_profile_id' => __('pumperdashboardnew::lang.operator_already_has_open_shift')]);
            }

            $shift = PoneShift::query()->create([
                'uuid' => Str::uuid()->toString(),
                'business_id' => $businessId,
                'location_id' => $locationId,
                'operator_profile_id' => $profile->id,
                'pd_operator_id' => $profile->pd_operator_id,
                'user_id' => $profile->user_id,
                'shift_number' => trim((string) ($data['shift_number'] ?? '')) ?: $this->numbers->next($businessId, $locationId, 'shift'),
                'status' => 'open',
                'opened_at' => $data['opened_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'integration_status' => 'pending',
                'created_by' => $userId,
            ]);

            foreach (array_values(array_unique(array_map('intval', $data['pump_ids'] ?? []))) as $pumpId) {
                $pump = $this->masterData->pump($businessId, $pumpId);
                if (! $pump || ($locationId && (int) ($pump->location_id ?? 0) !== $locationId)) continue;
                $last = PonePumpAssignment::query()->where('business_id', $businessId)->where('pump_id', $pumpId)
                    ->where('status', 'closed')->latest('closed_at')->first();
                $opening = (float) ($last?->closing_meter ?? $pump->last_meter_reading ?? $pump->pod_last_meter ?? $pump->starting_meter ?? 0);
                $product = ! empty($pump->product_id) ? $this->masterData->product($businessId, (int) $pump->product_id, $locationId) : null;
                $assignment = $shift->assignments()->create([
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'operator_profile_id' => $profile->id,
                    'pd_operator_id' => $profile->pd_operator_id,
                    'pump_id' => $pumpId,
                    'product_id' => $pump->product_id ?? null,
                    'opening_meter' => $opening,
                    'current_meter' => $opening,
                    'unit_price' => (float) ($product->unit_price ?? 0),
                    'status' => 'assigned',
                    'assigned_at' => $shift->opened_at,
                    'integration_status' => 'pending',
                ]);
                PoneMeterReading::query()->create([
                    'shift_id' => $shift->id,
                    'assignment_id' => $assignment->id,
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'operator_profile_id' => $profile->id,
                    'pump_id' => $pumpId,
                    'reading_type' => 'opening',
                    'meter_value' => $opening,
                    'source' => 'system',
                    'recorded_at' => $shift->opened_at,
                    'recorded_by' => $userId,
                ]);
                $this->events->record($assignment, 'assigned', $opening, 0, null, [], $userId);
                $this->bridge->syncAssignment($assignment);
            }

            if ($shift->assignments()->count() === 0) {
                throw ValidationException::withMessages(['pump_ids' => __('pumperdashboardnew::lang.select_at_least_one_pump')]);
            }
            $this->bridge->syncShift($shift);
            $this->ledger->synchronizeShift($shift->fresh());
            $this->audit->log('shift.created_by_admin', 'pone_shift', $shift->id, null, $shift->fresh('assignments'), $businessId, $locationId, $profile->id, $userId);
            return $shift->fresh('assignments');
        }, 3);
    }
}
