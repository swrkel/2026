<?php

namespace App\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Deterministic module bootstrap for portable/multi-tenant deployments.
 *
 * Nwidart's file activator/provider manifests are useful optimisations, but
 * they must not be a single point of failure for route registration after a
 * ZIP upload, server transfer, or a module added without a status entry.
 *
 * Rules:
 * - explicit modules_statuses.json false always wins globally;
 * - missing status means installed/available (business visibility is governed
 *   by Manage Side Bar, not by an accidentally incomplete status file);
 * - backup/mismatched module folders are ignored;
 * - providers are registered idempotently through Laravel's container;
 * - old route-only module folders get a narrow tenant-route fallback.
 */
class PortableModuleBootstrap
{
    private static ?array $requestModules = null;

    private static ?array $requestStatuses = null;

    private static array $registeredProviders = [];

    private static array $skippedProviders = [];

    public static function discover(?string $basePath = null): array
    {
        $usingApplicationBasePath = $basePath === null;
        if ($usingApplicationBasePath && self::$requestModules !== null) {
            return self::$requestModules;
        }

        $basePath = $basePath ?: base_path();
        $modulesPath = rtrim($basePath, '/\\') . '/Modules';
        if (! is_dir($modulesPath)) {
            return [];
        }

        $modules = [];
        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $folder = basename($directory);
            if (self::isBackupLikeFolder($folder)) {
                continue;
            }

            $jsonPath = $directory . '/module.json';
            if (! is_file($jsonPath)) {
                continue;
            }

            $metadata = json_decode((string) @file_get_contents($jsonPath), true);
            if (! is_array($metadata)) {
                continue;
            }

            $providers = array_values(array_filter(array_map(
                static fn ($provider): string => trim((string) $provider),
                (array) ($metadata['providers'] ?? [])
            )));

            if ($providers === []) {
                continue;
            }

            $providerFiles = [];
            $valid = true;
            foreach ($providers as $provider) {
                $providerFile = self::providerFile($basePath, $folder, $provider);
                if ($providerFile === null) {
                    $valid = false;
                    self::$skippedProviders[] = [
                        'module' => $folder,
                        'provider' => $provider,
                        'reason' => 'provider_path_mismatch_or_missing',
                    ];
                    break;
                }
                $providerFiles[$provider] = $providerFile;
            }

            if (! $valid) {
                continue;
            }

            $modules[$folder] = [
                'folder' => $folder,
                'name' => trim((string) ($metadata['name'] ?? $folder)) ?: $folder,
                'alias' => (string) ($metadata['alias'] ?? ''),
                'providers' => $providers,
                'provider_files' => $providerFiles,
                'module_json' => $jsonPath,
            ];
        }

        ksort($modules, SORT_NATURAL | SORT_FLAG_CASE);

        if ($usingApplicationBasePath) {
            self::$requestModules = $modules;
        }

