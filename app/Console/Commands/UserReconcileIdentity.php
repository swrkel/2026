<?php

namespace App\Console\Commands;

use App\Services\UserIdentityReconciler;
use Illuminate\Console\Command;

/**
 * Safe/idempotent schema + backfill command for current live databases and for
 * an imported tenant database. Run --dry-run first.
 */
class UserReconcileIdentity extends Command
{
    protected $signature = 'users:reconcile-identity
        {database? : One database to reconcile, normally an imported tenant database}
        {--all : Reconcile central plus every tenant database}
        {--dry-run : Show exactly what would change and write nothing}
        {--yes : Skip the live confirmation prompt}
        {--central= : Central database name}
        {--pattern= : Database name pattern, e.g. nivasa_%}
        {--connection= : Connection whose credentials to borrow}';

    protected $description = 'Add/backfill immutable global_user_id values without changing numeric users.id.';

    public function handle(): int
    {
        $database = trim((string) ($this->argument('database') ?? ''));
        $all = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');

        if (($database === '' && ! $all) || ($database !== '' && $all)) {
            $this->error('Choose exactly one target: provide a database OR use --all.');
            return self::FAILURE;
        }

        $r = new UserIdentityReconciler(
            $this->option('connection') ?: null,
            $this->option('central') ?: null,
            $this->option('pattern') ?: null
        );

        $targets = $all ? $r->userDatabases() : [$database];
        if ($targets === []) {
            $this->error('No databases with a users table were found.');
            return self::FAILURE;
        }

        $totalUsers = array_sum(array_map(fn (string $db): int => $r->userCount($db), $targets));

        $this->info('Central database : ' . $r->centralDb());
        $this->line('Target databases : ' . count($targets));
        $this->line('Target users     : ' . $totalUsers);
        $this->line('Mode             : ' . ($dryRun ? 'DRY RUN - writes nothing' : 'LIVE'));
        $this->newLine();

        if (! $dryRun && ! $this->option('yes') && $this->input->isInteractive()) {
            if (! $this->confirm(
                'This will only add/backfill global_user_id and its unique index. Existing users.id values and permissions are untouched. Continue?',
                false
            )) {
                $this->warn('Aborted.');
                return self::SUCCESS;
            }
        }

        // In --all mode compute keepers before writes start so any pre-existing
        // collision is repaired deterministically. In one-database mode the
        // target is treated as the imported/copy side and existing estate rows win.
        $keepers = $all ? $r->duplicateKeepers() : [];

        $totals = ['keep' => 0, 'mint' => 0, 'remint' => 0, 'failed' => 0];
        $hadErrors = false;

        foreach ($targets as $target) {
            $this->line('<comment>' . $target . '</comment>');

            $out = $r->reconcileDatabase($target, [
                'dry_run' => $dryRun,
                'estate_wide' => $all,
                'keepers' => $keepers,
            ]);

            foreach ($out['schema'] as $change) {
                $this->line('  schema: ' . $change);
            }

            $changedRows = array_values(array_filter(
                $out['rows'],
                fn (array $row): bool => $row['action'] !== 'keep'
            ));

            if ($changedRows !== []) {
                $previewRows = array_slice($changedRows, 0, 20);
                $this->table(
                    ['user id', 'username', 'business', 'action', 'reason'],
                    array_map(fn (array $row): array => [
                        $row['user_id'],
                        mb_strimwidth($row['username'], 0, 28, '...'),
                        $row['business_id'] ?? '-',
                        strtoupper($row['action']),
                        mb_strimwidth($row['reason'], 0, 55, '...'),
                    ], $previewRows)
                );

                if (count($changedRows) > count($previewRows)) {
                    $this->line('  ... ' . (count($changedRows) - count($previewRows)) . ' more changed user(s) not printed');
                }
            } else {
                $this->line('  users: no identity changes needed');
            }

            foreach ($out['rows'] as $row) {
                $totals[$row['action']] = ($totals[$row['action']] ?? 0) + 1;
            }

            foreach ($out['errors'] as $error) {
                $hadErrors = true;
                $totals['failed']++;
                $this->error('  ' . $error);
            }

            $this->newLine();
        }

        $this->line('Kept existing IDs ...... ' . $totals['keep']);
        $this->line('Minted missing IDs ...... ' . $totals['mint']);
        $this->line('Reminted collisions ..... ' . $totals['remint']);
        $this->line('Errors .................. ' . $totals['failed']);

        if (! $dryRun) {
            $remaining = $r->estateDuplicates(true);
            $this->line('Remaining estate dupes . ' . count($remaining));
            if ($remaining !== []) {
                $hadErrors = true;
                $this->error('Universal-ID collisions remain. Run users:identity-audit before continuing.');
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? 'Dry run finished. Nothing was written.'
            : 'Identity reconciliation finished. Existing numeric users.id values were not changed.');

        return $hadErrors ? self::FAILURE : self::SUCCESS;
    }
}
