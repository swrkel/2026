<?php
namespace Modules\RiceMill\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncPermissions extends Command
{
    protected $signature = 'rcm:sync-permissions {--tenant= : Tenant UID whose permission table must be updated}';
    protected $description = 'Expose Rice Mill permissions to User Management New in the selected tenant.';

    public function handle()
    {
        $tenantUid = trim((string) $this->option('tenant'));
        if ($tenantUid !== '') {
            if (! class_exists(\App\Tenant::class) || ! function_exists('tenancy')) {
                $this->error('Tenant initialization is not available in this installation.');
                return 1;
            }

            $tenant = \App\Tenant::find($tenantUid);
            if (! $tenant) {
                $this->error('Tenant UID ' . $tenantUid . ' was not found.');
                return 1;
            }

            tenancy()->initialize($tenant);
            $this->line('Tenant initialized: ' . $tenantUid . ' / DB ' . DB::connection()->getDatabaseName());
        }

        if (! Schema::hasTable('permissions')) {
            $this->warn('Host permissions table not found in DB ' . DB::connection()->getDatabaseName() . '.');
            return 0;
        }

        $permissions = array_values(array_unique(array_merge(
            (array) config('ricemill.permissions', []),
            // Keep Purchase Approval explicit: User Management New must always
            // be able to assign/revoke this right independently from create/view.
            ['rice_mill.paddy_purchase.approve']
        )));

        $cols = Schema::getColumnListing('permissions');
        foreach ($permissions as $name) {
            $data = ['name' => $name];
            $match = ['name' => $name];

            if (in_array('guard_name', $cols, true)) {
                $data['guard_name'] = 'web';
                $match['guard_name'] = 'web';
            }
            if (in_array('created_at', $cols, true)) {
                $data['created_at'] = now();
            }
            if (in_array('updated_at', $cols, true)) {
                $data['updated_at'] = now();
            }

            DB::table('permissions')->updateOrInsert($match, $data);
        }

        $this->info('Rice Mill permissions synchronized: ' . count($permissions));
        $this->line('Purchase approval permission: rice_mill.paddy_purchase.approve');
        return 0;
    }
}
