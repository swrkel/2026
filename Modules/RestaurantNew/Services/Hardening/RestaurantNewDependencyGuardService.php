<?php

namespace Modules\RestaurantNew\Services\Hardening;

class RestaurantNewDependencyGuardService
{
    public function scanFile(string $path): array
    {
        $blocked = config('restaurantnew.hardening.blocked_external_module_dependencies', []);
        $content = is_file($path) ? file_get_contents($path) : '';
        $hits = [];
        foreach ($blocked as $module) {
            if ($content && preg_match('/Modules\\\\' . preg_quote($module, '/') . '\\\\/', $content)) {
                $hits[] = $module;
            }
        }
        return array_values(array_unique($hits));
    }

    public function scanModule(): array
    {
        $base = module_path('RestaurantNew');
        $hits = [];
        if (!is_dir($base)) {
            return ['status' => 'warning', 'message' => 'RestaurantNew module path not found.', 'hits' => []];
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        foreach ($iterator as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'js', 'css', 'blade.php'])) { continue; }
            $found = $this->scanFile($file->getPathname());
            if (!empty($found)) {
                $hits[$file->getPathname()] = $found;
            }
        }
        return [
            'status' => empty($hits) ? 'passed' : 'failed',
            'message' => empty($hits) ? 'No blocked module dependencies found.' : 'Blocked dependencies were found.',
            'hits' => $hits,
        ];
    }
}
