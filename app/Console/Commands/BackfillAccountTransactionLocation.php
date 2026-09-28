<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LA-1214: fill in account_transactions.location_id for existing rows.
 *
 * Derives the location the same way the model does on create:
 *   transaction_id -> transactions.location_id
 *   journal_entry  -> journals.location_id
 *
 * Safe to run repeatedly - it only touches rows where location_id is still
 * null, so a second run finds nothing. Safe to interrupt - each chunk is
 * committed on its own, so stopping halfway simply leaves the rest for next
 * time.
 */
class BackfillAccountTransactionLocation extends Command
{
    protected $signature = 'account:backfill-location
        {--dry-run : Report what would change and write nothing}
        {--chunk=2000 : Rows per statement}
        {--limit=0 : Stop after this many rows (0 = no limit)}';

    protected $description = 'Fill account_transactions.location_id from the linked transaction or journal.';

    public function handle(): int
    {
        if (! Schema::hasTable('account_transactions')) {
            $this->error('account_transactions table not found.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('account_transactions', 'location_id')) {
            $this->error('account_transactions.location_id does not exist yet.');
            $this->line('Run the migration first:  php artisan migrate');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunk  = max(100, (int) $this->option('chunk'));
        $limit  = (int) $this->option('limit');

        $this->info('Mode : ' . ($dryRun ? 'DRY RUN - nothing will be written' : 'LIVE'));
        $this->newLine();

        // ---- what is there to do -------------------------------------------
        $total = DB::table('account_transactions')->whereNull('location_id')->count();

        $viaTxn = DB::table('account_transactions as AT')
            ->join('transactions as T', 'T.id', '=', 'AT.transaction_id')
            ->whereNull('AT.location_id')
            ->whereNotNull('T.location_id')
            ->count();

        $viaJrn = DB::table('account_transactions as AT')
            ->join('journals as J', 'J.id', '=', 'AT.journal_entry')
            ->whereNull('AT.location_id')
            ->whereNotNull('J.location_id')
            ->count();

        $this->line('  rows without a location .... ' . number_format($total));
        $this->line('  resolvable via transaction . ' . number_format($viaTxn));
        $this->line('  resolvable via journal ..... ' . number_format($viaJrn));
        $this->line('  unattributable ............. ' . number_format(max(0, $total - $viaTxn - $viaJrn)));
        $this->newLine();

        if ($total === 0) {
            $this->info('Nothing to do. Every row already has a location.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('Dry run finished. Nothing was written.');
            $this->line('Unattributable rows stay null and keep resolving through the join,');
            $this->line('exactly as they did before the column existed.');

            return self::SUCCESS;
        }

        if (! $this->option('no-interaction')) {
            $this->warn('This updates rows in account_transactions. Back it up first:');
            $this->line('  mysqldump DBNAME account_transactions > ~/backup_account_transactions_$(date +%Y%m%d_%H%M).sql');
            $this->newLine();

            if (! $this->confirm('Continue?', false)) {
                $this->warn('Aborted. Nothing was changed.');

                return self::SUCCESS;
            }
        }

        $done = 0;

        // ---- from the linked transaction ------------------------------------
        $done += $this->fillFrom(
            'transactions', 'transaction_id', $viaTxn, $chunk, $limit, $done, 'transaction'
        );

        // ---- then from the linked journal -----------------------------------
        if ($limit === 0 || $done < $limit) {
            $done += $this->fillFrom(
                'journals', 'journal_entry', $viaJrn, $chunk, $limit, $done, 'journal'
            );
        }

        $this->newLine(2);
        $this->info('Updated ' . number_format($done) . ' row(s).');

        $left = DB::table('account_transactions')->whereNull('location_id')->count();
        $this->line('Still without a location: ' . number_format($left)
            . ' (unattributable - no transaction and no journal)');
        $this->newLine();
        $this->line('Those rows are not lost. They keep resolving through the join,');
        $this->line('and they still count in the All Locations view.');

        return self::SUCCESS;
    }

    /**
     * Chunked UPDATE ... JOIN, bounded by id so progress is monotonic.
     */
    private function fillFrom(
        string $table, string $fk, int $expected, int $chunk, int $limit, int $alreadyDone, string $label
    ): int {
        if ($expected === 0) {
            return 0;
        }

        $this->line("Filling from {$label} ...");
        $bar = $this->output->createProgressBar($expected);
        $bar->start();

        $done = 0;
        $lastId = 0;

        while (true) {
            if ($limit > 0 && ($alreadyDone + $done) >= $limit) {
                break;
            }

            $ids = DB::table('account_transactions as AT')
                ->join($table . ' as SRC', 'SRC.id', '=', 'AT.' . $fk)
                ->whereNull('AT.location_id')
                ->whereNotNull('SRC.location_id')
                ->where('AT.id', '>', $lastId)
                ->orderBy('AT.id')
                ->limit($chunk)
                ->pluck('AT.id');

            if ($ids->isEmpty()) {
                break;
            }

            $lastId = $ids->last();

            $affected = DB::table('account_transactions as AT')
                ->join($table . ' as SRC', 'SRC.id', '=', 'AT.' . $fk)
                ->whereIn('AT.id', $ids)
                ->whereNull('AT.location_id')
                ->update(['AT.location_id' => DB::raw('SRC.location_id')]);

            $done += $affected;
            $bar->advance($affected);
        }

        $bar->finish();
        $this->newLine();

        return $done;
    }
}
