<?php

namespace App\Services;

/**
 * Metadata driven Manage Permission discovery for Super Admin > All Businesses > Manage.
 *
 * This service keeps future standalone modules maintainable. A new module can expose
 * its permissions through any of these lightweight metadata files:
 *   Modules/<Module>/Config/permissions.php
 *   Modules/<Module>/Config/module_permissions.php
 *   Modules/<Module>/Config/menu.php
 *   Modules/<Module>/Config/navigation.php
 *
 * If metadata is not available yet, the service safely falls back to routes and blade
 * tab/page discovery so the module still appears automatically in Super Admin Manage.
 */
class BusinessPermissionMetadataService
{
    public function sections(array $currentPermissions = []): array
    {
        $sections = [];
        $modulesPath = base_path('Modules');

        if (! is_dir($modulesPath)) {
            return $sections;
        }

        foreach (glob($modulesPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
            $moduleName = basename($moduleDir);

            if ($moduleName === 'Superadmin') {
                continue;
            }

            $moduleKey = $this->moduleKey($moduleName);
            $items = [[
                'key' => $moduleKey,
                'label' => $this->label($moduleName) . ' Module',
                'type' => 'module',
                'source' => 'module',
            ]];

            foreach ($this->metadataFiles($moduleDir) as $file) {
                $items = array_merge($items, $this->itemsFromMetadataFile($file, $moduleKey));
            }

            $items = array_merge($items, $this->itemsFromRoutes($moduleDir, $moduleKey));
            $items = array_merge($items, $this->itemsFromBladeTabs($moduleDir, $moduleKey));
            $items = array_merge($items, $this->itemsFromSidebarFilesForModule($moduleName, $moduleKey));

            $unique = [];
            foreach ($items as $item) {
                if (empty($item['key'])) {
                    continue;
                }

                $key = $this->key($item['key']);
                if ($key === '') {
                    continue;
                }

                $unique[$key] = [
                    'key' => $key,
                    'label' => $item['label'] ?? $this->label($key),
                    'type' => $item['type'] ?? 'page',
                    'source' => $item['source'] ?? 'auto',
                    // New module/page/tab permissions must be enabled automatically.
                    // Existing saved values still win, so Super Admin can later disable them.
                    'enabled' => array_key_exists($key, $currentPermissions) ? ! empty($currentPermissions[$key]) : true,
                ];
            }

            // Show even module-only discoveries. Some modules initially add only a
            // sidebar entry before adding metadata/routes; they must still appear in Manage.
            if (count($unique) > 0) {
                $sections[] = [
                    'module_name' => $moduleName,
                    'title' => $this->label($moduleName),
                    'module_key' => $moduleKey,
                    'items' => array_values($unique),
                ];
            }
        }

        // Also discover modules that are registered only in shared sidebar/layout files.
        // Many existing ERP modules are added to resources/views/layouts/partials/sidebar*.blade.php
        // before they have a Modules/<Name>/Config/permissions.php file.
        $sections = array_merge($sections, $this->sectionsFromSharedSidebarFiles($currentPermissions, array_column($sections, 'module_key')));

        $merged = [];
        foreach ($sections as $section) {
            $moduleKey = $section['module_key'] ?? $this->key($section['title'] ?? 'module');
            if (! isset($merged[$moduleKey])) {
                $merged[$moduleKey] = $section;
                $merged[$moduleKey]['items'] = [];
            }
            foreach (($section['items'] ?? []) as $item) {
                if (empty($item['key'])) {
                    continue;
                }
                $itemKey = $this->key($item['key']);
                $merged[$moduleKey]['items'][$itemKey] = array_merge($item, [
                    'key' => $itemKey,
                    'enabled' => array_key_exists($itemKey, $currentPermissions) ? ! empty($currentPermissions[$itemKey]) : ($item['enabled'] ?? true),
                ]);
            }
            $merged[$moduleKey]['items'] = array_values($merged[$moduleKey]['items']);
        }

        $sections = array_values($merged);
        usort($sections, fn ($a, $b) => strcmp($a['title'], $b['title']));

        return $sections;
    }


    private function itemsFromSidebarFilesForModule(string $moduleName, string $moduleKey): array
    {
        $items = [];
        foreach ($this->sharedSidebarFiles() as $file) {
            $base = strtolower(pathinfo($file, PATHINFO_FILENAME));
            $needleA = strtolower($moduleName);
            $needleB = str_replace('_', '-', $this->key($moduleName));
            if (! str_contains($base, $needleA) && ! str_contains($base, $needleB)) {
                continue;
            }
            $items = array_merge($items, $this->itemsFromSidebarFile($file, $moduleKey));
        }
        return $items;
    }

    private function sectionsFromSharedSidebarFiles(array $currentPermissions = [], array $alreadyDiscovered = []): array
    {
        $sections = [];
        $alreadyDiscovered = array_map(fn ($key) => $this->key((string) $key), $alreadyDiscovered);

        foreach ($this->sharedSidebarFiles() as $file) {
            $contents = @file_get_contents($file);
            if (! $contents) {
                continue;
            }

            $fileBase = pathinfo($file, PATHINFO_FILENAME);
            $guessedModuleKey = $this->moduleKeyFromSidebarFile($fileBase);
            $keys = [];

            if (preg_match_all("/has(?:The)?PermissionInSubscription\([^\)]*['\"]([^'\"]+)['\"]/i", $contents, $m)) {
                $keys = array_merge($keys, $m[1]);
            }
            if (preg_match_all("/hasModulePermission\(['\"]([^'\"]+)['\"]\)/i", $contents, $m)) {
                $keys = array_merge($keys, $m[1]);
            }
            if (preg_match_all("/package_details\[['\"]([^'\"]+)['\"]\]/i", $contents, $m)) {
                $keys = array_merge($keys, $m[1]);
            }
            if (preg_match_all("/manage_module_enable\[['\"]([^'\"]+)['\"]\]/i", $contents, $m)) {
                $keys = array_merge($keys, $m[1]);
            }

            $keys = array_values(array_unique(array_filter(array_map(fn ($v) => $this->key((string) $v), $keys))));
            $moduleKeys = array_values(array_filter($keys, fn ($key) => str_ends_with($key, '_module') || str_starts_with($key, 'enable_')));
            if (empty($moduleKeys) && $guessedModuleKey !== '') {
                $moduleKeys = [$guessedModuleKey];
            }

            foreach ($moduleKeys as $rawModuleKey) {
                $moduleKey = $this->normalizeSidebarModuleKey($rawModuleKey, $guessedModuleKey);
                if ($moduleKey === '' || in_array($moduleKey, $alreadyDiscovered, true)) {
                    continue;
                }

                $items = [[
                    'key' => $moduleKey,
                    'label' => $this->label(preg_replace('/_module$/', '', $moduleKey)) . ' Module',
                    'type' => 'module',
                    'source' => 'sidebar',
                    'enabled' => array_key_exists($moduleKey, $currentPermissions) ? ! empty($currentPermissions[$moduleKey]) : true,
                ]];

                $pageItems = $this->itemsFromSidebarFile($file, $moduleKey);
                foreach ($keys as $key) {
                    if ($key !== $moduleKey && ! str_contains($key, 'enable_')) {
                        $pageItems[] = [
                            'key' => $key,
                            'label' => $this->label($key),
                            'type' => str_contains($key, 'tab') ? 'tab' : 'page',
                            'source' => 'sidebar',
                        ];
                    }
                }
                $items = array_merge($items, $pageItems);

                $unique = [];
                foreach ($items as $item) {
                    $itemKey = $this->key($item['key'] ?? '');
                    if ($itemKey === '' || $this->isSystemRoute($itemKey)) {
                        continue;
                    }
                    $unique[$itemKey] = [
                        'key' => $itemKey,
                        'label' => $item['label'] ?? $this->label($itemKey),
                        'type' => $item['type'] ?? 'page',
                        'source' => $item['source'] ?? 'sidebar',
                        'enabled' => array_key_exists($itemKey, $currentPermissions) ? ! empty($currentPermissions[$itemKey]) : true,
                    ];
                }

                if (! empty($unique)) {
                    $sections[] = [
                        'module_name' => $this->label(preg_replace('/_module$/', '', $moduleKey)),
                        'title' => $this->label(preg_replace('/_module$/', '', $moduleKey)),
                        'module_key' => $moduleKey,
                        'items' => array_values($unique),
                    ];
                    $alreadyDiscovered[] = $moduleKey;
                }
            }
        }

        return $sections;
    }

    private function itemsFromSidebarFile(string $file, string $moduleKey): array
    {
        $items = [];
        $contents = @file_get_contents($file);
        if (! $contents) {
            return $items;
        }

        if (preg_match_all("/route\(['\"]([^'\"]+)['\"]/i", $contents, $routes)) {
            foreach ($routes[1] as $routeName) {
                if ($this->isSystemRoute($routeName)) {
                    continue;
                }
                $items[] = [
                    'key' => $this->key($routeName),
                    'label' => $this->label($routeName),
                    'type' => 'page',
                    'source' => 'sidebar-route',
                ];
            }
        }

        if (preg_match_all('/href=["\']#([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $contents, $tabs)) {
            foreach ($tabs[1] as $idx => $tabId) {
                $label = trim(strip_tags($tabs[2][$idx] ?? $tabId));
                $items[] = [
                    'key' => $this->key($moduleKey . '_tab_' . $tabId),
                    'label' => $label !== '' && strlen($label) <= 80 ? $label : $this->label($tabId),
                    'type' => 'tab',
                    'source' => 'sidebar-tab',
                ];
            }
        }

        return array_slice($items, 0, 100);
    }

    private function sharedSidebarFiles(): array
    {
        $roots = [
            resource_path('views/layouts/partials'),
            resource_path('views/layouts/partials/sidebar-sections'),
            base_path('resources/views/layouts/partials'),
            base_path('resources/views/layouts/partials/sidebar-sections'),
        ];

        $files = [];
        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }
            foreach (glob($root . DIRECTORY_SEPARATOR . '*sidebar*.blade.php') ?: [] as $file) {
                $files[] = $file;
            }
            foreach (glob($root . DIRECTORY_SEPARATOR . '*.blade.php') ?: [] as $file) {
                if (str_contains(strtolower($file), 'sidebar')) {
                    $files[] = $file;
                }
            }
        }

        return array_values(array_unique($files));
    }

