<?php

namespace App\Console\Commands;

use App\Services\BusinessIdentityReconciler;
use Illuminate\Console\Command;

/**
 * Run this as the LAST STEP of every database import.
 *
 * Safe to run twice - the second run reports "keep" for everything and
 * changes nothing.
 */
class BusinessReconcileIdentity extends Command
{
    protected $signature = 'business:reconcile-identity
        {tenant : The tenant database name, e.g. nivasa_maha}
        {--dry-run : Show what would happen and write nothing}
        {--force-remint : Give every business a brand new uid (rarely needed)}
        {--no-subscriptions : Do not copy the tenant permissions into central}
        {--tenant-key= : Value to store in business.tenant_id (default: the database name)}
        {--central= : Central database name}
        {--pattern= : Database name pattern, e.g. nivasa_%}
        {--connection= : Connection whose credentials to borrow}';

    protected $description = 'Give an imported tenant database identities that agree with central.';

    public function handle(): int
    {
        $tenantDb = $this->argument('tenant');
        $dryRun   = (bool) $this->option('dry-run');

        $r = new BusinessIdentityReconciler(
            $this->option('connection') ?: null,
            $this->option('central') ?: null,
            $this->option('pattern') ?: null
        );

        $this->info('Central database : ' . $r->centralDb());
        $this->info('Tenant database  : ' . $tenantDb);
        $this->info('Mode             : ' . ($dryRun ? 'DRY RUN - nothing will be written' : 'LIVE'));

        if ($this->option('force-remint') && ! $dryRun && ! $this->option('no-interaction')) {
            if (! $this->confirm('--force-remint will detach every business from its central permissions. Continue?', false)) {
                $this->warn('Aborted.');

                return self::SUCCESS;
            }
        }

        $this->newLine();

        $out = $r->reconcile($tenantDb, [
            'dry_run'       => $dryRun,
            'force_remint'  => (bool) $this->option('force-remint'),
            'subscriptions' => ! $this->option('no-subscriptions'),
            'tenant_key'    => $this->option('tenant-key') ?: $tenantDb,
        ]);

        foreach ($out['schema'] as $line) {
            $this->line('  schema: ' . $line);
        }

        if ($out['schema']) {
            $this->newLine();
        }

        if (! $out['rows']) {
            $this->warn('No businesses processed.');
            foreach ($out['errors'] as $e) {
                $this->error('  ' . $e);
            }

            return $out['errors'] ? self::FAILURE : self::SUCCESS;
        }

        $rows = array_map(fn ($x) => [
            $x['business_id'],
            mb_strimwidth((string) $x['name'], 0, 28, '...'),
            strtoupper($x['action']),
            $x['central_id'] ?? '-',
            $x['subs'],
            mb_strimwidth($x['why'], 0, 46, '...'),
        ], $out['rows']);

        $this->table(
            ['biz id', 'name', 'action', 'central id', 'perms', 'reason'],
            $rows
        );

        $count = fn ($a) => count(array_filter($out['rows'], fn ($x) => $x['action'] === $a));

        $this->newLine();
        $this->line('  left alone .......... ' . $count(BusinessIdentityReconciler::KEEP));
        $this->line('  newly identified .... ' . $count(BusinessIdentityReconciler::MINT));
        $this->line('  re-identified ....... ' . $count(BusinessIdentityReconciler::REMINT));
        $this->line('  failed .............. ' . $count('FAILED'));

        if ($out['errors']) {
            $this->newLine();
            $this->error('Problems that need a human:');
            foreach ($out['errors'] as $e) {
                $this->error('  ' . $e);
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? 'Dry run finished. Nothing was written. Re-run without --dry-run to apply.'
            : 'Done. Existing screens are unaffected until the permission lookup is switched to uid.');

        return $out['errors'] ? self::FAILURE : self::SUCCESS;
    }
}
