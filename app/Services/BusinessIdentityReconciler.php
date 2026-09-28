<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * BusinessIdentityReconciler
 *
 * Makes a tenant database's business identities agree with the central
 * registry, so that permissions can safely be looked up by global_uid
 * instead of by the colliding business_id.
 *
 * It never renumbers a business_id. It only ever assigns global_uid /
 * tenant_id on the tenant side, and inserts a matching registry row on the
 * central side.
 *
 * Decision rules, in order (see README):
 *   1. --force-remint given ................................. REMINT
 *   2. no global_uid at all ................................. MINT
 *   3. uid known to central, central row is THIS tenant ..... KEEP   (self-restore)
 *   4. uid known to central, central row is ANOTHER tenant .. REMINT (foreign copy)
 *   5. uid used by another tenant database in the estate .... REMINT (collision)
 *   6. uid unknown to central, tenant_id is THIS tenant ..... KEEP + REGISTER (backfill)
 *   7. anything else ........................................ REMINT + REGISTER
 */
class BusinessIdentityReconciler
{
    public const KEEP   = 'keep';
    public const MINT   = 'mint';
    public const REMINT = 'remint';

    /** Connection whose host/user/password we clone to reach every database. */
    protected string $baseConnection;

    /** Name of the central database, e.g. nivasa_base. */
    protected string $centralDb;

    /** Database name pattern used to scan the estate, e.g. nivasa_%. */
    protected string $pattern;

    /** Cache of uid => "db#id" seen anywhere in the estate. */
    protected ?array $estateUids = null;

    public function __construct(?string $baseConnection = null, ?string $centralDb = null, ?string $pattern = null)
    {
        $this->baseConnection = $baseConnection ?: Config::get('database.default');

        $this->centralDb = $centralDb
            ?: Config::get("database.connections.{$this->baseConnection}.database");

        $this->pattern = $pattern ?: $this->guessPattern($this->centralDb);
    }

    public function centralDb(): string
    {
        return $this->centralDb;
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    // ---------------------------------------------------------------- plumbing

    /** A connection pointed at an arbitrary database on the same server. */
    public function conn(string $database)
    {
        $name = 'bir_' . md5($database);

        if (! Config::has("database.connections.$name")) {
            $cfg = Config::get("database.connections.{$this->baseConnection}");
            $cfg['database'] = $database;
            unset($cfg['prefix_indexes']);
            Config::set("database.connections.$name", $cfg);
        }

        return DB::connection($name);
    }

    protected function guessPattern(string $centralDb): string
    {
        $pos = strrpos($centralDb, '_');

        return $pos === false ? $centralDb : substr($centralDb, 0, $pos + 1) . '%';
    }

    /** Every database matching the pattern, central excluded. */
    public function tenantDatabases(): array
    {
        $rows = $this->conn($this->centralDb)->select(
            'SELECT SCHEMA_NAME AS n FROM information_schema.SCHEMATA
             WHERE SCHEMA_NAME LIKE ? ORDER BY SCHEMA_NAME',
            [$this->pattern]
        );

        return array_values(array_filter(
            array_map(fn ($r) => $r->n, $rows),
            fn ($n) => $n !== $this->centralDb
        ));
    }

    public function tableExists(string $db, string $table): bool
    {
        $row = $this->conn($this->centralDb)->selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$db, $table]
        );

