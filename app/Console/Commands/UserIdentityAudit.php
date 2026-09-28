<?php

namespace App\Console\Commands;

use App\Services\UserIdentityReconciler;
use Illuminate\Console\Command;

/** Read-only estate-wide audit. Safe to run on a live system. */
class UserIdentityAudit extends Command
{
    protected $signature = 'users:identity-audit
        {--central= : Central database name}
        {--pattern= : Database name pattern, e.g. nivasa_%}
        {--connection= : Connection whose credentials to borrow}';

    protected $description = 'Audit universal user IDs across central and tenant databases. Writes nothing.';

    public function handle(): int
    {
        $r = new UserIdentityReconciler(
            $this->option('connection') ?: null,
            $this->option('central') ?: null,
            $this->option('pattern') ?: null
        );

        $this->info('Central database : ' . $r->centralDb());
        $this->info('Scanning pattern : ' . $r->pattern());
        $this->line('Mode             : READ ONLY');
        $this->newLine();

        $table = [];
        $totals = ['users' => 0, 'missing' => 0, 'local_duplicates' => 0];

        foreach ($r->userDatabases() as $database) {
            $a = $r->auditDatabase($database);
            $totals['users'] += (int) $a['users'];
            $totals['missing'] += (int) $a['missing'];
            $totals['local_duplicates'] += (int) $a['local_duplicates'];

            $state = 'ok';
            if (! $a['column']) {
                $state = 'NO COLUMN';
            } elseif ((int) $a['missing'] > 0) {
                $state = 'needs backfill';
            } elseif ((int) $a['local_duplicates'] > 0) {
                $state = 'duplicate IDs';
            } elseif (! $a['unique_index']) {
                $state = 'needs unique index';
            }

            $table[] = [
                $database,
                $a['users'],
                $a['column'] ? 'yes' : 'no',
                $a['missing'],
                $a['local_duplicates'],
                $a['unique_index'] ? 'yes' : 'no',
                $state,
            ];
        }

        $this->table(
            ['database', 'users', 'column', 'missing IDs', 'local dupes', 'unique index', 'state'],
            $table
        );

        $duplicates = $r->estateDuplicates(true);
        $this->newLine();
        if ($duplicates === []) {
            $this->info('No cross-database global_user_id collisions found.');
        } else {
            $this->error('Cross-database global_user_id collisions found:');
            foreach ($duplicates as $uid => $places) {
                $this->line('  ' . $uid . ' -> ' . implode(', ', $places));
            }
        }

        $this->newLine();
        $this->line('Estate users .......... ' . $totals['users']);
        $this->line('Missing universal IDs . ' . $totals['missing']);
        $this->line('Local duplicate groups  ' . $totals['local_duplicates']);
        $this->line('Estate collision groups ' . count($duplicates));
        $this->newLine();
        $this->info('Nothing was changed.');

        return self::SUCCESS;
    }
}
