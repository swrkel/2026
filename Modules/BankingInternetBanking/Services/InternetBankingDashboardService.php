<?php

namespace Modules\BankingInternetBanking\Services;

use Illuminate\Support\Facades\Schema;
use Modules\BankingInternetBanking\Entities\InternetBankingBeneficiary;
use Modules\BankingInternetBanking\Entities\InternetBankingBillPayment;
use Modules\BankingInternetBanking\Entities\InternetBankingCustomer;
use Modules\BankingInternetBanking\Entities\InternetBankingSecureMessage;
use Modules\BankingInternetBanking\Entities\InternetBankingTransfer;

class InternetBankingDashboardService
{
    public function summary(): array
    {
        return [
            'customers' => $this->safeCount('bkg_ib_customers', InternetBankingCustomer::class),
            'beneficiaries' => $this->safeCount('bkg_ib_beneficiaries', InternetBankingBeneficiary::class),
            'transfers' => $this->safeCount('bkg_ib_transfers', InternetBankingTransfer::class),
            'bill_payments' => $this->safeCount('bkg_ib_bill_payments', InternetBankingBillPayment::class),
            'secure_messages' => $this->safeCount('bkg_ib_secure_messages', InternetBankingSecureMessage::class),
        ];
    }

    private function safeCount(string $table, string $model): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) $model::query()->count();
    }
}
