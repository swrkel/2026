<?php
namespace Modules\AirlineTicketingNew\Services\Installer;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class ModuleInstallerService
{
    public function install(): array
    {
        Artisan::call('module:migrate', [
            'module' => 'AirlineTicketingNew',
            '--force' => true,
        ]);

        Artisan::call('optimize:clear');

        return [
            'status' => Schema::hasTable('atn_settings') ? 'installed' : 'attention_required',
            'migration_output' => Artisan::output(),
            'installed_at' => now()->toDateTimeString(),
        ];
    }
}
