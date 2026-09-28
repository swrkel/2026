<?php

namespace Modules\Distribution\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class DistributionStandaloneDependencyScanner
{
    protected array $patterns = [
        'main_app_namespace' => '/\\b(use\\s+App\\\\|extends\\s+\\\\App\\\\|new\\s+\\\\?App\\\\|App\\\\)/',
        'main_view_include' => '/@(extends|include|includeIf|includeWhen)\\((?:\'|")(?!(distribution::|adminlte::|components\\.|vendor\\.))/',
        'main_public_js' => '/public\\/js\\/(payment|pos|app)\\.js|asset\\((?:\'|")js\\//',
        'other_module_namespace' => '/Modules\\\\(?!Distribution\\\\)/',
    ];

    public function scan(string $modulePath): array
    {
        $findings = [];
        if (!is_dir($modulePath)) {
            return $findings;
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulePath));
        /** @var SplFileInfo $file */
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['php', 'blade', 'js', 'json', 'css'], true)) {
                continue;
            }
            $content = @file_get_contents($path) ?: '';
            foreach ($this->patterns as $key => $pattern) {
                if (preg_match($pattern, $content)) {
                    $findings[] = [
                        'type' => $key,
                        'file' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path),
                    ];
                }
            }
        }

        return $findings;
    }
}
