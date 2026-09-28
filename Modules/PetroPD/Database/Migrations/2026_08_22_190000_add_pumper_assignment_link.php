<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * STAGE 1 of "one place says which shift".
 *
 * Adds an assignment link to the tables that currently keep their own copy of
 * shift_id and pump_operator_id, and fills it in from those copies.
 *
 * THIS CHANGES NO BEHAVIOUR. Nothing reads the new column yet. The existing
 * columns stay exactly as they are and every screen keeps working from them.
 *
 * WHY
 * ---
 * Eight tables each store their own answer to "which shift, which operator".
 * Nothing keeps those answers equal. On 22 August they disagreed on three
 * settlements at once, an operator could not work for most of a day, and moving
 * ONE pump between two shifts took SEVEN update statements - six of which were
 * found only because the next screen was still wrong.
 *
 * With a link, the answer is stored once on the assignment and everything else
 * follows it. Moving a pump becomes one update and nothing can be left behind.
 *
 * WHAT THE BACKFILL WILL NOT DO
 * -----------------------------
 * Guess. Where a row's own copies disagree with each other, or match no
 * assignment at all, the link is left NULL and the row is reported by
 *
 *     php artisan petropd:shift-link-report
 *
 * That report is the point of this stage as much as the column is: it is every
 * place the data is currently contradictory, which today is invisible until a
 * user notices a figure they do not recognise.
 *
 * REVERSING
 * ---------
 * down() drops the columns. Nothing depends on them at this stage, so it is a
 * clean reversal.
 */
return new class extends Migration
{
    /**
     * Tables that need the link, with the columns to match an assignment on.
     */
    private array $targets = [
        'pump_operator_meter_sales' => ['shift_id', 'pump_operator_id'],
        'pump_operator_payments' => ['shift_id', 'pump_operator_id'],
        'daily_collections' => ['shift_id', 'pump_operator_id'],
    ];

    public function up(): void
    {
        foreach (array_keys($this->targets) as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'pumper_assignment_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                /*
                 * Nullable, and no foreign key at this stage.
                 *
                 * Nullable because the backfill must be allowed to leave a row
                 * unlinked rather than guess - see the class note.
                 *
                 * No FK yet because rows that cannot be linked would block it,
                 * and those rows are exactly what needs looking at first. The
                 * constraint belongs in stage 4, once the report is clean.
                 */
                $t->unsignedBigInteger('pumper_assignment_id')->nullable()->after('id');
                $t->index('pumper_assignment_id');
            });
        }

        $this->backfill();
    }

    /**
     * Fill the link from the copies that already exist, where they agree.
     */
    private function backfill(): void
    {
        if (! Schema::hasTable('pump_operator_assignments')) {
            return;
        }

        foreach ($this->targets as $table => $matchOn) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'pumper_assignment_id')) {
                continue;
            }

            foreach ($matchOn as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue 2;
                }
            }

            /*
             * Only where the match is UNAMBIGUOUS.
             *
             * A row is linked when exactly one assignment matches its business,
             * shift and operator. Where several match - the same operator on the
             * same pump twice in a shift - the link is left null rather than
             * picking one, because picking one is a guess about which physical
             * assignment the row belongs to, and 22 August is a long lesson in
             * what guesses cost here.
             */
            DB::statement("
                UPDATE {$table} t
                JOIN (
                    SELECT business_id, shift_id, pump_operator_id,
                           MIN(id) AS assignment_id,
                           COUNT(*) AS matches
                    FROM pump_operator_assignments
                    GROUP BY business_id, shift_id, pump_operator_id
                    HAVING COUNT(*) = 1
                ) a
                  ON a.business_id = t.business_id
                 AND a.shift_id = t.shift_id
                 AND a.pump_operator_id = t.pump_operator_id
                SET t.pumper_assignment_id = a.assignment_id
                WHERE t.pumper_assignment_id IS NULL
            ");
        }

        /*
         * Meter sale details reach their shift through their own sale, not
         * through an assignment of their own. That is the correct shape: a
         * detail belongs to a sale, and the sale belongs to a shift.
         *
         * On 22 August a detail row kept shift 17 after its header moved to 18,
         * and the settlement found the header but got nothing from it.
         */
        if (Schema::hasTable('pump_operator_meter_sale_details')
            && Schema::hasColumn('pump_operator_meter_sale_details', 'sale_id')) {
            // Nothing to add - sale_id is already the link. Recorded here so it
            // is clear this table was considered and not overlooked.
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->targets) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'pumper_assignment_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['pumper_assignment_id']);
                $t->dropColumn('pumper_assignment_id');
            });
        }
    }
};
