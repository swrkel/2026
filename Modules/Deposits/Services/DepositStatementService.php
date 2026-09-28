<?php

namespace Modules\Deposits\Services;

use Illuminate\Support\Collection;
use Modules\Deposits\Models\DepositAccount;

class DepositStatementService
{
    public function rows(DepositAccount $account, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        $query = $account->transactions()->orderBy('transaction_date')->orderBy('id');

        if (! empty($dateFrom)) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }

        if (! empty($dateTo)) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        return $query->get();
    }

    public function totals(Collection $transactions): array
    {
        $creditTypes = ['deposit', 'interest', 'renewal'];
        $debitTypes = ['withdrawal', 'charge', 'penalty', 'closure'];

        return [
            'credits' => $transactions->whereIn('type', $creditTypes)->sum('amount'),
            'debits' => $transactions->whereIn('type', $debitTypes)->sum('amount'),
            'count' => $transactions->count(),
            'closing_balance' => optional($transactions->last())->balance_after,
        ];
    }
}