    private function moduleKeyFromSidebarFile(string $fileBase): string
    {
        $fileBase = preg_replace('/^sidebar[-_]?/i', '', $fileBase);
        $fileBase = preg_replace('/[-_]?sidebar$/i', '', $fileBase);
        $key = $this->key($fileBase);
        if ($key === '' || in_array($key, ['sections', 'partials'], true)) {
            return '';
        }
        return $this->moduleKey($key);
    }

    private function normalizeSidebarModuleKey(string $rawModuleKey, string $fallback = ''): string
    {
        $key = $this->key($rawModuleKey);
        if ($key === '') {
            return $fallback;
        }
        if (str_starts_with($key, 'enable_')) {
            $key = substr($key, 7);
        }
        if (! str_ends_with($key, '_module')) {
            $key .= '_module';
        }
        return $this->key($key);
    }

    private function metadataFiles(string $moduleDir): array
    {
        $files = [];
        foreach (['permissions.php', 'module_permissions.php', 'menu.php', 'navigation.php'] as $name) {
            $file = $moduleDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . $name;
            if (is_file($file)) {
                $files[] = $file;
            }
        }
        return $files;
    }

    private function itemsFromMetadataFile(string $file, string $moduleKey): array
    {
        try {
            $config = include $file;
        } catch (\Throwable $e) {
            \Log::warning('Manage permission metadata file could not be read', ['file' => $file, 'error' => $e->getMessage()]);
            return [];
        }

        return $this->itemsFromArray($config, $moduleKey);
    }

