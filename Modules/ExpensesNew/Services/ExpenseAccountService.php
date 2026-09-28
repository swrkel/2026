<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ExpensesNew\Entities\ExpenseAccount;

class ExpenseAccountService
{
    public function activeOptions(int $businessId)
    {
        return ExpenseAccount::where('business_id', $businessId)
            ->where('is_active', 1)
            ->whereNotNull('external_account_id')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Synchronise Finance -> Expenses New expense-account choices.
     *
     * Finance/List Accounts stores the classification on
     * accounts.account_type_id, pointing to account_types.id.  Accounts may be
     * attached either directly to the top-level Expense/Expenses type or to
     * any child/sub-type under it, so this method resolves the whole hierarchy
     * before copying the matching Finance accounts into the module-owned
     * expnew_expense_accounts bridge table.
     *
     * This deliberately uses database tables only.  Expenses New therefore
     * remains independent from Finance PHP classes/controllers while still
     * following the Finance chart of accounts that the user maintains.
     */
    public function syncFromChartAccounts(int $businessId): int
    {
        if (! Schema::hasTable('accounts')
            || ! Schema::hasTable('account_types')
            || ! Schema::hasTable('expnew_expense_accounts')
            || ! Schema::hasColumn('accounts', 'account_type_id')) {
            return 0;
        }

        $expenseTypeIds = $this->expenseAccountTypeIds($businessId);

        /*
         * The Finance chart exists but has no Expense/Expenses type.  In that
         * case none of the previously synced Finance rows should remain
         * selectable as an expense account.  Local/manual records are left
         * untouched; only rows that originated from Finance are deactivated.
         */
        if (empty($expenseTypeIds)) {
            $this->deactivateSyncedAccounts($businessId, []);
            return 0;
        }

        $query = DB::table('accounts')
            ->where('business_id', $businessId)
            ->whereIn('account_type_id', $expenseTypeIds);

        // Match the live Finance List Accounts set: deleted/disabled accounts
        // are not selectable.  Column guards retain compatibility with older
        // tenant schemas.
        if (Schema::hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('accounts', 'disabled')) {
            $query->where('disabled', 0);
        }

        $columns = ['id', 'name'];
        if (Schema::hasColumn('accounts', 'account_number')) {
            $columns[] = 'account_number';
        }

        $rows = $query
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->orderBy('name')
            ->orderBy('id')
            ->get($columns);

        $financeIds = $rows->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $this->deactivateSyncedAccounts($businessId, $financeIds);

        foreach ($rows as $row) {
            $code = property_exists($row, 'account_number') && $row->account_number !== null
                ? (string) $row->account_number
                : 'ACC-' . $row->id;

            ExpenseAccount::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'external_account_id' => (int) $row->id,
                ],
                [
                    'name' => (string) $row->name,
                    'code' => $code,
                    'is_active' => 1,
                ]
            );
        }

        return $rows->count();
    }

    /**
     * Return the Expense/Expenses account type and every descendant sub-type
     * configured for this business.
     *
     * @return array<int, int>
     */
    private function expenseAccountTypeIds(int $businessId): array
    {
        $query = DB::table('account_types')
            ->where('business_id', $businessId);

        if (Schema::hasColumn('account_types', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $columns = ['id', 'name'];
        $hasParent = Schema::hasColumn('account_types', 'parent_account_type_id');
        if ($hasParent) {
            $columns[] = 'parent_account_type_id';
        }

        /** @var Collection<int, object> $types */
        $types = $query->get($columns);

        $selected = [];
        foreach ($types as $type) {
            $name = strtolower(trim((string) ($type->name ?? '')));
            if (in_array($name, ['expense', 'expenses'], true)) {
                $selected[(int) $type->id] = true;
            }
        }

        if (empty($selected) || ! $hasParent) {
            return array_keys($selected);
        }

        // account_types currently has one parent level, but resolving until no
        // new child is found also supports deeper future hierarchies safely.
        do {
            $added = false;
            foreach ($types as $type) {
                $id = (int) $type->id;
                $parentId = (int) ($type->parent_account_type_id ?? 0);

                if ($parentId > 0 && isset($selected[$parentId]) && ! isset($selected[$id])) {
                    $selected[$id] = true;
                    $added = true;
                }
            }
        } while ($added);

        return array_keys($selected);
    }

    /**
     * Disable Finance-synchronised rows which no longer belong to the Finance
     * Expense account type (or were disabled/deleted in Finance).
     *
     * Manual Expenses-New rows have external_account_id = NULL, so they are
     * never modified by this synchronisation.
     *
     * @param array<int, int> $currentFinanceIds
     */
    private function deactivateSyncedAccounts(int $businessId, array $currentFinanceIds): void
    {
        $query = ExpenseAccount::query()
            ->where('business_id', $businessId)
            ->whereNotNull('external_account_id');

        if (! empty($currentFinanceIds)) {
            $query->whereNotIn('external_account_id', $currentFinanceIds);
        }

        $query->update(['is_active' => 0]);
    }
}
