<?php

namespace Modules\LeadsNew\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\LeadsNew\Services\Validation\LeadsNewServerSmokeValidator;

class LeadsNewSmokeCheckCommand extends Command
{
    protected $signature = 'leads-new:smoke-check';
    protected $description = 'Run a lightweight Leads-New server smoke check.';

    public function handle(LeadsNewServerSmokeValidator $validator): int
    {
        $this->info('Leads-New smoke check started.');

        foreach ($validator->expectedRoutes() as $routeName) {
            $this->line((Route::has($routeName) ? '[OK] ' : '[MISSING] ') . 'Route: ' . $routeName);
        }

        foreach ($validator->expectedTables() as $table) {
            $this->line((Schema::hasTable($table) ? '[OK] ' : '[MISSING] ') . 'Table: ' . $table);
        }

        $this->info('Leads-New smoke check completed.');
        return self::SUCCESS;
    }
}
