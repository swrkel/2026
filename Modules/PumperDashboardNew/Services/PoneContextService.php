<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Entities\PoneShift;

class PoneContextService
{
    public function __construct(private ?Request $request = null) {}

    private function request(): Request
    {
        return $this->request ?? request();
    }

    public function businessId(): int { return (int) $this->request()->session()->get('pone.business_id'); }
    public function locationId(): ?int { $v = (int) $this->request()->session()->get('pone.location_id'); return $v > 0 ? $v : null; }
    public function operatorProfileId(): int { return (int) $this->request()->session()->get('pone.operator_profile_id'); }
    public function pdOperatorId(): int { return (int) $this->request()->session()->get('pone.pd_operator_id'); }
    public function userId(): int { return (int) $this->request()->session()->get('pone.user_id', auth()->id()); }
    public function shiftId(): ?int { $v = (int) $this->request()->session()->get('pone.shift_id'); return $v > 0 ? $v : null; }
    public function companyNumber(): ?string { return $this->request()->session()->get('pone.company_number'); }

    public function profile(): PonePdOperator
    {
        return PonePdOperator::query()
            ->whereKey($this->operatorProfileId())
            ->where('business_id', $this->businessId())
            ->firstOrFail();
    }

    public function shift(bool $allowClosed = false): PoneShift
    {
        $query = PoneShift::query()
            ->whereKey($this->shiftId())
            ->where('business_id', $this->businessId())
            ->where('operator_profile_id', $this->operatorProfileId());

        if (! $allowClosed) $query->whereIn('status', ['open', 'closing']);

        return $query->firstOrFail();
    }

    public function setShift(?PoneShift $shift): void
    {
        $locationId = $shift?->location_id;
        if (! $locationId && $this->operatorProfileId() > 0) {
            $locationId = PonePdOperator::query()
                ->whereKey($this->operatorProfileId())
                ->where('business_id', $this->businessId())
                ->value('location_id');
        }
        $locationId = $locationId ? (int) $locationId : null;

        $this->request()->session()->put([
            'pone.shift_id' => $shift?->id,
            'pone.shift_number' => $shift?->shift_number,
            'pone.location_id' => $locationId,
        ]);

        $sessionKey = $this->request()->session()->get('pone.session_key');
        if ($sessionKey && Schema::hasTable('pone_operator_sessions')) {
            $updates = [];
            foreach ([
                'shift_id' => $shift?->id,
                'shift_number' => $shift?->shift_number,
                'location_id' => $locationId,
                'last_seen_at' => now(),
                'updated_at' => now(),
            ] as $column => $value) {
                if (Schema::hasColumn('pone_operator_sessions', $column)) $updates[$column] = $value;
            }
            if ($updates !== []) {
                $query = DB::table('pone_operator_sessions')->where('session_key', $sessionKey);
                if (Schema::hasColumn('pone_operator_sessions', 'business_id')) $query->where('business_id', $this->businessId());
                $query->update($updates);
            }
        }
    }
}
