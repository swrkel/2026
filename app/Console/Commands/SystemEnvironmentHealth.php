<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class SystemEnvironmentHealth extends Command
{
    protected $signature = 'system:environment-health {--json : Output JSON}';
    protected $description = 'Read-only portability audit for module activation, providers, routes and sidebar links.';

    public function handle(): int
    {
        $modulesPath = base_path('Modules');
        $statusPath = base_path('modules_statuses.json');
        $statuses = [];
        if (is_file($statusPath)) {
            $decoded = json_decode((string) @file_get_contents($statusPath), true);
            $statuses = is_array($decoded) ? $decoded : [];
        }

        $missingStatuses = [];
        $disabled = [];
        $providerProblems = [];
        $unregistered = [];
        $backupDescriptors = [];
        $validModuleCount = 0;

        foreach (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $folder = basename($dir);
            $backupLike = preg_match('/(?:^|[._\-\s])(before|backup|bak|old|copy|archive|disabled)(?:[._\-\s]|$)/i', $folder) === 1
                || stripos($folder, 'no need') !== false;
            $jsonPath = $dir . '/module.json';
            if ($backupLike) {
                if (is_file($jsonPath)) {
                    $backupDescriptors[] = $folder;
                }
                continue;
            }
            if (! is_file($jsonPath)) {
                continue;
            }

            $json = json_decode((string) @file_get_contents($jsonPath), true);
            if (! is_array($json)) {
                $providerProblems[] = ['module' => $folder, 'error' => 'invalid module.json'];
                continue;
            }

            ++$validModuleCount;
            $name = trim((string) ($json['name'] ?? $folder)) ?: $folder;
            $keys = array_values(array_unique([$name, $folder]));
            $present = false;
            $enabled = true;
            foreach ($keys as $key) {
                if (array_key_exists($key, $statuses)) {
                    $present = true;
                    if (! (bool) $statuses[$key]) {
                        $enabled = false;
                    }
                }
            }
            if (! $present) {
                $missingStatuses[] = $folder . ' [' . $name . ']';
            }
            if (! $enabled) {
                $disabled[] = $folder;
                continue;
            }

            foreach ((array) ($json['providers'] ?? []) as $provider) {
                $provider = trim((string) $provider);
                if ($provider === '') {
                    continue;
                }
                try {
                    if (! class_exists($provider)) {
                        $providerProblems[] = ['module' => $folder, 'provider' => $provider, 'error' => 'class not autoloadable'];
                        continue;
                    }
                    if (! app()->getProvider($provider)) {
                        $unregistered[] = ['module' => $folder, 'provider' => $provider];
                    }
                } catch (\Throwable $e) {
                    $providerProblems[] = [
                        'module' => $folder,
                        'provider' => $provider,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        $sidebarWarnings = [];
        $sidebarPath = resource_path('views/layouts/partials/sidebar.blade.php');
        if (is_file($sidebarPath)) {
            $source = (string) @file_get_contents($sidebarPath);
            preg_match_all('/\broute\(\s*[\'"]([^\'"]+)[\'"]/', $source, $matches);
            foreach (array_values(array_unique($matches[1] ?? [])) as $routeName) {
                if (! Route::has($routeName)) {
                    $sidebarWarnings[] = $routeName;
                }
            }
        }

        $bootstrapSource = is_file(base_path('bootstrap/app.php'))
            ? (string) @file_get_contents(base_path('bootstrap/app.php'))
            : '';
        $guardIncluded = strpos($bootstrapSource, 'deployment_cache_guard.php') !== false;

        $report = [
            'ok' => $guardIncluded
                && $missingStatuses === []
                && $providerProblems === []
                && $unregistered === []
                && $backupDescriptors === [],
            'guard_in_bootstrap' => $guardIncluded,
            'runtime_routes' => Route::getRoutes()->count(),
            'routes_cached' => app()->routesAreCached(),
            'valid_modules' => $validModuleCount,
            'global_disabled' => $disabled,
            'missing_statuses' => $missingStatuses,
            'provider_problems' => $providerProblems,
            'unregistered_enabled_providers' => $unregistered,
            'backup_module_descriptors' => $backupDescriptors,
            'sidebar_route_warnings' => $sidebarWarnings,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $report['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $this->line('Environment portability health (read-only)');
        $this->line('------------------------------------------');
        $this->line('Guard in bootstrap: ' . ($guardIncluded ? 'OK' : 'MISSING'));
        $this->line('Valid modules:      ' . $validModuleCount);
        $this->line('Runtime routes:     ' . $report['runtime_routes']);
        $this->line('Route cache:        ' . ($report['routes_cached'] ? 'YES' : 'NO'));
        $this->line('Global disabled:    ' . count($disabled));
        $this->line('Missing statuses:   ' . count($missingStatuses));
        $this->line('Provider problems:  ' . count($providerProblems));
        $this->line('Unregistered:       ' . count($unregistered));
        $this->line('Backup descriptors: ' . count($backupDescriptors));
        $this->line('Sidebar route warn: ' . count($sidebarWarnings));

        foreach ($providerProblems as $item) {
            $this->error('Provider problem: ' . json_encode($item, JSON_UNESCAPED_SLASHES));
        }
        foreach ($unregistered as $item) {
            $this->error('Unregistered enabled provider: ' . $item['module'] . ' -> ' . $item['provider']);
        }
        if ($missingStatuses !== []) {
            $this->warn('Missing statuses: ' . implode(', ', $missingStatuses));
        }
        if ($backupDescriptors !== []) {
            $this->warn('Backup module descriptors still active: ' . implode(', ', $backupDescriptors));
        }
        if ($sidebarWarnings !== []) {
            $this->warn('Sidebar literal route warnings: ' . implode(', ', $sidebarWarnings));
        }

        if ($report['ok']) {
            $this->info('PASS: portable module/provider bootstrap is healthy.');
            return self::SUCCESS;
        }

        $this->error('FAIL: portability audit found critical issues.');
        return self::FAILURE;
    }
}
