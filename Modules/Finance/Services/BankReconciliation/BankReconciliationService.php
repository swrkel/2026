<?php

namespace Modules\Finance\Services\BankReconciliation;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountGroup;
use Modules\Finance\Entities\AccountTransaction;
use Modules\Finance\Entities\BankReconciliation;
use Modules\Finance\Entities\BankReconciliationLine;

class BankReconciliationService
{
    public function bankAccounts(int $businessId, $permittedLocations = 'all'): Collection
    {
        $bankGroupIds = AccountGroup::where('business_id', $businessId)
            ->whereRaw('LOWER(name) LIKE ?', ['%bank%'])
            ->pluck('id');

        $query = Account::query()
            ->where('business_id', $businessId)
            ->where('is_closed', 0)
            ->where('is_main_account', 0)
            ->when($bankGroupIds->isNotEmpty(), function ($q) use ($bankGroupIds) {
                $q->whereIn('asset_type', $bankGroupIds);
            }, function ($q) {
                $q->where('name', 'like', '%bank%');
            });

        if ($permittedLocations !== 'all' && is_array($permittedLocations)) {
            if (count($permittedLocations) === 0) {
                return collect();
            }

            $query->where(function ($q) use ($permittedLocations) {
                $q->whereIn('location_id', $permittedLocations)
                    ->orWhereNull('location_id')
                    ->orWhere('location_id', 'all');
            });
        }

        return $query->orderBy('name')->get(['id', 'name', 'account_number', 'location_id']);
    }

    public function assertBankAccount(int $businessId, int $accountId, $permittedLocations = 'all'): Account
    {
        $account = $this->bankAccounts($businessId, $permittedLocations)
            ->firstWhere('id', $accountId);

        abort_unless($account, 404);

        return $account;
    }

