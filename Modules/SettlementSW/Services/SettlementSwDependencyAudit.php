<?php

namespace Modules\SettlementSW\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Module-local dependency audit utility for Settlement SW.
 *
 * This class is intentionally read-only. It helps future maintenance by
 * reporting direct references to other functional modules without changing any
 * existing settlement behavior. Platform dependencies such as Laravel, auth,
 * tenancy middleware, DB, and App model wrappers remain allowed compatibility
 * boundaries.
 */
class SettlementSwDependencyAudit
{
    protected array $blockedPatterns = [
        'Modules\\Petro',
        'Modules\\PetroPD',
        'Modules\\PetroDirect',
        'Modules\\Finance',
        'Modules\\Contacts',
        'Modules\\Contact',
        'Modules\\Superadmin',
        'Modules\\Vat',
        'petro::',
        'finance::',
        'contacts::',
        'contact::',
        'superadmin::',
    ];

    public function scan(?string $modulePath = null): array
    {
        $modulePath = $modulePath ?: dirname(__DIR__);
        $findings = [];

        if (! is_dir($modulePath)) {
            return $findings;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulePath));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if (! in_array($file->getExtension(), ['php', 'js', 'css', 'json', 'md'], true)) {
                continue;
            }

            $relative = str_replace($modulePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);

            foreach ($lines as $lineNumber => $line) {
                foreach ($this->blockedPatterns as $pattern) {
                    if (stripos($line, $pattern) !== false) {
                        $findings[] = [
                            'file' => $relative,
                            'line' => $lineNumber + 1,
                            'pattern' => $pattern,
                            'content' => trim($line),
                        ];
                    }
                }
            }
        }

        return $findings;
    }
}
