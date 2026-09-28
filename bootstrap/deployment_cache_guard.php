<?php

/*
|--------------------------------------------------------------------------
| Portable deployment guard (filesystem only)
|--------------------------------------------------------------------------
|
| Runs before Laravel/Application is created. It MUST NOT resolve facades,
| the service container, service providers, routes, cache bindings, sessions,
| database connections, or tenant context.
|
| Safe responsibilities only:
|  - preserve existing modules_statuses.json decisions;
|  - add missing status keys for installed modules (default enabled);
|  - quarantine module.json inside obvious backup/archive module folders;
|  - invalidate generated route/module/config/view caches when the release,
|    environment, module catalogue, or activation statuses change.
|
| Fast path: ordinary requests only stat module descriptors/status/environment
| files and return immediately when nothing changed.
|
*/

$basePath = dirname(__DIR__);
$guardVersion = 'portable-foundation-v2-filesystem-only-20260915-r2';
$modulesPath = $basePath . '/Modules';
$statusPath = $basePath . '/modules_statuses.json';
$signaturePath = __DIR__ . '/deployment_signature.php';
$runtimeDir = $basePath . '/storage/framework/cache';

$readPhpReturnString = static function (string $path): string {
    if (! is_file($path)) {
        return '';
    }

    $source = @file_get_contents($path);
    if (! is_string($source)) {
        return '';
    }

    $source = trim((string) (preg_replace('/^\xEF\xBB\xBF/', '', $source) ?? $source));
    if (preg_match('/\breturn\s+([\'"])(.*?)\1\s*;/s', $source, $matches) === 1) {
        return trim((string) $matches[2]);
    }

    return '';
};

$isBackupLike = static function (string $folder): bool {
    $folder = strtolower(trim($folder));
    if ($folder === '') {
        return true;
    }

    return preg_match('/(?:^|[._\-\s])(before|backup|bak|old|copy|archive|disabled)(?:[._\-\s]|$)/i', $folder) === 1
        || strpos($folder, 'no need') !== false;
};

$atomicWrite = static function (string $path, string $contents): bool {
    $dir = dirname($path);
    if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
        return false;
    }

    $tmp = $path . '.' . getmypid() . '.' . str_replace('.', '', uniqid('', true)) . '.tmp';
    if (@file_put_contents($tmp, $contents, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }

    if (! @rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }

    return true;
};

if (! is_dir($runtimeDir) && ! @mkdir($runtimeDir, 0775, true) && ! is_dir($runtimeDir)) {
    return;
}

/*
 * Build a cheap fingerprint without parsing JSON. This catches server moves,
 * .env changes, module add/remove/module.json replacement, activation changes,
 * and portability release changes. On a stable request it avoids reading all
 * module descriptors.
 */
$releaseSignature = $readPhpReturnString($signaturePath);
$quickParts = [
    $guardVersion,
    $releaseSignature,
    (string) (realpath($basePath) ?: $basePath),
    is_file($basePath . '/.env') ? ((string) @filemtime($basePath . '/.env') . ':' . (string) @filesize($basePath . '/.env')) : 'no-env',
    is_file($statusPath) ? ((string) @filemtime($statusPath) . ':' . (string) @filesize($statusPath)) : 'no-status',
];

