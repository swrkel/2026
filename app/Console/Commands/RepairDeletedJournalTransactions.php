<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LA-1214: repair account transactions left behind by the old journal delete.
 *
 * Until this fix, JournalController@destroy flipped an account transaction's
 * type (debit <-> credit), set journal_deleted = 1 and left the row LIVE. So a
 * deleted debit of 1,000 kept contributing MINUS 1,000 to the account instead
 * of 0, and every balance was understated by twice the value of each deleted
 * journal.
 *
 * These rows are identifiable exactly:
 *
 *     journal_deleted = 1  AND  deleted_at IS NULL
 *
 * A row fixed by the new code is soft-deleted, so it has deleted_at set and is
 * skipped. Only rows written by the old path match, which also makes this
 * command idempotent - run it twice and the second run finds nothing.
 *
 * The repair does two things per row:
 *   1. flips the type BACK, so the history is truthful about what was posted
 *   2. soft-deletes the row, so it stops affecting balances
 */
class RepairDeletedJournalTransactions extends Command
{
    protected $signature = 'account:repair-deleted-journals
        {--dry-run : Report what would change and write nothing}
        {--no-restore-type : Soft-delete only, leave the flipped type as it is}';

    protected $description = 'Soft-delete account transactions left live by the old journal delete, and undo the type flip.';

    public function handle(): int
    {
        if (! Schema::hasTable('account_transactions')) {
            $this->error('account_transactions table not found.');

            return self::FAILURE;
        }

        foreach (['journal_deleted', 'deleted_at'] as $col) {
            if (! Schema::hasColumn('account_transactions', $col)) {
                $this->error("account_transactions.{$col} does not exist. Nothing to do.");

                return self::FAILURE;
            }
        }

        $dryRun      = (bool) $this->option('dry-run');
        $restoreType = ! $this->option('no-restore-type');

        $rows = DB::table('account_transactions')
            ->where('journal_deleted', 1)
            ->whereNull('deleted_at')
            ->select('id', 'account_id', 'type', 'amount', 'operation_date', 'journal_entry')
            ->get();

        $this->info('Mode : ' . ($dryRun ? 'DRY RUN - nothing will be written' : 'LIVE'));
        $this->newLine();

        if ($rows->isEmpty()) {
            $this->info('Nothing to repair. No account transaction is marked as a deleted journal '
                      . 'while still counting towards balances.');

            return self::SUCCESS;
        }

        // The balance error is TWICE the value of these rows: the posting was
        // never removed, and it was then counted on the opposite side.
        $total = (float) $rows->sum('amount');

        $this->line('  rows still counting ....... ' . number_format($rows->count()));
        $this->line('  total amount .............. ' . number_format($total, 2));
        $this->line('  balance error they cause .. ' . number_format($total * 2, 2)
                  . '  (posting not removed, then counted the other way)');
        $this->newLine();

        $byAccount = $rows->groupBy('account_id')
            ->map(fn ($g) => ['rows' => $g->count(), 'amount' => (float) $g->sum('amount')])
            ->sortByDesc('amount')
            ->take(15);

        $this->table(
            ['account_id', 'rows', 'amount'],
            $byAccount->map(fn ($v, $k) => [$k, $v['rows'], number_format($v['amount'], 2)])->values()->all()
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry run finished. Nothing was written.');
            $this->line('Balances will CHANGE when this is applied for real. That is the point -');
            $this->line('they are wrong now. Check the figures above against expectations first.');

            return self::SUCCESS;
        }

        if (! $this->option('no-interaction')) {
            $this->newLine();
            $this->warn('This changes account balances. Back the table up first:');
            $this->line('  mysqldump DBNAME account_transactions > ~/backup_account_transactions_$(date +%Y%m%d_%H%M).sql');
            $this->newLine();

            if (! $this->confirm('Continue?', false)) {
                $this->warn('Aborted. Nothing was changed.');

                return self::SUCCESS;
            }
        }

        $now      = now();
        $repaired = 0;

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        foreach ($rows->chunk(500) as $chunk) {
            DB::transaction(function () use ($chunk, $restoreType, $now, &$repaired, $bar) {
                foreach ($chunk as $row) {
                    $update = ['deleted_at' => $now];

                    if ($restoreType) {
                        // Undo the flip so the history records what was posted.
                        $update['type'] = $row->type === 'debit' ? 'credit' : 'debit';
                    }

                    DB::table('account_transactions')
                        ->where('id', $row->id)
                        ->whereNull('deleted_at')
                        ->update($update);

                    $repaired++;
                    $bar->advance();
                }
            });
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Repaired ' . number_format($repaired) . ' row(s).');
        $this->line('They are soft-deleted, so the history is intact and the deleted-items');
        $this->line('report still shows them as "Journal Deleted".');
        $this->newLine();
        $this->warn('Account balances have changed. Check the Finished Goods Account and any');
        $this->warn('account listed above against expectations before telling users it is done.');

        return self::SUCCESS;
    }
}
