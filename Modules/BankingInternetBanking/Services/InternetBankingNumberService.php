<?php

namespace Modules\BankingInternetBanking\Services;

class InternetBankingNumberService
{
    public function next(string $prefix): string
    {
        return strtoupper($prefix) . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
