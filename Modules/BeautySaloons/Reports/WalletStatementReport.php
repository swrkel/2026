<?php

namespace Modules\BeautySaloons\Reports;

use Modules\BeautySaloons\Entities\BeautyWalletTransaction;

class WalletStatementReport
{
    public function rows(int $walletId)
    {
        return BeautyWalletTransaction::where('wallet_id', $walletId)->orderBy('transaction_date')->orderBy('id')->get();
    }
}