        return (int) $row->c > 0;
    }

    public function columns(string $db, string $table): array
    {
        $rows = $this->conn($this->centralDb)->select(
            'SELECT COLUMN_NAME AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$db, $table]
        );

        return array_map(fn ($r) => $r->c, $rows);
    }

    protected function indexExists(string $db, string $table, string $index): bool
    {
        $row = $this->conn($this->centralDb)->selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$db, $table, $index]
        );

        return (int) $row->c > 0;
    }

    /** Two tenant identifiers are "the same tenant" ignoring the db prefix. */
    public function sameTenant(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null || $a === '' || $b === '') {
            return false;
        }

        $norm = function (string $v) {
            $v = strtolower(trim($v));
            $prefix = rtrim($this->pattern, '%');

            return $prefix !== '' && Str::startsWith($v, $prefix)
                ? substr($v, strlen($prefix))
                : $v;
        };

        return $norm($a) === $norm($b);
    }

    // ------------------------------------------------------------ schema guards

    /**
     * Make sure a tenant business table carries global_uid + tenant_id.
     * Returns the list of changes made (empty when nothing was needed).
     */
    public function ensureIdentityColumns(string $db, bool $dryRun = false): array
    {
        $done = [];
        $cols = $this->columns($db, 'business');

        if (! in_array('global_uid', $cols, true)) {
            $done[] = 'added business.global_uid';
            if (! $dryRun) {
                $this->conn($db)->statement(
                    "ALTER TABLE `business` ADD COLUMN `global_uid` CHAR(36) NULL AFTER `id`"
                );
            }
        }

        if (! in_array('tenant_id', $cols, true)) {
            $done[] = 'added business.tenant_id';
            if (! $dryRun) {
                $this->conn($db)->statement(
                    "ALTER TABLE `business` ADD COLUMN `tenant_id` VARCHAR(191) NULL AFTER `global_uid`"
                );
            }
        }

        if (! $dryRun && ! $this->indexExists($db, 'business', 'business_global_uid_unique')) {
            try {
                $this->conn($db)->statement(
                    "ALTER TABLE `business` ADD UNIQUE INDEX `business_global_uid_unique` (`global_uid`)"
                );
                $done[] = 'added unique index on business.global_uid';
            } catch (Throwable $e) {
                $done[] = 'WARNING could not add unique index: ' . $e->getMessage();
            }
        }

        return $done;
    }

    /**
     * Add global_uid to the CENTRAL subscriptions table and backfill it from
     * the central business table. Inert until the lookup is switched over.
     */
    public function ensureCentralSubscriptionUid(bool $dryRun = false): array
    {
        $done = [];
        $cols = $this->columns($this->centralDb, 'subscriptions');

        if (! in_array('global_uid', $cols, true)) {
            $done[] = 'added subscriptions.global_uid';
            if (! $dryRun) {
                $this->conn($this->centralDb)->statement(
                    "ALTER TABLE `subscriptions` ADD COLUMN `global_uid` CHAR(36) NULL AFTER `business_id`"
                );
                $this->conn($this->centralDb)->statement(
                    "ALTER TABLE `subscriptions` ADD INDEX `subscriptions_global_uid_index` (`global_uid`)"
                );
                $done[] = 'added index on subscriptions.global_uid';
            }
        }

        if (! $dryRun) {
            $n = $this->conn($this->centralDb)->update(
                "UPDATE `subscriptions` s
                 JOIN `business` b ON b.id = s.business_id
                 SET s.global_uid = b.global_uid
                 WHERE (s.global_uid IS NULL OR s.global_uid = '')
                   AND b.global_uid IS NOT NULL"
            );
            if ($n > 0) {
                $done[] = "backfilled global_uid on {$n} subscription row(s)";
            }

            $orphans = $this->conn($this->centralDb)->selectOne(
                "SELECT COUNT(*) AS c FROM `subscriptions` s
                 LEFT JOIN `business` b ON b.id = s.business_id
                 WHERE b.id IS NULL"
            );
            if ((int) $orphans->c > 0) {
                $done[] = "WARNING {$orphans->c} subscription row(s) point at a business "
                        . "that does not exist in central - left untouched";
            }
        }

        return $done;
    }

    // ------------------------------------------------------------- uid handling

    /** uid => "db#id" for every business anywhere in the estate, central included. */
    public function estateUidMap(bool $fresh = false): array
    {
        if ($this->estateUids !== null && ! $fresh) {
            return $this->estateUids;
        }

        $map = [];
        $dbs = array_merge([$this->centralDb], $this->tenantDatabases());

        foreach ($dbs as $db) {
            if (! $this->tableExists($db, 'business')) {
                continue;
            }
            if (! in_array('global_uid', $this->columns($db, 'business'), true)) {
                continue;
            }

            $rows = $this->conn($db)->select(
                "SELECT id, global_uid FROM `business`
                 WHERE global_uid IS NOT NULL AND global_uid <> ''"
            );

            foreach ($rows as $r) {
                $map[$r->global_uid][] = $db . '#' . $r->id;
            }
        }

        return $this->estateUids = $map;
    }

    /** A uuid guaranteed not to be in use anywhere in the estate. */
    public function freshUid(): string
    {
        $map = $this->estateUidMap();

        do {
            $uid = (string) Str::uuid();
        } while (isset($map[$uid]));

        $this->estateUids[$uid] = ['(reserved)'];

        return $uid;
    }

    // ----------------------------------------------------------------- the work

    /**
     * Reconcile one tenant database.
     *
     * @return array{tenant:string, schema:array, rows:array, errors:array}
     */
    public function reconcile(string $tenantDb, array $opts = []): array
    {
        $dryRun        = (bool) ($opts['dry_run'] ?? false);
        $forceRemint   = (bool) ($opts['force_remint'] ?? false);
        $withSubs      = (bool) ($opts['subscriptions'] ?? true);
        $tenantKey     = $opts['tenant_key'] ?? $tenantDb;

        $result = ['tenant' => $tenantDb, 'schema' => [], 'rows' => [], 'errors' => []];

        if (! $this->tableExists($tenantDb, 'business')) {
            $result['errors'][] = "{$tenantDb} has no business table - skipped";

            return $result;
        }

        $result['schema'] = array_merge(
            $this->ensureIdentityColumns($tenantDb, $dryRun),
            $this->ensureCentralSubscriptionUid($dryRun)
        );

        // Refresh caches now that columns may have appeared.
        $this->estateUidMap(true);

        $central       = $this->conn($this->centralDb);
        $tenant        = $this->conn($tenantDb);
        $centralByUid  = [];

        foreach ($central->select("SELECT id, global_uid, tenant_id FROM `business`") as $r) {
            if (! empty($r->global_uid)) {
                $centralByUid[$r->global_uid] = $r;
            }
        }

        $businessCols = array_values(array_intersect(
            $this->columns($tenantDb, 'business'),
            $this->columns($this->centralDb, 'business')
        ));
        $businessCols = array_values(array_diff($businessCols, ['id']));

        $rows = $tenant->select("SELECT * FROM `business` ORDER BY id");

        foreach ($rows as $row) {
            $uid        = $row->global_uid ?? null;
            $tid        = $row->tenant_id ?? null;
            $centralRow = ($uid && isset($centralByUid[$uid])) ? $centralByUid[$uid] : null;

            $action   = self::REMINT;
            $register = true;
            $why      = 'unknown identity - treated as a foreign copy';

            if ($forceRemint) {
                $why = 'forced by --force-remint';
            } elseif (empty($uid)) {
                $action = self::MINT;
                $why    = 'no global_uid present';
            } elseif ($centralRow && $this->sameTenant($centralRow->tenant_id, $tenantKey)) {
                $action   = self::KEEP;
                $register = false;
                $why      = 'already registered to this tenant - left alone';
            } elseif ($centralRow) {
                $why = 'uid belongs to tenant "' . $centralRow->tenant_id . '" in central';
            } elseif ($this->uidUsedElsewhere($uid, $tenantDb)) {
                $why = 'uid already used by another tenant database';
            } elseif ($this->sameTenant($tid, $tenantKey)) {
                $action = self::KEEP;
                $why    = 'our own business, missing from central - registering';
            }

            $entry = [
                'business_id' => $row->id,
                'name'        => $row->name ?? '(no name)',
                'action'      => $action,
                'why'         => $why,
                'old_uid'     => $uid,
                'new_uid'     => $uid,
                'central_id'  => $centralRow->id ?? null,
                'subs'        => 0,
            ];

            if ($action !== self::KEEP) {
                $entry['new_uid'] = $dryRun ? '(new uuid)' : $this->freshUid();
            }

            if (! $register) {
                $result['rows'][] = $entry;
                continue;
            }

            if ($dryRun) {
                $entry['central_id'] = '(new)';
                $entry['subs']       = $withSubs
                    ? $this->countTenantSubscriptions($tenantDb, $row->id)
                    : 0;
                $result['rows'][]    = $entry;
                continue;
            }

            try {
                $central->transaction(function () use (
                    $central, $tenant, $tenantDb, $row, $entry, $businessCols,
                    $tenantKey, $withSubs, &$result
                ) {
                    $payload = [];
                    foreach ($businessCols as $c) {
                        $payload[$c] = $row->$c ?? null;
                    }
                    $payload['global_uid'] = $entry['new_uid'];
                    $payload['tenant_id']  = $tenantKey;

                    $centralId = $central->table('business')->insertGetId($payload);

                    $tenant->table('business')->where('id', $row->id)->update([
                        'global_uid' => $entry['new_uid'],
                        'tenant_id'  => $tenantKey,
                    ]);

                    $entry['central_id'] = $centralId;

                    if ($withSubs) {
                        $entry['subs'] = $this->copySubscriptions(
                            $tenantDb, $row->id, $centralId, $entry['new_uid']
                        );
                    }

                    $result['rows'][] = $entry;
                });
            } catch (Throwable $e) {
                $entry['action']  = 'FAILED';
                $entry['why']     = $e->getMessage();
                $result['rows'][] = $entry;
                $result['errors'][] = "business {$row->id} ({$entry['name']}): " . $e->getMessage();
            }
        }

        return $result;
    }

    protected function uidUsedElsewhere(string $uid, string $tenantDb): bool
    {
        foreach ($this->estateUidMap()[$uid] ?? [] as $place) {
            if (! Str::startsWith($place, $tenantDb . '#')) {
                return true;
            }
        }

        return false;
    }

    protected function countTenantSubscriptions(string $tenantDb, int $businessId): int
    {
        if (! $this->tableExists($tenantDb, 'subscriptions')) {
            return 0;
        }

        return (int) $this->conn($tenantDb)
            ->table('subscriptions')->where('business_id', $businessId)->count();
    }

    /** Copy a tenant's own subscription rows into central against the new uid. */
    protected function copySubscriptions(
        string $tenantDb, int $tenantBusinessId, int $centralBusinessId, string $uid
    ): int {
        if (! $this->tableExists($tenantDb, 'subscriptions')) {
            return 0;
        }

        $cols = array_values(array_intersect(
            $this->columns($tenantDb, 'subscriptions'),
            $this->columns($this->centralDb, 'subscriptions')
        ));
        $cols = array_values(array_diff($cols, ['id']));

        $rows = $this->conn($tenantDb)->select(
            "SELECT * FROM `subscriptions` WHERE business_id = ? ORDER BY id",
            [$tenantBusinessId]
        );

        $n = 0;
        foreach ($rows as $r) {
            $payload = [];
            foreach ($cols as $c) {
                $payload[$c] = $r->$c ?? null;
            }
            $payload['business_id'] = $centralBusinessId;
            $payload['global_uid']  = $uid;

            $this->conn($this->centralDb)->table('subscriptions')->insert($payload);
            $n++;
        }

        return $n;
    }
}