    public function candidateTransactions(int $businessId, int $accountId, string $statementDate): Collection
    {
        $query = DB::table('account_transactions as at')
            ->where('at.business_id', $businessId)
            ->where('at.account_id', $accountId)
            ->whereNull('at.deleted_at')
            ->whereDate('at.operation_date', '<=', $statementDate);

        if (Schema::hasColumn('account_transactions', 'reconcile_status')) {
            $query->where(function ($q) {
                $q->whereNull('at.reconcile_status')
                    ->orWhere('at.reconcile_status', 0);
            });
        }

        if (Schema::hasTable('finance_bank_reconciliation_lines') && Schema::hasTable('finance_bank_reconciliations')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('finance_bank_reconciliation_lines as brl')
                    ->join('finance_bank_reconciliations as br', 'br.id', '=', 'brl.reconciliation_id')
                    ->whereColumn('brl.account_transaction_id', 'at.id')
                    ->where('brl.is_cleared', 1)
                    ->where('br.status', 'reconciled')
                    ->whereNull('br.deleted_at');
            });
        }

        $query->select([
                'at.id',
                'at.operation_date',
                'at.type',
                'at.amount',
                'at.note',
                'at.cheque_number',
                'at.slip_no',
                'at.transaction_id',
                'at.transaction_payment_id',
                'at.created_at',
            ])
            ->orderBy('at.operation_date')
            ->orderBy('at.id');

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return $rows;
        }

        $transactionRefs = collect();
        if (Schema::hasTable('transactions')) {
            $ids = $rows->pluck('transaction_id')->filter()->unique()->values();
            if ($ids->isNotEmpty()) {
                $columns = ['id'];
                foreach (['invoice_no', 'ref_no', 'type'] as $column) {
                    if (Schema::hasColumn('transactions', $column)) {
                        $columns[] = $column;
                    }
                }
                $transactionRefs = DB::table('transactions')->whereIn('id', $ids)->get($columns)->keyBy('id');
            }
        }

        return $rows->map(function ($row) use ($transactionRefs) {
            $tx = $row->transaction_id ? $transactionRefs->get($row->transaction_id) : null;
            $reference = $row->cheque_number ?: $row->slip_no;

            if (!$reference && $tx) {
                $reference = $tx->invoice_no ?? $tx->ref_no ?? null;
            }
            if (!$reference) {
                $reference = 'AT-' . $row->id;
            }

            $row->reference = $reference;
            $row->description = trim((string) ($row->note ?: ($tx->type ?? 'Bank transaction')));
            $row->deposit = $row->type === 'debit' ? (float) $row->amount : 0.0;
            $row->payment = $row->type === 'credit' ? (float) $row->amount : 0.0;

            return $row;
        });
    }

    public function bookBalance(int $businessId, int $accountId, string $statementDate): float
    {
        $row = DB::table('account_transactions')
            ->where('business_id', $businessId)
            ->where('account_id', $accountId)
            ->whereNull('deleted_at')
            ->whereDate('operation_date', '<=', $statementDate)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as debit_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0) as credit_total")
            ->first();

        return round((float) $row->debit_total - (float) $row->credit_total, 4);
    }

    public function summary(Collection $candidates, array $clearedIds, float $statementEndingBalance, float $bookBalance): array
    {
        $clearedLookup = array_fill_keys(array_map('intval', $clearedIds), true);
        $outstandingDeposits = 0.0;
        $outstandingPayments = 0.0;
        $clearedDeposits = 0.0;
        $clearedPayments = 0.0;

        foreach ($candidates as $row) {
            $cleared = isset($clearedLookup[(int) $row->id]);
            if ($row->type === 'debit') {
                $cleared ? $clearedDeposits += (float) $row->amount : $outstandingDeposits += (float) $row->amount;
            } elseif ($row->type === 'credit') {
                $cleared ? $clearedPayments += (float) $row->amount : $outstandingPayments += (float) $row->amount;
            }
        }

        $adjustedBankBalance = $statementEndingBalance + $outstandingDeposits - $outstandingPayments;
        $difference = $bookBalance - $adjustedBankBalance;

        return [
            'cleared_deposits' => round($clearedDeposits, 4),
            'cleared_payments' => round($clearedPayments, 4),
            'outstanding_deposits' => round($outstandingDeposits, 4),
            'outstanding_payments' => round($outstandingPayments, 4),
            'adjusted_bank_balance' => round($adjustedBankBalance, 4),
            'book_ending_balance' => round($bookBalance, 4),
            'difference' => round($difference, 4),
        ];
    }

    public function saveDraft(array $data, int $businessId, int $userId, $permittedLocations = 'all'): BankReconciliation
    {
        return DB::transaction(function () use ($data, $businessId, $userId, $permittedLocations) {
            $accountId = (int) $data['account_id'];
            $statementDate = Carbon::parse($data['statement_date'])->toDateString();
            $account = $this->assertBankAccount($businessId, $accountId, $permittedLocations);

            $candidates = $this->candidateTransactions($businessId, $accountId, $statementDate);
            $candidateIds = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
            $requestedIds = collect($data['cleared_transaction_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
            $clearedIds = array_values(array_intersect($requestedIds, $candidateIds));

            if (count($requestedIds) !== count($clearedIds)) {
                abort(422, 'One or more selected transactions are no longer eligible for reconciliation. Please reload the page.');
            }

            $bookBalance = $this->bookBalance($businessId, $accountId, $statementDate);
            $summary = $this->summary($candidates, $clearedIds, (float) $data['statement_ending_balance'], $bookBalance);

            $reconciliation = BankReconciliation::create([
                'business_id' => $businessId,
                'location_id' => is_numeric($account->location_id) ? (int) $account->location_id : null,
                'account_id' => $accountId,
                'reconciliation_no' => 'TEMP-' . bin2hex(random_bytes(8)),
                'statement_date' => $statementDate,
                'statement_ending_balance' => (float) $data['statement_ending_balance'],
                'book_ending_balance' => $summary['book_ending_balance'],
                'outstanding_deposits' => $summary['outstanding_deposits'],
                'outstanding_payments' => $summary['outstanding_payments'],
                'adjusted_bank_balance' => $summary['adjusted_bank_balance'],
                'difference' => $summary['difference'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $reconciliation->reconciliation_no = 'BR-' . Carbon::parse($statementDate)->format('Ym') . '-' . str_pad((string) $reconciliation->id, 6, '0', STR_PAD_LEFT);
            $reconciliation->save();

            $clearedLookup = array_fill_keys($clearedIds, true);
            foreach ($candidates as $row) {
                BankReconciliationLine::create([
                    'reconciliation_id' => $reconciliation->id,
                    'account_transaction_id' => $row->id,
                    'transaction_date' => $row->operation_date,
                    'reference' => $row->reference,
                    'description' => $row->description,
                    'transaction_type' => $row->type,
                    'amount' => $row->amount,
                    'is_cleared' => isset($clearedLookup[(int) $row->id]),
                ]);
            }

            return $reconciliation->fresh(['account', 'lines']);
        }, 3);
    }


    public function updateDraft(BankReconciliation $reconciliation, array $data, int $businessId, $permittedLocations = 'all'): BankReconciliation
    {
        abort_unless((int) $reconciliation->business_id === $businessId, 404);
        abort_if($reconciliation->status !== 'draft', 422, 'Only draft bank reconciliations can be edited.');

        return DB::transaction(function () use ($reconciliation, $data, $businessId, $permittedLocations) {
            $locked = BankReconciliation::where('id', $reconciliation->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->status !== 'draft', 422, 'Only draft bank reconciliations can be edited.');

            $accountId = (int) $data['account_id'];
            $statementDate = Carbon::parse($data['statement_date'])->toDateString();
            $this->assertBankAccount($businessId, $accountId, $permittedLocations);

            $candidates = $this->candidateTransactions($businessId, $accountId, $statementDate);
            $candidateIds = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
            $requestedIds = collect($data['cleared_transaction_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
            $clearedIds = array_values(array_intersect($requestedIds, $candidateIds));

            if (count($requestedIds) !== count($clearedIds)) {
                abort(422, 'One or more selected transactions are no longer eligible for reconciliation. Please reload the page.');
            }

            $bookBalance = $this->bookBalance($businessId, $accountId, $statementDate);
            $summary = $this->summary($candidates, $clearedIds, (float) $data['statement_ending_balance'], $bookBalance);

            $account = $this->assertBankAccount($businessId, $accountId, $permittedLocations);
            $locationId = is_numeric($account->location_id) ? (int) $account->location_id : null;

            $locked->fill([
                'location_id' => $locationId,
                'account_id' => $accountId,
                'statement_date' => $statementDate,
                'statement_ending_balance' => (float) $data['statement_ending_balance'],
                'book_ending_balance' => $summary['book_ending_balance'],
                'outstanding_deposits' => $summary['outstanding_deposits'],
                'outstanding_payments' => $summary['outstanding_payments'],
                'adjusted_bank_balance' => $summary['adjusted_bank_balance'],
                'difference' => $summary['difference'],
                'notes' => $data['notes'] ?? null,
            ])->save();

            BankReconciliationLine::where('reconciliation_id', $locked->id)->delete();
            $clearedLookup = array_fill_keys($clearedIds, true);
            foreach ($candidates as $row) {
                BankReconciliationLine::create([
                    'reconciliation_id' => $locked->id,
                    'account_transaction_id' => $row->id,
                    'transaction_date' => $row->operation_date,
                    'reference' => $row->reference,
                    'description' => $row->description,
                    'transaction_type' => $row->type,
                    'amount' => $row->amount,
                    'is_cleared' => isset($clearedLookup[(int) $row->id]),
                ]);
            }

            return $locked->fresh(['account', 'lines']);
        }, 3);
    }

    public function finalize(BankReconciliation $reconciliation, int $businessId, int $userId): BankReconciliation
    {
        abort_unless((int) $reconciliation->business_id === $businessId, 404);

        if ($reconciliation->status === 'reconciled') {
            return $reconciliation;
        }

        if (abs((float) $reconciliation->difference) > 0.0001) {
            abort(422, 'The reconciliation difference must be 0.0000 before finalizing.');
        }

        return DB::transaction(function () use ($reconciliation, $userId) {
            $locked = BankReconciliation::where('id', $reconciliation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'reconciled') {
                return $locked;
            }

            $clearedIds = BankReconciliationLine::where('reconciliation_id', $locked->id)
                ->where('is_cleared', 1)
                ->whereNotNull('account_transaction_id')
                ->pluck('account_transaction_id');

            // Protect the finalization from stale drafts. If any bank entry was
            // inserted, deleted or reconciled after this draft was saved, the
            // snapshot must be refreshed before it can be finalized.
            $currentCandidates = $this->candidateTransactions(
                (int) $locked->business_id,
                (int) $locked->account_id,
                Carbon::parse($locked->statement_date)->toDateString()
            );
            $snapshotIds = BankReconciliationLine::where('reconciliation_id', $locked->id)
                ->whereNotNull('account_transaction_id')
                ->pluck('account_transaction_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();
            $currentIds = $currentCandidates->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($snapshotIds !== $currentIds) {
                abort(422, 'The bank ledger changed after this draft was saved. Edit the draft to refresh the transactions before finalizing.');
            }

            $currentBookBalance = $this->bookBalance(
                (int) $locked->business_id,
                (int) $locked->account_id,
                Carbon::parse($locked->statement_date)->toDateString()
            );
            $currentSummary = $this->summary(
                $currentCandidates,
                $clearedIds->map(fn ($id) => (int) $id)->all(),
                (float) $locked->statement_ending_balance,
                $currentBookBalance
            );

            if (abs((float) $currentSummary['difference']) > 0.0001
                || abs((float) $currentSummary['book_ending_balance'] - (float) $locked->book_ending_balance) > 0.0001
                || abs((float) $currentSummary['outstanding_deposits'] - (float) $locked->outstanding_deposits) > 0.0001
                || abs((float) $currentSummary['outstanding_payments'] - (float) $locked->outstanding_payments) > 0.0001) {
                abort(422, 'The reconciliation totals changed after this draft was saved. Edit the draft and save it again before finalizing.');
            }

            if ($clearedIds->isNotEmpty()) {
                $eligible = AccountTransaction::where('business_id', $locked->business_id)
                    ->where('account_id', $locked->account_id)
                    ->whereIn('id', $clearedIds)
                    ->whereNull('deleted_at');

                if (Schema::hasColumn('account_transactions', 'reconcile_status')) {
                    $eligible->where(function ($q) {
                        $q->whereNull('reconcile_status')->orWhere('reconcile_status', 0);
                    });
                }

                $eligibleCount = $eligible->count();

                $alreadyInAnotherFinal = BankReconciliationLine::query()
                    ->join('finance_bank_reconciliations as br', 'br.id', '=', 'finance_bank_reconciliation_lines.reconciliation_id')
                    ->whereIn('finance_bank_reconciliation_lines.account_transaction_id', $clearedIds)
                    ->where('finance_bank_reconciliation_lines.is_cleared', 1)
                    ->where('br.status', 'reconciled')
                    ->where('br.id', '<>', $locked->id)
                    ->whereNull('br.deleted_at')
                    ->exists();

                if ($eligibleCount !== $clearedIds->count() || $alreadyInAnotherFinal) {
                    abort(422, 'One or more cleared transactions were changed or reconciled elsewhere. Reopen the draft and refresh the transaction list.');
                }

                if (Schema::hasColumn('account_transactions', 'reconcile_status')) {
                    AccountTransaction::where('business_id', $locked->business_id)
                        ->where('account_id', $locked->account_id)
                        ->whereIn('id', $clearedIds)
                        ->update(['reconcile_status' => 1]);
                }
            }

            $locked->status = 'reconciled';
            $locked->reconciled_by = $userId;
            $locked->reconciled_at = Carbon::now();
            $locked->save();

            return $locked->fresh(['account', 'lines']);
        }, 3);
    }

    public function reopen(BankReconciliation $reconciliation, int $businessId): BankReconciliation
    {
        abort_unless((int) $reconciliation->business_id === $businessId, 404);

        return DB::transaction(function () use ($reconciliation) {
            $locked = BankReconciliation::where('id', $reconciliation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'reconciled') {
                return $locked;
            }

            $clearedIds = BankReconciliationLine::where('reconciliation_id', $locked->id)
                ->where('is_cleared', 1)
                ->whereNotNull('account_transaction_id')
                ->pluck('account_transaction_id');

            if ($clearedIds->isNotEmpty() && Schema::hasColumn('account_transactions', 'reconcile_status')) {
                AccountTransaction::where('business_id', $locked->business_id)
                    ->where('account_id', $locked->account_id)
                    ->whereIn('id', $clearedIds)
                    ->update(['reconcile_status' => 0]);
            }

            $locked->status = 'draft';
            $locked->reconciled_by = null;
            $locked->reconciled_at = null;
            $locked->save();

            return $locked->fresh(['account', 'lines']);
        }, 3);
    }
}
