<?php
namespace Modules\HRManager\Providers;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        foreach (['web.php','employees.php','setup.php','attendance.php','face.php','leave.php','payroll.php','employee_records.php','reports.php'] as $file) {
            $path = module_path('HRManager', 'Routes/'.$file);
            if (file_exists($path)) { Route::middleware('web')->group($path); }
        }
    }
}
