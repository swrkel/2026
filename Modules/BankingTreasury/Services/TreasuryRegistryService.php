<?php

namespace Modules\BankingTreasury\Services;

class TreasuryRegistryService
{
    public function items(): array
    {
        return [
            ['code' => 'VAULT', 'name' => 'Vault / cash position control', 'status' => 'Ready for UI testing'],
            ['code' => 'TRANSFER', 'name' => 'Inter-branch treasury transfer workflow', 'status' => 'Ready for UI testing'],
            ['code' => 'DEAL', 'name' => 'FX, money market, investment and treasury bill register', 'status' => 'Ready for UI testing'],
            ['code' => 'APPROVAL', 'name' => 'Treasury approval and audit flow', 'status' => 'Ready for UI testing'],
        ];
    }
    public function reports(): array
    {
        return ['Treasury Position', 'Liquidity Report', 'Vault Balance', 'Cash Position', 'Treasury Deal Register', 'Dealer Limit Report'];
    }
    public function settings(): array
    {
        return ['Dealer limits', 'Position limits', 'Currency setup', 'Approval levels', 'Number series', 'Treasury products'];
    }
}