        return $modules;
    }

    public static function statuses(?string $basePath = null): array
    {
        $usingApplicationBasePath = $basePath === null;
        if ($usingApplicationBasePath && self::$requestStatuses !== null) {
            return self::$requestStatuses;
        }

        $basePath = $basePath ?: base_path();
        $path = rtrim($basePath, '/\\') . '/modules_statuses.json';
        $statuses = [];

        if (is_file($path)) {
            $decoded = json_decode((string) @file_get_contents($path), true);
            $statuses = is_array($decoded) ? $decoded : [];
        }

        if ($usingApplicationBasePath) {
            self::$requestStatuses = $statuses;
        }

        return $statuses;
    }

    public static function isGloballyEnabled(string $folder, ?string $basePath = null): bool
    {
        $statuses = self::statuses($basePath);
        $modules = self::discover($basePath);
        $moduleName = trim((string) ($modules[$folder]['name'] ?? $folder));

        // Nwidart keys modules_statuses.json by module.json name, while some
        // historical ERP code used the physical folder name. Honour BOTH. An
        // explicit false under either identity always wins; a missing key must
        // never become an accidental global disable after a server transfer.
        foreach (array_values(array_unique(array_filter([$folder, $moduleName]))) as $statusKey) {
            if (array_key_exists($statusKey, $statuses) && ! (bool) $statuses[$statusKey]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register every valid enabled module provider independent of Nwidart's
     * generated provider manifest. Laravel ignores providers already loaded.
     */
    public static function registerProviders(Application $app): array
    {
        $registered = [];

        foreach (self::discover() as $folder => $module) {
            if (! self::isGloballyEnabled($folder)) {
                continue;
            }

            foreach ($module['providers'] as $providerClass) {
                try {
                    if ($app->getProvider($providerClass)) {
                        continue;
                    }

                    $providerFile = $module['provider_files'][$providerClass] ?? null;
                    if (! class_exists($providerClass, false) && is_file($providerFile)) {
                        require_once $providerFile;
                    }

                    if (! class_exists($providerClass)) {
                        self::$skippedProviders[] = [
                            'module' => $folder,
                            'provider' => $providerClass,
                            'reason' => 'provider_class_not_loadable',
                        ];
                        continue;
                    }

                    $app->register($providerClass);
                    $registered[] = $providerClass;
                    self::$registeredProviders[$providerClass] = true;
                } catch (\Throwable $exception) {
                    self::$skippedProviders[] = [
                        'module' => $folder,
                        'provider' => $providerClass,
                        'reason' => get_class($exception) . ': ' . $exception->getMessage(),
                    ];

                    // Do not let one optional module prevent the whole ERP from
                    // booting. The health report exposes the skipped provider.
                    if (function_exists('logger')) {
                        try {
                            logger()->error('Portable module provider registration failed.', [
                                'module' => $folder,
                                'provider' => $providerClass,
                                'error' => $exception->getMessage(),
                            ]);
                        } catch (\Throwable $ignored) {
                            // Logging is best-effort during early bootstrap.
                        }
                    }
                }
            }
        }

        return $registered;
    }

    /**
     * Compatibility for very old standalone folders that contain Routes/web.php
     * but no module.json/provider. Keep them tenant-only and never let this path
     * override a real module provider.
     */
    public static function registerLegacyRouteOnlyModules(Application $app): array
    {
        if ($app->routesAreCached()) {
            return [];
        }

        $loaded = [];
        $modulesPath = base_path('Modules');
        if (! is_dir($modulesPath)) {
            return $loaded;
        }

        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $folder = basename($directory);
            if (self::isBackupLikeFolder($folder) || is_file($directory . '/module.json')) {
                continue;
            }

            $webRoutes = $directory . '/Routes/web.php';
            if (! is_file($webRoutes) || ! self::isGloballyEnabled($folder)) {
                continue;
            }

            $guardKey = 'portable.legacy.routes.' . sha1($directory);
            if ($app->bound($guardKey)) {
                continue;
            }
            $app->instance($guardKey, true);

            $viewPath = $directory . '/Resources/views';
            if (is_dir($viewPath)) {
                $namespace = strtolower(preg_replace('/[^a-z0-9]+/i', '', $folder));
                if ($namespace !== '') {
                    View::addNamespace($namespace, $viewPath);
                }
            }

            Route::middleware([
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ])->group($webRoutes);

            $loaded[] = $folder;
        }

        return $loaded;
    }

    public static function health(): array
    {
        $statuses = self::statuses();
        $modules = self::discover();
        $missingStatuses = [];
        $explicitlyDisabled = [];

        foreach ($modules as $folder => $module) {
            $moduleName = trim((string) ($module['name'] ?? $folder));
            $statusKeys = array_values(array_unique(array_filter([$folder, $moduleName])));
            $hasStatus = false;
            $disabled = false;

            foreach ($statusKeys as $statusKey) {
                if (! array_key_exists($statusKey, $statuses)) {
                    continue;
                }
                $hasStatus = true;
                if (! (bool) $statuses[$statusKey]) {
                    $disabled = true;
                }
            }

            if (! $hasStatus) {
                $missingStatuses[] = $folder;
            } elseif ($disabled) {
                $explicitlyDisabled[] = $folder;
            }
        }

        return [
            'valid_modules' => array_keys($modules),
            'missing_status_entries' => $missingStatuses,
            'explicitly_disabled' => $explicitlyDisabled,
            'registered_by_portable_bootstrap' => array_keys(self::$registeredProviders),
            'skipped_providers' => self::$skippedProviders,
        ];
    }

    public static function forget(): void
    {
        self::$requestModules = null;
        self::$requestStatuses = null;
        self::$registeredProviders = [];
        self::$skippedProviders = [];
    }

    private static function providerFile(string $basePath, string $folder, string $providerClass): ?string
    {
        $providerClass = trim($providerClass, " \\t\n\r\0\x0B\\");
        if ($providerClass === '') {
            return null;
        }

        $expectedPrefix = 'Modules\\' . $folder . '\\';
        if (strpos($providerClass, $expectedPrefix) !== 0) {
            return null;
        }

        $path = rtrim($basePath, '/\\') . '/' . str_replace('\\', '/', $providerClass) . '.php';

        return is_file($path) ? $path : null;
    }

    private static function isBackupLikeFolder(string $folder): bool
    {
        $normal = strtolower(trim($folder));
        if ($normal === '') {
            return true;
        }

        return preg_match('/(?:^|[._\-\s])(before|backup|bak|old|copy|disabled|archive)(?:[._\-\s]|$)/i', $normal) === 1
            || str_contains($normal, 'no need');
    }
}
