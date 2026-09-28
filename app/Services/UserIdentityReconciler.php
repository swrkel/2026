<?php

namespace App\Services;

use Illuminate\Support\Str;
use Throwable;

/**
 * UserIdentityReconciler
 *
 * Adds and maintains an immutable `users.global_user_id` across the central
 * database and every tenant database without changing any existing numeric
 * `users.id` value or foreign key.
 *
 * Important safety rules:
 * - Never deletes a user.
 * - Never renumbers users.id.
 * - Never changes business_id, roles, permissions, passwords, or locations.
 * - A unique, non-empty existing global_user_id is always preserved.
 * - Missing IDs are minted.
 * - When an imported/copied database contains an ID already used elsewhere,
 *   the copy being reconciled is reminted while the existing estate row stays.
 */
class UserIdentityReconciler
{
    public const COLUMN = 'global_user_id';
    public const INDEX = 'users_global_user_id_unique';

    protected BusinessIdentityReconciler $estate;

    /** @var array<string, array<int, string>>|null */
    protected ?array $identityMap = null;

    public function __construct(?string $baseConnection = null, ?string $centralDb = null, ?string $pattern = null)
    {
        // Reuse the application's already-proven central/tenant database
        // discovery and connection plumbing. We do not modify business identity.
        $this->estate = new BusinessIdentityReconciler($baseConnection, $centralDb, $pattern);
    }

    public function centralDb(): string
    {
        return $this->estate->centralDb();
    }

    public function pattern(): string
    {
        return $this->estate->pattern();
    }

    public function conn(string $database)
    {
        return $this->estate->conn($database);
    }

    public function tableExists(string $database, string $table): bool
    {
        return $this->estate->tableExists($database, $table);
    }

    /** @return array<int, string> */
    public function columns(string $database, string $table): array
    {
        return $this->estate->columns($database, $table);
    }

    /**
     * Central first, then tenant databases in a deterministic order.
     * Databases without a users table are omitted.
     *
     * @return array<int, string>
     */
    public function userDatabases(): array
    {
        $databases = array_merge([$this->centralDb()], $this->estate->tenantDatabases());

        return array_values(array_filter($databases, function (string $database): bool {
            return $this->tableExists($database, 'users');
        }));
    }

    public function userCount(string $database): int
    {
        if (! $this->tableExists($database, 'users')) {
            return 0;
        }

        return (int) $this->conn($database)->table('users')->count();
    }

    protected function indexExists(string $database): bool
    {
        $row = $this->conn($this->centralDb())->selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$database, 'users', self::INDEX]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    public function hasIdentityColumn(string $database): bool
    {
        return $this->tableExists($database, 'users')
            && in_array(self::COLUMN, $this->columns($database, 'users'), true);
    }

    /**
     * Add only the nullable identity column. Existing live users are backfilled
     * separately, so deployment does not require one long ALTER+UPDATE operation.
     *
     * @return array<int, string>
     */
    public function ensureIdentityColumn(string $database, bool $dryRun = false): array
    {
        $done = [];

        if (! $this->tableExists($database, 'users')) {
            return ["{$database}: no users table - skipped"];
        }

        if (! $this->hasIdentityColumn($database)) {
            $done[] = 'add users.global_user_id CHAR(36) NULL';

            if (! $dryRun) {
                $this->conn($database)->statement(
                    'ALTER TABLE `users` ADD COLUMN `global_user_id` CHAR(36) NULL AFTER `id`'
                );
                $this->identityMap = null;
            }
        }

        return $done;
    }

    /**
     * Add the per-database unique index after duplicate/missing rows are fixed.
     * MySQL cannot enforce uniqueness across databases, so estate-wide audit +
     * reconciliation remains the cross-database guard.
     *
     * @return array<int, string>
     */
    public function ensureUniqueIndex(string $database, bool $dryRun = false): array
    {
        if (! $this->hasIdentityColumn($database)) {
            return [];
        }

        if ($this->indexExists($database)) {
            return [];
        }

        if ($dryRun) {
            return ['add unique index users_global_user_id_unique'];
        }

        try {
            $this->conn($database)->statement(
                'ALTER TABLE `users` ADD UNIQUE INDEX `users_global_user_id_unique` (`global_user_id`)'
            );

            return ['added unique index users_global_user_id_unique'];
        } catch (Throwable $e) {
            return ['ERROR adding unique index: ' . $e->getMessage()];
        }
    }

