<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class GlobalModuleRegistryCache
{
    private static ?array $requestModules = null;
    private static array $requestInstalled = [];

    public static function modules(): array
    {
        if (self::$requestModules !== null) {
            return self::$requestModules;
        }

        $resolver = static function (): array {
            try {
                return \Module::toCollection()->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        };

        if (! config('global_performance.enabled', true)) {
            return self::$requestModules = $resolver();
        }

        $signature = self::registrySignature();
        return self::$requestModules = Cache::remember(
            'gpo:module-registry:' . $signature,
            config('global_performance.module_registry_ttl', 300),
            $resolver
        );
    }

    public static function has(string $moduleName): bool
    {
        $key = strtolower($moduleName);
        if (array_key_exists($key, self::$requestInstalled)) {
            return self::$requestInstalled[$key];
        }

        try {
            // Reuse the request/persistent registry instead of asking the module
            // repository to resolve the filesystem again for each has() call.
            foreach (self::modules() as $moduleKey => $details) {
                $candidates = [
                    (string) $moduleKey,
                    (string) ($details['name'] ?? ''),
                    (string) ($details['alias'] ?? ''),
                ];

                foreach ($candidates as $candidate) {
                    if ($candidate !== '' && strtolower($candidate) === $key) {
                        return self::$requestInstalled[$key] = true;
                    }
                }
            }

            return self::$requestInstalled[$key] = false;
        } catch (\Throwable $e) {
            return self::$requestInstalled[$key] = false;
        }
    }

    public static function forget(): void
    {
        self::$requestModules = null;
        self::$requestInstalled = [];
        Cache::forever('gpo:module-registry-version', time());
    }

    private static function registrySignature(): string
    {
        $version = Cache::get('gpo:module-registry-version', 1);
        $statuses = base_path('modules_statuses.json');
        $mtime = is_file($statuses) ? (int) filemtime($statuses) : 0;

        return sha1($version . '|' . $mtime . '|' . base_path('Modules'));
    }
}
