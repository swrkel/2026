<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ModulePortabilityPreflight extends Command
{
    protected $signature = 'system:module-preflight {--json : Print machine-readable JSON}';

    protected $description = 'Read-only pre-deployment audit for module status, identity and provider portability';

    public function handle(): int
    {
        $modulesPath = base_path('Modules');
        $statusPath = base_path('modules_statuses.json');

        $report = [
            'status_file' => $statusPath,
            'status_valid' => false,
            'status_entries' => 0,
            'status_enabled' => 0,
            'status_disabled' => 0,
            'installed_descriptors' => 0,
            'active_descriptors' => 0,
            'missing_status' => [],
            'disabled_descriptors' => [],
            'backup_descriptors' => [],
            'duplicate_module_names' => [],
            'folder_name_mismatches' => [],
            'provider_problems' => [],
            'orphan_statuses' => [],
            'stale_module_cache_files' => [],
            'critical_count' => 0,
            'warning_count' => 0,
        ];

        $statuses = [];
        if (is_file($statusPath)) {
            $decoded = json_decode((string) @file_get_contents($statusPath), true);
            if (is_array($decoded)) {
                $statuses = $decoded;
                $report['status_valid'] = true;
                $report['status_entries'] = count($statuses);
                $report['status_enabled'] = count(array_filter($statuses, static fn ($v) => $v === true));
                $report['status_disabled'] = count(array_filter($statuses, static fn ($v) => $v === false));
            }
        }

        if (!$report['status_valid']) {
            $report['critical_count']++;
        }

        $names = [];
        $declaredNames = [];
        foreach (is_dir($modulesPath) ? (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: []) : [] as $directory) {
            $folder = basename($directory);
            $moduleJsonPath = $directory . '/module.json';
            if (!is_file($moduleJsonPath)) {
                continue;
            }

            $moduleJson = json_decode((string) @file_get_contents($moduleJsonPath), true);
            if (!is_array($moduleJson)) {
                $report['provider_problems'][] = [
                    'folder' => $folder,
                    'problem' => 'invalid module.json',
                    'active' => false,
                ];
                $report['warning_count']++;
                continue;
            }

            $report['installed_descriptors']++;
            $moduleName = trim((string) ($moduleJson['name'] ?? ''));
            if ($moduleName === '') {
                $report['provider_problems'][] = [
                    'folder' => $folder,
                    'problem' => 'module.json has no name',
                    'active' => false,
                ];
                $report['warning_count']++;
                continue;
            }

            $declaredNames[$moduleName] = true;
            $names[$moduleName][] = $folder;
            $active = array_key_exists($moduleName, $statuses) && $statuses[$moduleName] === true;
            if ($active) {
                $report['active_descriptors']++;
            } elseif (!array_key_exists($moduleName, $statuses)) {
                $report['missing_status'][] = ['folder' => $folder, 'name' => $moduleName];
            } else {
                $report['disabled_descriptors'][] = ['folder' => $folder, 'name' => $moduleName];
            }

            if ($folder !== $moduleName) {
                $report['folder_name_mismatches'][] = [
                    'folder' => $folder,
                    'name' => $moduleName,
                    'active' => $active,
                ];
                $report['warning_count']++;
            }

            if ($this->looksLikeBackupFolder($folder)) {
                $report['backup_descriptors'][] = [
                    'folder' => $folder,
                    'name' => $moduleName,
                    'active' => $active,
                ];
                // A module-shaped backup inside Modules can be discovered by
                // Laravel Modules and compete with the real module. Treat it as
                // a deployment blocker when its module name is globally active.
                if ($active) {
                    $report['critical_count']++;
                } else {
                    $report['warning_count']++;
                }
            }

            foreach ((array) ($moduleJson['providers'] ?? []) as $provider) {
                $provider = trim((string) $provider);
                if ($provider === '') {
                    continue;
                }
                $providerPath = base_path(str_replace('\\', '/', $provider) . '.php');
                if (!is_file($providerPath)) {
                    $report['provider_problems'][] = [
                        'folder' => $folder,
                        'name' => $moduleName,
                        'provider' => $provider,
                        'expected_file' => $providerPath,
                        'active' => $active,
                    ];
                    if ($active) {
                        $report['critical_count']++;
                    } else {
                        $report['warning_count']++;
                    }
                }
            }
        }

        foreach ($names as $name => $folders) {
            if (count($folders) > 1) {
                $report['duplicate_module_names'][$name] = array_values($folders);
                $active = array_key_exists($name, $statuses) && $statuses[$name] === true;
                if ($active) {
                    $report['critical_count']++;
                } else {
                    $report['warning_count']++;
                }
            }
        }

        foreach ($statuses as $name => $enabled) {
            if (!isset($declaredNames[$name])) {
                $report['orphan_statuses'][] = ['name' => (string) $name, 'enabled' => (bool) $enabled];
                $report['warning_count']++;
            }
        }

        foreach (glob(base_path('bootstrap/cache/*_module.php')) ?: [] as $cacheFile) {
            $report['stale_module_cache_files'][] = $cacheFile;
            $report['warning_count']++;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $report['critical_count'] > 0 ? 2 : 0;
        }

        $this->line('Module portability preflight (READ ONLY)');
        $this->line('--------------------------------------');
        $this->line('Status file valid:      ' . ($report['status_valid'] ? 'YES' : 'NO'));
        $this->line('Status entries:         ' . $report['status_entries']);
        $this->line('Globally enabled:       ' . $report['status_enabled']);
        $this->line('Globally disabled:      ' . $report['status_disabled']);
        $this->line('Module descriptors:     ' . $report['installed_descriptors']);
        $this->line('Active descriptors:     ' . $report['active_descriptors']);
        $this->line('Missing statuses:       ' . count($report['missing_status']) . ' (left unavailable; NOT auto-enabled)');
        $this->line('Backup descriptors:     ' . count($report['backup_descriptors']));
        $this->line('Duplicate module names: ' . count($report['duplicate_module_names']));
        $this->line('Provider problems:      ' . count($report['provider_problems']));
        $this->line('Orphan statuses:        ' . count($report['orphan_statuses']));
        $this->line('Module cache files:     ' . count($report['stale_module_cache_files']));
        $this->line('Warnings:               ' . $report['warning_count']);
        $this->line('Critical:               ' . $report['critical_count']);

        if ($report['backup_descriptors']) {
            $this->warn('Backup-like module folders:');
            foreach ($report['backup_descriptors'] as $item) {
                $this->line('  - ' . $item['folder'] . ' -> ' . $item['name'] . ($item['active'] ? ' [ACTIVE NAME]' : ''));
            }
        }
        if ($report['duplicate_module_names']) {
            $this->warn('Duplicate module identities:');
            foreach ($report['duplicate_module_names'] as $name => $folders) {
                $this->line('  - ' . $name . ': ' . implode(', ', $folders));
            }
        }
        if ($report['provider_problems']) {
            $this->warn('Provider/descriptor problems:');
            foreach ($report['provider_problems'] as $item) {
                $line = '  - ' . ($item['folder'] ?? '?') . ': ' . ($item['provider'] ?? $item['problem'] ?? 'problem');
                if (!empty($item['active'])) {
                    $line .= ' [ACTIVE]';
                }
                $this->line($line);
            }
        }
        if ($report['missing_status']) {
            $this->comment('Installed descriptors with no global status are intentionally left unavailable.');
        }

        if ($report['critical_count'] > 0) {
            $this->error('FAIL: fix critical deployment issues before putting the environment live.');
            return 2;
        }

        $this->info('PASS: no critical module portability problems detected.');
        return 0;
    }

    private function looksLikeBackupFolder(string $folder): bool
    {
        return (bool) preg_match('/(?:^|[._-])(before|backup|bak|old|copy|disabled)(?:[._-]|$)/i', $folder);
    }
}
