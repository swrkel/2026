<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\PumperDashboardNew\Entities\PoneOperatorSession;
use Modules\PumperDashboardNew\Entities\PonePdOperator;

class PoneOperatorSessionService
{
    public function start(Request $request, object $business, object $user, PonePdOperator $profile): PoneOperatorSession
    {
        Auth::guard('web')->loginUsingId((int) $user->id, false);
        $request->session()->regenerate();

        $sessionUuid = Str::uuid()->toString();
        $sessionKey = hash('sha256', $sessionUuid . '|' . Str::random(40));
        $now = now();

        $attributes = [
            'business_id' => (int) $business->id,
            'location_id' => $profile->location_id,
            'operator_profile_id' => $profile->id,
            'user_id' => (int) $user->id,
            'logged_in_at' => $now,
            'status' => 'active',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ];

        // Current schema columns.
        $this->putIfColumnExists($attributes, 'session_key', $sessionKey);
        $this->putIfColumnExists($attributes, 'pd_operator_id', $profile->pd_operator_id);
        $this->putIfColumnExists($attributes, 'last_seen_at', $now);
        $this->putIfColumnExists($attributes, 'shift_id', null);
        $this->putIfColumnExists($attributes, 'shift_number', null);

        // Foundation-schema aliases. These remain populated when an older tenant
        // table has not yet been archived, preventing NOT NULL insert failures.
        $this->putIfColumnExists($attributes, 'session_uuid', $sessionUuid);
        $this->putIfColumnExists($attributes, 'petro_pd_operator_id', $profile->pd_operator_id);
        $this->putIfColumnExists($attributes, 'last_activity_at', $now);
        $this->putIfColumnExists(
            $attributes,
            'laravel_session_hash',
            hash('sha256', (string) $request->session()->getId())
        );

        /** @var PoneOperatorSession $session */
        $session = PoneOperatorSession::query()->create($attributes);

        $request->session()->put([
            'pone.session_key' => $sessionKey,
            'pone.operator_profile_id' => $profile->id,
            'pone.pd_operator_id' => $profile->pd_operator_id,
            'pone.user_id' => (int) $user->id,
            'pone.business_id' => (int) $business->id,
            'pone.location_id' => $profile->location_id,
            'pone.company_number' => $business->company_number ?? $business->company_no ?? null,
            'pone.operator_name' => $profile->display_name,
            'pone.shift_id' => null,
            'pone.shift_number' => null,
            'user.id' => (int) $user->id,
            'user.business_id' => (int) $business->id,
            'user.is_pump_operator' => true,
            'business.id' => (int) $business->id,
            'business.name' => $business->name ?? null,
            'business.company_number' => $business->company_number ?? $business->company_no ?? null,
        ]);

        return $session;
    }

    public function end(Request $request): void
    {
        $sessionKey = $request->session()->get('pone.session_key');
        if ($sessionKey && Schema::hasTable('pone_operator_sessions')) {
            $updates = ['status' => 'logged_out', 'logged_out_at' => now()];
            if (Schema::hasColumn('pone_operator_sessions', 'last_seen_at')) {
                $updates['last_seen_at'] = now();
            }
            if (Schema::hasColumn('pone_operator_sessions', 'last_activity_at')) {
                $updates['last_activity_at'] = now();
            }

            $query = PoneOperatorSession::query()->where('status', 'active');
            if (Schema::hasColumn('pone_operator_sessions', 'session_key')) {
                $query->where('session_key', $sessionKey);
            } elseif (Schema::hasColumn('pone_operator_sessions', 'laravel_session_hash')) {
                $query->where('laravel_session_hash', hash('sha256', (string) $request->session()->getId()));
            }
            $query->update($updates);
        }

        Auth::guard('web')->logout();
        $request->session()->forget('pone');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function putIfColumnExists(array &$attributes, string $column, mixed $value): void
    {
        if (Schema::hasColumn('pone_operator_sessions', $column)) {
            $attributes[$column] = $value;
        }
    }
}
