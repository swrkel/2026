<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Close Financial Year — the single rule every module consults.
 *
 * WHY THIS IS IN FINANCE
 *
 * A closed year constrains purchases, sales, expenses, journals and
 * settlements. If each module kept its own closing date they would drift, and a
 * system that refuses an entry on one screen while accepting it on another is
 * worse than one with no rule at all.
 *
 * Modules ask this class. They do not read the table.
 */
class YearClosureService
{
    /**
     * The date up to and including which everything is closed, or null.
     *
     * The latest closure that has not been reopened. Several closures
     * accumulate over the years; only the most recent constrains anything.
     */
    public function closedUpto(int $businessId): ?Carbon
    {
        if ($businessId <= 0 || ! Schema::hasTable('finance_year_closures')) {
            return null;
        }

        $row = DB::table('finance_year_closures')
            ->where('business_id', $businessId)
            ->whereNull('reopened_at')
            ->orderByDesc('closed_upto')
            ->first(['closed_upto']);

        return $row ? Carbon::parse($row->closed_upto)->endOfDay() : null;
    }

    /**
     * May a transaction carry this date?
     *
     * Two rules, both from the business record and both cheap to check:
     *   - not on or before a closed year end
     *   - not before the business start date, so nothing predates the system
     */
    public function isDateAllowed(int $businessId, $date): bool
    {
        return $this->rejectionReason($businessId, $date) === null;
    }

    /**
     * Why a date is not allowed, in words a user can act on, or null if it is.
     *
     * Returning the reason rather than a boolean means every screen can say
     * WHY - "the year to 31/03/2026 is closed" is actionable, "invalid date" is
     * not.
     */
    public function rejectionReason(int $businessId, $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            $when = Carbon::parse($date)->startOfDay();
        } catch (\Throwable $e) {
            return 'That date could not be read.';
        }

        $closed = $this->closedUpto($businessId);
        if ($closed && $when->lte($closed)) {
            return 'The financial year up to ' . $closed->format('d/m/Y')
                 . ' is closed. Choose a date after it, or ask a Super Admin to reopen the year.';
        }

        $start = $this->businessStartDate($businessId);
        if ($start && $when->lt($start)) {
            return 'The system opening date is ' . $start->format('d/m/Y')
                 . '. Nothing can be dated before it.';
        }

        return null;
    }

    /** The earliest date any transaction may carry. */
    public function earliestAllowedDate(int $businessId): ?Carbon
    {
        $closed = $this->closedUpto($businessId);
        $start = $this->businessStartDate($businessId);

        if ($closed) {
            $afterClosure = $closed->copy()->addDay()->startOfDay();
            return ($start && $start->gt($afterClosure)) ? $start : $afterClosure;
        }

        return $start;
    }

    protected function businessStartDate(int $businessId): ?Carbon
    {
        if ($businessId <= 0 || ! Schema::hasTable('business')
            || ! Schema::hasColumn('business', 'start_date')) {
            return null;
        }

        $value = DB::table('business')->where('id', $businessId)->value('start_date');

        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    // ------------------------------------------------------------------
    // Closing and reopening
    // ------------------------------------------------------------------

    /**
     * Close everything on or before a date.
     *
     * Refused if it would move the closing date BACKWARDS. Closing to an
     * earlier date than an existing closure would silently reopen the period
     * between them, which is the opposite of what the button says.
     */
    public function close(int $businessId, $closedUpto, int $userId, ?string $label = null, ?string $note = null): array
    {
        try {
            $date = Carbon::parse($closedUpto)->startOfDay();
        } catch (\Throwable $e) {
            return ['success' => false, 'msg' => 'That date could not be read.'];
        }

        if ($date->isFuture()) {
            return ['success' => false, 'msg' => 'A financial year cannot be closed to a future date.'];
        }

        $existing = $this->closedUpto($businessId);
        if ($existing && $date->lte($existing)) {
            return [
                'success' => false,
                'msg' => 'Everything up to ' . $existing->format('d/m/Y')
                       . ' is already closed. Choose a later date.',
            ];
        }

        DB::table('finance_year_closures')->insert([
            'business_id' => $businessId,
            'closed_upto' => $date->toDateString(),
            'label' => $label,
            'note' => $note,
            'closed_by' => $userId,
            'closed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'msg' => 'Financial year closed. Nothing dated on or before '
                   . $date->format('d/m/Y') . ' can now be entered or changed.',
        ];
    }

    /**
     * Reopen a closure.
     *
     * The row is marked, never deleted: the fact that a year was closed, and
     * then reopened by someone, is exactly what an auditor asks about.
     */
    public function reopen(int $closureId, int $businessId, int $userId, ?string $reason = null): array
    {
        $row = DB::table('finance_year_closures')
            ->where('id', $closureId)
            ->where('business_id', $businessId)
            ->first();

        if (! $row) {
            return ['success' => false, 'msg' => 'That closure could not be found.'];
        }
        if ($row->reopened_at) {
            return ['success' => false, 'msg' => 'That year has already been reopened.'];
        }

        DB::table('finance_year_closures')->where('id', $closureId)->update([
            'reopened_by' => $userId,
            'reopened_at' => now(),
            'reopen_reason' => $reason,
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'msg' => 'Financial year to ' . Carbon::parse($row->closed_upto)->format('d/m/Y')
                   . ' reopened. The closure remains on record.',
        ];
    }

    /** Every closure for a business, newest first. */
    public function history(int $businessId)
    {
        if (! Schema::hasTable('finance_year_closures')) {
            return collect();
        }

        return DB::table('finance_year_closures')
            ->where('business_id', $businessId)
            ->orderByDesc('closed_upto')
            ->get();
    }
}
