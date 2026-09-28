<?php

namespace Modules\ChurchManagement\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Business and location scoping for Church Management.
 *
 * This is the ONLY place the module reads anything from the host application:
 * the signed-in user and the business/location on the session. Every controller
 * goes through it, so if the module is ever moved to its own authentication
 * this is the single file to change.
 *
 * Everything here is written to survive a database that has not been fully
 * installed. Schema::hasTable and hasColumn are checked before use, and a
 * missing table returns an empty collection instead of throwing. A church
 * running only the Members phase should not see a 500 because a later phase's
 * table does not exist yet.
 */
trait ChurchTenantContext
{
    protected function tablePrefix(): string
    {
        return (string) config('churchmanagement.table_prefix', 'chc_');
    }

    /**
     * Resolve a short name to its prefixed table: 'members' -> 'chc_members'.
     *
     * Controllers name tables without the prefix so that changing
     * churchmanagement.table_prefix moves the whole module in one edit.
     */
    protected function table(string $name): string
    {
        $prefix = $this->tablePrefix();

        return str_starts_with($name, $prefix) ? $name : $prefix . $name;
    }

    protected function businessId(): ?int
    {
        $businessId = session('business.id')
            ?? session('user.business_id')
            ?? session('business_id')
            ?? (Auth::check() ? (Auth::user()->business_id ?? null) : null);

        return $businessId ? (int) $businessId : null;
    }

    protected function locationId(): ?int
    {
        $locationId = session('business_location_id')
            ?? session('location_id')
            ?? request()->get('business_location_id');

        return $locationId ? (int) $locationId : null;
    }

