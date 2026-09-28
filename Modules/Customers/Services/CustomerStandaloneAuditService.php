<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\File;

/**
 * RC13 helper for the Customers standalone verification/final dependency pass.
 *
 * This service scans only the Customers module source tree and reports route,
 * view, controller and class references that still point back to the legacy
 * Contacts customer implementation. It does not change data and is safe to run
 * from an authenticated ERP user with Customers settings/report access.
 */
class CustomerStandaloneAuditService
{
    /**
     * Legacy references that should be removed or intentionally documented.
     * contact_id is excluded because the existing tenant database still stores
     * customer transaction foreign keys with that column name until a future
     * database migration phase is approved.
     */
    protected array $patterns = [
        'App\\Contact',
        'App\\\\Contact',
        'Modules\\Contact',
        'Modules\\\\Contact',
        'ContactsController',
        'contacts::',
        'contact.customer',
        'contact.view',
        'contact.create',
        "route('contacts",
        'route("contacts',
        "view('contact",
        'view("contact',
    ];

    protected array $allowedExtensions = [
        'php', 'blade.php', 'js', 'json', 'css', 'yml', 'yaml', 'xml'
    ];

    public function scan(): array
    {
        $basePath = module_path('Customers');

        if (! is_dir($basePath)) {
            return [
                'base_path' => $basePath,
                'total_files_scanned' => 0,
                'total_matches' => 0,
                'matches' => [],
                'summary' => ['status' => 'missing_module_path'],
            ];
        }

        $matches = [];
        $filesScanned = 0;

        foreach (File::allFiles($basePath) as $file) {
            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());

            if ($this->shouldSkip($relativePath)) {
                continue;
            }

            $filesScanned++;
            $contents = File::get($file->getPathname());
            $lines = preg_split('/\r\n|\r|\n/', $contents);

            foreach ($lines as $index => $line) {
                foreach ($this->patterns as $pattern) {
                    if (stripos($line, $pattern) !== false) {
                        $matches[] = [
                            'file' => $relativePath,
                            'line' => $index + 1,
                            'pattern' => $pattern,
                            'snippet' => trim($line),
                        ];
                    }
                }
            }
        }

        return [
            'base_path' => $basePath,
            'total_files_scanned' => $filesScanned,
            'total_matches' => count($matches),
            'matches' => $matches,
            'summary' => $this->buildSummary($matches),
        ];
    }

    protected function shouldSkip(string $relativePath): bool
    {
        $normalised = str_replace('\\', '/', $relativePath);

        if (str_contains($normalised, '/vendor/') || str_contains($normalised, '/node_modules/')) {
            return true;
        }

        // RC15: ignore module documentation/readme/audit-source files in the runtime dependency score.
        // They intentionally mention old Contacts classes/routes for audit history or scan pattern definitions.
        if (str_starts_with($normalised, 'Documentation/') || str_starts_with($normalised, 'README')) {
            return true;
        }

        if ($normalised === 'Services/CustomerStandaloneAuditService.php') {
            return true;
        }

        $extension = pathinfo($normalised, PATHINFO_EXTENSION);

        if ($extension === 'php' && str_ends_with($normalised, '.blade.php')) {
            return false;
        }

        return ! in_array($extension, $this->allowedExtensions, true);
    }

    protected function buildSummary(array $matches): array
    {
        $byPattern = [];
        $byFile = [];

        foreach ($matches as $match) {
            $byPattern[$match['pattern']] = ($byPattern[$match['pattern']] ?? 0) + 1;
            $byFile[$match['file']] = ($byFile[$match['file']] ?? 0) + 1;
        }

        arsort($byPattern);
        arsort($byFile);

        return [
            'status' => count($matches) === 0 ? 'clean' : 'needs_review',
            'by_pattern' => $byPattern,
            'by_file' => $byFile,
        ];
    }
}
