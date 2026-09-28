<?php

namespace Modules\SW\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Expense categories used by the SW Settlement Expenses payment line.
 *
 * Expenses New is a standalone module and stores its categories in
 * expnew_categories.  Older integrations used the host application's legacy
 * expense_categories table; keep that table only as a compatibility fallback
 * for tenants where Expenses New has not been installed yet.
 */
class ExpenseCategoryLookupService
{
    /**
     * Options for the server-rendered settlement form.
     */
    public function options(int $businessId): Collection
    {
        if ($businessId <= 0) {
            return collect();
        }

        $table = $this->sourceTable();
        if ($table === null) {
            return collect();
        }

        return $this->query($table, $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Live Select2 rows. Every call reads the current tenant database.
     */
    public function rows(int $businessId, string $search = ''): array
    {
        if ($businessId <= 0) {
            return [];
        }

        $table = $this->sourceTable();
        if ($table === null) {
            return [];
        }

        $search = trim($search);

        return $this->query($table, $businessId)
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn ($row) => [
                'id' => (int) $row->id,
                'text' => (string) $row->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Expenses New is authoritative whenever it exists.
     */
    protected function sourceTable(): ?string
    {
        if ($this->liveTableExists('expnew_categories')) {
            return 'expnew_categories';
        }

        if ($this->liveTableExists('expense_categories')) {
            return 'expense_categories';
        }

        return null;
    }

    protected function query(string $table, int $businessId): Builder
    {
        $query = DB::table($table)
            ->where('business_id', $businessId)
            ->whereNotNull('name');

        if ($table === 'expnew_categories') {
            // Match Expenses New's OptionService exactly: only active (or
            // legacy NULL-active) categories are selectable.
            if ($this->liveHasColumn($table, 'is_active')) {
                $query->where(function (Builder $active) {
                    $active->where('is_active', 1)->orWhereNull('is_active');
                });
            }
        } elseif ($this->liveHasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    /**
     * Inspect the database currently selected by tenant.context.  This avoids
     * carrying Schema metadata from a connection that was active earlier in the
     * request lifecycle.
     */
    protected function liveTableExists(string $table): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$table]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT 1 FROM `' . $table . '` LIMIT 0');
                return true;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
    }

    protected function liveHasColumn(string $table, string $column): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)
            || ! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                [$table, $column]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT `' . $column . '` FROM `' . $table . '` LIMIT 0');
                return true;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
    }
}
