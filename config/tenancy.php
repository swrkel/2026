<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;

/*
|--------------------------------------------------------------------------
| Dynamic central / tenant domain resolution
|--------------------------------------------------------------------------
|
| IMPORTANT: APP_URL identifies the URL of THIS deployment. In this ERP the
| same codebase can be copied to a central root or to a tenant/business root,
| therefore APP_URL is not automatically a central domain.
|
| Resolution priority:
|   1. CENTRAL_DOMAINS = authoritative list of true central hostnames.
|   2. CENTRAL_DOMAIN  = backward-compatible single-host fallback.
|   3. APP_URL         = last-resort fallback only when neither central setting
|                        has been configured.
|
| TenancyServiceProvider performs a second runtime safety check against the
| central `domains` table. If the current hostname is assigned to a tenant,
| that tenant mapping wins even if an old/copied .env accidentally lists the
| hostname as CENTRAL_DOMAIN.
|
*/
$normalizeCentralDomain = static function ($value): ?string {
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $candidate = str_contains($value, '://')
        ? $value
        : 'http://' . ltrim($value, '/');

    $host = parse_url($candidate, PHP_URL_HOST);
    $host = strtolower(trim((string) $host, ". \t\n\r\0\x0B"));

    return $host !== '' ? $host : null;
};

$centralDomains = [];
$extraCentralDomains = preg_split(
    '/[\s,;]+/',
    (string) env('CENTRAL_DOMAINS', ''),
    -1,
    PREG_SPLIT_NO_EMPTY
) ?: [];

// A multi-host CENTRAL_DOMAINS declaration is authoritative. This is what
// allows APP_URL to safely point at a copied tenant root without turning that
// tenant root into a central domain.
if ($extraCentralDomains !== []) {
    $centralDomainSources = $extraCentralDomains;
} elseif ($normalizeCentralDomain(env('CENTRAL_DOMAIN')) !== null) {
    $centralDomainSources = [env('CENTRAL_DOMAIN')];
} else {
    // Backward compatibility for older single-root installs that never defined
    // CENTRAL_DOMAIN(S). APP_URL is used only in this final fallback case.
    $centralDomainSources = [env('APP_URL')];
}

foreach ($centralDomainSources as $configuredCentralDomain) {
    $domain = $normalizeCentralDomain($configuredCentralDomain);

    if ($domain !== null) {
        $centralDomains[] = $domain;
    }
}

$centralDomains[] = '127.0.0.1';
$centralDomains[] = 'localhost';
$centralDomains = array_values(array_unique($centralDomains));

