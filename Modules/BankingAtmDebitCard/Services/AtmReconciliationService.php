<?php
namespace Modules\BankingAtmDebitCard\Services;

use Modules\BankingAtmDebitCard\Entities\AtmTransaction;

class AtmReconciliationService
{
    public function markReconciled(AtmTransaction $transaction): AtmTransaction
    {
        $transaction->update(['is_reconciled' => true, 'status' => 'reconciled']);
        return $transaction;
    }
}
