<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ExpensesNew\Entities\ExpensePrefix;

/**
 * IS1991 (#1): how many expense transactions a prefix is responsible for.
 *
 * A prefix is "used" when an expense exists against a category whose code was
 * generated from it - that is, whose code starts with the prefix. That is the
 * rule the issue asks for, and it has a useful property: it is COUNTED LIVE,
 * never stored. So when the last expense using a prefix is removed through
 * List Expenses > Action > Delete, the count falls to zero on the next page
 * load and Edit and Delete become available again with no extra bookkeeping.
 *
 * This deliberately mirrors CategoryCodeUsageService, which answers the same
 * question for a whole code rather than a prefix.
 */
class ExpensePrefixUsageService
{
    /**
     * Attach transaction_count and has_transactions to each prefix.
     */
    public function attachUsage(Collection $prefixes, int $businessId): Collection
    {
        $counts = $this->transactionCounts($prefixes, $businessId);

        return $prefixes->each(function (ExpensePrefix $prefix) use ($counts): void {
            $count = (int) $counts->get($this->normalize($prefix->prefix), 0);

            $prefix->setAttribute('transaction_count', $count);
            $prefix->setAttribute('has_transactions', $count > 0);
        });
    }

    public function hasTransactions(ExpensePrefix $prefix): bool
    {
        return $this->transactionCount($prefix) > 0;
    }

    public function transactionCount(ExpensePrefix $prefix): int
    {
        $needle = $this->normalize($prefix->prefix);

        if ($needle === '' || ! $this->tablesPresent()) {
            return 0;
        }

        return (int) $this->baseQuery((int) $prefix->business_id)
            ->whereRaw('LOWER(TRIM(categories.code)) LIKE ?', [$this->like($needle)])
            ->count('expenses.id');
    }

    /**
     * @return Collection<string, int> keyed by normalised prefix
     */
    private function transactionCounts(Collection $prefixes, int $businessId): Collection
    {
        $needles = $prefixes
            ->pluck('prefix')
            ->map(fn ($prefix) => $this->normalize(is_string($prefix) ? $prefix : ''))
            ->filter(fn (string $prefix): bool => $prefix !== '')
            ->unique()
            ->values();

        if ($needles->isEmpty() || ! $this->tablesPresent()) {
            return collect();
        }

        /*
         * One query, then matched in PHP.
         *
         * Counting per prefix in SQL would need a LIKE per prefix, and prefixes
         * overlap by nature - "EX" and "EXP-" both match the code EXP-0001, and
         * both are meant to. Pulling the distinct codes once and testing each
         * against every prefix keeps that overlap correct and costs a single
         * round trip regardless of how many prefixes are on the list.
         */
        $codeCounts = $this->baseQuery($businessId)
            ->selectRaw('LOWER(TRIM(categories.code)) AS normalized_code, COUNT(expenses.id) AS transaction_count')
            ->groupByRaw('LOWER(TRIM(categories.code))')
            ->pluck('transaction_count', 'normalized_code');

        $totals = [];

        foreach ($needles as $needle) {
            $total = 0;

            foreach ($codeCounts as $code => $count) {
                if (str_starts_with((string) $code, $needle)) {
                    $total += (int) $count;
                }
            }

            $totals[$needle] = $total;
        }

        return collect($totals);
    }

    private function baseQuery(int $businessId)
    {
        return DB::table('expnew_expenses as expenses')
            ->join('expnew_categories as categories', function ($join): void {
                $join->on('categories.id', '=', 'expenses.category_id')
                    ->on('categories.business_id', '=', 'expenses.business_id');
            })
            ->where('expenses.business_id', $businessId)
            ->whereNotNull('categories.code')
            ->where('categories.code', '<>', '');
    }

    private function tablesPresent(): bool
    {
        return Schema::hasTable('expnew_expenses') && Schema::hasTable('expnew_categories');
    }

    /**
     * Escape the wildcards so a prefix containing % or _ cannot widen the match.
     */
    private function like(string $needle): string
    {
        return addcslashes($needle, '%_\\') . '%';
    }

    private function normalize(?string $prefix): string
    {
        return mb_strtolower(trim((string) $prefix));
    }
}
