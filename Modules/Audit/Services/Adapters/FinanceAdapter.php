<?php
namespace Modules\Audit\Services\Adapters;
class FinanceAdapter extends AbstractTableAdapter
{
    public function accounts(): ?string { return $this->firstTable(['accounts', 'finance_accounts']); }
    public function accountTransactions(): ?string { return $this->firstTable(['account_transactions', 'finance_account_transactions']); }
}