    private function itemsFromArray($config, string $moduleKey): array
    {
        $items = [];
        if (! is_array($config)) {
            return $items;
        }

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $label = $value['label'] ?? $value['title'] ?? $value['name'] ?? (is_string($key) ? $key : null);
                $rawKey = $value['permission'] ?? $value['ability'] ?? $value['can'] ?? $value['key'] ?? $value['route'] ?? null;

                if ($rawKey || $label) {
                    $items[] = [
                        'key' => $rawKey ? $this->key($rawKey) : $this->key($moduleKey . '_' . $label),
                        'label' => $label ?: $this->label($rawKey),
                        'type' => $value['type'] ?? 'page',
                        'source' => 'metadata',
                    ];
                }

                foreach (['children', 'items', 'pages', 'tabs', 'submenus', 'permissions'] as $childKey) {
                    if (! empty($value[$childKey]) && is_array($value[$childKey])) {
                        $items = array_merge($items, $this->itemsFromArray($value[$childKey], $moduleKey));
                    }
                }
            } elseif (is_string($value)) {
                $items[] = [
                    'key' => $this->key($value),
                    'label' => is_string($key) ? $this->label($key) : $this->label($value),
                    'type' => 'page',
                    'source' => 'metadata',
                ];
            }
        }

        return $items;
    }

    private function itemsFromRoutes(string $moduleDir, string $moduleKey): array
    {
        $items = [];
        foreach (['web.php', 'admin.php'] as $routeFileName) {
            $file = $moduleDir . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . $routeFileName;
            if (! is_file($file)) {
                continue;
            }

            $contents = @file_get_contents($file);
            if (! $contents) {
                continue;
            }

            if (preg_match_all("/->name\(['\"]([^'\"]+)['\"]\)/", $contents, $matches)) {
                foreach ($matches[1] as $routeName) {
                    if ($this->isSystemRoute($routeName)) {
                        continue;
                    }
                    $items[] = [
                        'key' => $this->key($routeName),
                        'label' => $this->label($routeName),
                        'type' => 'page',
                        'source' => 'route',
                    ];
                }
            }

            if (preg_match_all("/Route::(?:get|post|match|any|resource)\(['\"]([^'\"]+)['\"]/", $contents, $uriMatches)) {
                foreach ($uriMatches[1] as $uri) {
                    if ($this->isSystemRoute($uri)) {
                        continue;
                    }
                    $items[] = [
                        'key' => $this->key($moduleKey . '_' . $uri),
                        'label' => $this->label($uri),
                        'type' => 'page',
                        'source' => 'route',
                    ];
                }
            }
        }

        return array_slice($items, 0, 80);
    }

    private function itemsFromBladeTabs(string $moduleDir, string $moduleKey): array
    {
        $items = [];
        $viewsPath = $moduleDir . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';
        if (! is_dir($viewsPath)) {
            return $items;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewsPath, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') {
                continue;
            }

            $contents = @file_get_contents($file->getPathname());
            if (! $contents) {
                continue;
            }

            if (preg_match_all('/data-permission=["\']([^"\']+)["\']/i', $contents, $permissions)) {
                foreach ($permissions[1] as $permission) {
                    $items[] = ['key' => $this->key($permission), 'label' => $this->label($permission), 'type' => 'tab', 'source' => 'blade'];
                }
            }

            if (preg_match_all('/href=["\']#([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $contents, $tabs)) {
                foreach ($tabs[1] as $idx => $tabId) {
                    $label = trim(strip_tags($tabs[2][$idx] ?? $tabId));
                    if ($label === '' || strlen($label) > 80) {
                        $label = $this->label($tabId);
                    }
                    $items[] = ['key' => $this->key($moduleKey . '_tab_' . $tabId), 'label' => $label, 'type' => 'tab', 'source' => 'blade'];
                }
            }
        }

        return array_slice($items, 0, 80);
    }

    private function isSystemRoute(string $value): bool
    {
        $value = strtolower($value);
        return str_contains($value, 'datatable')
            || str_contains($value, 'ajax')
            || str_contains($value, 'api')
            || str_contains($value, 'get-')
            || str_contains($value, 'modal')
            || str_contains($value, '{')
            || str_contains($value, 'store')
            || str_contains($value, 'update')
            || str_contains($value, 'destroy')
            || str_contains($value, 'delete');
    }

    public function moduleKey(string $moduleName): string
    {
        return $this->key($moduleName) . '_module';
    }

    public function key(string $value): string
    {
        $value = trim((string) $value);
        // Convert StudlyCase/CamelCase module names (ProductsNew, AutoService, PetroPD)
        // into stable snake_case keys before lower-casing. This prevents duplicate keys such
        // as productsnew_module and products_new_module for the same module.
        $value = preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $value);
        $value = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim((string) $value, '_');

        $aliases = [
            'productsnew' => 'products_new',
            'productsnew_module' => 'products_new_module',
            'productnew_module' => 'products_new_module',
            'petropd_module' => 'petro_pd_module',
            'myhealth_module' => 'my_health_module',
            'myhealthmembers_module' => 'my_health_module',
            'autoservice_module' => 'auto_service_module',
            'hrmanager_module' => 'hr_manager_module',
            'communicationhub_module' => 'communication_hub_module',
            'productcatalogue' => 'product_catalogue',
            'productcatalogue_module' => 'product_catalogue_module',
            'products_new' => 'products_new',
            'product_new' => 'products_new',
            'product_new_module' => 'products_new_module',
            'dailycollection_module' => 'daily_collection_module',
            'dailycollectionsw' => 'daily_collection_sw',
            'dailycollectionsw_module' => 'daily_collection_module',
            'dailycollectionsubmenu' => 'daily_collection_sub_menu',
            'bankingmicrofinance_module' => 'banking_microfinance_module',
            'bankingmicrofinance' => 'banking_microfinance',
            'bankingmicrofinancetreasury' => 'banking_microfinance_treasury',
        ];

        return $aliases[$value] ?? $value;
    }

    /**
     * Human friendly labels used by Manage Sidebar and Manage Permissions.
     * Keep this generic: new modules still work through the formatter below,
     * while common legacy keys get clean user-facing names.
     */
    private function knownLabels(): array
    {
        return [
            'products_new' => 'Products New',
            'products_new_module' => 'Products New Module',
            'product_catalogue' => 'Product Catalogue',
            'product_catalogue_module' => 'Product Catalogue Module',
            'petro_pd' => 'Petro PD',
            'petro_pd_module' => 'Petro PD Module',
            'petro_direct' => 'Petro Direct',
            'petro_direct_module' => 'Petro Direct Module',
            'daily_collection' => 'Daily Collection',
            'daily_collection_module' => 'Daily Collection Module',
            'daily_collection_sw' => 'Daily Collection SW',
            'daily_collection_sub_menu' => 'Daily Collection Sub Menu',
            'my_health' => 'My Health',
            'my_health_module' => 'My Health Module',
            'membership_new' => 'Membership New',
            'membership_new_module' => 'Membership New Module',
            'hr_manager' => 'HR Manager',
            'hr_manager_module' => 'HR Manager Module',
            'auto_service' => 'Auto Service',
            'auto_service_module' => 'Auto Service Module',
            'communication_hub' => 'Communication Hub',
            'communication_hub_module' => 'Communication Hub Module',
            'banking_microfinance' => 'Banking Microfinance',
            'banking_microfinance_module' => 'Banking Microfinance Module',
            'banking_microfinance_treasury' => 'Banking Microfinance Treasury',
            'finance_reports' => 'Finance Reports',
        ];
    }

    public function label(string $value): string
    {
        $raw = trim((string) $value);
        $key = $this->key($raw);
        $known = $this->knownLabels();
        if (isset($known[$key])) {
            return $known[$key];
        }

        // Convert StudlyCase and legacy compressed module names to readable text.
        $value = preg_replace('/(?<=[a-z0-9])([A-Z])/', ' $1', $raw);
        $value = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1 $2', $value);
        $value = str_replace(['_', '-', '.', '::', '/', '\\'], ' ', $value);
        $value = preg_replace('/\bnew\b/i', 'New', $value);
        $value = preg_replace('/\bpd\b/i', 'PD', $value);
        $value = preg_replace('/\bsw\b/i', 'SW', $value);
        $value = preg_replace('/\bhr\b/i', 'HR', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $label = trim($value) ?: 'Module Permission';

        return ucwords(strtolower($label));
    }
}