    /**
     * global_user_id => ["database#user_id", ...]
     *
     * @return array<string, array<int, string>>
     */
    public function estateIdentityMap(bool $fresh = false): array
    {
        if ($this->identityMap !== null && ! $fresh) {
            return $this->identityMap;
        }

        $map = [];

        foreach ($this->userDatabases() as $database) {
            if (! $this->hasIdentityColumn($database)) {
                continue;
            }

            $rows = $this->conn($database)->select(
                'SELECT `id`, `global_user_id` FROM `users`
                 WHERE `global_user_id` IS NOT NULL
                   AND TRIM(`global_user_id`) <> \'\''
            );

            foreach ($rows as $row) {
                $uid = trim((string) $row->global_user_id);
                $map[$uid][] = $this->place($database, (int) $row->id);
            }
        }

        return $this->identityMap = $map;
    }

    /**
     * Deterministic keeper for estate-wide duplicate repair: central wins when
     * present; otherwise database name then user ID. Only the non-keepers are
     * reminted. This map is calculated before writes start.
     *
     * @return array<string, string>
     */
    public function duplicateKeepers(): array
    {
        $keepers = [];

        foreach ($this->estateIdentityMap(true) as $uid => $places) {
            if (count($places) < 2) {
                continue;
            }

            usort($places, function (string $a, string $b): int {
                [$adb, $aid] = $this->splitPlace($a);
                [$bdb, $bid] = $this->splitPlace($b);

                if ($adb === $this->centralDb() && $bdb !== $this->centralDb()) {
                    return -1;
                }
                if ($bdb === $this->centralDb() && $adb !== $this->centralDb()) {
                    return 1;
                }

                $dbCmp = strcmp($adb, $bdb);
                return $dbCmp !== 0 ? $dbCmp : ($aid <=> $bid);
            });

            $keepers[$uid] = $places[0];
        }

        return $keepers;
    }

    /** A UUID not currently used anywhere we can see in the estate. */
    public function freshIdentity(): string
    {
        $map = $this->estateIdentityMap();

        do {
            $uid = (string) Str::uuid();
        } while (isset($map[$uid]));

        $this->identityMap[$uid] = ['(reserved)'];

        return $uid;
    }

    /**
     * Reconcile one database.
     *
     * Options:
     * - dry_run: write nothing
     * - estate_wide: use precomputed keepers so exactly one duplicate keeps ID
     * - keepers: uid => place map returned by duplicateKeepers()
     *
     * @return array{database:string,schema:array,rows:array,errors:array}
     */
    public function reconcileDatabase(string $database, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $estateWide = (bool) ($options['estate_wide'] ?? false);
        $keepers = (array) ($options['keepers'] ?? []);

        $out = [
            'database' => $database,
            'schema' => [],
            'rows' => [],
            'errors' => [],
        ];

        if (! $this->tableExists($database, 'users')) {
            $out['errors'][] = "{$database} has no users table - skipped";
            return $out;
        }

        $columnExisted = $this->hasIdentityColumn($database);
        $out['schema'] = array_merge(
            $out['schema'],
            $this->ensureIdentityColumn($database, $dryRun)
        );

        if (! $dryRun && ! $columnExisted) {
            // The column now exists. Refresh the map before minting IDs.
            $this->estateIdentityMap(true);
        }

        $select = ['id', 'username', 'business_id'];
        if ($columnExisted || ! $dryRun) {
            $select[] = self::COLUMN;
        }

        $rows = $this->conn($database)
            ->table('users')
            ->select($select)
            ->orderBy('id')
            ->get();

        // In a dry run against a database that does not yet have the column,
        // every existing user would receive a new identity.
        $map = $this->estateIdentityMap();

        foreach ($rows as $row) {
            $userId = (int) $row->id;
            $place = $this->place($database, $userId);
            $old = isset($row->{self::COLUMN})
                ? trim((string) $row->{self::COLUMN})
                : '';

            $action = 'keep';
            $reason = 'existing unique identity';

            if ($old === '') {
                $action = 'mint';
                $reason = 'missing global_user_id';
            } else {
                $places = $map[$old] ?? [];

                if (count($places) > 1) {
                    if ($estateWide) {
                        $keeper = $keepers[$old] ?? $this->lowestPlace($places);
                        if ($place !== $keeper) {
                            $action = 'remint';
                            $reason = "duplicate identity; keeper is {$keeper}";
                        } else {
                            $reason = 'duplicate identity keeper';
                        }
                    } else {
                        $outsideThisDatabase = array_values(array_filter(
                            $places,
                            fn (string $p): bool => ! str_starts_with($p, $database . '#')
                        ));

                        if ($outsideThisDatabase !== []) {
                            // Single-database mode is intended for an imported/copied
                            // tenant: the existing estate copy wins.
                            $action = 'remint';
                            $reason = 'identity already exists in another database';
                        } elseif ($place !== $this->lowestPlace($places)) {
                            $action = 'remint';
                            $reason = 'duplicate identity inside this database';
                        } else {
                            $reason = 'local duplicate keeper';
                        }
                    }
                }
            }

            $new = $old;

            if ($action !== 'keep') {
                $new = $dryRun ? '(new uuid)' : $this->freshIdentity();

                if (! $dryRun) {
                    $this->conn($database)
                        ->table('users')
                        ->where('id', $userId)
                        ->update([self::COLUMN => $new]);

                    // Keep the in-memory map accurate for this command run.
                    if ($old !== '' && isset($this->identityMap[$old])) {
                        $this->identityMap[$old] = array_values(array_filter(
                            $this->identityMap[$old],
                            fn (string $p): bool => $p !== $place
                        ));
                        if ($this->identityMap[$old] === []) {
                            unset($this->identityMap[$old]);
                        }
                    }
                    $this->identityMap[$new] = [$place];
                }
            }

            $out['rows'][] = [
                'user_id' => $userId,
                'username' => (string) ($row->username ?? ''),
                'business_id' => $row->business_id ?? null,
                'action' => $action,
                'old_id' => $old,
                'new_id' => $new,
                'reason' => $reason,
            ];
        }

        // Only create the index after duplicate repair. In dry-run mode where
        // the column does not yet exist, report the planned index separately.
        if ($dryRun && ! $columnExisted) {
            $out['schema'][] = 'add unique index users_global_user_id_unique after backfill';
        } else {
            $indexChanges = $this->ensureUniqueIndex($database, $dryRun);
            foreach ($indexChanges as $change) {
                if (str_starts_with($change, 'ERROR')) {
                    $out['errors'][] = $change;
                } else {
                    $out['schema'][] = $change;
                }
            }
        }

        return $out;
    }

