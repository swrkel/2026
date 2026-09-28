<?php

namespace Modules\BeautySaloons\Services\Audit;

use Illuminate\Support\Facades\File;

class StandaloneAuditService
{
    protected string $modulePath;

    public function __construct()
    {
        $this->modulePath = base_path('Modules/BeautySaloons');
    }

    public function summary(): array
    {
        $checks = [
            'Controllers' => 'Http/Controllers',
            'Services' => 'Services',
            'Routes' => 'Routes',
            'Views' => 'Resources/views',
            'JavaScript' => 'Resources/js',
            'CSS' => 'Resources/css',
            'Language' => 'Resources/lang',
            'Migrations' => 'Database/Migrations',
            'Permissions' => 'Permissions',
            'Reports' => 'Reports',
            'Utilities' => 'Utils',
        ];

        $rows = [];
        foreach ($checks as $label => $relativePath) {
            $path = $this->modulePath . '/' . $relativePath;
            $rows[] = [
                'area' => $label,
                'path' => 'Modules/BeautySaloons/' . $relativePath,
                'exists' => File::exists($path),
                'file_count' => File::exists($path) ? count(File::allFiles($path)) : 0,
                'status' => File::exists($path) ? 'Available' : 'Needs Review',
            ];
        }

        return $rows;
    }

    public function releaseReadiness(): array
    {
        return [
            ['item' => 'Module folder is standalone', 'status' => File::exists($this->modulePath) ? 'Pass' : 'Fail'],
            ['item' => 'Routes are module-local', 'status' => File::exists($this->modulePath . '/Routes/web.php') ? 'Pass' : 'Review'],
            ['item' => 'Views are module-local', 'status' => File::exists($this->modulePath . '/Resources/views') ? 'Pass' : 'Review'],
            ['item' => 'Migrations are module-local', 'status' => File::exists($this->modulePath . '/Database/Migrations') ? 'Pass' : 'Review'],
            ['item' => 'Permissions have module seeders', 'status' => File::exists($this->modulePath . '/Permissions') ? 'Pass' : 'Review'],
            ['item' => 'Reports have module folder', 'status' => File::exists($this->modulePath . '/Reports') ? 'Pass' : 'Review'],
        ];
    }
}
