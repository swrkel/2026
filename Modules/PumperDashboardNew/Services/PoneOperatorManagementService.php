<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Throwable;

class PoneOperatorManagementService
{
    public function sync(int $businessId): int
    {
        if ($businessId <= 0 || ! Schema::hasTable('pump_operators') || ! Schema::hasColumn('pump_operators', 'id')) {
            return 0;
        }

        $lockName = 'pone_operator_sync_' . $businessId;
        $lockAcquired = $this->acquireDatabaseLock($lockName);

        if (! $lockAcquired) {
            // Another request is already synchronizing this business. The caller can
            // safely continue using the records that are already stored.
            return 0;
        }

        try {
            return $this->performSync($businessId);
        } finally {
            $this->releaseDatabaseLock($lockName);
        }
    }

    private function performSync(int $businessId): int
    {
        $profileColumns = collect(Schema::getColumnListing('pone_pd_operators'))->flip();
        $hasCanonicalOperatorId = $profileColumns->has('pd_operator_id');
        $hasLegacyOperatorId = $profileColumns->has('petro_pd_operator_id');

        if (! $hasCanonicalOperatorId && ! $hasLegacyOperatorId) {
            return 0;
        }

        $this->repairCompatibilityIds($businessId, $hasCanonicalOperatorId, $hasLegacyOperatorId);

        $operatorQuery = DB::table('pump_operators');
        if (Schema::hasColumn('pump_operators', 'business_id')) {
            $operatorQuery->where('business_id', $businessId);
        }

        $operators = $operatorQuery->orderBy('id')->get();
        if ($operators->isEmpty()) {
            return 0;
        }

        $usersByOperator = $this->usersByOperator(
            $businessId,
            $operators->pluck('id')->map(fn ($id): int => (int) $id)->all()
        );

        $existingProfiles = PonePdOperator::query()
            ->where('business_id', $businessId)
            ->get()
            ->keyBy(function (PonePdOperator $profile) use ($hasCanonicalOperatorId, $hasLegacyOperatorId): int {
                $canonicalId = $hasCanonicalOperatorId ? (int) ($profile->pd_operator_id ?? 0) : 0;
                $legacyId = $hasLegacyOperatorId ? (int) ($profile->petro_pd_operator_id ?? 0) : 0;
                $settings = (array) ($profile->settings ?? []);

                return $canonicalId > 0
                    ? $canonicalId
                    : ($legacyId > 0 ? $legacyId : (int) ($settings['source_operator_id'] ?? 0));
            })
            ->filter(fn (PonePdOperator $profile, int $operatorId): bool => $operatorId > 0);

        $count = 0;

        foreach ($operators as $operator) {
            $operatorId = (int) $operator->id;
            if ($operatorId <= 0) {
                continue;
            }

            $profile = $existingProfiles->get($operatorId)
                ?: $this->findCompatibleProfile(
                    $businessId,
                    $operatorId,
                    $hasCanonicalOperatorId,
                    $hasLegacyOperatorId
                )
                ?: new PonePdOperator();

            $user = $usersByOperator->get($operatorId);
            if (! $user && Schema::hasColumn('pump_operators', 'user_id') && ! empty($operator->user_id)) {
                $user = $this->userById($businessId, (int) $operator->user_id);
            }
            if (! $user && ! empty($profile->user_id)) {
                $user = $this->userById($businessId, (int) $profile->user_id);
            }

            $sourceActive = $this->operatorIsActive($operator);
            $displayName = $this->operatorName($operator, $user, $operatorId);
            $settings = (array) ($profile->settings ?? []);
            $settings['source'] = 'pump_operators';
            $settings['source_operator_id'] = $operatorId;
            $settings['source_active'] = $sourceActive;
            $settings['operator_master'] = $this->operatorSnapshot($operator);
            $settings['user_master'] = $user ? $this->userSnapshot($user) : null;

            if ($user) {
                $legacyPasscode = trim((string) ($user->pump_operator_passcode ?? ''));
                if ($legacyPasscode !== '' && empty($settings['passcode_hash'])) {
                    $settings['passcode_hash'] = Hash::make($legacyPasscode);
                }
            }

            $profile->business_id = $businessId;

            if ($hasCanonicalOperatorId) {
                $profile->pd_operator_id = $operatorId;
            }
            if ($hasLegacyOperatorId) {
                // Required for tenants created from the first Pumper Dashboard-New
                // schema. Its old unique key is (business_id, petro_pd_operator_id).
                $profile->petro_pd_operator_id = $operatorId;
            }

            $profile->location_id = $this->operatorLocationId($operator) ?: ($profile->location_id ?: null);
            $profile->user_id = $user ? (int) $user->id : ($profile->user_id ?: null);

            if ($profileColumns->has('display_name')) {
                $profile->display_name = $displayName;
            }
            if ($profileColumns->has('operator_code')) {
                $profile->operator_code = trim((string) ($operator->operator_code ?? '')) ?: $displayName;
            }

            $loginEnabled = $profile->exists
                ? (bool) ($profile->login_enabled ?? $profile->can_login ?? false)
                : (bool) $user;

            if ($profileColumns->has('login_enabled')) {
                $profile->login_enabled = $loginEnabled;
            }
            if ($profileColumns->has('can_login')) {
                $profile->can_login = $loginEnabled;
            }
            if ($profileColumns->has('status')) {
                $profile->status = $sourceActive
                    ? ($profile->exists ? ((string) $profile->status ?: 'active') : 'active')
                    : 'inactive';
            }
            if ($profileColumns->has('enabled')) {
                $profile->enabled = $sourceActive;
            }
            if ($profileColumns->has('settings')) {
                $profile->settings = $settings;
            }
            if ($profileColumns->has('last_synced_at')) {
                $profile->last_synced_at = now();
            }

            try {
                $profile->save();
            } catch (QueryException $exception) {
                if (! $this->isDuplicateKeyException($exception)) {
                    throw $exception;
                }

                // A pre-existing hybrid row may still be addressed by the old
                // operator-id column or by the linked user. Reuse it rather than
                // creating a second profile.
                $profile = $this->findCompatibleProfile(
                    $businessId,
                    $operatorId,
                    $hasCanonicalOperatorId,
                    $hasLegacyOperatorId,
                    $user ? (int) $user->id : null
                );

                if (! $profile) {
                    throw $exception;
                }

                if ($hasCanonicalOperatorId) {
                    $profile->pd_operator_id = $operatorId;
                }
                if ($hasLegacyOperatorId) {
                    $profile->petro_pd_operator_id = $operatorId;
                }
                $profile->location_id = $this->operatorLocationId($operator) ?: ($profile->location_id ?: null);
                $profile->user_id = $user ? (int) $user->id : ($profile->user_id ?: null);
                if ($profileColumns->has('display_name')) {
                    $profile->display_name = $displayName;
                }
                if ($profileColumns->has('operator_code')) {
                    $profile->operator_code = trim((string) ($operator->operator_code ?? '')) ?: $displayName;
                }
                if ($profileColumns->has('login_enabled')) {
                    $profile->login_enabled = $loginEnabled;
                }
                if ($profileColumns->has('can_login')) {
                    $profile->can_login = $loginEnabled;
                }
                if ($profileColumns->has('status')) {
                    $profile->status = $sourceActive ? 'active' : 'inactive';
                }
                if ($profileColumns->has('enabled')) {
                    $profile->enabled = $sourceActive;
                }
                if ($profileColumns->has('settings')) {
                    $profile->settings = $settings;
                }
                if ($profileColumns->has('last_synced_at')) {
                    $profile->last_synced_at = now();
                }
                $profile->save();
            }

            $existingProfiles->put($operatorId, $profile);
            $count++;
        }

        return $count;
    }

