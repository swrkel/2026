<?php

namespace Modules\PumperDashboardNew\Services;

use Modules\PumperDashboardNew\Entities\PoneLoginAttempt;

class PoneLoginAttemptService
{
    public function isBlocked(?string $companyNumber, string $ipAddress): bool
    {
        $attempt = $this->find($companyNumber, $ipAddress);
        if (! $attempt || $attempt->status !== 'blocked') return false;

        if ($attempt->blocked_until && $attempt->blocked_until->isPast()) {
            $attempt->update(['status' => 'active', 'attempt_count' => 0, 'blocked_until' => null]);
            return false;
        }

        return true;
    }

    public function fail(?int $businessId, ?string $companyNumber, string $ipAddress, string $passcode): PoneLoginAttempt
    {
        $maximum = (int) config('pumperdashboardnew.login.maximum_attempts', 5);
        $blockMinutes = (int) config('pumperdashboardnew.login.block_minutes', 30);
        $attempt = $this->find($companyNumber, $ipAddress);

        if (! $attempt) {
            $attempt = PoneLoginAttempt::query()->create([
                'business_id' => $businessId,
                'company_number' => $companyNumber,
                'ip_address' => $ipAddress,
                'attempt_count' => 0,
            ]);
        }

        $count = (int) $attempt->attempt_count + 1;
        $blocked = $count >= $maximum;
        $attempt->update([
            'business_id' => $businessId,
            'passcode_fingerprint' => hash_hmac('sha256', $passcode, (string) config('app.key', 'pone')),
            'attempt_count' => $count,
            'status' => $blocked ? 'blocked' : 'active',
            'blocked_until' => $blocked ? now()->addMinutes($blockMinutes) : null,
            'last_attempt_at' => now(),
        ]);

        return $attempt->fresh();
    }

    public function reset(?int $businessId, ?string $companyNumber, string $ipAddress): void
    {
        $attempt = $this->find($companyNumber, $ipAddress);
        if ($attempt) {
            $attempt->update([
                'business_id' => $businessId,
                'attempt_count' => 0,
                'status' => 'active',
                'blocked_until' => null,
                'last_attempt_at' => now(),
            ]);
        }
    }

    public function unblock(int $attemptId): PoneLoginAttempt
    {
        $attempt = PoneLoginAttempt::query()->whereKey($attemptId)->firstOrFail();
        $attempt->update([
            'attempt_count' => 0,
            'status' => 'active',
            'blocked_until' => null,
            'last_attempt_at' => now(),
        ]);
        return $attempt->fresh();
    }

    public function unblockAll(?int $businessId = null): int
    {
        return PoneLoginAttempt::query()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->where('status', 'blocked')
            ->update(['attempt_count' => 0, 'status' => 'active', 'blocked_until' => null, 'updated_at' => now()]);
    }

    private function find(?string $companyNumber, string $ipAddress): ?PoneLoginAttempt
    {
        return PoneLoginAttempt::query()
            ->where('company_number', $companyNumber)
            ->where('ip_address', $ipAddress)
            ->first();
    }
}
