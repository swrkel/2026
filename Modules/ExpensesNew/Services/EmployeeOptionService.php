<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IS1991 (#2): the employee names for the Employee dropdown.
 *
 * The issue asks for HR Module > Employees > List Employees. That list does not
 * belong to this module, and which table holds it depends on which HR module an
 * installation has - Config/integration.php names HRManager, but the employee
 * lookup already written into CategoryController assumed a bare `employees`
 * table. Two places guessing separately is how they end up disagreeing, so the
 * guess is made ONCE, here, and both callers use it.
 *
 * CANDIDATES is tried in order and the first table that exists wins. If your HR
 * module stores employees somewhere else, add it to the top of that list and
 * nothing else in the module needs touching.
 *
 * Nothing here throws. An installation with no HR module gets an empty list, so
 * the Employee checkbox simply offers nothing to pick rather than breaking the
 * category form.
 */
class EmployeeOptionService
{
    /**
     * Table => [first name column, last name column, single name column].
     *
     * A null last-name column means the table holds one name column only.
     */
    private const CANDIDATES = [
        'hrm_employees' => ['first_name', 'last_name', null],
        'hr_employees' => ['first_name', 'last_name', null],
        'employees' => ['first_name', 'last_name', null],
        // UltimatePOS-family HR modules keep employees as user records. Used
        // only when no dedicated employee table is present.
        'users' => ['first_name', 'last_name', null],
    ];

    /**
     * Employee names for a business, as id => name.
     */
    public function options(int $businessId): Collection
    {
        $table = $this->resolveTable();

        if ($table === null) {
            return collect();
        }

        [$first, $last, $single] = self::CANDIDATES[$table];

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        // Only rows that are still current, where the table says so.
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (Schema::hasColumn($table, 'termination')) {
            $query->where('termination', 0);
        } elseif (Schema::hasColumn($table, 'status')) {
            $query->where('status', 'active');
        }

        $nameExpression = $this->nameExpression($table, $first, $last, $single);

        if ($nameExpression === null) {
            return collect();
        }

        return $query
            ->selectRaw('id, ' . $nameExpression . ' AS full_name')
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->reject(static fn ($name): bool => trim((string) $name) === '');
    }

    /**
     * The first candidate table that is actually present.
     */
    private function resolveTable(): ?string
    {
        foreach (array_keys(self::CANDIDATES) as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    /**
     * Build the display-name expression from whichever columns exist.
     */
    private function nameExpression(string $table, ?string $first, ?string $last, ?string $single): ?string
    {
        if ($single !== null && Schema::hasColumn($table, $single)) {
            return "TRIM(COALESCE({$single}, ''))";
        }

        $hasFirst = $first !== null && Schema::hasColumn($table, $first);
        $hasLast = $last !== null && Schema::hasColumn($table, $last);

        if ($hasFirst && $hasLast) {
            return "TRIM(CONCAT(COALESCE({$first}, ''), ' ', COALESCE({$last}, '')))";
        }

        if ($hasFirst) {
            return "TRIM(COALESCE({$first}, ''))";
        }

        foreach (['name', 'employee_name', 'full_name', 'username'] as $fallback) {
            if (Schema::hasColumn($table, $fallback)) {
                return "TRIM(COALESCE({$fallback}, ''))";
            }
        }

        return null;
    }
}
