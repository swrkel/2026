<?php

namespace Modules\PetroDirectNew\Console\Commands;

use Illuminate\Console\Command;

class InstallPetroDirectNew extends Command
{
    protected $signature = 'petro-direct-new:install {--seed-permissions}';
    protected $description = 'Install Petro Direct-New tenant tables and optional permissions.';

    public function handle(): int
    {
        $this->call('module:migrate', ['module' => 'PetroDirectNew', '--force' => true]);
        if ($this->option('seed-permissions')) {
            $this->call('db:seed', [
                '--class' => 'Modules\\PetroDirectNew\\Database\\Seeders\\PetroDirectNewPermissionSeeder',
                '--force' => true,
            ]);
        }
        $this->info('Petro Direct-New installation completed for the active tenant connection.');
        return 0;
    }
}
