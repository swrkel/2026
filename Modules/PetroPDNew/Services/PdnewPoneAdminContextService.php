<?php

namespace Modules\PetroPDNew\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PoneShift;

/**
 * Executes a Pumper Dashboard-New operation from an authenticated Petro PD-New
 * management request without creating a second operator login or copying PONE
 * business logic. The original session values are restored in all cases.
 */
class PdnewPoneAdminContextService
{
    /**
     * @template T
     * @param Closure(PoneShift):T $callback
     * @return T
     */
    public function run(int $businessId, ?int $locationId, int $shiftId, Closure $callback, bool $allowSettled = false)
    {
        $shift = PoneShift::query()
            ->whereKey($shiftId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        if ($locationId && (int) $shift->location_id !== $locationId) {
            abort(403, 'The selected shift does not belong to the active business location.');
        }

        if (! $allowSettled && $this->hasFinalSettlementReference($businessId, $shiftId)) {
            throw ValidationException::withMessages([
                'shift' => 'This Pumper Dashboard-New shift already has a finalized Petro PD-New settlement and can no longer be edited.',
            ]);
        }

        $request = request();
        abort_unless($request->hasSession(), 419, 'A valid management session is required.');

        $session = $request->session();
        $keys = [
            'pone.business_id', 'pone.location_id', 'pone.operator_profile_id',
            'pone.pd_operator_id', 'pone.user_id', 'pone.shift_id',
            'pone.shift_number', 'pone.company_number',
        ];
        $previous = [];
        $missing = [];
        foreach ($keys as $key) {
            if ($session->has($key)) {
                $previous[$key] = $session->get($key);
            } else {
                $missing[] = $key;
            }
        }

        $session->put([
            'pone.business_id' => (int) $shift->business_id,
            'pone.location_id' => $shift->location_id ? (int) $shift->location_id : null,
            'pone.operator_profile_id' => (int) $shift->operator_profile_id,
            'pone.pd_operator_id' => (int) $shift->pd_operator_id,
            'pone.user_id' => (int) auth()->id() ?: null,
            'pone.shift_id' => (int) $shift->id,
            'pone.shift_number' => (string) $shift->shift_number,
        ]);

        try {
            return $callback($shift);
        } finally {
            foreach ($missing as $key) {
                $session->forget($key);
            }
            if ($previous !== []) {
                $session->put($previous);
            }
        }
    }

    private function hasFinalSettlementReference(int $businessId, int $shiftId): bool
    {
        if (! Schema::hasTable('pone_shift_settlement_references')) {
            return false;
        }

        return DB::table('pone_shift_settlement_references')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->exists();
    }
}
