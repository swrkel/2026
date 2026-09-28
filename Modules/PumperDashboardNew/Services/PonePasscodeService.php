<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\PumperDashboardNew\Entities\PonePdOperator;

class PonePasscodeService
{
    public function __construct(private PoneContextService $context, private PoneAuditService $audit) {}

    public function update(string $current, string $new): void
    {
        $profile = $this->context->profile();
        $user = $this->user();

        if (! $this->matches($user, $profile, $current)) {
            throw ValidationException::withMessages(['current_passcode' => __('pumperdashboardnew::lang.current_passcode_incorrect')]);
        }

        if ($user && Schema::hasColumn('users', 'pump_operator_passcode')) {
            $updates = ['pump_operator_passcode' => trim($new)];
            if (Schema::hasColumn('users', 'updated_at')) $updates['updated_at'] = now();
            DB::table('users')->where('id', $user->id)->update($updates);
        }

        $settings = (array) ($profile->settings ?? []);
        $settings['passcode_hash'] = Hash::make(trim($new));
        $profile->forceFill(['settings' => $settings])->save();

        $this->audit->log('operator.passcode_updated', 'pone_pd_operator', (int) $profile->id, null, ['passcode_updated' => true]);
    }

    private function user(): ?object
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'id')) return null;
        $query = DB::table('users')->where('id', $this->context->userId());
        if (Schema::hasColumn('users', 'business_id')) $query->where('business_id', $this->context->businessId());
        if (Schema::hasColumn('users', 'deleted_at')) $query->whereNull('deleted_at');
        return $query->first();
    }

    private function matches(?object $user, PonePdOperator $profile, string $value): bool
    {
        if ($user && trim((string) ($user->pump_operator_passcode ?? '')) === trim($value)) return true;
        if ($user && ! empty($user->password) && Hash::check($value, (string) $user->password)) return true;

        $hash = (string) data_get((array) ($profile->settings ?? []), 'passcode_hash', '');
        return $hash !== '' && Hash::check($value, $hash);
    }
}
