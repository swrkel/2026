<?php

namespace App\Services;

use App\PumperLoginAttempt;
use App\PumperLoginAttemptHistory;
use App\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PumperLoginAttemptAuditService
{
    public function isReady(): bool
    {
        return Schema::hasTable('pumper_login_attempt_histories');
    }

    public function recordBlocked(
        PumperLoginAttempt $attempt,
        ?string $enteredPasscode,
        string $source = 'PumpOperatorLogin'
    ): void {
        $this->safely(function () use ($attempt, $enteredPasscode, $source): void {
            $existingOpenIncident = PumperLoginAttemptHistory::query()
                ->where('pumper_login_attempt_id', $attempt->id)
                ->whereNull('unblocked_at')
                ->latest('id')
                ->first();

            if ($existingOpenIncident) {
                return;
            }

            $operator = $this->resolveOperator(
                (int) $attempt->business_id,
                $enteredPasscode
            );

            PumperLoginAttemptHistory::query()->create([
                'pumper_login_attempt_id' => $attempt->id,
                'business_id' => $attempt->business_id,
                'pump_operator_id' => $operator['pump_operator_id'],
                'operator_user_id' => $operator['operator_user_id'],
                'operator_name' => $operator['operator_name'],
                'company_number' => $attempt->company_number,
                'ip_address' => $attempt->ip_address,
                'passcode_mask' => $this->maskPasscode($enteredPasscode),
                'attempt_count' => (int) $attempt->attempt_count,
                'blocked_at' => now(),
                'source_module' => $source,
            ]);
        });
    }

    public function recordUnblocked(
        PumperLoginAttempt $attempt,
        ?User $actor,
        string $source,
        ?CarbonInterface $blockedAt = null,
        ?int $attemptCount = null
    ): void {
        $this->safely(function () use ($attempt, $actor, $source, $blockedAt, $attemptCount): void {
            $incident = PumperLoginAttemptHistory::query()
                ->where('pumper_login_attempt_id', $attempt->id)
                ->whereNull('unblocked_at')
                ->latest('id')
                ->first();

            if (! $incident) {
                $operator = $this->resolveOperator(
                    (int) $attempt->business_id,
                    (string) $attempt->last_entered_passcode
                );

                $incident = PumperLoginAttemptHistory::query()->create([
                    'pumper_login_attempt_id' => $attempt->id,
                    'business_id' => $attempt->business_id,
                    'pump_operator_id' => $operator['pump_operator_id'],
                    'operator_user_id' => $operator['operator_user_id'],
                    'operator_name' => $operator['operator_name'],
                    'company_number' => $attempt->company_number,
                    'ip_address' => $attempt->ip_address,
                    'passcode_mask' => $this->maskPasscode((string) $attempt->last_entered_passcode),
                    'attempt_count' => $attemptCount ?? (int) $attempt->attempt_count,
                    'blocked_at' => $blockedAt ?: now(),
                    'source_module' => $source,
                    'notes' => 'Blocked before detailed login history was available.',
                ]);
            }

            $incident->unblocked_at = now();
            $incident->unblocked_by_user_id = $actor ? $actor->id : null;
            $incident->unblocked_by_name = $this->userName($actor);
            $incident->source_module = $source;
            $incident->save();
        });
    }

    private function resolveOperator(int $businessId, ?string $passcode): array
    {
        $empty = [
            'pump_operator_id' => null,
            'operator_user_id' => null,
            'operator_name' => null,
        ];

        if ($businessId <= 0 || trim((string) $passcode) === '' || ! Schema::hasTable('users')) {
            return $empty;
        }

        $user = User::query()
            ->where('business_id', $businessId)
            ->where('pump_operator_passcode', trim((string) $passcode))
            ->first();

        if (! $user) {
            return $empty;
        }

        $operatorName = null;
        if (! empty($user->pump_operator_id) && Schema::hasTable('pump_operators')) {
            $operatorName = DB::table('pump_operators')
                ->where('business_id', $businessId)
                ->where('id', $user->pump_operator_id)
                ->value('name');
        }

        return [
            'pump_operator_id' => $user->pump_operator_id ?: null,
            'operator_user_id' => $user->id,
            'operator_name' => $operatorName ?: $this->userName($user),
        ];
    }

    private function userName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $name = trim(implode(' ', array_filter([
            $user->surname ?? null,
            $user->first_name ?? null,
            $user->last_name ?? null,
        ])));

        return $name !== '' ? $name : ($user->username ?: 'User #' . $user->id);
    }

    private function maskPasscode(?string $passcode): ?string
    {
        $passcode = trim((string) $passcode);
        if ($passcode === '') {
            return null;
        }

        $visible = substr($passcode, -2);

        return str_repeat('*', max(2, strlen($passcode) - strlen($visible))) . $visible;
    }

    private function safely(callable $callback): void
    {
        try {
            if (! $this->isReady()) {
                return;
            }

            $callback();
        } catch (\Throwable $exception) {
            Log::warning('Pumper login attempt history could not be recorded.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
