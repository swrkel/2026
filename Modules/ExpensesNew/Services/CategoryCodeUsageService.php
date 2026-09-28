<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ExpensesNew\Entities\CategoryCode;

class CategoryCodeUsageService
{
    /**
     * Attach transaction_count and has_transactions attributes to each code.
     */
    public function attachUsage(Collection $categoryCodes, int $businessId): Collection
    {
        $counts = $this->transactionCounts($categoryCodes, $businessId);

        return $categoryCodes->each(function (CategoryCode $categoryCode) use ($counts): void {
            $count = (int) $counts->get($this->normalize($categoryCode->code), 0);

            $categoryCode->setAttribute('transaction_count', $count);
            $categoryCode->setAttribute('has_transactions', $count > 0);
        });
    }

    public function hasTransactions(CategoryCode $categoryCode): bool
    {
        return $this->transactionCount($categoryCode) > 0;
    }

    public function transactionCount(CategoryCode $categoryCode): int
    {
        return (int) DB::table('expnew_expenses as expenses')
            ->join('expnew_categories as categories', function ($join): void {
                $join->on('categories.id', '=', 'expenses.category_id')
                    ->on('categories.business_id', '=', 'expenses.business_id');
            })
            ->where('expenses.business_id', $categoryCode->business_id)
            ->whereRaw('LOWER(TRIM(categories.code)) = ?', [$this->normalize($categoryCode->code)])
            ->count('expenses.id');
    }

    private function transactionCounts(Collection $categoryCodes, int $businessId): Collection
    {
        $normalizedCodes = $categoryCodes
            ->pluck('code')
            ->filter(fn ($code) => is_string($code) && trim($code) !== '')
            ->map(fn ($code) => $this->normalize($code))
            ->unique()
            ->values();

        if ($normalizedCodes->isEmpty()) {
            return collect();
        }

        return DB::table('expnew_expenses as expenses')
            ->join('expnew_categories as categories', function ($join): void {
                $join->on('categories.id', '=', 'expenses.category_id')
                    ->on('categories.business_id', '=', 'expenses.business_id');
            })
            ->where('expenses.business_id', $businessId)
            ->whereIn(DB::raw('LOWER(TRIM(categories.code))'), $normalizedCodes->all())
            ->selectRaw('LOWER(TRIM(categories.code)) AS normalized_code, COUNT(expenses.id) AS transaction_count')
            ->groupByRaw('LOWER(TRIM(categories.code))')
            ->pluck('transaction_count', 'normalized_code')
            ->map(fn ($count) => (int) $count);
    }

    private function normalize(?string $code): string
    {
        return Str::lower(trim((string) $code));
    }
}
