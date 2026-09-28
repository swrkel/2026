<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Str;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Entities\PoneShift;

/**
 * Resolves Pumper Dashboard-New-owned shifts only.
 *
 * Legacy Petro PD assignments are deliberately not imported.  Administrators
 * create shifts and pump assignments inside Pumper Dashboard-New, after which
 * Petro PD-New consumes the closed source snapshot.
 */
class PoneShiftImportService
{
    public function __construct(
        private PoneNumberSequenceService $numbers,
        private PoneSettingsService $settings,
        private PoneAuditService $audit
    ) {}

    public function importForOperator(PonePdOperator $profile): ?PoneShift
    {
        $shift = PoneShift::query()
            ->where('business_id', $profile->business_id)
            ->where('operator_profile_id', $profile->id)
            ->whereIn('status', ['open', 'closing'])
            ->orderBy('opened_at')
            ->first();

        return $shift ?: $this->createLocalShiftWhenAllowed($profile);
    }

    private function createLocalShiftWhenAllowed(PonePdOperator $profile): ?PoneShift
    {
        $config = $this->settings->get($profile->business_id, $profile->location_id);
        if (! ($config['allow_operator_open_shift'] ?? false)) {
            return null;
        }

        $shift = PoneShift::query()->create([
            'uuid' => Str::uuid()->toString(),
            'business_id' => $profile->business_id,
            'location_id' => $profile->location_id,
            'operator_profile_id' => $profile->id,
            'pd_operator_id' => $profile->pd_operator_id,
            'user_id' => $profile->user_id,
            'shift_number' => $this->numbers->next(
                $profile->business_id,
                $profile->location_id,
                'shift'
            ),
            'status' => 'open',
            'opened_at' => now(),
            'integration_status' => 'synced',
            'integration_error' => null,
            'created_by' => $profile->user_id,
        ]);

        $this->audit->log(
            'shift.created_locally',
            'pone_shift',
            $shift->id,
            null,
            $shift,
            $shift->business_id,
            $shift->location_id,
            $profile->id,
            $profile->user_id
        );

        return $shift;
    }
}
