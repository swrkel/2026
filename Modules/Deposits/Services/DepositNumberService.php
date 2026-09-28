<?php

namespace Modules\Deposits\Services;

use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Models\DepositTransaction;

class DepositNumberService
{
    protected $settings;

    public function __construct(DepositSettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function nextAccountNumber(?string $prefix = null): string
    {
        $prefix = $prefix ?: $this->settings->get('account_prefix', 'DEP');
        $next = (int) DepositAccount::query()->count() + 1;
        return strtoupper($prefix) . '-' . date('Y') . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function nextTransactionNumber(?string $prefix = null): string
    {
        $prefix = $prefix ?: $this->settings->get('transaction_prefix', 'DTR');
        $next = (int) DepositTransaction::query()->count() + 1;
        return strtoupper($prefix) . '-' . date('Ymd') . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function nextCertificateNumber(?string $prefix = null): string
    {
        $prefix = $prefix ?: $this->settings->get('certificate_prefix', 'CERT');
        return strtoupper($prefix) . '-' . date('YmdHis');
    }
}
