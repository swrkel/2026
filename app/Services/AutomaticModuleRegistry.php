<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AutomaticModuleRegistry
{
    public const DISABLED_MARKER_PREFIX = '__sidebar_disabled__:';

    private const EXCLUDED_MODULES = [
        'core_ui',
        'customizer',
        'enterprise_framework',
        'identity_access',
        'superadmin',
        'translation',
    ];

    private static ?array $requestRegistry = null;

    /** Lightweight registry used by the Manage Side Bar modal. */
    private static ?array $requestSidebarRegistry = null;

    /** Installed standalone modules, including globally unavailable ones. */
    private static ?array $requestInstalledSidebarRegistry = null;

    /** Alias => canonical map built once per request from the lightweight registry. */
    private static ?array $requestCanonicalMap = null;

    /** Route prefix map built once per request from the lightweight registry. */
    private static ?array $requestRoutePrefixMap = null;

    /** Route-name prefix map built once per request without parsing route files. */
    private static ?array $requestRouteNamePrefixMap = null;

    private static array $requestSidebarEntries = [];

    public static function all(): array
    {
        if (self::$requestRegistry !== null) {
            return self::$requestRegistry;
        }

        $modulesPath = base_path('Modules');
        $statusPath = base_path('modules_statuses.json');
        $fingerprint = self::registryFingerprint($modulesPath, $statusPath);
        $manifestPath = base_path('bootstrap/cache/automatic_module_registry_manifest.php');

        $manifest = self::readManifest($manifestPath, $fingerprint, 'registry');
        if (is_array($manifest)) {
            return self::$requestRegistry = $manifest;
        }

        $registry = Cache::remember(
            'automatic_module_registry:' . $fingerprint,
            86400,
            static fn (): array => self::scanModules($modulesPath, $statusPath)
        );

        self::writeManifest($manifestPath, $fingerprint, 'registry', $registry);

        return self::$requestRegistry = $registry;
    }

    private static function registryFingerprint(string $modulesPath, string $statusPath): string
    {
        $parts = [
            // v13: the key comparison in discoverDeclaredPermissionItems() now
            // accepts a module key with or without underscores. THIS manifest
            // is the one that matters - all() reads it, and manageSections()
            // builds from all(). Bumping only the sections fingerprint left
            // this file untouched, so the rebuild produced identical contents
            // from a stale source.
            'automatic-module-registry-persistent-v23-v6-standard-sidebar-contract',
            is_file($statusPath) ? (string) md5_file($statusPath) : 'no-status',
        ];

        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $parts[] = basename($directory);
            $parts[] = (string) @filemtime($directory);
            $moduleJson = $directory . '/module.json';
            if (is_file($moduleJson)) {
                $parts[] = (string) md5_file($moduleJson);
            }
            foreach ([
                $directory . '/Routes',
                $directory . '/routes',
                $directory . '/Resources/views',
                $directory . '/Config',
                $directory . '/Permissions',
            ] as $metadataDirectory) {
                if (is_dir($metadataDirectory)) {
                    $parts[] = $metadataDirectory . ':' . (string) @filemtime($metadataDirectory);
                }
            }
        }

        return sha1(implode('|', $parts));
    }

    private static function readManifest(string $path, string $fingerprint, string $payloadKey): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        try {
            $data = include $path;
            if (!is_array($data)
                || ($data['fingerprint'] ?? null) !== $fingerprint
                || !is_array($data[$payloadKey] ?? null)) {
                return null;
            }

            return $data[$payloadKey];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function writeManifest(string $path, string $fingerprint, string $payloadKey, array $payload): void
    {
        try {
            $directory = dirname($path);
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }

            $content = '<?php return ' . var_export([
                'fingerprint' => $fingerprint,
                $payloadKey => $payload,
            ], true) . ';' . PHP_EOL;
            $temporary = $path . '.' . getmypid() . '.tmp';
            if (@file_put_contents($temporary, $content, LOCK_EX) !== false) {
                @rename($temporary, $path);
            }
        } catch (\Throwable $e) {
            // The normal Laravel cache remains a safe fallback on read-only hosts.
        }
    }

    /**
     * Fast module list for sidebar-management screens.
     *
     * The full registry parses every route/provider file to discover pages and
     * permissions. That is required by Super Admin > Manage, but it is far too
     * expensive for a small modal that only needs module names and aliases.
     * This registry reads only module.json and directory metadata.
     */
    public static function sidebarModules(): array
    {
        if (self::$requestSidebarRegistry !== null) {
            return self::$requestSidebarRegistry;
        }

        // Core/sidebar groups are always catalogue entries. Standalone modules
        // are exposed only when Laravel Modules explicitly marks the exact
        // module.json `name` as true in modules_statuses.json. Missing is NOT
        // treated as enabled. That distinction prevents Manage Side Bar from
        // advertising a module whose provider/routes will not boot.
        $registry = self::coreModules();
        foreach (self::installedSidebarModules() as $key => $module) {
            // Central-only modules (for example Simple Audit) belong exclusively
            // to the Central Super Admin UI and must never become business
            // Manage Side Bar/sidebar catalogue entries.
            if (!empty($module['central_only'])) {
                continue;
            }
            if (!empty($module['globally_active'])) {
                $registry[$key] = $module;
            }
        }

        ksort($registry, SORT_NATURAL | SORT_FLAG_CASE);

        return self::$requestSidebarRegistry = $registry;
    }

    /**
     * Installed standalone module catalogue with global availability metadata.
     *
     * This is intentionally read-only. It never changes modules_statuses.json
     * and never registers a service provider.
     */
    public static function installedSidebarModules(): array
    {
        if (self::$requestInstalledSidebarRegistry !== null) {
            return self::$requestInstalledSidebarRegistry;
        }

        $modulesPath = base_path('Modules');
        $statusPath = base_path('modules_statuses.json');
        $fingerprint = sha1(implode('|', [
            'automatic-module-installed-sidebar-registry-v8-central-only-contract',
            (string) @filemtime($modulesPath),
            is_file($statusPath) ? (string) md5_file($statusPath) : 'no-status',
        ]));

        return self::$requestInstalledSidebarRegistry = Cache::remember(
            'automatic_module_installed_sidebar_registry:' . $fingerprint,
            (int) config('global_performance.module_registry_ttl', 300),
            static function () use ($modulesPath, $statusPath): array {
                $directories = is_dir($modulesPath)
                    ? (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: [])
                    : [];

                return self::scanInstalledSidebarModules($directories, $statusPath);
            }
        );
    }

    /**
     * Return true/false for an installed standalone module, or null when the
     * key belongs only to a core/static sidebar section.
     */
    public static function standaloneGlobalAvailability(?string $value): ?bool
    {
        $canonical = self::canonicalKeyWithoutSidebarLookup($value);
        if ($canonical === '') {
            return null;
        }

        $module = self::installedSidebarModules()[$canonical] ?? null;

        return is_array($module) ? !empty($module['globally_active']) : null;
    }

    public static function findSidebar(?string $value): ?array
    {
        $canonical = self::canonicalKey($value);

        return self::sidebarModules()[$canonical] ?? null;
    }

    /**
     * Modules that must use the shared system sidebar renderer instead of an
     * older module-owned navigation partial. Their own navigation files remain
     * available inside module pages, but the Main System Sidebar follows the
     * same Manage Side Bar -> Manage Page -> Role contract as every new module.
     */
    public static function prefersStandardSidebar(?string $value): bool
    {
        $canonical = self::canonicalKey($value);

        return in_array($canonical, [
            'airline_ticketing_new',
            'airline_ticketing',
            'tea_estate_management',
            'restaurant_new',
        ], true);
    }

    public static function forget(): void
    {
        self::$requestRegistry = null;
        self::$requestSidebarRegistry = null;
        self::$requestInstalledSidebarRegistry = null;
        self::$requestCanonicalMap = null;
        self::$requestRoutePrefixMap = null;
        self::$requestRouteNamePrefixMap = null;
        self::$requestSidebarEntries = [];
    }

    private static function canonicalKeyWithoutSidebarLookup(?string $value): string
    {
        $normal = self::normalizeKey($value);
        if ($normal === '') {
            return '';
        }

        foreach (self::installedSidebarModules() as $key => $module) {
            if ($normal === $key || in_array($normal, $module['aliases'] ?? [], true)) {
                return $key;
            }
        }

        return $normal;
    }

    public static function normalizeKey(?string $value): string
    {
        $value = trim((string) $value);
        // Preserve acronyms while splitting CamelCase: HRManager => hr_manager, MPCS => mpcs.
        $value = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $value);
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', (string) $value);
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);

        return trim((string) preg_replace('/_+/', '_', (string) $value), '_');
    }

    public static function canonicalKey(?string $value): string
    {
        $normal = self::normalizeKey($value);
        if ($normal === '') {
            return '';
        }

        $modules = self::sidebarModules();
        if (isset($modules[$normal])) {
            return $normal;
        }

        if (self::$requestCanonicalMap === null) {
            self::$requestCanonicalMap = [];
            foreach ($modules as $key => $module) {
                self::$requestCanonicalMap[$key] = $key;
                foreach ($module['aliases'] ?? [] as $alias) {
                    $alias = self::normalizeKey((string) $alias);
                    if ($alias !== '') {
                        self::$requestCanonicalMap[$alias] = $key;
                    }
                }
            }
        }

        if (isset(self::$requestCanonicalMap[$normal])) {
            return self::$requestCanonicalMap[$normal];
        }

        // Keep canonical identity stable even when a standalone module is
        // installed but globally unavailable. This lets the business-level
        // policy recognise and block an old saved value without making that
        // module visible in the active catalogue.
        foreach (self::installedSidebarModules() as $key => $module) {
            if ($normal === $key || in_array($normal, $module['aliases'] ?? [], true)) {
                return $key;
            }
        }

        return str_ends_with($normal, '_module') ? substr($normal, 0, -7) : $normal;
    }

    public static function find(?string $value): ?array
    {
        // Runtime permission/sidebar checks only need the lightweight registry.
        // Loading the full route/page registry here added almost 1 MB of cached
        // data to ordinary requests, including Super Admin business listing.
        return self::findSidebar($value);
    }

    public static function moduleKeyFromController(?string $actionName): ?string
    {
        if (preg_match('/^Modules\\\\([^\\\\]+)\\\\/i', (string) $actionName, $match)) {
            $canonical = self::canonicalKey($match[1]);
            if (isset(self::sidebarModules()[$canonical])) {
                return $canonical;
            }
        }

        return null;
    }

    public static function moduleKeyFromRouteName(?string $routeName): ?string
    {
        $routeName = trim((string) $routeName);
        if ($routeName === '') {
            return null;
        }

        // Runtime access checks do not need the full registry, which parses all
        // module route/provider files. Most module route names begin with the
        // module key or alias, so resolve that prefix from the lightweight
        // module.json registry instead.
        if (self::$requestRouteNamePrefixMap === null) {
            $map = [];
            $ambiguous = [];

            foreach (self::sidebarModules() as $key => $module) {
                $prefixes = array_merge(
                    [$key, $module['folder'] ?? '', $module['key'] ?? ''],
                    $module['aliases'] ?? [],
                    $module['route_prefixes'] ?? []
                );

                foreach ($prefixes as $prefix) {
                    $normalized = self::normalizeKey((string) $prefix);
                    if ($normalized === '') {
                        continue;
                    }

                    if (isset($map[$normalized]) && $map[$normalized] !== $key) {
                        $ambiguous[$normalized] = true;
                        continue;
                    }

                    $map[$normalized] = $key;
                }
            }

            foreach (array_keys($ambiguous) as $prefix) {
                unset($map[$prefix]);
            }

            self::$requestRouteNamePrefixMap = $map;
        }

        $firstSegment = explode('.', $routeName, 2)[0] ?? '';
        $normalized = self::normalizeKey($firstSegment);

        return $normalized !== ''
            ? (self::$requestRouteNamePrefixMap[$normalized] ?? null)
            : null;
    }

    public static function routePrefixMap(): array
    {
        if (self::$requestRoutePrefixMap !== null) {
            return self::$requestRoutePrefixMap;
        }

        $map = [];
        foreach (self::sidebarModules() as $key => $module) {
            foreach ($module['route_prefixes'] ?? [] as $prefix) {
                $normalized = self::normalizeKey($prefix);
                if ($normalized !== '') {
                    $map[$normalized] = array_values(array_unique(array_merge($map[$normalized] ?? [], [$key])));
                }
            }
        }

        return self::$requestRoutePrefixMap = $map;
    }

    /**
     * Stable business permission key for the current module route.
     *
     * Missing keys remain enabled for backward compatibility; once the Manage
     * page saves a discovered key, the same key is used by the request guard.
     */
    public static function permissionKeyForRoute(
        ?string $moduleKey,
        ?string $routeName = null,
        ?string $path = null
    ): ?string {
        $moduleKey = self::canonicalKey($moduleKey);
        if ($moduleKey === '') {
            return null;
        }

        $module = self::findSidebar($moduleKey) ?? [];
        $fullModule = self::all()[$moduleKey] ?? [];
        $normalizedPath = trim((string) preg_replace(
            '#/+#',
            '/',
            (string) preg_replace('/\{[^}]+\}/', '', trim((string) $path, '/ '))
        ), '/ ');

        if ($normalizedPath !== '') {
            foreach ((array) ($fullModule['permission_items'] ?? []) as $permissionItem) {
                if (($permissionItem['type'] ?? '') !== 'page') {
                    continue;
                }
                foreach ((array) ($permissionItem['route_paths'] ?? []) as $candidatePath) {
                    $candidatePath = trim((string) preg_replace(
                        '#/+#',
                        '/',
                        (string) preg_replace('/\{[^}]+\}/', '', trim((string) $candidatePath, '/ '))
                    ), '/ ');
                    if ($candidatePath !== '' && $candidatePath === $normalizedPath) {
                        return self::normalizeKey((string) ($permissionItem['key'] ?? '')) ?: null;
                    }
                }
            }
        }

        $identityPrefixes = array_values(array_unique(array_filter(array_map(
            [self::class, 'normalizeKey'],
            array_merge(
                [$moduleKey, $module['folder'] ?? '', $module['key'] ?? ''],
                $module['aliases'] ?? [],
                $module['route_prefixes'] ?? []
            )
        ))));

        $page = trim((string) $routeName, '. ');
        if ($page === '') {
            $page = trim((string) $path, '/ ');
            $page = preg_replace('/\{[^}]+\}/', '', str_replace('/', '.', $page));
        }

        if ($page === '') {
            $page = 'dashboard';
        }

        $segments = array_values(array_filter(explode('.', str_replace(['/', '-'], '.', $page))));
        if ($segments !== [] && in_array(self::normalizeKey($segments[0]), $identityPrefixes, true)) {
            array_shift($segments);
        }

        $pageKey = self::routePermissionPageKey(implode('.', $segments));
        $derivedKey = $moduleKey . '_' . $pageKey;

        foreach ((array) ($fullModule['permission_items'] ?? []) as $permissionItem) {
            if (self::normalizeKey((string) ($permissionItem['key'] ?? '')) === $derivedKey) {
                return $derivedKey;
            }
        }

        return null; // Not an automatically managed menu/tab permission.
    }

    public static function sidebarEntries(array $layoutFiles = []): array
    {
        $layoutFiles = array_values(array_unique(array_merge(
            $layoutFiles,
            glob(resource_path('views/layouts/partials/sidebar-sections/*.blade.php')) ?: [],
            glob(resource_path('views/layouts/sidebar-sections/*.blade.php')) ?: []
        )));

        $fingerprintParts = ['automatic-sidebar-entries-v12-central-only-contract', implode('|', array_keys(self::all()))];
        foreach ($layoutFiles as $layoutFile) {
            $fingerprintParts[] = $layoutFile . ':' . (string) @filemtime($layoutFile);
        }
        $fingerprint = sha1(implode('|', $fingerprintParts));

        if (isset(self::$requestSidebarEntries[$fingerprint])) {
            return self::$requestSidebarEntries[$fingerprint];
        }

        return self::$requestSidebarEntries[$fingerprint] = Cache::remember(
            'automatic_module_sidebar_entries:' . $fingerprint,
            3600,
            static function () use ($layoutFiles): array {
                $sources = '';
                foreach ($layoutFiles as $layoutFile) {
                    if (is_file($layoutFile)) {
                        $sources .= "\n" . (string) @file_get_contents($layoutFile);
                    }
                }

                $entries = [];
                foreach (self::all() as $key => $module) {
                    // Central-only modules are rendered only by the Central
                    // Super Admin sidebar and must never appear in a business.
                    if (!empty($module['central_only'])) {
                        continue;
                    }

                    // Core application sections already have hand-built sidebar
                    // menus. They belong in Manage Side Bar and route guards,
                    // but must never receive an automatic duplicate menu entry.
                    if (!empty($module['core'])) {
                        continue;
                    }

                    if (self::isAlreadyIntegrated($module, $sources)) {
                        continue;
                    }

                    $entries[$key] = $module;
                }

                uasort($entries, static fn (array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));

                return $entries;
            }
        );
    }

    /**
     * Return the page/tab metadata used by both Manage Page and the automatic
     * sidebar. Static module declarations remain authoritative. A runtime route
     * fallback is used only when an older standalone module exposes no static
     * page metadata at all (for example config/controller-driven sidebars).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function effectivePermissionItems(string $moduleKey, array $module = []): array
    {
        $moduleKey = self::normalizeKey($moduleKey);
        if ($moduleKey === '') {
            return [];
        }

        if ($module === []) {
            $module = self::all()[$moduleKey]
                ?? self::sidebarModules()[$moduleKey]
                ?? [];
        }

        $declared = array_values(array_filter(
            (array) ($module['permission_items'] ?? []),
            static fn ($item): bool => is_array($item) && !empty($item['key'])
        ));
        if ($declared !== []) {
            return $declared;
        }

        return self::runtimeNavigationPermissionItems($moduleKey, $module);
    }

    /**
     * Discover safe top-level GET pages from Laravel's already-booted route
     * collection. This is deliberately conservative: parameterised actions,
     * API/AJAX/export/print helpers and CRUD action endpoints are not promoted
     * to Manage Page. It exists only as a compatibility bridge for old modules
     * whose navigation is generated dynamically and therefore cannot be parsed
     * from Blade source.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function runtimeNavigationPermissionItems(string $moduleKey, array $module): array
    {
        try {
            if (!app()->bound('router')) {
                return [];
            }
            $routes = app('router')->getRoutes();
        } catch (\Throwable $e) {
            return [];
        }

        $installed = self::installedSidebarModules()[$moduleKey] ?? [];
        $folder = trim((string) ($module['folder'] ?? $installed['folder'] ?? ''));
        $aliases = array_values(array_unique(array_filter(array_map(
            [self::class, 'normalizeKey'],
            array_merge(
                [$moduleKey, $folder, str_replace('_', '', $moduleKey)],
                (array) ($module['aliases'] ?? []),
                (array) ($installed['aliases'] ?? []),
                self::legacyAliasesFor($moduleKey)
            )
        ))));

        $prefixes = [];
        foreach (array_merge(
            (array) ($module['route_prefixes'] ?? []),
            (array) ($installed['route_prefixes'] ?? []),
            [str_replace('_', '-', $moduleKey), $moduleKey]
        ) as $prefix) {
            $prefix = strtolower(trim((string) $prefix, '/ '));
            if ($prefix !== '' && !str_contains($prefix, '{')) {
                $prefixes[$prefix] = true;
            }
        }
        foreach ($aliases as $alias) {
            $dashed = str_replace('_', '-', $alias);
            if ($dashed !== '') {
                $prefixes[$dashed] = true;
            }
        }
        $prefixes = array_keys($prefixes);
        usort($prefixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $namespacePrefix = ($folder !== '' && $folder !== '__core__')
            ? 'Modules\\' . $folder . '\\'
            : '';

        $items = [];
        $seenPaths = [];
        $seenKeys = [];

        foreach ($routes as $route) {
            try {
                $methods = array_map('strtoupper', (array) $route->methods());
                if (!in_array('GET', $methods, true)) {
                    continue;
                }

                $uri = strtolower(trim((string) $route->uri(), '/ '));
                if ($uri === '' || str_contains($uri, '{') || str_starts_with($uri, 'api/')) {
                    continue;
                }

                $routeName = trim((string) ($route->getName() ?? ''), '. ');
                $actionName = trim((string) $route->getActionName());

                $belongs = false;
                if ($namespacePrefix !== '' && str_starts_with($actionName, $namespacePrefix)) {
                    $belongs = true;
                }
                if (!$belongs) {
                    foreach ($prefixes as $prefix) {
                        if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
                            $belongs = true;
                            break;
                        }
                    }
                }
                if (!$belongs && $routeName !== '') {
                    $firstNameSegment = self::normalizeKey((string) explode('.', $routeName)[0]);
                    $belongs = in_array($firstNameSegment, $aliases, true);
                }
                if (!$belongs || self::isTechnicalRuntimePageRoute($routeName, $uri)) {
                    continue;
                }

                $localPath = $uri;
                foreach ($prefixes as $prefix) {
                    if ($uri === $prefix) {
                        $localPath = 'dashboard';
                        break;
                    }
                    if (str_starts_with($uri, $prefix . '/')) {
                        $localPath = substr($uri, strlen($prefix) + 1);
                        break;
                    }
                }

                $routePage = $routeName;
                if ($routePage !== '') {
                    $segments = array_values(array_filter(explode('.', $routePage)));
                    if ($segments !== [] && in_array(self::normalizeKey($segments[0]), $aliases, true)) {
                        array_shift($segments);
                    }
                    $routePage = implode('.', $segments);
                }
                $pageSource = $routePage !== '' ? $routePage : str_replace('/', '.', $localPath);
                $pageKey = self::routePermissionPageKey($pageSource);
                if ($pageKey === '' || self::isTechnicalRuntimePageKey($pageKey)) {
                    continue;
                }

                $permissionKey = $moduleKey . '_' . $pageKey;
                if (isset($seenKeys[$permissionKey]) || isset($seenPaths[$uri])) {
                    continue;
                }
                $seenKeys[$permissionKey] = true;
                $seenPaths[$uri] = true;

                $labelSource = $pageKey === 'dashboard' ? 'Dashboard' : $pageKey;
                $items[] = [
                    'key' => $permissionKey,
                    'label' => self::displayName($labelSource),
                    'type' => 'page',
                    'route_paths' => [$uri],
                    'selectors' => [],
                    'source' => 'runtime_route_fallback',
                ];

                // Keep runaway/generated route collections out of Manage Page.
                if (count($items) >= 40) {
                    break;
                }
            } catch (\Throwable $routeException) {
                continue;
            }
        }

        usort($items, static function (array $a, array $b): int {
            $aKey = (string) ($a['key'] ?? '');
            $bKey = (string) ($b['key'] ?? '');
            if (str_ends_with($aKey, '_dashboard')) return -1;
            if (str_ends_with($bKey, '_dashboard')) return 1;
            return strnatcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return $items;
    }

    private static function isTechnicalRuntimePageRoute(string $routeName, string $uri): bool
    {
        $haystack = strtolower(trim($routeName . ' ' . $uri));
        foreach ([
            'api.', '/api/', '.ajax', '/ajax', '.data', '/data', '.lookup', '/lookup',
            '.options', '/options', '.export', '/export', '.download', '/download',
            '.print', '/print', '.pdf', '/pdf', '.csv', '/csv', '.excel', '/excel',
            '.preview', '/preview', '.health', '/health', '.ping', '/ping',
            '.heartbeat', '/heartbeat', '.create', '/create', '.edit', '/edit',
        ] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function isTechnicalRuntimePageKey(string $pageKey): bool
    {
        $parts = array_values(array_filter(explode('_', self::normalizeKey($pageKey))));
        if ($parts === []) {
            return true;
        }
        $last = (string) end($parts);

        return in_array($last, [
            'store', 'update', 'delete', 'destroy', 'remove', 'save', 'ajax', 'data',
            'lookup', 'options', 'export', 'download', 'print', 'pdf', 'csv', 'excel',
            'preview', 'health', 'ping', 'heartbeat',
        ], true);
    }

    public static function explicitManagePermissionKeys(): array
    {
        $path = base_path('Modules/Superadmin/Resources/views/business/manage.blade.php');
        if (!is_file($path)) {
            return [];
        }

        $source = (string) @file_get_contents($path);
        $keys = [];

        preg_match_all('/Form::checkbox\(\s*[\'\"]([^\'\"]+)[\'\"]/', $source, $formMatches);
        preg_match_all('/<input[^>]+name=[\'\"]([^\'\"]+)[\'\"]/i', $source, $inputMatches);

        foreach (array_merge($formMatches[1] ?? [], $inputMatches[1] ?? []) as $key) {
            if (!str_contains((string) $key, '$') && !str_contains((string) $key, '[')) {
                $normalized = self::normalizeKey($key);
                if ($normalized !== '') {
                    $keys[$normalized] = true;
                }
            }
        }

        return array_keys($keys);
    }

    /**
     * Literal checkbox names rendered by the hand-written Manage page.
     *
     * explicitManagePermissionKeys() intentionally also contains ordinary
     * inputs because it is used to prevent duplicate automatic controls.  The
     * save path must be narrower: only real checkboxes may be converted to
     * boolean package permissions.
     *
     * @return array<int, string>
     */
    public static function explicitManageCheckboxKeys(): array
    {
        $path = base_path('Modules/Superadmin/Resources/views/business/manage.blade.php');
        if (!is_file($path)) {
            return [];
        }

        $source = (string) @file_get_contents($path);
        $keys = [];

        preg_match_all('/Form::checkbox\(\s*[\'\"]([^\'\"]+)[\'\"]/', $source, $formMatches);
        preg_match_all(
            '/<input(?=[^>]*\btype=[\'\"]checkbox[\'\"])(?=[^>]*\bname=[\'\"]([^\'\"]+)[\'\"])[^>]*>/i',
            $source,
            $inputMatches
        );

        foreach (array_merge($formMatches[1] ?? [], $inputMatches[1] ?? []) as $key) {
            if (str_contains((string) $key, '$') || str_contains((string) $key, '[')) {
                continue;
            }

            $normalized = self::normalizeKey($key);
            if ($normalized !== '') {
                $keys[$normalized] = true;
            }
        }

        return array_keys($keys);
    }

    public static function manageSections(): array
    {
        $modulesPath = base_path('Modules');
        $statusPath = base_path('modules_statuses.json');
        $manageBlade = base_path('Modules/Superadmin/Resources/views/business/manage.blade.php');
        $fingerprint = sha1(implode('|', [
            // v11: the key comparison in discoverDeclaredPermissionItems() now accepts
            // a module key with or without underscores. The manifest is cached
            // against this fingerprint, so the version must change for the fix to
            // take effect - deleting the cache file alone regenerates identical
            // contents from the same inputs.
            'automatic-manage-sections-persistent-v19-sidebar-v5',
            self::registryFingerprint($modulesPath, $statusPath),
            is_file($manageBlade) ? (string) md5_file($manageBlade) : 'no-manage-blade',
        ]));
        $manifestPath = base_path('bootstrap/cache/automatic_module_manage_sections.php');
        $manifest = self::readManifest($manifestPath, $fingerprint, 'sections');
        if (is_array($manifest)) {
            return $manifest;
        }

        $explicit = array_fill_keys(self::explicitManagePermissionKeys(), true);
        $sections = [];

        foreach (self::all() as $key => $module) {
            // Central-only modules are not assignable to tenant businesses and
            // therefore do not belong on Manage Page/Role business controls.
            if (!empty($module['central_only'])) {
                continue;
            }

            $items = [];
            $parentKey = $key . '_module';
            $parentCandidates = array_merge([$key, $parentKey], $module['aliases'] ?? []);
            $parentExplicit = false;
            foreach ($parentCandidates as $parentCandidate) {
                if (isset($explicit[self::normalizeKey((string) $parentCandidate)])) {
                    $parentExplicit = true;
                    $parentKey = self::normalizeKey((string) $parentCandidate);
                    break;
                }
            }

            if (! $parentExplicit) {
                $items[] = [
                    'key' => $parentKey,
                    'label' => $module['title'] . ' Module',
                    'type' => 'module',
                ];
            }

            foreach (self::effectivePermissionItems($key, $module) as $item) {
                if (!isset($explicit[self::normalizeKey($item['key'] ?? '')])) {
                    $items[] = $item;
                }
            }

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'module_key' => $key,
                'title' => $module['title'],
                'parent_explicit' => $parentExplicit,
                'parent_key' => $parentKey,
                'parent_candidates' => array_values(array_unique(array_filter(array_map(
                    [self::class, 'normalizeKey'],
                    $parentCandidates
                )))),
                'items' => $items,
            ];
        }

        usort($sections, static fn (array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));
        self::writeManifest($manifestPath, $fingerprint, 'sections', $sections);

        return $sections;
    }

    public static function markerFor(string $moduleKey): string
    {
        return self::DISABLED_MARKER_PREFIX . self::canonicalKey($moduleKey);
    }

    public static function markerModuleKey(?string $value): ?string
    {
        $value = (string) $value;
        if (!str_starts_with($value, self::DISABLED_MARKER_PREFIX)) {
            return null;
        }

        $key = self::canonicalKey(substr($value, strlen(self::DISABLED_MARKER_PREFIX)));

        return $key !== '' ? $key : null;
    }

    /**
     * Build one hand-written/core sidebar definition. These definitions are
     * catalogue metadata only: no module controller, route or view is changed.
     */
    /*
     | $permissionItems lets a CORE module publish permissions.
     |
     | Core modules - those without a Modules/<Name> folder - always returned
     | an empty permission_items, so Manage New showed them as "0 of 0
     | enabled" with nothing to configure. Their settings lived only in the old
     | Manage page's section blades.
     |
     | Modules with a folder declare theirs in Config/module_permissions.php.
     | A core module has nowhere to put such a file, so it declares them here.
     */
    private static function coreSidebarModule(
        string $key,
        string $title,
        array $aliases = [],
        array $routePrefixes = [],
        string $primaryUrl = '',
        array $permissionItems = []
    ): array {
        $key = self::normalizeKey($key);
        $defaultAliases = [$key];
        if (! str_ends_with($key, '_module')
            && ! str_starts_with($key, 'enable_')
            && ! str_ends_with($key, '_page')) {
            $defaultAliases[] = $key . '_module';
        }
        $aliases = array_values(array_unique(array_filter(array_map(
            [self::class, 'normalizeKey'],
            array_merge($defaultAliases, $aliases)
        ))));
        $routePrefixes = array_values(array_unique(array_filter(array_map(static function ($prefix): string {
            return trim((string) $prefix, '/ ');
        }, $routePrefixes))));

        return [
            'key' => $key,
            'folder' => '__core__',
            'title' => $title,
            'aliases' => $aliases,
            'route_files' => [],
            'route_names' => [],
            'route_name_prefixes' => [],
            'route_prefixes' => $routePrefixes,
            'permission_items' => array_values(array_filter(
                array_map(static function ($item): ?array {
                    if (! is_array($item) || empty($item['key'])) {
                        return null;
                    }
                    return [
                        'key' => self::normalizeKey((string) $item['key']),
                        'label' => (string) ($item['label'] ?? $item['key']),
                        // ?? before the check, not inside it: reading
                        // $item['type'] to decide whether to use it still
                        // triggers "Undefined array key" when it is absent.
                        'type' => in_array(($item['type'] ?? 'page'), ['page', 'tab'], true)
                            ? ($item['type'] ?? 'page')
                            : 'page',
                        'route_paths' => [],
                        'selectors' => [],
                        'source' => 'core_module',
                    ];
                }, $permissionItems)
            )),
            'sidebar_views' => [],
            'primary_url' => $primaryUrl !== ''
                ? '/' . ltrim($primaryUrl, '/')
                : ($routePrefixes !== [] ? '/' . $routePrefixes[0] : '#'),
            // Already rendered by the legacy/core sidebar. Never auto-append a
            // duplicate menu item.
            'core' => true,
        ];
    }

    /**
     * Historical top-level sidebar sections that do not own a Modules/<Name>
     * folder. Keeping them in the shared registry makes them appear in Manage
     * Side Bar and lets one central visibility policy control the old sidebar.
     *
     * Similar standalone modules remain independent (for example CRM (Core)
     * versus CRM Module, and old Cheque Writing versus Chequer).
     */
    private static function legacyCoreSidebarModules(): array
    {
        return [
            'enable_crm' => self::coreSidebarModule(
                'enable_crm',
                'CRM (Core)',
                ['core_crm', 'legacy_crm'],
                ['crm', 'crm-activity', 'crmgroups'],
                '/crm'
            ),
            'list_credit_sales_page' => self::coreSidebarModule(
                'list_credit_sales_page',
                'List Credit Sales',
                ['list_credit_sales', 'credit_sales_page'],
                ['contacts/credit-sales'],
                '/contacts/credit-sales'
            ),
            // Ezy Products reuses core Product/Unit/Category URLs. It therefore
            // has no URL prefix of its own; the authoritative PHP variable gate
            // hides only this sidebar section without blocking core Products.
            'ezy_products' => self::coreSidebarModule(
                'ezy_products',
                'Ezy Products',
                ['ezy_product'],
                []
            ),
            'list_easy_payment' => self::coreSidebarModule(
                'list_easy_payment',
                'List Easy Payments',
                ['list_easy_payments'],
                ['property/easy-payments'],
                '/property/easy-payments'
            ),
            'issue_customer_bill' => self::coreSidebarModule(
                'issue_customer_bill',
                'Issue Customer Bill',
                ['customer_bill'],
                ['petro/issue-customer-bill']
            ),
            'issue_customer_bill_vat' => self::coreSidebarModule(
                'issue_customer_bill_vat',
                'Issue Customer Bill VAT',
                ['customer_bill_vat'],
                ['petro/issue-customer-bill-with-vat']
            ),
            'petro_quota_module' => self::coreSidebarModule(
                'petro_quota_module',
                'Petro Quota',
                ['petro_quota'],
                ['petro-quota']
            ),
            'tpos_module' => self::coreSidebarModule(
                'tpos_module',
                'TPOS',
                ['tpos'],
                ['tpos']
            ),
            'payday' => self::coreSidebarModule(
                'payday',
                'Payday',
                ['pay_day'],
                ['payday']
            ),
            'post_dated_cheque' => self::coreSidebarModule(
                'post_dated_cheque',
                'Post Dated Cheques',
                ['post_dated_cheques'],
                ['post-dated-cheques'],
                '',
                /*
                 | IS2049. These three keys are the ones the application
                 | already checks - taken from section_02.blade.php on the old
                 | Manage page, where they have always lived.
                 |
                 | Using the existing names matters: a new name would save
                 | happily and control nothing, which is what happened with
                 | tank_dip_chart - set to 0, and the tab kept appearing.
                 */
                [
                    ['key' => 'add_pd_cheque', 'label' => 'Add PD Cheques'],
                    ['key' => 'show_post_dated_cheque', 'label' => 'Show Post Dated Cheque Check Box in Account Transfers'],
                    ['key' => 'update_post_dated_cheque', 'label' => 'Update Post Dated on the Same Date in Account'],
                ]
            ),
            'realize_cheque' => self::coreSidebarModule(
                'realize_cheque',
                'Realize Cheque',
                ['realise_cheque'],
                ['realize-cheque']
            ),
            'backup_module' => self::coreSidebarModule(
                'backup_module',
                'Backup',
                ['backup'],
                ['backup']
            ),
            'enable_booking' => self::coreSidebarModule(
                'enable_booking',
                'Bookings',
                ['booking', 'bookings'],
                ['bookings']
            ),
            'kitchen' => self::coreSidebarModule(
                'kitchen',
                'Kitchen',
                ['kitchen_module'],
                ['kitchen']
            ),
            'orders' => self::coreSidebarModule(
                'orders',
                'Orders',
                ['orders_module'],
                ['orders']
            ),
            'notification_template_module' => self::coreSidebarModule(
                'notification_template_module',
                'Notification Templates',
                ['notification_templates', 'notification_template'],
                ['notification-templates']
            ),
            // This is the legacy/core Cheque Writing switch. It intentionally
            // does not alias to the newer standalone Chequer module.
            'enable_cheque_writing' => self::coreSidebarModule(
                'enable_cheque_writing',
                'Cheque Writing (Old)',
                ['cheque_writing', 'cheque_writing_old'],
                []
            ),
        ];
    }

    /**
     * Core application modules that live under app/resources rather than a
     * Modules/<Name> folder. They still use the same Manage Side Bar and
     * direct-route protection as standalone modules.
     */
    private static function coreModules(): array
    {
        $modules = [
            'accounting_module' => self::coreSidebarModule(
                'accounting_module',
                'Accounting Module',
                ['access_account', 'account', 'accounts', 'accounting'],
                ['accounting-module', 'account', 'accounts'],
                '/accounting-module/journal'
            ),
            'contact_module' => self::coreSidebarModule(
                'contact_module',
                'Contact Module',
                ['contact', 'contacts'],
                [
                    'contacts',
                    'contact-group',
                    'customer-group',
                    'customer-reference',
                    'customer-statement',
                    'outstanding-received-report',
                    'issued-payment-details',
                    'returned-cheque-details',
                    'contact-user-activity',
                    'product-bind-supplier',
                    'list-product-bind',
                    'product-bind',
                    'import-balance',
                ],
                '/contacts'
            ),
        ];

        return array_merge($modules, self::legacyCoreSidebarModules());
    }

    private static function scanInstalledSidebarModules(array $directories, string $statusPath): array
    {
        $statuses = [];
        if (is_file($statusPath)) {
            $decoded = json_decode((string) @file_get_contents($statusPath), true);
            $statuses = is_array($decoded) ? $decoded : [];
        }

        $registry = [];
        foreach ($directories as $directory) {
            $folder = basename($directory);
            $key = self::normalizeKey($folder);

            if ($key === '' || in_array($key, self::EXCLUDED_MODULES, true) || self::looksLikeBackupModuleFolder($folder)) {
                continue;
            }

            // Match the full registry's basic eligibility without reading route
            // source. Empty/internal folders stay outside Manage Side Bar.
            $hasRoutes = is_dir($directory . '/Routes')
                || is_dir($directory . '/routes')
                || is_file($directory . '/Http/routes.php')
                || is_file($directory . '/routes.php');
            $hasViews = is_dir($directory . '/Resources/views');
            if (!$hasRoutes && !$hasViews) {
                continue;
            }

            $moduleJson = [];
            $moduleJsonPath = $directory . '/module.json';
            if (is_file($moduleJsonPath)) {
                $decoded = json_decode((string) @file_get_contents($moduleJsonPath), true);
                $moduleJson = is_array($decoded) ? $decoded : [];
            }

            $moduleName = trim((string) ($moduleJson['name'] ?? ''));
            $alias = trim((string) ($moduleJson['alias'] ?? ''));
            $title = self::displayTitleForModule($key, $moduleName !== '' ? $moduleName : $folder);

            // Nwidart activation is keyed by module.json name. Missing and false
            // are both unavailable, but kept distinct for diagnostics. A folder
            // without module.json cannot be assumed globally active.
            $hasGlobalStatus = $moduleName !== '' && array_key_exists($moduleName, $statuses);
            $globallyActive = $hasGlobalStatus && $statuses[$moduleName] === true;
            $globalStatus = !$hasGlobalStatus ? 'missing' : ($globallyActive ? 'active' : 'disabled');

            $aliases = array_merge([
                $key,
                $key . '_module',
                str_replace('_', '', $key),
                str_replace('_', '', $key) . '_module',
                str_replace('_', '-', $key),
                strtolower($folder),
                self::normalizeKey($moduleName),
                self::normalizeKey($alias),
            ], self::legacyAliasesFor($key));

            $routePrefixes = array_values(array_unique(array_filter([
                trim($alias, '/ '),
                str_replace('_', '-', $key),
                $key,
            ])));
            $primaryUrl = self::primaryUrlForModule($key, $routePrefixes);

            $registry[$key] = [
                'key' => $key,
                'folder' => $folder,
                'module_name' => $moduleName,
                'title' => $title,
                'aliases' => array_values(array_unique(array_filter(array_map([self::class, 'normalizeKey'], $aliases)))),
                'route_prefixes' => $routePrefixes,
                'primary_url' => $primaryUrl,
                'central_only' => !empty($moduleJson['central_only']),
                'globally_active' => $globallyActive,
                'global_status' => $globalStatus,
            ];
        }

        ksort($registry, SORT_NATURAL | SORT_FLAG_CASE);

        return $registry;
    }

    private static function looksLikeBackupModuleFolder(string $folder): bool
    {
        return (bool) preg_match('/(?:^|[._-])(before|backup|bak|old|copy|disabled)(?:[._-]|$)/i', $folder);
    }

    private static function scanModules(string $modulesPath, string $statusPath): array
    {
        if (!is_dir($modulesPath)) {
            return self::coreModules();
        }

        $statuses = [];
        if (is_file($statusPath)) {
            $decoded = json_decode((string) @file_get_contents($statusPath), true);
            $statuses = is_array($decoded) ? $decoded : [];
        }

        $registry = self::coreModules();
        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $folder = basename($directory);
            $key = self::normalizeKey($folder);

            if ($key === '' || in_array($key, self::EXCLUDED_MODULES, true) || self::looksLikeBackupModuleFolder($folder)) {
                continue;
            }

            $moduleJsonPath = $directory . '/module.json';
            $moduleJson = [];
            if (is_file($moduleJsonPath)) {
                $decoded = json_decode((string) @file_get_contents($moduleJsonPath), true);
                $moduleJson = is_array($decoded) ? $decoded : [];
            }
            $moduleName = trim((string) ($moduleJson['name'] ?? ''));

            // Full Manage/permission discovery must follow the same exact
            // global activation contract as the lightweight sidebar registry.
            // Missing status is intentionally NOT equivalent to enabled.
            if ($moduleName === ''
                || !array_key_exists($moduleName, $statuses)
                || $statuses[$moduleName] !== true) {
                continue;
            }

            $module = self::scanOneModule($directory, $folder, $key);
            if ($module !== null) {
                $registry[$key] = $module;
            }
        }

        ksort($registry, SORT_NATURAL | SORT_FLAG_CASE);

        return $registry;
    }

    private static function scanOneModule(string $directory, string $folder, string $key): ?array
    {
        $moduleJson = [];
        $moduleJsonPath = $directory . '/module.json';
        if (is_file($moduleJsonPath)) {
            $decoded = json_decode((string) @file_get_contents($moduleJsonPath), true);
            $moduleJson = is_array($decoded) ? $decoded : [];
        }

        $routeFiles = [];
        foreach ([$directory . '/Routes', $directory . '/routes'] as $routesDirectory) {
            if (is_dir($routesDirectory)) {
                foreach (glob($routesDirectory . '/*.php') ?: [] as $routeFile) {
                    if (!preg_match('/^api/i', basename($routeFile))) {
                        $routeFiles[] = $routeFile;
                    }
                }
            }
        }
        foreach ([$directory . '/Http/routes.php', $directory . '/routes.php'] as $routeFile) {
            if (is_file($routeFile)) {
                $routeFiles[] = $routeFile;
            }
        }
        $routeFiles = array_values(array_unique($routeFiles));
        $routeMetadataFiles = array_values(array_unique(array_merge(
            glob($directory . '/Providers/*RouteServiceProvider*.php') ?: [],
            $routeFiles
        )));

        $sidebarRelativePaths = [
            'layouts_v2/partials/sidebar',
            'layouts/partials/sidebar',
            'layouts/sidebar',
            'partials/sidebar_v2',
            'partials/sidebar_menu',
            'partials/sidebar',
            'navigation/sidebar_items',
            'sidebar',
        ];
        $existingSidebarRelativePaths = self::discoverSidebarRelativePaths(
            $directory,
            $sidebarRelativePaths
        );

        if ($routeFiles === [] && $existingSidebarRelativePaths === []) {
            return null;
        }

        $alias = trim((string) ($moduleJson['alias'] ?? ''));
        $title = trim((string) ($moduleJson['name'] ?? $folder));
        $title = self::displayTitleForModule($key, $title);

        $aliases = [
            $key,
            $key . '_module',
            str_replace('_', '', $key),
            str_replace('_', '', $key) . '_module',
            str_replace('_', '-', $key),
            strtolower($folder),
            self::normalizeKey($alias),
        ];
        $aliases = array_merge($aliases, self::legacyAliasesFor($key));

        $viewNamespaces = [];
        foreach (glob($directory . '/Providers/*.php') ?: [] as $providerFile) {
            $source = (string) @file_get_contents($providerFile);
            preg_match_all('/loadViewsFrom\([^;]+?,\s*[\'\"]([^\'\"]+)[\'\"]\s*\)/s', $source, $matches);
            foreach ($matches[1] ?? [] as $namespace) {
                $viewNamespaces[] = trim((string) $namespace);
            }
        }
        $viewNamespaces = array_merge($viewNamespaces, [
            strtolower($alias),
            str_replace('-', '', strtolower($alias)),
            strtolower($folder),
            str_replace('_', '', $key),
        ]);
        $viewNamespaces = array_values(array_unique(array_filter($viewNamespaces)));

        $sidebarViews = [];
        foreach ($viewNamespaces as $namespace) {
            foreach ($existingSidebarRelativePaths as $relativePath) {
                $sidebarViews[] = $namespace . '::' . str_replace('/', '.', $relativePath);
            }
        }
        $sidebarViews = array_values(array_unique($sidebarViews));

        $routeNames = [];
        $routePrefixes = [];
        $directPaths = [];
        $namedDirectPaths = [];
        $namedRoutePaths = [];
        $resourcePaths = [];
        foreach ($routeMetadataFiles as $routeFile) {
            $source = (string) @file_get_contents($routeFile);

            preg_match_all('/->(?:name|as)\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $source, $nameMatches);
            $routeNames = array_merge($routeNames, $nameMatches[1] ?? []);

            preg_match_all('/(?:Route::|->)?prefix\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $source, $prefixMatches);
            preg_match_all('/prefix\(\s*config\([^,]+,\s*[\'"]([^\'"]+)[\'"]\s*\)\s*\)/', $source, $configPrefixMatches);
            preg_match_all('/[\'"]prefix[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $source, $groupPrefixMatches);
            $routePrefixes = array_merge(
                $routePrefixes,
                $prefixMatches[1] ?? [],
                $configPrefixMatches[1] ?? [],
                $groupPrefixMatches[1] ?? []
            );

            preg_match_all('/Route::(?:get|post|put|patch|delete|any|match)\(\s*[\'"]([^\'"]*)[\'"]/', $source, $pathMatches);
            $directPaths = array_merge($directPaths, $pathMatches[1] ?? []);

            preg_match_all(
                '/Route::(?:get|post|put|patch|delete|any|match)\(\s*[\'"]([^\'"]*)[\'"][^;]*?->(?:name|as)\(\s*[\'"]([^\'"]+)[\'"]\s*\)[^;]*;/s',
                $source,
                $namedPathMatches,
                PREG_SET_ORDER
            );
            foreach ($namedPathMatches as $namedPathMatch) {
                $namedPath = trim((string) ($namedPathMatch[1] ?? ''), '/ ');
                $namedRoute = trim((string) ($namedPathMatch[2] ?? ''), '. ');
                if ($namedRoute === '') {
                    continue;
                }
                $namedDirectPaths[$namedPath] = true;
                $namedRoutePaths[$namedRoute][] = $namedPath;
            }

            preg_match_all('/Route::(?:resource|apiResource)\(\s*[\'"]([^\'"]+)[\'"]/', $source, $resourceMatches);
            $resourcePaths = array_merge($resourcePaths, $resourceMatches[1] ?? []);
        }

        $routeNames = array_values(array_unique(array_filter(array_map('trim', $routeNames))));

        $identityKeys = array_values(array_unique(array_filter(array_map([self::class, 'normalizeKey'], array_merge(
            [$key, $alias, $folder, str_replace('_', '', $key)],
            $aliases
        )))));

        // Only retain route-group prefixes that identify this module. Generic
        // nested prefixes such as dashboard, reports, accounts or settings are
        // deliberately excluded because they otherwise collide with core pages.
        $routePrefixes = array_values(array_unique(array_filter(array_map(static function ($prefix) use ($identityKeys, $key): string {
            $prefix = trim((string) $prefix, '/ ');
            if ($prefix === '' || str_contains($prefix, '{') || $prefix === 'api') {
                return '';
            }

            $normalized = self::normalizeKey($prefix);
            $qualified = str_contains($prefix, '/')
                || in_array($normalized, $identityKeys, true)
                || str_starts_with($normalized, $key . '_')
                || str_ends_with($normalized, '_' . $key);

            return $qualified ? $prefix : '';
        }, $routePrefixes))));

        if ($alias !== '') {
            $routePrefixes[] = trim($alias, '/ ');
        }
        $routePrefixes[] = str_replace('_', '-', $key);
        $routePrefixes = array_values(array_unique(array_filter($routePrefixes)));

        $routeNamePrefixes = [];
        foreach ($routeNames as $routeName) {
            $fullPrefix = trim((string) $routeName, '. ');
            $firstPrefix = trim((string) explode('.', $fullPrefix)[0]);

            if ($fullPrefix !== '' && in_array(self::normalizeKey($fullPrefix), $identityKeys, true)) {
                $routeNamePrefixes[] = $fullPrefix;
            }
            if ($firstPrefix !== '' && in_array(self::normalizeKey($firstPrefix), $identityKeys, true)) {
                $routeNamePrefixes[] = $firstPrefix;
            }
        }
        $routeNamePrefixes = array_values(array_unique($routeNamePrefixes));

        $permissionItems = [];
        $seenPermissions = [];
        $permissionRoutes = $routeNames;
        $permissionRoutePaths = [];
        $sidebarNavigation = self::discoverSidebarNavigationReferences(
            $directory,
            $existingSidebarRelativePaths
        );
        foreach ($namedRoutePaths as $namedRoute => $paths) {
            foreach (array_values(array_unique($paths)) as $path) {
                $permissionRoutePaths[$namedRoute] = array_values(array_unique(array_merge(
                    $permissionRoutePaths[$namedRoute] ?? [],
                    self::candidateRoutePaths($path, $routePrefixes)
                )));
            }
        }

        // Laravel resource routes create seven runtime names from one source
        // declaration. Represent them as the four user-facing permission types
        // rather than duplicating GET/POST/PUT/DELETE implementation routes.
        foreach ($resourcePaths as $resourcePath) {
            $resourcePath = trim((string) $resourcePath, '/ ');
            if ($resourcePath === '') {
                continue;
            }
            $resourceActionPaths = [
                'view' => $resourcePath,
                'create' => $resourcePath . '/create',
                'edit' => $resourcePath . '/{id}/edit',
                'delete' => $resourcePath . '/{id}',
            ];
            foreach ($resourceActionPaths as $resourceAction => $resourceActionPath) {
                $resourcePermissionRoute = str_replace('/', '.', $resourcePath) . '.' . $resourceAction;
                $permissionRoutes[] = $resourcePermissionRoute;
                $permissionRoutePaths[$resourcePermissionRoute] = self::candidateRoutePaths(
                    $resourceActionPath,
                    $routePrefixes
                );
            }
        }

        // Include unnamed routes even when the module also has named routes.
        // Otherwise one unnamed page silently disappeared as soon as any other
        // page in the same module received a route name.
        foreach ($directPaths as $directPath) {
            $directPath = trim((string) $directPath, '/ ');
            if (isset($namedDirectPaths[$directPath])) {
                continue;
            }
            $permissionRoute = $directPath === ''
                ? 'dashboard'
                : preg_replace('/\{[^}]+\}/', '', str_replace('/', '.', $directPath));
            $permissionRoutes[] = $permissionRoute;
            $permissionRoutePaths[$permissionRoute] = self::candidateRoutePaths(
                $directPath,
                $routePrefixes
            );
        }

        $permissionRoutes = self::filterNavigationPermissionRoutes(
            $permissionRoutes,
            $permissionRoutePaths,
            $sidebarNavigation['route_names'],
            $sidebarNavigation['paths']
        );

        foreach (array_values(array_unique(array_filter($permissionRoutes))) as $routeName) {
            $page = self::stripRouteModulePrefix($routeName, $key, $routeNamePrefixes);
            $pageKey = self::routePermissionPageKey($page);

            $permissionKey = $key . '_' . $pageKey;
            if (isset($seenPermissions[$permissionKey])) {
                if (!empty($permissionRoutePaths[$routeName])) {
                    foreach ($permissionItems as &$existingPermissionItem) {
                        if (($existingPermissionItem['key'] ?? '') === $permissionKey) {
                            $existingPermissionItem['route_paths'] = array_values(array_unique(array_merge(
                                $existingPermissionItem['route_paths'] ?? [],
                                $permissionRoutePaths[$routeName]
                            )));
                            break;
                        }
                    }
                    unset($existingPermissionItem);
                }
                continue;
            }
            $seenPermissions[$permissionKey] = true;
            $permissionItems[] = [
                'key' => $permissionKey,
                'label' => self::navigationLabelForRoute(
                    $routeName,
                    $sidebarNavigation['labels']
                ) ?? self::displayName($pageKey),
                'type' => 'page',
                'route_paths' => $permissionRoutePaths[$routeName] ?? [],
                'selectors' => [],
            ];
        }

        foreach (self::discoverViewTabPermissionItems($directory, $key) as $tabItem) {
            $permissionKey = (string) ($tabItem['key'] ?? '');
            if ($permissionKey === '' || isset($seenPermissions[$permissionKey])) {
                continue;
            }
            $seenPermissions[$permissionKey] = true;
            $permissionItems[] = $tabItem;
        }

        $declaredPermissionItems = self::discoverDeclaredPermissionItems(
            $directory,
            $key,
            $routeNamePrefixes,
            $permissionRoutePaths
        );
        if ($declaredPermissionItems !== []) {
            // A module-owned manifest is authoritative. It lets every new
            // module publish its menu pages and real tabs without adding that
            // module to this registry, while keeping action-level permissions
            // (store/edit/delete/print/Ajax) out of the Manage page.
            $permissionItems = $declaredPermissionItems;
        }

        $primaryUrl = self::primaryUrlForModule($key, $routePrefixes);

        return [
            'key' => $key,
            'folder' => $folder,
            'title' => $title,
            'aliases' => array_values(array_unique(array_filter(array_map([self::class, 'normalizeKey'], $aliases)))),
            // Store project-relative paths so the persistent manifest is
            // portable between development, staging and production servers.
            'route_files' => array_map(static function (string $routeFile): string {
                $base = rtrim(base_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                return str_starts_with($routeFile, $base)
                    ? substr($routeFile, strlen($base))
                    : $routeFile;
            }, $routeFiles),
            'route_names' => $routeNames,
            'route_name_prefixes' => $routeNamePrefixes,
            'route_prefixes' => $routePrefixes,
            'permission_items' => $permissionItems,
            'sidebar_views' => $sidebarViews,
            'primary_url' => $primaryUrl,
            'central_only' => !empty($moduleJson['central_only']),
        ];
    }

    private static function routePermissionPageKey(?string $page): string
    {
        $pageKey = self::normalizeKey($page);
        if ($pageKey === '' || $pageKey === 'index') {
            return 'dashboard';
        }

        $parts = explode('_', $pageKey);
        $action = end($parts);
        $actionGroups = [
            'view' => ['index', 'show', 'list', 'view'],
            'create' => ['create', 'store', 'add'],
            'edit' => ['edit', 'update'],
            'delete' => ['destroy', 'delete', 'remove'],
        ];
        foreach ($actionGroups as $canonicalAction => $aliases) {
            if (in_array($action, $aliases, true)) {
                array_pop($parts);
                $parts[] = $canonicalAction;
                break;
            }
        }

        return implode('_', array_filter($parts)) ?: 'dashboard';
    }

    /**
     * Build URL candidates for the rendered-sidebar safety net. Controller and
     * route-name matching in middleware remains authoritative.
     */
    private static function candidateRoutePaths(string $path, array $routePrefixes): array
    {
        $path = trim($path, '/ ');
        $paths = [];

        if ($path !== '') {
            $paths[] = $path;
        }
        foreach ($routePrefixes as $prefix) {
            $prefix = trim((string) $prefix, '/ ');
            if ($prefix === '') {
                continue;
            }
            $paths[] = $path === '' ? $prefix : $prefix . '/' . $path;
        }

        return array_values(array_unique(array_filter($paths)));
    }

    /**
     * Discover client-side Bootstrap/custom tabs that do not own a Laravel
     * route. Static tab targets are business permissions; dynamic Blade targets
     * are skipped because they represent records rather than pages.
     */
    private static function discoverViewTabPermissionItems(string $directory, string $key): array
    {
        $viewsPath = $directory . '/Resources/views';
        if (!is_dir($viewsPath)) {
            return [];
        }

        $items = [];
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($viewsPath, \FilesystemIterator::SKIP_DOTS)
            );
        } catch (\Throwable $e) {
            return [];
        }

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with(strtolower($file->getFilename()), '.blade.php')) {
                continue;
            }

            $source = (string) @file_get_contents($file->getPathname());
            if ($source === '' || !preg_match('/(?:role|data-[a-z0-9_-]*tab|data-(?:bs-)?toggle)\s*=/i', $source)) {
                continue;
            }

            preg_match_all('/<(?:a|button)\b[^>]*>/is', $source, $tagMatches);
            foreach ($tagMatches[0] ?? [] as $tag) {
                if (!preg_match('/(?:role\s*=\s*[\'"]tab[\'"]|data-(?:bs-)?toggle\s*=\s*[\'"](?:tab|pill)[\'"]|data-[a-z0-9_-]*tab(?:\s*=|\s|>))/i', $tag)) {
                    continue;
                }

                $target = '';
                foreach ([
                    '/(?:href|data-target|data-bs-target)\s*=\s*[\'"]#([^\'"]+)[\'"]/i',
                    '/aria-controls\s*=\s*[\'"]([^\'"]+)[\'"]/i',
                    '/data-[a-z0-9_-]*tab\s*=\s*[\'"]#?([^\'"]+)[\'"]/i',
                ] as $targetPattern) {
                    if (preg_match($targetPattern, $tag, $targetMatch)) {
                        $target = trim((string) ($targetMatch[1] ?? ''));
                        break;
                    }
                }

                if ($target === ''
                    || str_contains($target, '$')
                    || str_contains($target, '{')
                    || str_contains($target, '}')) {
                    continue;
                }

                $targetKey = self::normalizeKey($target);
                if ($targetKey === '' || in_array($targetKey, ['tab', 'tabs', 'content'], true)) {
                    continue;
                }

                $permissionKey = $key . '_tab_' . $targetKey;
                if (!isset($items[$permissionKey])) {
                    $items[$permissionKey] = [
                        'key' => $permissionKey,
                        'label' => self::displayName($targetKey) . ' Tab',
                        'type' => 'tab',
                        'route_paths' => [],
                        'selectors' => [],
                    ];
                }
                $items[$permissionKey]['selectors'][] = '#' . $target;
                $items[$permissionKey]['selectors'] = array_values(array_unique(
                    $items[$permissionKey]['selectors']
                ));
            }
        }

        return array_values($items);
    }

    /**
     * Read the optional global module contract:
     * Modules/<Module>/Config/module_permissions.php.
     *
     * The file returns a flat list of module/page/tab definitions. The module
     * parent is generated centrally, so only page and tab entries are retained.
     */
    private static function discoverDeclaredPermissionItems(
        string $directory,
        string $key,
        array $routeNamePrefixes,
        array $permissionRoutePaths
    ): array {
        $manifest = $directory . '/Config/module_permissions.php';
        if (!is_file($manifest)) {
            return [];
        }

        try {
            $definitions = (static fn (string $file) => include $file)($manifest);
        } catch (\Throwable $e) {
            return [];
        }

        if (!is_array($definitions)) {
            return [];
        }
        if (is_array($definitions['items'] ?? null)) {
            $definitions = $definitions['items'];
        }

        $items = [];
        foreach ($definitions as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $type = strtolower(trim((string) ($definition['type'] ?? 'page')));

            /*
             | IS2101: accept 'permission' as a page-like item.
             |
             | Modules/ProductsNew declares products_pumper_dashboard_default with
             | type 'permission' - the "Show in Pumper Dashboard" control that the
             | old Manage page offered. Only 'page' and 'tab' were accepted here,
             | so that declaration was silently discarded and the checkbox never
             | appeared in Manage New, even though BusinessController already had
             | the code to act on it (IS2089).
             |
             | It is normalised to 'page' rather than carried through as its own
             | type, so it renders through the same path as the 2,113 existing
             | page items instead of an untested one. It is currently the only
             | declaration of this type in the estate.
             */
            if ($type === 'permission') {
                $type = 'page';
            }

            if (!in_array($type, ['page', 'tab'], true)) {
                continue;
            }

            $permissionKey = self::normalizeKey((string) ($definition['key'] ?? ''));

            /*
             | Accept the module key with or without its underscores.
             |
             | A module named ExpensesNew normalises to 'expenses_new', but the
             | generated Config/module_permissions.php declares its keys as
             | 'expensesnew_dashboard' - no underscore. The strict comparison
             | below discarded every one of them, so the module offered no page
             | permissions in Manage Page and therefore none on the Role screen.
             |
             | Measured: 31 modules affected, roughly 400 page permissions
             | silently dropped, including BeautySaloons (59), DistributionNew
             | (65) and ExpensesNew (65). Single-word modules such as Airline
             | were unaffected, which is why the mechanism appeared to work.
             */
            $keyCompact = str_replace('_', '', $key);
            $matchesKey = $permissionKey === $key
                || str_starts_with($permissionKey, $key . '_')
                || $permissionKey === $keyCompact
                || str_starts_with($permissionKey, $keyCompact . '_');

            if ($permissionKey === '' || !$matchesKey) {
                continue;
            }

            $label = trim((string) ($definition['label'] ?? ''));
            $routePaths = array_values(array_unique(array_filter(array_map(
                [self::class, 'normalizeNavigationPath'],
                (array) ($definition['route_paths'] ?? [])
            ))));

            if ($type === 'page' && $routePaths === []) {
                $pageTail = substr($permissionKey, strlen($key) + 1);
                foreach ($permissionRoutePaths as $routeName => $candidatePaths) {
                    $page = self::stripRouteModulePrefix(
                        (string) $routeName,
                        $key,
                        $routeNamePrefixes
                    );
                    $routePageKey = self::routePermissionPageKey($page);
                    $routePageStem = preg_replace(
                        '/_(?:view|index|show|list)$/',
                        '',
                        $routePageKey
                    );
                    if ($pageTail === $routePageKey || $pageTail === $routePageStem) {
                        $routePaths = array_values(array_unique(array_merge(
                            $routePaths,
                            (array) $candidatePaths
                        )));
                    }
                }
            }

            $items[$permissionKey] = [
                'key' => $permissionKey,
                'label' => $label !== '' ? $label : self::displayName(
                    substr($permissionKey, strlen($key) + 1)
                ),
                'type' => $type,
                'route_paths' => $routePaths,
                'selectors' => array_values(array_unique(array_filter(array_map(
                    'trim',
                    (array) ($definition['selectors'] ?? [])
                )))),
                /*
                 * MA-004: carry the 'source' marker through.
                 *
                 * This array is rebuilt from a fixed set of keys, so anything
                 * else declared in a module_permissions.php file was silently
                 * dropped here. The page permissions generated for the Role
                 * screen mark themselves with 'source' => 'module_pages' so the
                 * Super Admin Manage form can leave them out - that form posts
                 * every control as one json field, and 2,054 extra entries made
                 * the payload too large to survive the request.
                 *
                 * Without this line the marker never reaches the filter and the
                 * Manage page breaks again.
                 */
                'source' => isset($definition['source'])
                    ? (string) $definition['source']
                    : null,
            ];
        }

        return array_values($items);
    }

    /**
     * Locate every module-owned sidebar/menu/navigation Blade, including new
     * modules that do not use one of the historical fixed layout paths.
     */
    private static function discoverSidebarRelativePaths(
        string $directory,
        array $knownRelativePaths = []
    ): array {
        $viewsPath = $directory . '/Resources/views';
        $paths = [];

        foreach ($knownRelativePaths as $relativePath) {
            if (is_file($viewsPath . '/' . $relativePath . '.blade.php')) {
                $paths[$relativePath] = true;
            }
        }

        if (!is_dir($viewsPath)) {
            return array_keys($paths);
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($viewsPath, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file->isFile()
                    || !str_ends_with(strtolower($file->getFilename()), '.blade.php')) {
                    continue;
                }

                $source = (string) @file_get_contents($file->getPathname());
                if (!self::isSidebarNavigationSource($file->getPathname(), $source)) {
                    continue;
                }

                $relative = str_replace('\\', '/', substr(
                    $file->getPathname(),
                    strlen(rtrim($viewsPath, DIRECTORY_SEPARATOR)) + 1
                ));
                $relative = preg_replace('/\.blade\.php$/i', '', $relative);
                if ($relative !== '') {
                    $paths[$relative] = true;
                }
            }
        } catch (\Throwable $e) {
            // Known conventional paths remain a safe fallback.
        }

        return array_keys($paths);
    }

    /**
     * Identify module menu partials by their navigation markup as well as their
     * filename. New modules therefore require no registry/module-specific code.
     */
    private static function isSidebarNavigationSource(string $path, string $source): bool
    {
        if ($source === '') {
            return false;
        }

        $filename = basename(str_replace('\\', '/', $path));
        $normalizedPath = strtolower(str_replace('\\', '/', $path));

        /*
         | SIDEBAR-AUTO-V5
         |
         | A file called navigation.blade.php is very often an in-page tab bar.
         | AirlineTicketingNew has several of these and some use generic
         | Bootstrap nav/collapse classes. Those must never be injected into the
         | ERP's dark main sidebar merely because they contain links or nav-item.
         */
        if (preg_match(
            '/@extends\s*\(|<html\b|@section\s*\(\s*[\'\"]content[\'\"]/i',
            $source
        ) === 1) {
            return false;
        }

        $hasNavigationReference = preg_match(
            '/\broute\s*\(|\bRoute::has\s*\(|[\'\"]route[\'\"]\s*=>|'
            . '\b(?:url|secure_url|URL::to)\s*\(|\bhref\s*=/i',
            $source
        ) === 1;
        if (!$hasNavigationReference) {
            return false;
        }

        $isExplicitSidebarPath = str_contains($normalizedPath, '/sidebar/')
            || str_contains($normalizedPath, '/sidebars/')
            || str_contains($normalizedPath, '/menu/')
            || str_contains($normalizedPath, '/menus/')
            || str_contains($normalizedPath, '/layouts/partials/sidebar')
            || preg_match('/(?:sidebar|side_menu|sidemenu|menu)\b/i', $filename) === 1;

        $hasStrongSidebarMarkup = preg_match(
            '/data-sidebar-module\s*=|data-module-key\s*=|'
            . 'class\s*=\s*[\'\"][^\'\"]*(?:sidebar-menu|nav-sidebar|side-menu|main-menu|treeview-menu|metismenu|collapse-item)[^\'\"]*[\'\"]|'
            . 'data-widget\s*=\s*[\'\"]treeview[\'\"]|'
            . '<aside\b[^>]*class\s*=\s*[\'\"][^\'\"]*(?:sidebar|main-sidebar)[^\'\"]*[\'\"]/i',
            $source
        ) === 1;

        /*
         | "navigation" alone is never sufficient. It must live in an explicit
         | sidebar/menu path or carry strong sidebar-specific markup. Generic
         | nav-item/nav-link/data-toggle classes are deliberately excluded.
         */
        if (preg_match('/navigation/i', $filename)) {
            return $isExplicitSidebarPath || $hasStrongSidebarMarkup;
        }

        if ($isExplicitSidebarPath || $hasStrongSidebarMarkup) {
            return true;
        }

        return false;
    }

    /**
     * Read only the links intentionally exposed by a module's sidebar. Route
     * actions such as store/update/delete/Ajax are implementation details and
     * must not become automatic Manage permissions.
     *
     * @return array{
     *   route_names: array<int, string>,
     *   paths: array<int, string>,
     *   labels: array<string, string>
     * }
     */
    private static function discoverSidebarNavigationReferences(
        string $directory,
        array $sidebarRelativePaths
    ): array {
        $routeNames = [];
        $paths = [];
        $labels = [];

        foreach ($sidebarRelativePaths as $relativePath) {
            $file = $directory . '/Resources/views/' . $relativePath . '.blade.php';
            if (!is_file($file)) {
                continue;
            }

            $source = (string) @file_get_contents($file);
            preg_match_all('/\broute\(\s*[\'"]([^\'"]+)[\'"]/', $source, $routeMatches);
            preg_match_all('/[\'"]route[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $source, $routeArrayMatches);
            foreach (array_merge(
                $routeMatches[1] ?? [],
                $routeArrayMatches[1] ?? []
            ) as $routeName) {
                $routeName = trim((string) $routeName, '. ');
                if ($routeName !== '') {
                    $routeNames[$routeName] = true;
                }
            }
            foreach (self::discoverSidebarRouteLabels($source) as $routeName => $label) {
                $labels[$routeName] = $label;
            }

            preg_match_all(
                '/\b(?:url|secure_url|URL::to)\(\s*[\'"]([^\'"]+)[\'"]/',
                $source,
                $urlMatches
            );
            preg_match_all(
                '/\bhref\s*=\s*[\'"]\/([^\'"?#{}$]+)[\'"]/i',
                $source,
                $hrefMatches
            );
            foreach (array_merge($urlMatches[1] ?? [], $hrefMatches[1] ?? []) as $path) {
                $path = self::normalizeNavigationPath((string) $path);
                if ($path !== '') {
                    $paths[$path] = true;
                }
            }
        }

        return [
            'route_names' => array_keys($routeNames),
            'paths' => array_keys($paths),
            'labels' => $labels,
        ];
    }

    /**
     * Pair route declarations with the nearest sidebar label in the same menu
     * item. Literal labels and translation keys are both supported.
     *
     * @return array<string, string>
     */
    private static function discoverSidebarRouteLabels(string $source): array
    {
        $routes = [];
        foreach ([
            '/\broute\(\s*[\'"]([^\'"]+)[\'"]/',
            '/[\'"]route[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/',
        ] as $pattern) {
            preg_match_all($pattern, $source, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[1] ?? [] as $match) {
                $route = trim((string) ($match[0] ?? ''), '. ');
                if ($route !== '') {
                    $routes[] = ['route' => $route, 'offset' => (int) ($match[1] ?? 0)];
                }
            }
        }

        $labelCandidates = [];
        preg_match_all(
            '/[\'"]label[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/',
            $source,
            $literalLabels,
            PREG_OFFSET_CAPTURE
        );
        foreach ($literalLabels[1] ?? [] as $match) {
            $labelCandidates[] = [
                'label' => trim((string) ($match[0] ?? '')),
                'offset' => (int) ($match[1] ?? 0),
            ];
        }

        preg_match_all(
            '/[\'"]label[\'"]\s*=>\s*(?:__|trans)\(\s*[\'"]([^\'"]+)[\'"]/',
            $source,
            $translatedLabels,
            PREG_OFFSET_CAPTURE
        );
        foreach ($translatedLabels[1] ?? [] as $match) {
            $translationKey = trim((string) ($match[0] ?? ''));
            $segments = preg_split('/[.:]+/', $translationKey) ?: [];
            $labelKey = (string) end($segments);
            if ($labelKey !== '') {
                $labelCandidates[] = [
                    'label' => self::displayName($labelKey),
                    'offset' => (int) ($match[1] ?? 0),
                ];
            }
        }

        $labels = [];
        foreach ($routes as $route) {
            $nearest = null;
            $distance = PHP_INT_MAX;
            foreach ($labelCandidates as $candidate) {
                $candidateDistance = abs($candidate['offset'] - $route['offset']);
                if ($candidateDistance <= 700 && $candidateDistance < $distance) {
                    $nearest = $candidate['label'];
                    $distance = $candidateDistance;
                }
            }
            if (is_string($nearest) && $nearest !== '') {
                $labels[$route['route']] = $nearest;
            }
        }

        return $labels;
    }

    /**
     * Keep only named or literal routes that are represented by a menu link.
     */
    private static function filterNavigationPermissionRoutes(
        array $permissionRoutes,
        array $permissionRoutePaths,
        array $sidebarRouteNames,
        array $sidebarPaths
    ): array {
        $named = [];
        foreach ($sidebarRouteNames as $sidebarRouteName) {
            foreach (self::navigationRouteNameVariants((string) $sidebarRouteName) as $variant) {
                $named[$variant] = true;
            }
        }
        $paths = array_fill_keys(array_filter(array_map(
            [self::class, 'normalizeNavigationPath'],
            $sidebarPaths
        )), true);

        $filtered = [];
        foreach (array_values(array_unique(array_filter($permissionRoutes))) as $routeName) {
            $routeName = trim((string) $routeName, '. ');
            $routeVariants = self::navigationRouteNameVariants($routeName);
            if (array_intersect_key(array_fill_keys($routeVariants, true), $named) !== []) {
                $filtered[] = $routeName;
                continue;
            }

            foreach ((array) ($permissionRoutePaths[$routeName] ?? []) as $candidatePath) {
                if (isset($paths[self::normalizeNavigationPath((string) $candidatePath)])) {
                    $filtered[] = $routeName;
                    break;
                }
            }
        }

        return $filtered;
    }

    /**
     * Route providers commonly add the module prefix at runtime, while route
     * files contain only the local name. Resource index routes are represented
     * internally by the existing "view" permission.
     *
     * @return array<int, string>
     */
    private static function navigationRouteNameVariants(string $routeName): array
    {
        $routeName = strtolower(trim($routeName, '. '));
        if ($routeName === '') {
            return [];
        }

        $variants = [$routeName];
        $segments = explode('.', $routeName);
        if (count($segments) > 1) {
            $variants[] = implode('.', array_slice($segments, 1));
        }

        foreach ($variants as $variant) {
            $parts = explode('.', $variant);
            $last = end($parts);
            if (in_array($last, ['index', 'list', 'show'], true)) {
                array_pop($parts);
                $parts[] = 'view';
                $variants[] = implode('.', $parts);
            }
        }

        return array_values(array_unique(array_filter($variants)));
    }

    private static function navigationLabelForRoute(string $routeName, array $labels): ?string
    {
        $routeVariants = array_fill_keys(
            self::navigationRouteNameVariants($routeName),
            true
        );
        foreach ($labels as $labelRoute => $label) {
            foreach (self::navigationRouteNameVariants((string) $labelRoute) as $labelVariant) {
                if (isset($routeVariants[$labelVariant])) {
                    $label = trim((string) $label);
                    return $label !== '' ? $label : null;
                }
            }
        }

        return null;
    }

    private static function normalizeNavigationPath(string $path): string
    {
        return strtolower(trim((string) preg_replace('#/+#', '/', trim($path)), '/ '));
    }


    private static function legacyAliasesFor(string $key): array
    {
        $map = [
            'petro' => ['enable_petro_module'],
            'petro_pd' => ['petro_p_d', 'petro_p_d_module', 'petropd', 'pd_settlements', 'pd_operators'],
            'petro_general' => ['petro_general_module'],
            'petro_direct' => ['petro_direct_module'],
            'daily_collection_sw' => ['dailycollectionsw', 'dailycollectionsw_module', 'daily_collection_sw_enabled'],
            'daily_collection' => ['dailycollection', 'daily_collection_sub_menu', 'enable_petro_daily_collection'],
            'products_new' => ['productsnew', 'productsnew_module', 'product_new_module', 'productnew_module'],
            // Standalone Finance is independent from the core Accounting Module.
            // SW Module - shift working, settlements and the change log.
            'sw' => ['sw_module'],
            'finance' => ['finance_module', 'accounts_finance', 'accounts_finance_module'],
            'mpcs' => ['mpcs_module'],
            'auto_service' => ['autoservice_module'],
            'auto_repair_services' => ['autorepairservices_module', 'auto_services_and_repair_module'],

            // Legacy package/sidebar variable names used by the active main
            // sidebar. These aliases let the new registry suppress the existing
            // variable without modifying the owning module.
            'manufacturing' => ['mf_module', 'manufacturing_module'],
            'my_health' => ['myhealth', 'myhealth_module', 'patient_module'],
            'stocktaking' => ['stock_taking_module', 'stock_taking_page'],
            'asset_management' => ['assetmanagement', 'asset_module', 'ns_asset_management'],
            'deposits' => ['deposits_module', 'ns_deposits_module'],
            'discount' => ['discount_module', 'ns_discount_module'],
            'dsr' => ['dsr_module', 'ns_dsr_module'],
            'vat' => ['vat_module', 'vat_module_main', 'ns_vat_module'],
            'reports_customized' => ['reportscustomized', 'customized_report', 'customized_reports_module'],
            'sales_agent' => ['salesagent', 'enable_sale_cmsn_agent', 'sales_agent_module'],
            'membership' => ['member_registration', 'membership_module'],
            'sms' => ['enable_sms', 'sms_module', 'smsmodule_module'],
            'hr' => ['hr_module'],
            'essentials' => ['essentials_module'],
            'stock_reports' => ['stockreports', 'stock_report'],
            'subscription' => ['enable_subscription'],
            'product_catalogue' => ['productcatalogue', 'catalogue_qr'],
            'visitor' => ['visitors_registration_module', 'visitors_registration'],
            'help_guide' => ['helpguide'],

            // New standalone modules. These aliases preserve older/simplified
            // Manage Side Bar keys while keeping one canonical parent switch.
            'airline_ticketing_new' => [
                'airline_new',
                'airline_new_module',
                'airline_ticketing',
                'airline_ticketing_module',
                'airline_ticketing_new_module',
                'airlineticketing',
                'airlineticketing_module',
                'airlineticketingnew',
                'airlineticketingnew_module',
            ],
            'airline_ticketing' => [
                'airline_new',
                'airline_new_module',
                'airline_ticketing_new',
                'airline_ticketing_new_module',
                'airlineticketing',
                'airlineticketing_module',
                'airlineticketingnew',
                'airlineticketingnew_module',
            ],
            'tea_estate_management' => [
                'tea_estate',
                'tea_estate_module',
                'tea_estate_management_module',
                'teaestatemanagement',
                'teaestatemanagement_module',
            ],
            'restaurant_new' => [
                'restaurant_new_module',
                'restaurantnew',
                'restaurantnew_module',
            ],
        ];

        return $map[$key] ?? [];
    }

    private static function stripRouteModulePrefix(string $routeName, string $key, array $prefixes): string
    {
        $possible = array_merge($prefixes, [
            str_replace('_', '.', $key),
            str_replace('_', '-', $key),
            str_replace('_', '', $key),
        ]);

        foreach (array_unique(array_filter($possible)) as $prefix) {
            if ($routeName === $prefix) {
                return 'dashboard';
            }
            if (str_starts_with($routeName, $prefix . '.')) {
                return substr($routeName, strlen($prefix) + 1);
            }
        }

        return $routeName;
    }

    private static function isAlreadyIntegrated(array $module, string $source): bool
    {
        if ($source === '') {
            return false;
        }

        foreach ($module['aliases'] ?? [] as $alias) {
            $alias = self::normalizeKey((string) $alias);
            if ($alias === '') {
                continue;
            }

            $quoted = preg_quote($alias, '/');
            if (preg_match('/\$' . $quoted . '\b/', $source)
                || str_contains($source, "['" . $alias . "']")
                || str_contains($source, '["' . $alias . '"]')
                || preg_match('/SidebarPermissionUtil::(?:isEnabled|isVisibleInSidebar)\(\s*[\'"]' . $quoted . '[\'"]/', $source)) {
                return true;
            }
        }

        foreach ($module['sidebar_views'] ?? [] as $view) {
            $namespace = strstr($view, '::', true);
            if ($namespace && str_contains($source, $namespace . '::')) {
                return true;
            }
        }

        foreach ($module['route_prefixes'] ?? [] as $prefix) {
            $quoted = preg_quote(trim((string) $prefix, '/'), '/');
            if ($quoted === '') {
                continue;
            }
            if (preg_match('/(?:href\s*=|url\(|segment\(1\)|request\(\)->is\()[^\n]{0,100}' . $quoted . '/i', $source)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stable user-facing titles for standalone modules whose historical module
     * class name is not the label used in the ERP sidebar.
     */
    private static function displayTitleForModule(string $key, string $value): string
    {
        $titles = [
            'airline_ticketing_new' => 'Airline New',
            'airline_ticketing' => 'Airline New',
            'tea_estate_management' => 'Tea Estate Management',
            'restaurant_new' => 'Restaurant New',
        ];

        return $titles[$key] ?? self::displayName($value);
    }

    /**
     * Compatibility landing URLs used only by the lightweight sidebar catalogue.
     * The detailed registry still discovers actual route prefixes from module
     * route files. AirlineTicketingNew's original module.json alias omits the
     * hyphens while its real route prefix is /airline-ticketing-new.
     */
    private static function primaryUrlForModule(string $key, array $routePrefixes): string
    {
        $urls = [
            'airline_ticketing_new' => '/airline-ticketing-new',
            'airline_ticketing' => '/airline-ticketing',
            'tea_estate_management' => '/tea-estate-management',
            'restaurant_new' => '/restaurant-new',
            'simple_audit' => '/superadmin/simple-audit',
        ];

        if (isset($urls[$key])) {
            return $urls[$key];
        }

        return '/' . ($routePrefixes[0] ?? str_replace('_', '-', $key));
    }

    private static function displayName(string $value): string
    {
        if ($value !== '' && strlen($value) <= 8 && strtoupper($value) === $value) {
            return $value;
        }

        $value = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $value);
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', (string) $value);
        $value = str_replace(['_', '-', '.'], ' ', (string) $value);

        return trim(ucwords((string) preg_replace('/\s+/', ' ', (string) $value)));
    }
}
