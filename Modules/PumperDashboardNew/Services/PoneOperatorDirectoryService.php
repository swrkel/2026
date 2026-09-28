<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\PumperDashboardNew\Entities\PonePdOperator;

class PoneOperatorDirectoryService
{
    public function businessByCompanyNumber(string $companyNumber): ?object
    {
        $companyNumber = trim(rawurldecode($companyNumber));
        if ($companyNumber === '') {
            return null;
        }

        // Use the currently active database first. On a tenant host this is the
        // tenant database; on a central host it is the central/default database.
        $business = $this->findBusinessOnConnection(null, $companyNumber);
        if ($business) {
            return $business;
        }

        // Some installations expose the central database through a dedicated
        // `system` connection. Use it only when tenancy is not active, because
        // returning a central business ID inside an active tenant could cross
        // the business boundary used by operator/user queries.
        if (! $this->tenancyInitialized()) {
            $centralConnection = $this->centralConnectionName();
            if ($centralConnection !== null && $centralConnection !== config('database.default')) {
                return $this->findBusinessOnConnection($centralConnection, $companyNumber);
            }
        }

        return null;
    }

    private function findBusinessOnConnection(?string $connectionName, string $companyNumber): ?object
    {
        try {
            $connection = $connectionName !== null
                ? DB::connection($connectionName)
                : DB::connection();
            $schema = $connection->getSchemaBuilder();

            if (! $schema->hasTable('business')) {
                return null;
            }

            $identifierColumns = array_values(array_filter([
                'company_number',
                'company_no',
                'ref_no',
                'reference_no',
                'registration_number',
                'registration_no',
            ], static fn (string $column): bool => $schema->hasColumn('business', $column)));

            $query = $connection->table('business');
            $hasCondition = false;
            $upperCompanyNumber = function_exists('mb_strtoupper')
                ? mb_strtoupper(trim($companyNumber), 'UTF-8')
                : strtoupper(trim($companyNumber));

            if ($identifierColumns !== []) {
                $query->where(function (Builder $inner) use ($identifierColumns, $upperCompanyNumber): void {
                    foreach ($identifierColumns as $index => $column) {
                        $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                        $inner->{$method}(
                            'UPPER(TRIM(CAST(`' . $column . '` AS CHAR))) = ?',
                            [$upperCompanyNumber]
                        );
                    }
                });
                $hasCondition = true;
            }

            if ($schema->hasColumn('business', 'name')) {
                $hasCondition
                    ? $query->orWhereRaw('UPPER(TRIM(CAST(`name` AS CHAR))) = ?', [$upperCompanyNumber])
                    : $query->whereRaw('UPPER(TRIM(CAST(`name` AS CHAR))) = ?', [$upperCompanyNumber]);
                $hasCondition = true;
            }

            if (ctype_digit($companyNumber) && $schema->hasColumn('business', 'id')) {
                $hasCondition
                    ? $query->orWhere('id', (int) $companyNumber)
                    : $query->where('id', (int) $companyNumber);
                $hasCondition = true;
            }

            if ($hasCondition) {
                $business = $query->first();
                if ($business) {
                    return $business;
                }
            }

            // Company references transferred between servers sometimes contain
            // Unicode dashes, non-breaking spaces or formatting characters. A
            // normalized comparison prevents RA-9, RA–9 and " RA-9 " from being
            // treated as different businesses without selecting another record.
            if ($identifierColumns === []) {
                return null;
            }

            $select = array_values(array_unique(array_merge(
                $schema->hasColumn('business', 'id') ? ['id'] : [],
                $schema->hasColumn('business', 'name') ? ['name'] : [],
                $identifierColumns
            )));
            $needle = $this->normaliseBusinessCode($companyNumber);
            if ($needle === '') {
                return null;
            }

            foreach ($connection->table('business')->select($select)->get() as $candidate) {
                foreach ($identifierColumns as $column) {
                    if ($this->normaliseBusinessCode((string) ($candidate->{$column} ?? '')) === $needle) {
                        return $candidate;
                    }
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return null;
    }

    private function normaliseBusinessCode(string $value): string
    {
        $value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(
            ["\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}", "\u{2212}", "\u{00A0}"],
            ['-', '-', '-', '-', '-', '-', ' '],
            $value
        );
        $value = function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);

        return preg_replace('/[^\pL\pN]+/u', '', $value) ?: '';
    }

    private function tenancyInitialized(): bool
    {
        try {
            return function_exists('tenancy') && (bool) tenancy()->initialized;
        } catch (\Throwable) {
            return false;
        }
    }

    private function centralConnectionName(): ?string
    {
        if ((string) config('database.connections.system.database', '') !== '') {
            return 'system';
        }

        $connection = trim((string) config('tenancy.database.central_connection', ''));
        return $connection !== '' ? $connection : null;
    }

    public function findUserByPasscode(int $businessId, string $passcode): ?object
    {
        if (! $this->usableUsersTable()) return null;

        $values = $this->passcodeVariants($passcode);
        $candidateIds = $this->candidateUserIds($businessId);

        if (Schema::hasColumn('users', 'pump_operator_passcode')) {
            $query = $this->baseUserQuery($businessId);
            $this->restrictToOperatorUsers($query, $candidateIds, $businessId);
            $query->where(function (Builder $inner) use ($values): void {
                foreach ($values as $value) {
                    $inner->orWhereRaw('TRIM(CAST(`pump_operator_passcode` AS CHAR)) = ?', [$value]);
                }
            });

            $user = $query->orderByDesc('id')->first();
            if ($user) return $user;
        }

        $profiles = PonePdOperator::query()
            ->where('business_id', $businessId)
            ->where('login_enabled', true)
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->get()
            ->keyBy(fn (PonePdOperator $profile): int => (int) $profile->user_id);

        $query = $this->baseUserQuery($businessId);
        $this->restrictToOperatorUsers($query, $candidateIds ?: $profiles->keys()->map(fn ($id) => (int) $id)->all(), $businessId);

        foreach ($query->orderByDesc('id')->get() as $candidate) {
            $profile = $profiles->get((int) $candidate->id);
            if ($this->matchesPasscode($candidate, $profile, $passcode, $values)) {
                $this->backfillLegacyPasscodeColumns($candidate, $passcode);
                return DB::table('users')->where('id', $candidate->id)->first();
            }
        }

        return null;
    }

    public function operatorForUser(object $user, int $businessId): ?object
    {
        if (! Schema::hasTable('pump_operators')) return null;

        $operatorId = (int) ($user->pump_operator_id ?? 0);
        if ($operatorId <= 0 && Schema::hasTable('pone_pd_operators')) {
            $operatorId = (int) PonePdOperator::query()
                ->where('business_id', $businessId)
                ->where('user_id', (int) $user->id)
                ->value('pd_operator_id');
        }
        if ($operatorId <= 0 && Schema::hasColumn('pump_operators', 'user_id')) {
            $operatorQuery = DB::table('pump_operators')->where('user_id', (int) $user->id);
            if (Schema::hasColumn('pump_operators', 'business_id')) $operatorQuery->where('business_id', $businessId);
            $operatorId = (int) $operatorQuery->value('id');
        }
        if ($operatorId <= 0) return null;

        $query = DB::table('pump_operators')->where('id', $operatorId);
        if (Schema::hasColumn('pump_operators', 'business_id')) $query->where('business_id', $businessId);

        return $query
            ->when(Schema::hasColumn('pump_operators', 'active'), fn ($q) => $q->where('active', 1))
            ->when(Schema::hasColumn('pump_operators', 'status'), function ($q): void {
                $q->whereRaw("LOWER(TRIM(CAST(`status` AS CHAR))) IN ('1','active')");
            })
            ->first();
    }

    public function mapOperator(object $user, object $operator, int $businessId): PonePdOperator
    {
        $profile = PonePdOperator::query()->firstOrNew([
            'business_id' => $businessId,
            'pd_operator_id' => (int) $operator->id,
        ]);

        if ($profile->exists && (! $profile->login_enabled || $profile->status !== 'active')) {
            return $profile;
        }

        $settings = (array) ($profile->settings ?? []);
        $legacyPasscode = trim((string) ($user->pump_operator_passcode ?? ''));
        if ($legacyPasscode !== '' && empty($settings['passcode_hash'])) {
            $settings['passcode_hash'] = Hash::make($legacyPasscode);
        }

        $enabled = $profile->exists ? (bool) $profile->login_enabled : true;
        $displayName = trim((string) ($operator->name ?? '')) ?: $this->userName($user);
        $attributes = [
            'location_id' => ! empty($operator->location_id) ? (int) $operator->location_id : $profile->location_id,
            'user_id' => (int) $user->id,
            'display_name' => $displayName,
            'login_enabled' => $enabled,
            'status' => $profile->exists ? $profile->status : 'active',
            'settings' => $settings,
        ];

        /*
         * MA-002: "Duplicate entry '3-0' for key 'pone_op_business_pd_unique'"
         * (laravel-2026-08-04.log, 14:21:09, Connection: tenant).
         *
         * The unique key is (business_id, petro_pd_operator_id). The failing
         * INSERT contained business_id, pd_operator_id, location_id, user_id,
         * display_name, login_enabled, status and settings - but NOT
         * petro_pd_operator_id. That column is NOT NULL with no default, so it
         * was written as 0. The first operator for a business stored (3, 0)
         * successfully; the second collided with it.
         *
         * The population below was already conditional on hasColumn(), so the
         * check must have returned false against a table that demonstrably has
         * the column. Schema:: resolves against the DEFAULT connection, which
         * is fragile in a multi-database tenant request. It is now bound
         * explicitly to the connection this model actually writes to, so the
         * check and the INSERT can never disagree.
         *
         * $schema is resolved once and reused for every alias below.
         */
        $schema = Schema::connection($profile->getConnectionName());

        // Populate foundation-schema aliases when they are still present. This
        // prevents NOT NULL failures while the idempotent schema repair is being
        // applied to an older tenant database.
        if ($schema->hasColumn('pone_pd_operators', 'petro_pd_operator_id')) {
            $attributes['petro_pd_operator_id'] = (int) $operator->id;
        }
        if ($schema->hasColumn('pone_pd_operators', 'operator_code')) {
            $attributes['operator_code'] = (string) ($operator->operator_code ?? $operator->code ?? $operator->id);
        }
        if ($schema->hasColumn('pone_pd_operators', 'enabled')) {
            $attributes['enabled'] = $enabled ? 1 : 0;
        }
        if ($schema->hasColumn('pone_pd_operators', 'can_login')) {
            $attributes['can_login'] = $enabled ? 1 : 0;
        }
        if ($schema->hasColumn('pone_pd_operators', 'force_location_id')) {
            $attributes['force_location_id'] = $attributes['location_id'];
        }

        $profile->fill($attributes);
        $profile->save();

        return $profile;
    }

    public function touchLogin(PonePdOperator $profile, string $ip): void
    {
        $updates = [];
        if (Schema::hasColumn('pone_pd_operators', 'last_login_at')) $updates['last_login_at'] = now();
        if (Schema::hasColumn('pone_pd_operators', 'last_login_ip')) $updates['last_login_ip'] = $ip;
        if (Schema::hasColumn('pone_pd_operators', 'last_synced_at')) $updates['last_synced_at'] = now();
        if ($updates !== []) $profile->update($updates);
    }

    private function usableUsersTable(): bool
    {
        return Schema::hasTable('users') && Schema::hasColumn('users', 'id');
    }

    private function baseUserQuery(int $businessId): Builder
    {
        $query = DB::table('users');
        if (Schema::hasColumn('users', 'business_id')) $query->where('business_id', $businessId);

        return $query
            ->when(Schema::hasColumn('users', 'status'), function ($q): void {
                $q->whereRaw("LOWER(TRIM(CAST(`status` AS CHAR))) IN ('1','active')");
            })
            ->when(Schema::hasColumn('users', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'));
    }

    private function restrictToOperatorUsers(Builder $query, array $candidateIds, ?int $businessId = null): void
    {
        $candidateIds = array_values(array_unique(array_filter(array_map('intval', $candidateIds))));

        if (Schema::hasColumn('users', 'pump_operator_id')) {
            $operatorIds = [];
            if ($businessId && Schema::hasTable('pump_operators') && Schema::hasColumn('pump_operators', 'id')) {
                $operatorQuery = DB::table('pump_operators');
                if (Schema::hasColumn('pump_operators', 'business_id')) $operatorQuery->where('business_id', $businessId);
                $operatorIds = $operatorQuery->pluck('id')->map(fn ($id) => (int) $id)->all();
            }

            $query->where(function (Builder $inner) use ($candidateIds, $operatorIds): void {
                $hasCondition = false;
                if ($operatorIds !== []) {
                    $inner->whereIn('pump_operator_id', $operatorIds);
                    $hasCondition = true;
                }
                if ($candidateIds !== []) {
                    $hasCondition
                        ? $inner->orWhereIn('id', $candidateIds)
                        : $inner->whereIn('id', $candidateIds);
                    $hasCondition = true;
                }
                if (! $hasCondition) $inner->whereRaw('1 = 0');
            });
            return;
        }

        $candidateIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('id', $candidateIds);
    }

    private function candidateUserIds(int $businessId): array
    {
        $ids = [];

        if (Schema::hasTable('pone_pd_operators')) {
            $ids = array_merge($ids, PonePdOperator::query()
                ->where('business_id', $businessId)
                ->whereNotNull('user_id')
                ->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        }

        if (Schema::hasTable('pump_operators') && Schema::hasColumn('pump_operators', 'user_id')) {
            $query = DB::table('pump_operators')->whereNotNull('user_id');
            if (Schema::hasColumn('pump_operators', 'business_id')) $query->where('business_id', $businessId);
            $ids = array_merge($ids, $query->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function matchesPasscode(object $user, ?PonePdOperator $profile, string $passcode, array $variants): bool
    {
        $legacy = trim((string) ($user->pump_operator_passcode ?? ''));
        if ($legacy !== '' && in_array($legacy, $variants, true)) return true;

        if (! empty($user->password) && Hash::check($passcode, (string) $user->password)) return true;

        $hash = (string) data_get((array) ($profile?->settings ?? []), 'passcode_hash', '');
        return $hash !== '' && Hash::check($passcode, $hash);
    }

    private function backfillLegacyPasscodeColumns(object $user, string $passcode): void
    {
        $updates = [];
        if (Schema::hasColumn('users', 'pump_operator_passcode') && empty($user->pump_operator_passcode)) {
            $updates['pump_operator_passcode'] = trim($passcode);
        }
        if (Schema::hasColumn('users', 'is_pump_operator')) {
            $updates['is_pump_operator'] = 1;
        }
        if (Schema::hasColumn('users', 'updated_at')) {
            $updates['updated_at'] = now();
        }
        if ($updates !== []) DB::table('users')->where('id', $user->id)->update($updates);
    }

    private function passcodeVariants(string $passcode): array
    {
        $passcode = trim($passcode);
        $digits = preg_replace('/\D+/', '', $passcode);
        $trimmed = $digits !== '' ? ltrim($digits, '0') : '';
        if ($digits !== '' && $trimmed === '') $trimmed = '0';

        return array_values(array_unique(array_filter([
            $passcode,
            $digits,
            $trimmed,
            $trimmed !== '' ? str_pad($trimmed, 4, '0', STR_PAD_LEFT) : null,
        ], static fn ($value) => $value !== null && $value !== '')));
    }

    private function userName(object $user): string
    {
        return trim(implode(' ', array_filter([
            $user->surname ?? null,
            $user->first_name ?? null,
            $user->last_name ?? null,
        ]))) ?: (string) ($user->username ?? ('Operator ' . $user->id));
    }
}