$moduleDirectories = is_dir($modulesPath) ? (glob($modulesPath . '/*', GLOB_ONLYDIR) ?: []) : [];
sort($moduleDirectories, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($moduleDirectories as $dir) {
    $folder = basename($dir);
    $jsonPath = $dir . '/module.json';
    $quarantinedPath = $dir . '/module.json.portability-disabled';
    $quickParts[] = $folder;
    if (is_file($jsonPath)) {
        $quickParts[] = 'live:' . (string) @filemtime($jsonPath) . ':' . (string) @filesize($jsonPath);
    } elseif (is_file($quarantinedPath)) {
        $quickParts[] = 'quarantined:' . (string) @filemtime($quarantinedPath) . ':' . (string) @filesize($quarantinedPath);
    } else {
        $quickParts[] = 'no-json';
    }
}
$quickSignature = hash('sha256', implode('|', $quickParts));
$quickMarker = $runtimeDir . '/.portable_foundation_v2.quick';
$previousQuick = is_file($quickMarker) ? trim((string) @file_get_contents($quickMarker)) : '';

if ($previousQuick !== '' && hash_equals($previousQuick, $quickSignature)) {
    return;
}

$lockHandle = @fopen($runtimeDir . '/.portable_foundation_v2.lock', 'c');
if ($lockHandle !== false && ! @flock($lockHandle, LOCK_EX)) {
    @fclose($lockHandle);
    return;
}

try {
    // Re-check after waiting for another request that may already have repaired it.
    $previousQuick = is_file($quickMarker) ? trim((string) @file_get_contents($quickMarker)) : '';
    if ($previousQuick !== '' && hash_equals($previousQuick, $quickSignature)) {
        return;
    }

    /* Quarantine only module descriptors inside obvious backup folders. */
    foreach ($moduleDirectories as $dir) {
        $folder = basename($dir);
        if (! $isBackupLike($folder)) {
            continue;
        }

        $descriptor = $dir . '/module.json';
        $quarantined = $dir . '/module.json.portability-disabled';
        if (is_file($descriptor) && ! is_file($quarantined)) {
            @rename($descriptor, $quarantined);
        }
    }

    /*
     * Reconcile activation metadata without replacing live settings.
     * Nwidart FileActivator keys by module.json `name`; older ERP code often
     * keys by the physical folder. Mirror both identities. Explicit false on
     * either identity always wins. Missing installed modules default true so
     * Manage Side Bar remains the per-business authority.
     */
    $statuses = [];
    if (is_file($statusPath)) {
        $decoded = json_decode((string) @file_get_contents($statusPath), true);
        if (is_array($decoded)) {
            $statuses = $decoded;
        }
    }

    $catalog = [];
    foreach ($moduleDirectories as $dir) {
        $folder = basename($dir);
        if ($isBackupLike($folder)) {
            continue;
        }

        $jsonPath = $dir . '/module.json';
        if (! is_file($jsonPath)) {
            continue;
        }

        $raw = @file_get_contents($jsonPath);
        $json = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($json)) {
            continue;
        }

        $name = trim((string) ($json['name'] ?? $folder));
        if ($name === '') {
            $name = $folder;
        }

        $catalog[] = [
            'folder' => $folder,
            'name' => $name,
            'hash' => is_string($raw) ? sha1($raw) : '',
        ];
    }

    usort($catalog, static function (array $a, array $b): int {
        return strnatcasecmp((string) $a['folder'], (string) $b['folder']);
    });

    $statusChanged = false;
    foreach ($catalog as $module) {
        $folder = (string) $module['folder'];
        $name = (string) $module['name'];
        $keys = array_values(array_unique([$name, $folder]));

        $found = false;
        $disabled = false;
        foreach ($keys as $key) {
            if (! array_key_exists($key, $statuses)) {
                continue;
            }
            $found = true;
            if (! (bool) $statuses[$key]) {
                $disabled = true;
            }
        }

        $desired = $found ? ! $disabled : true;
        foreach ($keys as $key) {
            if (! array_key_exists($key, $statuses) || (bool) $statuses[$key] !== $desired) {
                $statuses[$key] = $desired;
                $statusChanged = true;
            }
        }
    }

    if ($statusChanged || ! is_file($statusPath)) {
        $statusBackup = $statusPath . '.before_portable_v2';
        if (is_file($statusPath) && ! is_file($statusBackup)) {
            @copy($statusPath, $statusBackup);
        }

        uksort($statuses, 'strnatcasecmp');
        $json = json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (is_string($json)) {
            $atomicWrite($statusPath, $json . PHP_EOL);
        }
    }

    $statusHash = is_file($statusPath) ? (string) @hash_file('sha256', $statusPath) : 'no-status';
    $catalogParts = [];
    foreach ($catalog as $module) {
        $catalogParts[] = (string) $module['folder'];
        $catalogParts[] = (string) $module['name'];
        $catalogParts[] = (string) $module['hash'];
    }
    $catalogHash = hash('sha256', implode('|', $catalogParts));

    // Config reads this tiny file to scope Redis/database-backed Nwidart cache.
    $catalogHashFile = $basePath . '/bootstrap/cache/portable_module_catalog.hash';
    $atomicWrite($catalogHashFile, $catalogHash . PHP_EOL);

    $effective = hash('sha256', implode('|', [
        $guardVersion,
        $releaseSignature,
        (string) (realpath($basePath) ?: $basePath),
        is_file($basePath . '/.env') ? (string) @hash_file('sha256', $basePath . '/.env') : 'no-env',
        $statusHash,
        $catalogHash,
    ]));

    $effectiveMarker = $runtimeDir . '/.portable_foundation_v2.signature';
    $previousEffective = is_file($effectiveMarker) ? trim((string) @file_get_contents($effectiveMarker)) : '';

    if ($previousEffective === '' || ! hash_equals($previousEffective, $effective)) {
        $removeFile = static function (string $path): void {
            if (is_file($path) || is_link($path)) {
                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate($path, true);
                }
                @unlink($path);
            }
        };

        $removeFilesRecursively = static function (string $directory) use ($removeFile): void {
            if (! is_dir($directory)) {
                return;
            }

            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $item) {
                    $path = $item->getPathname();
                    if ($item->isFile() || $item->isLink()) {
                        $removeFile($path);
                    } elseif ($item->isDir()) {
                        @rmdir($path);
                    }
                }
            } catch (Throwable $e) {
                // Cache invalidation is best-effort; Laravel can still boot.
            }
        };

        $bootstrapCache = $basePath . '/bootstrap/cache';
        foreach (glob($bootstrapCache . '/routes-*.php') ?: [] as $file) {
            $removeFile($file);
        }
        foreach (glob($bootstrapCache . '/*_module.php') ?: [] as $file) {
            $removeFile($file);
        }
        foreach ([
            'routes.php',
            'config.php',
            'events.php',
            'compiled.php',
            'automatic_module_registry_manifest.php',
            'automatic_module_manage_sections.php',
            'superadmin_manage_permission_keys.php',
        ] as $file) {
            $removeFile($bootstrapCache . '/' . $file);
        }

        // Persistent Redis/database caches are protected by scoped keys in
        // config/modules.php. File cache/views can be safely purged here.
        $removeFilesRecursively($basePath . '/storage/framework/cache/data');
        $removeFilesRecursively($basePath . '/storage/framework/views');

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        $atomicWrite($effectiveMarker, $effective . PHP_EOL);
    }

    /*
     * Recompute the cheap signature after status reconciliation/quarantine;
     * those operations may have changed file mtime/size during this request.
     */
    clearstatcache();
    $finalQuickParts = [
        $guardVersion,
        $releaseSignature,
        (string) (realpath($basePath) ?: $basePath),
        is_file($basePath . '/.env') ? ((string) @filemtime($basePath . '/.env') . ':' . (string) @filesize($basePath . '/.env')) : 'no-env',
        is_file($statusPath) ? ((string) @filemtime($statusPath) . ':' . (string) @filesize($statusPath)) : 'no-status',
    ];
    foreach ($moduleDirectories as $dir) {
        $folder = basename($dir);
        $jsonPath = $dir . '/module.json';
        $quarantinedPath = $dir . '/module.json.portability-disabled';
        $finalQuickParts[] = $folder;
        if (is_file($jsonPath)) {
            $finalQuickParts[] = 'live:' . (string) @filemtime($jsonPath) . ':' . (string) @filesize($jsonPath);
        } elseif (is_file($quarantinedPath)) {
            $finalQuickParts[] = 'quarantined:' . (string) @filemtime($quarantinedPath) . ':' . (string) @filesize($quarantinedPath);
        } else {
            $finalQuickParts[] = 'no-json';
        }
    }
    $atomicWrite($quickMarker, hash('sha256', implode('|', $finalQuickParts)) . PHP_EOL);
} catch (Throwable $e) {
    // A portability safeguard must never prevent the ERP itself from booting.
} finally {
    if ($lockHandle !== false) {
        @flock($lockHandle, LOCK_UN);
        @fclose($lockHandle);
    }
}
