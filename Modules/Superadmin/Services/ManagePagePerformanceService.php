<?php

namespace Modules\Superadmin\Services;

use App\Services\AutomaticModuleRegistry;
use Illuminate\Support\Facades\Cache;
use Modules\Superadmin\Entities\Subscription;

class ManagePagePerformanceService
{
    private function databaseKey(): string
    {
        $database = (string) (config('database.connections.mysql.database') ?: 'default');

        return preg_replace('/[^a-zA-Z0-9_.-]+/', '_', $database) ?: 'default';
    }

    private function businessVersion(int $businessId): int
    {
        return (int) Cache::get($this->versionKey($businessId), 1);
    }

    private function versionKey(int $businessId): string
    {
        return 'superadmin_manage_page_version:' . $this->databaseKey() . ':' . $businessId;
    }

    public function rememberGlobal(string $name, int $seconds, callable $callback)
    {
        $key = 'superadmin_manage_global:' . $this->databaseKey() . ':' . $name;

        return Cache::remember($key, max(1, $seconds), $callback);
    }

    public function rememberBusiness(int $businessId, string $name, int $seconds, callable $callback)
    {
        $version = $this->businessVersion($businessId);
        $key = 'superadmin_manage_business:' . $this->databaseKey() . ':' . $businessId . ':v' . $version . ':' . $name;

        return Cache::remember($key, max(1, $seconds), $callback);
    }

    public function forgetBusiness(int $businessId): void
    {
        Cache::forever($this->versionKey($businessId), $this->businessVersion($businessId) + 1);
    }

    /**
     * Build a cached allow-list from the actual Manage form. This lets the
     * lightweight permission-only save endpoint reject arbitrary keys while
     * still supporting newly added module checkboxes automatically.
     *
     * @return array<string, bool>
     */
    public function permissionKeyMap(): array
    {
        $bladePath = base_path('Modules/Superadmin/Resources/views/business/manage.blade.php');
        $fingerprint = sha1(implode('|', [
            'manage-permission-key-map-persistent-v1',
            is_file($bladePath) ? (string) md5_file($bladePath) : 'missing',
            implode(',', array_keys(AutomaticModuleRegistry::sidebarModules())),
        ]));
        $manifestPath = bootstrap_path('cache/superadmin_manage_permission_keys.php');

        if (is_file($manifestPath)) {
            try {
                $manifest = include $manifestPath;
                if (is_array($manifest)
                    && ($manifest['fingerprint'] ?? null) === $fingerprint
                    && is_array($manifest['keys'] ?? null)) {
                    return $manifest['keys'];
                }
            } catch (\Throwable $e) {
                // Rebuild below.
            }
        }

        $keys = [];
        $add = static function ($key) use (&$keys): void {
            $key = trim((string) $key);
            if ($key === '' || str_contains($key, '$') || str_contains($key, '[')) {
                return;
            }

            $key = strtolower($key);
            $key = str_replace(['-', '.', '/', '\\'], '_', $key);
            $key = preg_replace('/[^a-z0-9_]+/', '_', $key);
            $key = trim((string) preg_replace('/_+/', '_', (string) $key), '_');

            if ($key !== '' && preg_match('/^[a-z0-9_]+$/', $key)) {
                $keys[$key] = true;
            }
        };

        foreach (Subscription::getBusinessPermissionsArray() as $key) {
            $add($key);
        }
        foreach (AutomaticModuleRegistry::explicitManagePermissionKeys() as $key) {
            $add($key);
        }
        foreach (AutomaticModuleRegistry::manageSections() as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $add($item['key'] ?? '');
            }
        }
        foreach (app(ModulePermissionService::class)->groups() as $canonical => $group) {
            foreach (array_merge(
                [$canonical, $canonical . '_module'],
                $group['aliases'] ?? [],
                $group['parents'] ?? [],
                $group['module_permissions'] ?? []
            ) as $key) {
                $add($key);
            }
        }

        try {
            $directory = dirname($manifestPath);
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
            $content = '<?php return ' . var_export([
                'fingerprint' => $fingerprint,
                'keys' => $keys,
            ], true) . ';' . PHP_EOL;
            $tmp = $manifestPath . '.' . getmypid() . '.tmp';
            if (@file_put_contents($tmp, $content, LOCK_EX) !== false) {
                @rename($tmp, $manifestPath);
            }
        } catch (\Throwable $e) {
            // Read-only hosts continue with the in-memory result.
        }

        return $keys;
    }

    /**
     * @param mixed $values
     * @return array<string, int>
     */
    public function filterPermissionValues($values, array $additionalAllowedKeys = []): array
    {
        if (!is_array($values)) {
            return [];
        }

        $allowed = $this->permissionKeyMap();
        foreach ($additionalAllowedKeys as $additionalKey) {
            $additionalKey = strtolower(trim((string) $additionalKey));
            $additionalKey = str_replace(['-', '.', '/', '\\'], '_', $additionalKey);
            $additionalKey = trim((string) preg_replace('/_+/', '_', (string) preg_replace('/[^a-z0-9_]+/', '_', $additionalKey)), '_');
            if ($additionalKey !== '' && preg_match('/^[a-z0-9_]+$/', $additionalKey)) {
                $allowed[$additionalKey] = true;
            }
        }
        $filtered = [];

        foreach ($values as $key => $value) {
            $normal = strtolower(trim((string) $key));
            $normal = str_replace(['-', '.', '/', '\\'], '_', $normal);
            $normal = trim((string) preg_replace('/_+/', '_', (string) preg_replace('/[^a-z0-9_]+/', '_', $normal)), '_');

            if ($normal === '' || !isset($allowed[$normal])) {
                continue;
            }

            if (is_array($value)) {
                $value = end($value);
            }
            $flag = is_string($value) ? strtolower(trim($value)) : $value;
            $filtered[$normal] = in_array($flag, [1, '1', true, 'true', 'yes', 'on', 'enabled'], true) ? 1 : 0;
        }

        return $filtered;
    }
}