    protected function tableExists(string $name): bool
    {
        try {
            return Schema::hasTable($this->table($name));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * A query already scoped to this business, location and non-deleted rows.
     *
     * Location is applied only when the row actually carries one AND a location
     * is selected. Members belong to a congregation rather than to a till, so
     * most of this module's tables are business-scoped only; forcing a location
     * filter on them would hide every row.
     */
    protected function scopedQuery(string $name)
    {
        $table = $this->table($name);
        $query = DB::table($table);

        if (Schema::hasColumn($table, 'business_id') && $this->businessId()) {
            $query->where($table . '.business_id', $this->businessId());
        }

        /*
         | Location filtering is OPT-IN.
         |
         | A congregation is usually one site even when the business runs
         | several, so a member belongs to the business rather than to a branch.
         | Filtering on location by default would hide every member from a user
         | whose session happens to carry a location - which is most of them.
         |
         | Businesses that genuinely run separate congregations per location set
         | churchmanagement.scope_by_location, and then each location sees only
         | its own roll.
         */
        if (config('churchmanagement.scope_by_location', false)
            && Schema::hasColumn($table, 'business_location_id')
            && $this->locationId()) {
            $query->where($table . '.business_location_id', $this->locationId());
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }

        return $query;
    }

    /**
     * Stamp the scope onto a row about to be written.
     *
     * Set here rather than taken from the form: a posted business_id is a way
     * to write into another tenant's data.
     */
    protected function withScope(array $data): array
    {
        $table = null;

        if ($this->businessId()) {
            $data['business_id'] = $this->businessId();
        }

        /*
         | array_key_exists rather than empty(): a form that offers a location
         | dropdown posts the key even when nothing is chosen, and that explicit
         | "no location" must survive rather than being replaced by whatever the
         | session happens to carry.
         */
        if ($this->locationId() && ! array_key_exists('business_location_id', $data)) {
            $data['business_location_id'] = $this->locationId();
        }

        return $data;
    }

    /**
     * Count rows for a dashboard tile, tolerating a table that is not installed.
     */
    protected function safeCount(string $name, ?callable $constrain = null): int
    {
        if (! $this->tableExists($name)) {
            return 0;
        }

        try {
            $query = $this->scopedQuery($name);

            if ($constrain) {
                $constrain($query);
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * The next code in a sequence, e.g. CM-000001.
     *
     * Derived from the highest existing code rather than from a row count:
     * counting repeats a code as soon as anything is deleted.
     */
    protected function nextCode(string $name, string $column, string $prefix): string
    {
        $number = 1;

        try {
            if ($this->tableExists($name)) {
                $latest = $this->scopedQuery($name)
                    ->where($column, 'like', $prefix . '-%')
                    ->orderByRaw('LENGTH(' . $column . ') DESC')
                    ->orderBy($column, 'desc')
                    ->value($column);

                if ($latest && preg_match('/(\d+)$/', (string) $latest, $matches)) {
                    $number = ((int) $matches[1]) + 1;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to 1. A duplicate is caught by the unique index.
        }

        return $prefix . '-' . str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Update a row, refusing to touch one outside the current scope.
     */
    protected function updateScopedRow(string $name, int $id, array $data): bool
    {
        if (! $this->tableExists($name)) {
            return false;
        }

        $data['updated_at'] = now();

        return (bool) $this->scopedQuery($name)->where('id', $id)->update($data);
    }

    /**
     * Soft delete where the table supports it, hard delete otherwise.
     *
     * Member and family records are the congregation's history. Where the
     * column exists the row is kept and hidden rather than destroyed.
     */
    protected function deleteScopedRow(string $name, int $id): bool
    {
        if (! $this->tableExists($name)) {
            return false;
        }

        $table = $this->table($name);
        $query = $this->scopedQuery($name)->where('id', $id);

        if (Schema::hasColumn($table, 'deleted_at')) {
            return (bool) $query->update(['deleted_at' => now(), 'updated_at' => now()]);
        }

        return (bool) $query->delete();
    }


    /**
     * Business locations, for the optional location selector on a record.
     *
     * Read straight from the shared `business_locations` table rather than
     * through a model, so the module has no compile-time dependency on a class
     * outside itself - and still returns nothing rather than throwing on an
     * install where that table is shaped differently.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function locationOptions()
    {
        try {
            if (! Schema::hasTable('business_locations')) {
                return collect();
            }

            $query = DB::table('business_locations')->select('id', 'name');

            if ($this->businessId() && Schema::hasColumn('business_locations', 'business_id')) {
                $query->where('business_id', $this->businessId());
            }

            if (Schema::hasColumn('business_locations', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if (Schema::hasColumn('business_locations', 'is_active')) {
                $query->where('is_active', 1);
            }

            return $query->orderBy('name')->pluck('name', 'id');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Display names for the users who created the rows on screen.
     *
     * Fetched for the whole page in one query, keyed by id. Looking a name up
     * per row would issue a query per row - the classic N+1, and a roll of any
     * size would slow down as it grew.
     *
     * @param  array<int, int|null>  $userIds
     * @return \Illuminate\Support\Collection
     */
    protected function userNames(array $userIds)
    {
        $userIds = array_values(array_unique(array_filter($userIds)));

        if (empty($userIds)) {
            return collect();
        }

        try {
            if (! Schema::hasTable('users')) {
                return collect();
            }

            $parts = [];
            foreach (['first_name', 'last_name'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $parts[] = "COALESCE(users.{$column}, '')";
                }
            }

            // `users` differs across installs, so the display name is assembled
            // from whichever columns are actually present.
            $expression = $parts
                ? "NULLIF(TRIM(CONCAT_WS(' ', " . implode(', ', $parts) . ")), '')"
                : 'NULL';

            if (Schema::hasColumn('users', 'username')) {
                $expression = "COALESCE({$expression}, users.username)";
            }

            return DB::table('users')
                ->whereIn('id', $userIds)
                ->select('id', DB::raw("{$expression} as display_name"))
                ->pluck('display_name', 'id');
        } catch (\Throwable $e) {
            return collect();
        }
    }


    /**
     * Format a money amount to the business's own currency precision.
     *
     * Read from the session's business record, which is where the rest of the
     * application keeps it, with a sane fallback. Deliberately NOT routed
     * through App\Utils\Util: a two-line format is not worth a hard dependency
     * on a class outside this module, and the fallback means a session without
     * a business still renders a sensible number rather than throwing.
     */
    protected function formatAmount($amount): string
    {
        $precision = session('business.currency_precision');

        if (! is_numeric($precision)) {
            $precision = 2;
        }

        return number_format((float) $amount, (int) $precision);
    }

    /**
     * True when the module's core tables are present.
     *
     * The views use this to show an install notice rather than a stack trace on
     * a tenant where the SQL has not been run yet.
     */
    protected function moduleInstalled(): bool
    {
        return $this->tableExists('members') && $this->tableExists('families');
    }
}