    /**
     * Read-only status for one database.
     *
     * @return array<string, mixed>
     */
    public function auditDatabase(string $database): array
    {
        if (! $this->tableExists($database, 'users')) {
            return [
                'database' => $database,
                'users' => 0,
                'column' => false,
                'missing' => 0,
                'local_duplicates' => 0,
                'unique_index' => false,
            ];
        }

        $count = $this->userCount($database);
        $hasColumn = $this->hasIdentityColumn($database);

        if (! $hasColumn) {
            return [
                'database' => $database,
                'users' => $count,
                'column' => false,
                'missing' => $count,
                'local_duplicates' => 0,
                'unique_index' => false,
            ];
        }

        $missing = (int) $this->conn($database)->selectOne(
            'SELECT COUNT(*) AS c FROM `users`
             WHERE `global_user_id` IS NULL OR TRIM(`global_user_id`) = \'\''
        )->c;

        $dupes = (int) $this->conn($database)->selectOne(
            'SELECT COUNT(*) AS c FROM (
                SELECT `global_user_id`
                FROM `users`
                WHERE `global_user_id` IS NOT NULL AND TRIM(`global_user_id`) <> \'\'
                GROUP BY `global_user_id`
                HAVING COUNT(*) > 1
             ) d'
        )->c;

        return [
            'database' => $database,
            'users' => $count,
            'column' => true,
            'missing' => $missing,
            'local_duplicates' => $dupes,
            'unique_index' => $this->indexExists($database),
        ];
    }

    /** @return array<string, array<int, string>> */
    public function estateDuplicates(bool $fresh = true): array
    {
        return array_filter(
            $this->estateIdentityMap($fresh),
            fn (array $places): bool => count($places) > 1
        );
    }

    protected function place(string $database, int $userId): string
    {
        return $database . '#' . $userId;
    }

    /** @return array{0:string,1:int} */
    protected function splitPlace(string $place): array
    {
        $parts = explode('#', $place, 2);
        return [(string) ($parts[0] ?? ''), (int) ($parts[1] ?? 0)];
    }

    protected function lowestPlace(array $places): string
    {
        usort($places, function (string $a, string $b): int {
            [$adb, $aid] = $this->splitPlace($a);
            [$bdb, $bid] = $this->splitPlace($b);
            $dbCmp = strcmp($adb, $bdb);
            return $dbCmp !== 0 ? $dbCmp : ($aid <=> $bid);
        });

        return (string) ($places[0] ?? '');
    }
}
