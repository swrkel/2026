<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\ExpensesNew\Entities\Expense;

/**
 * Posts Expenses-New entries to the shared Finance account books without
 * depending on Finance controllers, models, routes or views.
 */
class FinanceAccountBookPostingService
{
    private const MARKER_PREFIX = 'EXPNEW-';

    /** @var array<string, bool> */
    private static array $columnCache = [];

    public function sync(Expense $expense): void
    {
        $this->assertLedgerIsAvailable();

        $expense->loadMissing(['category', 'payee', 'account']);
        $businessId = (int) $expense->business_id;
        $total = round((float) $expense->total_amount, 4);
        $paid = round(min((float) $expense->paid_amount, $total), 4);
        $due = round(max(0, $total - $paid), 4);

        if ($total <= 0) {
            $this->remove($expense);
            return;
        }

        $expenseAccountId = $this->resolveExpenseAccountId($expense);
        $paymentAccountId = (int) $expense->bank_account_id;
        $this->assertBusinessAccount($paymentAccountId, $businessId, 'bank_account_id');

        $description = $this->description($expense);
        $base = [
            'business_id' => $businessId,
            'operation_date' => $this->operationDate($expense),
            'created_by' => (int) ($expense->updated_by ?: $expense->created_by ?: auth()->id() ?: 1),
            'sub_type' => 'expense',
            'note' => $description,
            'location_id' => $expense->location_id ? (int) $expense->location_id : null,
            'cheque_number' => $expense->payment_method === 'cheque' ? $expense->cheque_no : null,
            'cheque_ref_no' => $expense->reference_no,
        ];

        $desired = [];
        $desired[] = $base + [
            'slip_no' => $this->marker($expense, 'EXPENSE'),
            'account_id' => $expenseAccountId,
            'amount' => $total,
            'type' => 'debit',
        ];

        if ($paid > 0) {
            $desired[] = $base + [
                'slip_no' => $this->marker($expense, 'PAYMENT'),
                'account_id' => $paymentAccountId,
                'amount' => $paid,
                'type' => 'credit',
            ];
        }

        if ($due > 0) {
            $desired[] = $base + [
                'slip_no' => $this->marker($expense, 'PAYABLE'),
                'account_id' => $this->resolveAccountsPayableId($businessId),
                'amount' => $due,
                'type' => 'credit',
            ];
        }

        $desiredMarkers = [];
        foreach ($desired as $entry) {
            $desiredMarkers[] = $entry['slip_no'];
            $this->upsertLedgerRow($entry);
        }

        $this->deleteObsoleteRows($expense, $desiredMarkers);
    }

    public function remove(Expense $expense): void
    {
        if (! Schema::hasTable('account_transactions') || ! $this->hasColumn('account_transactions', 'slip_no')) {
            return;
        }

        $query = DB::table('account_transactions')
            ->where('slip_no', 'like', $this->markerPrefix($expense) . '%');

        if ($this->hasColumn('account_transactions', 'business_id')) {
            $query->where('business_id', (int) $expense->business_id);
        }

        $this->deleteQuery($query);
    }

