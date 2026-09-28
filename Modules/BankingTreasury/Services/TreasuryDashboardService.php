<?php

namespace Modules\BankingTreasury\Services;

use Illuminate\Support\Facades\Schema;

class TreasuryDashboardService
{
    public function summary(): array
    {
        return [
            'vaults' => $this->count('bkg_treasury_vaults'),
            'cash_positions' => $this->count('bkg_treasury_cash_positions'),
            'transfers' => $this->count('bkg_treasury_transfers'),
            'deals' => $this->count('bkg_treasury_deals'),
            'approvals' => $this->count('bkg_treasury_approvals'),
        ];
    }

    private function count(string $table): int
    {
        return Schema::hasTable($table) ? (int) app('db')->table($table)->count() : 0;
    }
}
