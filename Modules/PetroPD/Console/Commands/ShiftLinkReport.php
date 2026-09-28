<?php

namespace Modules\PetroPD\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every row whose copies of "which shift, which operator" disagree.
 *
 *     php artisan petropd:shift-link-report
 *     php artisan petropd:shift-link-report --business=2
 *
 * Reports only. Changes nothing.
 *
 * WHY THIS IS THE POINT OF STAGE 1
 * --------------------------------
 * Eight tables each keep their own copy of the same two facts. Nothing keeps
 * them equal, so the system can hold a contradiction indefinitely and nobody
 * finds out until a user looks at a figure and does not recognise it.
 *
 * That is how 22 August went: three settlements wrong, discovered one screen at
 * a time over a full day, while an operator could not work.
 *
 * Every one of those was already visible in the data that morning. Nothing was
 * looking. This looks.
 *
 * WHAT IT REPORTS
 * ---------------
 *   unlinked      the backfill could not match the row to one assignment,
 *                 either because its own copies match none, or because they
 *                 match several and picking one would be a guess
 *
 *   contradicted  the row IS linked, but its own copy of shift or operator
 *                 disagrees with the assignment it points at
 *
 * The second is the more serious: two answers exist and the screens choose
 * between them by accident of which query runs.
 */
class ShiftLinkReport extends Command
{
    protected $signature = 'petropd:shift-link-report
                            {--business= : limit to one business id}
                            {--limit=20 : rows to show per finding}';

    protected $description = 'Report rows whose shift/operator copies disagree with their assignment';

    private array $tables = [
        'pump_operator_meter_sales' => 'Meter sales',
        'pump_operator_payments' => 'Payments',
        'daily_collections' => 'Cash collections',
    ];

    public function handle(): int
    {
        $businessId = $this->option('business') ? (int) $this->option('business') : null;
        $limit = max(1, (int) $this->option('limit'));

        $this->line('');

        $totalUnlinked = 0;
        $totalContradicted = 0;

        foreach ($this->tables as $table => $label) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'pumper_assignment_id')) {
                $this->line(sprintf('  %-20s not present - run the migration first', $label));
                continue;
            }

            $unlinked = $this->unlinked($table, $businessId);
            $contradicted = $this->contradicted($table, $businessId);

            $totalUnlinked += $unlinked->count();
            $totalContradicted += $contradicted->count();

            $this->line(sprintf(
                '<comment>%s</comment>  %d unlinked, %d contradicted',
                $label,
                $unlinked->count(),
                $contradicted->count()
            ));

            if ($contradicted->isNotEmpty()) {
                $this->line('    <fg=red>These rows hold two different answers:</>');

                foreach ($contradicted->take($limit) as $row) {
                    $this->line(sprintf(
                        '      id %-6d row says shift %s operator %s, assignment %d says shift %s operator %s',
                        $row->id,
                        $row->row_shift ?? '-',
                        $row->row_operator ?? '-',
                        $row->pumper_assignment_id,
                        $row->assignment_shift ?? '-',
                        $row->assignment_operator ?? '-'
                    ));
                }

                if ($contradicted->count() > $limit) {
                    $this->line(sprintf('      ... and %d more', $contradicted->count() - $limit));
                }
            }

            if ($unlinked->isNotEmpty()) {
                $ids = $unlinked->take($limit)->pluck('id')->implode(', ');
                $this->line('    <fg=yellow>Could not be matched to one assignment:</> ' . $ids
                    . ($unlinked->count() > $limit ? ' ...' : ''));
            }

            $this->line('');
        }

        $this->line(str_repeat('-', 62));
        $this->line(sprintf(
            '%d contradicted, %d unlinked.',
            $totalContradicted,
            $totalUnlinked
        ));

        if ($totalContradicted > 0) {
            $this->line('');
            $this->line('A contradicted row holds two answers to the same question.');
            $this->line('Which is right cannot be decided from the data - on 22 August');
            $this->line('the office had to check the physical slips. Nothing here has');
            $this->line('been changed.');
        }

        return self::SUCCESS;
    }

    private function unlinked(string $table, ?int $businessId)
    {
        $q = DB::table($table)->whereNull('pumper_assignment_id')->select('id');

        if ($businessId !== null && Schema::hasColumn($table, 'business_id')) {
            $q->where('business_id', $businessId);
        }

        return $q->orderBy('id')->get();
    }

    private function contradicted(string $table, ?int $businessId)
    {
        $q = DB::table($table . ' as t')
            ->join('pump_operator_assignments as a', 'a.id', '=', 't.pumper_assignment_id')
            ->whereNotNull('t.pumper_assignment_id')
            ->where(function ($w) {
                $w->whereColumn('t.shift_id', '<>', 'a.shift_id')
                    ->orWhereColumn('t.pump_operator_id', '<>', 'a.pump_operator_id');
            })
            ->select(
                't.id',
                't.pumper_assignment_id',
                't.shift_id as row_shift',
                't.pump_operator_id as row_operator',
                'a.shift_id as assignment_shift',
                'a.pump_operator_id as assignment_operator'
            );

        if ($businessId !== null) {
            $q->where('t.business_id', $businessId);
        }

        return $q->orderBy('t.id')->get();
    }
}