    private function assertLedgerIsAvailable(): void
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasTable('account_transactions')) {
            throw ValidationException::withMessages([
                'accounting_module' => 'The Finance account books are not available in this tenant database.',
            ]);
        }

        foreach (['account_id', 'amount', 'type', 'operation_date', 'slip_no'] as $column) {
            if (! $this->hasColumn('account_transactions', $column)) {
                throw ValidationException::withMessages([
                    'accounting_module' => 'The Finance account book structure is incomplete. Missing column: ' . $column . '.',
                ]);
            }
        }
    }

    private function resolveExpenseAccountId(Expense $expense): int
    {
        $businessId = (int) $expense->business_id;
        $moduleAccount = DB::table('expnew_expense_accounts')
            ->where('business_id', $businessId)
            ->where('id', (int) $expense->expense_account_id)
            ->first();

        if (! $moduleAccount) {
            throw ValidationException::withMessages([
                'expense_account_id' => 'The linked Expenses-New expense account is unavailable.',
            ]);
        }

        $externalId = (int) ($moduleAccount->external_account_id ?? 0);
        if ($externalId > 0 && $this->businessAccountExists($externalId, $businessId)) {
            return $externalId;
        }

        $targetName = $this->normalize((string) ($moduleAccount->name ?? ''));
        if ($targetName !== '') {
            foreach ($this->businessAccounts($businessId) as $account) {
                if ($this->normalize((string) ($account->name ?? '')) === $targetName) {
                    return (int) $account->id;
                }
            }
        }

        throw ValidationException::withMessages([
            'expense_account_id' => 'The category expense account is not linked to a Finance account. Sync or relink the expense account before saving.',
        ]);
    }

    private function resolveAccountsPayableId(int $businessId): int
    {
        $accounts = $this->businessAccounts($businessId);
        $exactNames = [
            'accounts payable',
            'account payable',
            'expenses payable',
            'expense payable',
        ];

        foreach ($exactNames as $name) {
            foreach ($accounts as $account) {
                if ($this->normalize((string) ($account->name ?? '')) === $name) {
                    return (int) $account->id;
                }
            }
        }

        foreach ($accounts as $account) {
            if (str_contains($this->normalize((string) ($account->name ?? '')), 'payable')) {
                return (int) $account->id;
            }
        }

        throw ValidationException::withMessages([
            'paid_amount' => 'The unpaid amount cannot be posted because an Accounts Payable account is not configured.',
        ]);
    }

    private function assertBusinessAccount(int $accountId, int $businessId, string $field): void
    {
        if ($accountId <= 0 || ! $this->businessAccountExists($accountId, $businessId)) {
            throw ValidationException::withMessages([
                $field => 'The selected accounting account is not available for this business.',
            ]);
        }
    }

    private function businessAccountExists(int $accountId, int $businessId): bool
    {
        $query = DB::table('accounts')
            ->where('id', $accountId)
            ->where('business_id', $businessId);

        if ($this->hasColumn('accounts', 'is_closed')) {
            $query->where(function ($builder): void {
                $builder->whereNull('is_closed')->orWhere('is_closed', 0);
            });
        }
        if ($this->hasColumn('accounts', 'disabled')) {
            $query->where(function ($builder): void {
                $builder->whereNull('disabled')->orWhere('disabled', 0);
            });
        }
        if ($this->hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    private function businessAccounts(int $businessId)
    {
        $query = DB::table('accounts')
            ->where('business_id', $businessId);

        if ($this->hasColumn('accounts', 'is_closed')) {
            $query->where(function ($builder): void {
                $builder->whereNull('is_closed')->orWhere('is_closed', 0);
            });
        }
        if ($this->hasColumn('accounts', 'disabled')) {
            $query->where(function ($builder): void {
                $builder->whereNull('disabled')->orWhere('disabled', 0);
            });
        }
        if ($this->hasColumn('accounts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->select(['id', 'name'])->orderBy('name')->get();
    }

    /** @param array<string, mixed> $entry */
    private function upsertLedgerRow(array $entry): void
    {
        $payload = $this->supportedPayload($entry);
        $now = now();

        if ($this->hasColumn('account_transactions', 'updated_at')) {
            $payload['updated_at'] = $now;
        }
        if ($this->hasColumn('account_transactions', 'deleted_at')) {
            $payload['deleted_at'] = null;
        }

        $existing = DB::table('account_transactions')
            ->where('slip_no', $entry['slip_no'])
            ->when($this->hasColumn('account_transactions', 'business_id'), function ($query) use ($entry): void {
                $query->where('business_id', $entry['business_id']);
            })
            ->orderBy('id')
            ->get(['id']);

        $first = $existing->first();
        if ($first) {
            DB::table('account_transactions')->where('id', $first->id)->update($payload);

            $duplicateIds = $existing->slice(1)->pluck('id')->all();
            if ($duplicateIds) {
                $this->deleteQuery(DB::table('account_transactions')->whereIn('id', $duplicateIds));
            }
            return;
        }

        if ($this->hasColumn('account_transactions', 'created_at')) {
            $payload['created_at'] = $now;
        }

        DB::table('account_transactions')->insert($payload);
    }

    /** @param array<int, string> $desiredMarkers */
    private function deleteObsoleteRows(Expense $expense, array $desiredMarkers): void
    {
        $query = DB::table('account_transactions')
            ->where('slip_no', 'like', $this->markerPrefix($expense) . '%')
            ->whereNotIn('slip_no', $desiredMarkers);

        if ($this->hasColumn('account_transactions', 'business_id')) {
            $query->where('business_id', (int) $expense->business_id);
        }

        $this->deleteQuery($query);
    }

    private function deleteQuery($query): void
    {
        if ($this->hasColumn('account_transactions', 'deleted_at')) {
            $values = ['deleted_at' => now()];
            if ($this->hasColumn('account_transactions', 'updated_at')) {
                $values['updated_at'] = now();
            }
            $query->update($values);
            return;
        }

        $query->delete();
    }

    /** @param array<string, mixed> $entry */
    private function supportedPayload(array $entry): array
    {
        $payload = [];
        foreach ($entry as $column => $value) {
            if ($this->hasColumn('account_transactions', $column)) {
                $payload[$column] = $value;
            }
        }

        return $payload;
    }

    private function description(Expense $expense): string
    {
        $parts = ['Expenses New', (string) $expense->expense_no];
        if ($expense->category && $expense->category->name) {
            $parts[] = (string) $expense->category->name;
        }
        if ($expense->payee && $expense->payee->name) {
            $parts[] = (string) $expense->payee->name;
        }
        if ($expense->reference_no) {
            $parts[] = 'Ref: ' . $expense->reference_no;
        }
        if ($expense->notes) {
            $parts[] = trim((string) $expense->notes);
        }

        return implode(' / ', array_filter($parts, static fn ($part): bool => trim((string) $part) !== ''));
    }

    private function operationDate(Expense $expense): string
    {
        if ($expense->expense_date instanceof \DateTimeInterface) {
            return $expense->expense_date->format('Y-m-d 00:00:00');
        }

        return date('Y-m-d 00:00:00', strtotime((string) $expense->expense_date));
    }

    private function marker(Expense $expense, string $suffix): string
    {
        return $this->markerPrefix($expense) . $suffix;
    }

    private function markerPrefix(Expense $expense): string
    {
        return self::MARKER_PREFIX . (int) $expense->id . '-';
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?: $value;

        return $value;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (! array_key_exists($key, self::$columnCache)) {
            self::$columnCache[$key] = Schema::hasColumn($table, $column);
        }

        return self::$columnCache[$key];
    }
}