return [
    /*
     | Superadmin: work against the ACTIVE database rather than central.
     |
     | Defined here, not read with env() at the point of use, because env()
     | returns null once config is cached - which silently switched this off
     | and made Super Admin on a tenant host show central's businesses instead
     | of the tenant's own.
     */
    'superadmin_use_active_connection' => filter_var(
        env('SUPERADMIN_USE_ACTIVE_CONNECTION', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'tenant_model' => \App\Tenant::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,

    /**
     * The list of domains hosting your central app.
     *
     * Only relevant if you're using the domain or subdomain identification middleware.
     */
    'central_domains' => $centralDomains,

    /**
     * Tenancy bootstrappers are executed when tenancy is initialized.
     * Their responsibility is making Laravel features tenant-aware.
     *
     * To configure their behavior, see the config keys below.
     */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class, // Note: phpredis is needed
    ],
     'create_database' => true,

    /**
     * Database tenancy config. Used by DatabaseTenancyBootstrapper.
     */
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),

        /**
         * Connection used as a "template" for the tenant database connection.
         */
        'template_tenant_connection' => null,

        /**
         * Tenant database names are created like this:
         * prefix + tenant_id + suffix.
         */
        'prefix' => env('TENANT_DATABASE_PREFIX','nivasa_'),
        'suffix' => '',

        /**
         * TenantDatabaseManagers are classes that handle the creation & deletion of tenant databases.
         */
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,

            /**
             * Use this database manager for MySQL to have a DB user created for each tenant database.
             * You can customize the grants given to these users by changing the $grants property.
             */
            // 'mysql' => Stancl\Tenancy\TenantDatabaseManagers\PermissionControlledMySQLDatabaseManager::class,

            /**
             * Disable the pgsql manager above, and enable the one below if you
             * want to separate tenant DBs by schemas rather than databases.
             */
            // 'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager::class, // Separate by schema instead of database
        ],
    ],

    /**
     * Cache tenancy config. Used by CacheTenancyBootstrapper.
     *
     * This works for all Cache facade calls, cache() helper
     * calls and direct calls to injected cache stores.
     *
     * Each key in cache will have a tag applied on it. This tag is used to
     * scope the cache both when writing to it and when reading from it.
     *
     * You can clear cache selectively by specifying the tag.
     */
    'cache' => [
        'tag_base' => 'tenant', // This tag_base, followed by the tenant_id, will form a tag that will be applied on each cache call.
    ],

    /**
     * Filesystem tenancy config. Used by FilesystemTenancyBootstrapper.
     * https://tenancy.samuelstancl.me/docs/v2/filesystem-tenancy/.
     */
    'filesystem' => [
        /**
         * Each disk listed in the 'disks' array will be suffixed by the suffix_base, followed by the tenant_id.
         */
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            // 's3',
        ],

        /**
         * Use this for local disks.
         *
         * See https://tenancy.samuelstancl.me/docs/v2/filesystem-tenancy/
         */
        'root_override' => [
            // Disks whose roots should be overriden after storage_path() is suffixed.
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        /**
         * Should storage_path() be suffixed.
         *
         * Note: Disabling this will likely break local disk tenancy. Only disable this if you're using an external file storage service like S3.
         *
         * For the vast majority of applications, this feature should be enabled. But in some
         * edge cases, it can cause issues (like using Passport with Vapor - see #196), so
         * you may want to disable this if you are experiencing these edge case issues.
         */
        'suffix_storage_path' => true,

        /**
         * By default, asset() calls are made multi-tenant too. You can use global_asset() and mix()
         * for global, non-tenant-specific assets. However, you might have some issues when using
         * packages that use asset() calls inside the tenant app. To avoid such issues, you can
         * disable asset() helper tenancy and explicitly use tenant_asset() calls in places
         * where you want to use tenant-specific assets (product images, avatars, etc).
         */
        'asset_helper_tenancy' => false,
    ],

    /**
     * Redis tenancy config. Used by RedisTenancyBoostrapper.
     *
     * Note: You need phpredis to use Redis tenancy.
     *
     * Note: You don't need to use this if you're using Redis only for cache.
     * Redis tenancy is only relevant if you're making direct Redis calls,
     * either using the Redis facade or by injecting it as a dependency.
     */
    'redis' => [
        'prefix_base' => 'tenant', // Each key in Redis will be prepended by this prefix_base, followed by the tenant id.
        'prefixed_connections' => [ // Redis connections whose keys are prefixed, to separate one tenant's keys from another.
            // 'default',
        ],
    ],

    /**
     * Features are classes that provide additional functionality
     * not needed for tenancy to be bootstrapped. They are run
     * regardless of whether tenancy has been initialized.
     *
     * See the documentation page for each class to
     * understand which ones you want to enable.
     */
    'features' => [
        Stancl\Tenancy\Features\UserImpersonation::class,
        Stancl\Tenancy\Features\TelescopeTags::class,
        Stancl\Tenancy\Features\UniversalRoutes::class,
        Stancl\Tenancy\Features\TenantConfig::class, // https://tenancy.samuelstancl.me/docs/v2/features/tenant-config/
        Stancl\Tenancy\Features\CrossDomainRedirect::class, // https://tenancy.samuelstancl.me/docs/v2/features/tenant-redirect/
    ],

    /**
     * Parameters used by the tenants:migrate command.
     */
    'migration_parameters' => [
        '--force' => true, // This needs to be true to run migrations in production.
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    /**
     * Parameters used by the tenants:seed command.
     */
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder', // root seeder class
        // '--force' => true,
    ],
];
