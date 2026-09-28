<?php

namespace Modules\Superadmin\Services;

use App\Business;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin always operates on the CENTRAL database.
 *
 * -----------------------------------------------------------------------
 * The problem this solves
 * -----------------------------------------------------------------------
 * Super Admin routes run through the `tenant.context` middleware
 * (App\Http\Middleware\SetTenantContext). When the request arrives on a host
 * that is not listed in `tenancy.central_domains`, that middleware calls
 * `tenancy()->initialize($tenant)`, and Stancl's DatabaseTenancyBootstrapper
 * repoints Laravel's DEFAULT database connection at that tenant's database.
 *
 * Nothing in the Super Admin screens re-pins the connection, so from that
 * point on `Business::find()`, `Business::save()` and any bare `DB::table()`
 * silently read and write the TENANT database instead of the central one.
 *
 * The visible symptom was Manage Side Bar: the POST returned 200, the success
 * toast appeared, and the row really was written - into whichever tenant
 * database the browsing host resolved to. The central row never changed, so
 * reopening the modal showed the old state and the Main System Sidebar (which
 * reads the business's OWN tenant database) never changed either.
 *
 * -----------------------------------------------------------------------
 * How the connection is resolved
 * -----------------------------------------------------------------------
 * Deliberately defensive, because this has to be correct on every server and
 * for every tenant, including installs that do not define a `system`
 * connection:
 *
 *   1. `system`, when it is configured. config/database.php defines it as the
 *      central/system connection bound to DB_DATABASE, and tenancy never
 *      rewrites it.
 *   2. `tenancy.database.central_connection`, unless tenancy has repointed it
 *      (checked by comparing against the tenant connection name and against
 *      the live tenant's database).
 *   3. A connection built at runtime from the central credentials, registered
 *      as `superadmin_central`. This is the last-resort path so a server with
 *      an unusual database config still gets a correct central handle rather
 *      than silently falling back to the tenant connection.
 *
 * The result is memoised per request; the resolution runs at most once.
 */
class CentralContext
{
    /** @var string|null */
    private static $resolved = null;

    /** Runtime connection name used only when nothing else is usable. */
    private const FALLBACK_CONNECTION = 'superadmin_central';

    /**
     * Name of a database connection that is guaranteed to point at the central
     * database, whether or not tenancy is currently initialised.
     */
    /**
     * The true central connection, ignoring SUPERADMIN_USE_ACTIVE_CONNECTION.
     *
     * connectionName() honours that switch, because when it is on Super Admin
     * is deliberately editing one database directly - usually a tester's own.
     *
     * The central BUSINESS REGISTRY is a different concern. Whether central
     * knows a business exists should not depend on a temporary testing
     * setting, otherwise the registry silently stops being written and the gap
     * only shows up in production.
     *
     * So registry writes call this instead: the same resolution order, minus
     * the opt-out.
     */
    public static function trueCentralConnectionName(): string
    {
        $tenantConnection = (string) config('tenancy.database.tenant_connection_name', 'tenant');
        $centralDatabase = self::centralDatabaseName();

        $systemDatabase = config('database.connections.system.database');
        if (! empty($systemDatabase)
            && (empty($centralDatabase) || $systemDatabase === $centralDatabase)) {
            return 'system';
        }

        $candidate = (string) config('tenancy.database.central_connection', config('database.default'));
        if ($candidate !== '' && $candidate !== $tenantConnection) {
            $candidateDatabase = config('database.connections.' . $candidate . '.database');
            if (! empty($candidateDatabase)
                && (empty($centralDatabase) || $candidateDatabase === $centralDatabase)) {
                return $candidate;
            }
        }

        return self::registerFallbackConnection($centralDatabase);
    }

    public static function connectionName(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        /*
         |------------------------------------------------------------------
         | TEMPORARY OPT-OUT: SUPERADMIN_USE_ACTIVE_CONNECTION
         |------------------------------------------------------------------
         |
         | Default is false. Leave it false everywhere except an install where
         | someone genuinely needs Super Admin to work against the TENANT
         | database - for example a tester whose own data lives there.
         |
         | When true, the Superadmin business screens read AND write whatever
         | connection is currently active: the tenant database on a tenant
         | host, central on a central host. Reads and writes therefore stay
         | together, which is the only property that makes this safe.
         |
         | WHY BOTH, NEVER ONE:
         |
         | Business ids are per-database auto-increments, so the same id means
         | different companies in different databases. On this estate today,
         | id 2 is "MPCS Filling Station - Manana" in nivasa_ishadi-pd and
         | "Cool50" in nivasa_base.
         |
         | Scoping only the LIST would show the tester MPCS Filling Station
         | while the save landed on Cool50's row in central - their work would
         | appear to succeed while quietly corrupting another company. Scoping
         | only the WRITE has the mirror problem. Either half alone is worse
         | than doing nothing, so this switch moves both or neither.
         |
         | WHAT YOU GIVE UP WHILE IT IS ON:
         |
         | Super Admin stops being an estate-wide registry on that host. It
         | shows and edits one database. Enable it per install, and turn it
         | off when the work is done.
         */
        if ((bool) config('tenancy.superadmin_use_active_connection', false)) {
            return self::$resolved = (string) config('database.default');
        }

        $tenantConnection = (string) config('tenancy.database.tenant_connection_name', 'tenant');
        $centralDatabase = self::centralDatabaseName();

        // 1. The explicit system connection, when present and central.
        $systemDatabase = config('database.connections.system.database');
        if (! empty($systemDatabase)
            && (empty($centralDatabase) || $systemDatabase === $centralDatabase)) {
            return self::$resolved = 'system';
        }

        // 2. The configured central connection, provided tenancy has not
        //    repointed it at a tenant database.
        $candidate = (string) config('tenancy.database.central_connection', config('database.default'));
        if ($candidate !== '' && $candidate !== $tenantConnection) {
            $candidateDatabase = config('database.connections.' . $candidate . '.database');
            if (! empty($candidateDatabase)
                && (empty($centralDatabase) || $candidateDatabase === $centralDatabase)) {
                return self::$resolved = $candidate;
            }
        }

        // 3. Build one from the central credentials.
        return self::$resolved = self::registerFallbackConnection($centralDatabase);
    }

    /**
     * The central database name, read from a source tenancy does not rewrite.
     */
    public static function centralDatabaseName(): string
    {
        $candidates = [
            config('database.connections.system.database'),
            env('DB_DATABASE'),
        ];

        foreach ($candidates as $candidate) {
            if (! empty($candidate)) {
                return (string) $candidate;
            }
        }

        return '';
    }

    /**
     * A Business query bound to the central database.
     *
     * Eloquent remembers the connection a model was resolved on, so anything
     * done with the returned model afterwards - save(), fresh(), update() -
     * also stays central. That is what makes this safe to use as a drop-in
     * replacement for Business::findOrFail().
     */
    public static function businessQuery()
    {
        return Business::on(self::connectionName());
    }

    /**
     * Find a business on the central connection.
     *
     * @param  int|string  $id
     */
    public static function findBusinessOrFail($id): Business
    {
        return self::businessQuery()->findOrFail($id);
    }

    /**
     * True when tenancy has repointed the default connection away from central.
     * Used for diagnostics only - the code never depends on it.
     */
    public static function isTenantContextActive(): bool
    {
        try {
            $default = (string) config('database.default');
            $defaultDatabase = config('database.connections.' . $default . '.database');
            $central = self::centralDatabaseName();

            return ! empty($central)
                && ! empty($defaultDatabase)
                && $defaultDatabase !== $central;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Register a runtime connection pointing at the central database, copying
     * driver/host/credentials from an existing central-ish connection so this
     * works on servers with non-default database settings.
     */
    private static function registerFallbackConnection(string $centralDatabase): string
    {
        $template = config('database.connections.system')
            ?: config('database.connections.mysql')
            ?: [];

        if (! is_array($template) || $template === []) {
            // Nothing sensible to copy. Return the default and let the caller
            // behave as before rather than throwing inside a save path.
            Log::warning('Superadmin CentralContext: no template connection available; falling back to the default connection.');

            return (string) config('database.default');
        }

        if ($centralDatabase !== '') {
            $template['database'] = $centralDatabase;
        }

        Config::set('database.connections.' . self::FALLBACK_CONNECTION, $template);

        try {
            DB::purge(self::FALLBACK_CONNECTION);
        } catch (\Throwable $e) {
            // Purging an unopened connection is harmless; ignore.
        }

        return self::FALLBACK_CONNECTION;
    }
}
