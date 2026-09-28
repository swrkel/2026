<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\ExpensesNew\Entities\Category;
use Modules\ExpensesNew\Entities\ExpenseAccount;
use Modules\ExpensesNew\Entities\Payee;
use Throwable;

class OptionService
{
    public function __construct(
        private readonly ExpenseAccountService $expenseAccountService,
        private readonly PayeeSyncService $payeeSyncService,
        private readonly ModuleAvailabilityService $moduleAvailability
    ) {
    }

    public function categories(int $businessId): Collection
    {
        return Category::query()
            ->where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('is_active', 1)->orWhereNull('is_active');
            })
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Full business category list used by Add/Edit Expense.
     *
     * Expense entry must also be able to reopen an older expense whose
     * category has since been deactivated, so this intentionally does not
     * apply the active-only filter used by report/list filters.
     */
    public function expenseFormCategories(int $businessId): Collection
    {
        return Category::query()
            ->where('business_id', $businessId)
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('name', 'id');
    }

    public function payees(int $businessId): Collection
    {
        $this->syncPayeeMasters($businessId);

        $chequerEnabled = $this->moduleAvailability->isExplicitlyEnabled(
            ['Chequer', 'ChequeWriting', 'ChequeWriter']
        );

        if (! $chequerEnabled) {
            try {
                $this->payeeSyncService->ensureChequeModuleFallback($businessId);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $payees = $this->payeeOptions($businessId);

        if ($chequerEnabled) {
            return $payees->reject(
                static fn (string $name): bool => $name === PayeeSyncService::CHEQUE_MODULE_NOT_ENABLED
            );
        }

        $fallbackId = $payees->search(PayeeSyncService::CHEQUE_MODULE_NOT_ENABLED);
        if ($fallbackId !== false) {
            $fallbackName = $payees->pull($fallbackId);
            $payees = collect([$fallbackId => $fallbackName])->union($payees);
        }

        return $payees;
    }

    /**
     * Category defaults are supplier based. When Chequer is not enabled, the
     * required system fallback is displayed first. The fallback remains stored
     * locally but is hidden immediately after Chequer becomes enabled.
     */
    public function categoryDefaultPayees(int $businessId): Collection
    {
        $supplierNames = collect();
        try {
            $this->payeeSyncService->syncFromSuppliers($businessId);
            $supplierNames = $this->payeeSyncService->supplierNames($businessId);
        } catch (Throwable $exception) {
            report($exception);
        }

        $chequerEnabled = $this->moduleAvailability->isExplicitlyEnabled(
            ['Chequer', 'ChequeWriting', 'ChequeWriter']
        );

        if (! $chequerEnabled) {
            try {
                $this->payeeSyncService->ensureChequeModuleFallback($businessId);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $query = Payee::query()
            ->where('business_id', $businessId)
            ->where(function ($builder) {
                $builder->where('is_active', 1)->orWhereNull('is_active');
            })
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->where(function ($builder) use ($supplierNames, $chequerEnabled): void {
                if ($supplierNames->isNotEmpty()) {
                    $builder->whereIn('name', $supplierNames->all());
                    if (! $chequerEnabled) {
                        $builder->orWhere('name', PayeeSyncService::CHEQUE_MODULE_NOT_ENABLED);
                    }
                    return;
                }

                if (! $chequerEnabled) {
                    $builder->where('name', PayeeSyncService::CHEQUE_MODULE_NOT_ENABLED);
                    return;
                }

                $builder->whereRaw('1 = 0');
            });

        $payees = $query->orderBy('name')->pluck('name', 'id');

        if (! $chequerEnabled) {
            $fallbackId = $payees->search(PayeeSyncService::CHEQUE_MODULE_NOT_ENABLED);
            if ($fallbackId !== false) {
                $fallbackName = $payees->pull($fallbackId);
                $payees = collect([$fallbackId => $fallbackName])->union($payees);
            }
        }

        return $payees;
    }

    public function expenseAccounts(int $businessId): Collection
    {
        /*
         * Finance/List Accounts is the source of truth for this dropdown.
         * Sync on every Add/Edit Category open so a newly-created Finance
         * Expense account appears immediately and an account moved out of the
         * Expense type disappears immediately.  The sync service talks only to
         * shared database tables, so this does not introduce a PHP dependency
         * on the Finance module.
         */
        try {
            $this->expenseAccountService->syncFromChartAccounts($businessId);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $this->expenseAccountOptions($businessId);
    }

    /** @return array<int, array{id:int,name:string}> */
    public function categoryExpenseAccountMap(int $businessId): array
    {
        return Category::query()
            ->with('expenseAccount:id,business_id,name')
            ->where('business_id', $businessId)
            ->whereNotNull('expense_account_id')
            ->get(['id', 'business_id', 'expense_account_id'])
            ->filter(static fn (Category $category): bool => (bool) $category->expenseAccount)
            ->mapWithKeys(static fn (Category $category): array => [
                (int) $category->id => [
                    'id' => (int) $category->expenseAccount->id,
                    'name' => (string) $category->expenseAccount->name,
                ],
            ])
            ->all();
    }

    /** @return array<int, int> */
    public function categoryDefaultPayeeMap(int $businessId): array
    {
        return Category::query()
            ->where('business_id', $businessId)
            ->whereNotNull('default_payee_id')
            ->pluck('default_payee_id', 'id')
            ->mapWithKeys(static fn ($payeeId, $categoryId): array => [(int) $categoryId => (int) $payeeId])
            ->all();
    }

    public function locations(int $businessId): Collection
    {
        if (! DB::getSchemaBuilder()->hasTable('business_locations')) {
            return collect();
        }

        return DB::table('business_locations')
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function syncPayeeMasters(int $businessId): void
    {
        try {
            $this->payeeSyncService->syncFromSuppliers($businessId);
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $this->payeeSyncService->syncFromChequePayees($businessId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function payeeOptions(int $businessId): Collection
    {
        return Payee::query()
            ->where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('is_active', 1)->orWhereNull('is_active');
            })
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    private function expenseAccountOptions(int $businessId): Collection
    {
        return ExpenseAccount::query()
            ->where('business_id', $businessId)
            ->where('is_active', 1)
            // Category Expense Account must come from Finance/List Accounts.
            // Local manual rows remain available elsewhere in Expenses New,
            // but they are intentionally not offered in this dropdown.
            ->whereNotNull('external_account_id')
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->orderBy('name')
            ->orderBy('id')
            ->pluck('name', 'id');
    }
}
