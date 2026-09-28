<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\File;

class LeadsNewFinalAuditService
{
    public function scan(string $modulePath): array
    {
        $blocked = ['Modules\\Leads\\', 'Modules/Leads/', 'Leads\\Http', 'Leads\\Models'];
        $findings = [];

        foreach (File::allFiles($modulePath) as $file) {
            $content = File::get($file->getPathname());
            foreach ($blocked as $needle) {
                if (str_contains($content, $needle)) {
                    $findings[] = [
                        'file' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                        'match' => $needle,
                    ];
                }
            }
        }

        return [
            'module' => 'LeadsNew',
            'checked_at' => now()->toDateTimeString(),
            'status' => empty($findings) ? 'passed' : 'review_required',
            'findings' => $findings,
        ];
    }
}
