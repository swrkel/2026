<?php

namespace App\Console\Commands;

use App\Services\BusinessIdentityReconciler;
use Illuminate\Console\Command;

/**
 * One-off. Walks every tenant database and registers its businesses in
 * central. This is the catch-up for everything created before
 * registerBusinessCentrally() existed.
 */
class BusinessBackfillCentralRegistry extends Command
{
    protected $signature = 'business:backfill-registry
        {--dry-run : Show what would happen and write nothing}
        {--only= : Comma separated list of databases to limit the run to}
        {--except= : Comma separated list of databases to skip}
        {--no-subscriptions : Do not copy tenant permissions into central}
        {--central= : Central database name}
        {--pattern= : Database name pattern, e.g. nivasa_%}
        {--connection= : Connection whose credentials to borrow}';

    protected $description = 'Register every existing tenant business in the central registry.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $r = new BusinessIdentityReconciler(
            $this->option('connection') ?: null,
            $this->option('central') ?: null,
            $this->option('pattern') ?: null
        );

        $dbs = $r->tenantDatabases();

        if ($only = $this->option('only')) {
            $keep = array_map('trim', explode(',', $only));
            $dbs  = array_values(array_intersect($dbs, $keep));
        }

        if ($except = $this->option('except')) {
            $drop = array_map('trim', explode(',', $except));
            $dbs  = array_values(array_diff($dbs, $drop));
        }

        $this->info('Central database : ' . $r->centralDb());
        $this->info('Databases        : ' . count($dbs));
        $this->info('Mode             : ' . ($dryRun ? 'DRY RUN - nothing will be written' : 'LIVE'));
        $this->newLine();

        if (! $dryRun && ! $this->option('no-interaction')) {
            $this->warn('This writes to the central database. Make sure you have a backup of ' . $r->centralDb() . '.');
            if (! $this->confirm('Continue?', false)) {
                $this->warn('Aborted.');

                return self::SUCCESS;
            }
            $this->newLine();
        }

        $summary = [];
        $problems = [];

        foreach ($dbs as $db) {
            $this->line("<comment>{$db}</comment>");

            $out = $r->reconcile($db, [
                'dry_run'       => $dryRun,
                'force_remint'  => false,
                'subscriptions' => ! $this->option('no-subscriptions'),
                'tenant_key'    => $db,
            ]);

            foreach ($out['schema'] as $line) {
                $this->line('  schema: ' . $line);
            }

            $count = fn ($a) => count(array_filter($out['rows'], fn ($x) => $x['action'] === $a));

            $kept    = $count(BusinessIdentityReconciler::KEEP);
            $minted  = $count(BusinessIdentityReconciler::MINT);
            $rem     = $count(BusinessIdentityReconciler::REMINT);
            $failed  = $count('FAILED');
            $perms   = array_sum(array_column($out['rows'], 'subs'));

            $this->line("  businesses {$kept} kept / {$minted} new / {$rem} re-identified / {$failed} failed, {$perms} permission rows copied");

            $summary[] = [$db, count($out['rows']), $kept, $minted, $rem, $perms, $failed];

            foreach ($out['errors'] as $e) {
                $problems[] = "{$db}: {$e}";
            }
        }

        $this->newLine();
        $this->table(
            ['database', 'businesses', 'kept', 'new', 're-id', 'perm rows', 'failed'],
            $summary
        );

        if ($problems) {
            $this->newLine();
            $this->error('Problems that need a human:');
            foreach ($problems as $p) {
                $this->error('  ' . $p);
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? 'Dry run finished. Nothing was written.'
            : 'Done. Run business:identity-audit to confirm the estate is clean.');

        return $problems ? self::FAILURE : self::SUCCESS;
    }
}