    public function listing(int $businessId): Collection
    {
        $this->sync($businessId);

        return PonePdOperator::query()
            ->where('business_id', $businessId)
            ->orderBy('display_name')
            ->get();
    }

    public function updateAccess(int $businessId, int $profileId, array $data): PonePdOperator
    {
        $profile = PonePdOperator::query()
            ->whereKey($profileId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $attributes = [
            'location_id' => ! empty($data['location_id']) ? (int) $data['location_id'] : null,
        ];

        $columns = collect(Schema::getColumnListing('pone_pd_operators'))->flip();
        $loginEnabled = filter_var($data['login_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $active = ($data['status'] ?? 'active') !== 'inactive';

        if ($columns->has('login_enabled')) {
            $attributes['login_enabled'] = $loginEnabled;
        }
        if ($columns->has('can_login')) {
            $attributes['can_login'] = $loginEnabled;
        }
        if ($columns->has('status')) {
            $attributes['status'] = $active ? 'active' : 'inactive';
        }
        if ($columns->has('enabled')) {
            $attributes['enabled'] = $active;
        }

        $profile->update($attributes);

        return $profile->fresh();
    }

    private function repairCompatibilityIds(int $businessId, bool $hasCanonicalOperatorId, bool $hasLegacyOperatorId): void
    {
        if (! $hasCanonicalOperatorId || ! $hasLegacyOperatorId) {
            return;
        }

        DB::update(
            'UPDATE `pone_pd_operators`
             SET `pd_operator_id` = `petro_pd_operator_id`
             WHERE `business_id` = ?
               AND (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)
               AND `petro_pd_operator_id` > 0',
            [$businessId]
        );

        DB::update(
            'UPDATE `pone_pd_operators`
             SET `petro_pd_operator_id` = `pd_operator_id`
             WHERE `business_id` = ?
               AND (`petro_pd_operator_id` IS NULL OR `petro_pd_operator_id` = 0)
               AND `pd_operator_id` > 0',
            [$businessId]
        );
    }

    private function findCompatibleProfile(
        int $businessId,
        int $operatorId,
        bool $hasCanonicalOperatorId,
        bool $hasLegacyOperatorId,
        ?int $userId = null
    ): ?PonePdOperator {
        return PonePdOperator::query()
            ->where('business_id', $businessId)
            ->where(function ($query) use ($operatorId, $hasCanonicalOperatorId, $hasLegacyOperatorId, $userId): void {
                $hasCondition = false;

                if ($hasCanonicalOperatorId) {
                    $query->where('pd_operator_id', $operatorId);
                    $hasCondition = true;
                }
                if ($hasLegacyOperatorId) {
                    $hasCondition
                        ? $query->orWhere('petro_pd_operator_id', $operatorId)
                        : $query->where('petro_pd_operator_id', $operatorId);
                    $hasCondition = true;
                }
                if ($userId) {
                    $hasCondition
                        ? $query->orWhere('user_id', $userId)
                        : $query->where('user_id', $userId);
                }
            })
            ->orderBy('id')
            ->first();
    }

    /** @param array<int> $operatorIds */
    private function usersByOperator(int $businessId, array $operatorIds): Collection
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'id') || ! Schema::hasColumn('users', 'pump_operator_id')) {
            return collect();
        }

        $query = DB::table('users')->whereIn('pump_operator_id', $operatorIds);
        if (Schema::hasColumn('users', 'business_id')) {
            $query->where('business_id', $businessId);
        }
        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->get()->keyBy(fn ($user): int => (int) $user->pump_operator_id);
    }

