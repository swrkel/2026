<?php

namespace Modules\MyHealthMembers\Services\Audit;

class MyHealthStandaloneAuditService
{
    public function run(): array
    {
        $base = module_path('MyHealthMembers');

        $checks = [
            'Module Folder' => is_dir($base),
            'Controllers' => is_dir($base . '/Http/Controllers'),
            'Models / Entities' => is_dir($base . '/Entities'),
            'Services' => is_dir($base . '/Services'),
            'Routes' => is_dir($base . '/Routes'),
            'Views' => is_dir($base . '/Resources/views'),
            'Config' => is_dir($base . '/Config'),
            'Database Migrations' => is_dir($base . '/Database/Migrations'),
            'Reports Inside Module' => is_dir($base . '/Resources/views/reports') && file_exists($base . '/Routes/reports.php'),
            'Settings Inside Module' => file_exists($base . '/Routes/settings.php'),
            'Public Registration Routes' => file_exists($base . '/Routes/public.php'),
            'Member Portal Routes' => file_exists($base . '/Routes/portal.php'),
            'Business Consent Routes' => file_exists($base . '/Routes/access.php'),
            'Audit Routes' => file_exists($base . '/Routes/audit.php'),
        ];

        $passed = count(array_filter($checks));
        $total = count($checks);

        return [
            'checks' => $checks,
            'passed' => $passed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
            'status' => $passed === $total ? 'Ready' : 'Needs Attention',
        ];
    }
}
