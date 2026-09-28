<?php
namespace Modules\AirlineTicketingNew\Services\Upgrade;

use Illuminate\Support\Facades\Artisan;
use Modules\AirlineTicketingNew\Entities\ModuleUpgrade;

class ModuleUpgradeService
{
    public function run(string $fromVersion, string $toVersion): ModuleUpgrade
    {
        $record = ModuleUpgrade::query()->create([
            'from_version' => $fromVersion,
            'to_version' => $toVersion,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            Artisan::call('module:migrate', ['module' => 'AirlineTicketingNew', '--force' => true]);
            Artisan::call('optimize:clear');

            $record->update([
                'status' => 'completed',
                'steps_json' => [
                    'module_migrate' => 'completed',
                    'optimize_clear' => 'completed',
                ],
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $record->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            throw $e;
        }

        return $record->refresh();
    }
}