    private function userById(int $businessId, int $userId): ?object
    {
        if ($userId <= 0 || ! Schema::hasTable('users') || ! Schema::hasColumn('users', 'id')) {
            return null;
        }

        $query = DB::table('users')->where('id', $userId);
        if (Schema::hasColumn('users', 'business_id')) {
            $query->where('business_id', $businessId);
        }
        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
    }

    private function operatorLocationId(object $operator): ?int
    {
        foreach (['location_id', 'business_location_id'] as $column) {
            $value = (int) ($operator->{$column} ?? 0);
            if ($value > 0) {
                return $value;
            }
        }

        return null;
    }

    private function operatorIsActive(object $operator): bool
    {
        if (property_exists($operator, 'active')) {
            return (int) $operator->active === 1;
        }

        if (property_exists($operator, 'status')) {
            return in_array(strtolower(trim((string) $operator->status)), ['1', 'active', 'enabled'], true);
        }

        return true;
    }

    private function operatorName(object $operator, ?object $user, int $operatorId): string
    {
        foreach (['name', 'operator_name', 'display_name'] as $column) {
            $value = trim((string) ($operator->{$column} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        if ($user) {
            $name = trim(implode(' ', array_filter([
                $user->surname ?? null,
                $user->first_name ?? null,
                $user->last_name ?? null,
            ])));
            if ($name !== '') {
                return $name;
            }

            $username = trim((string) ($user->username ?? ''));
            if ($username !== '') {
                return $username;
            }
        }

        return 'Operator #' . $operatorId;
    }

    private function operatorSnapshot(object $operator): array
    {
        return collect((array) $operator)
            ->except(['password', 'passcode', 'passcode_hash'])
            ->all();
    }

    private function userSnapshot(object $user): array
    {
        return collect((array) $user)
            ->only(['id', 'surname', 'first_name', 'last_name', 'username', 'email', 'contact_number'])
            ->all();
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000'
            || str_contains(strtolower($exception->getMessage()), 'duplicate entry');
    }

    private function acquireDatabaseLock(string $lockName): bool
    {
        try {
            $result = DB::selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockName]);

            return (int) ($result->acquired ?? 0) === 1;
        } catch (Throwable) {
            // Non-MySQL development connections may not provide advisory locks.
            return true;
        }
    }

    private function releaseDatabaseLock(string $lockName): void
    {
        try {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        } catch (Throwable) {
            // No action is required when the connection does not support locks.
        }
    }
}
