<?php

namespace Modules\BankingUI\Support;

class BankingMenuMatrix
{
    public static function items(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'permission' => 'banking.dashboard.view', 'route' => 'banking.dashboard'],
            ['key' => 'deposits', 'label' => 'Core Deposits', 'permission' => 'banking.deposits.view', 'route' => 'banking.deposits.index'],
            ['key' => 'teller', 'label' => 'Teller Operations', 'permission' => 'banking.teller.view', 'route' => 'banking.teller.index'],
            ['key' => 'cheques', 'label' => 'Cheque Management', 'permission' => 'banking.cheques.view', 'route' => 'banking.cheques.index'],
            ['key' => 'cards', 'label' => 'ATM & Debit Cards', 'permission' => 'banking.cards.view', 'route' => 'banking.cards.index'],
            ['key' => 'internet', 'label' => 'Internet Banking', 'permission' => 'banking.internet.view', 'route' => 'banking.internet.index'],
            ['key' => 'mobile', 'label' => 'Mobile Banking', 'permission' => 'banking.mobile.view', 'route' => 'banking.mobile.index'],
            ['key' => 'microfinance', 'label' => 'Microfinance', 'permission' => 'banking.microfinance.view', 'route' => 'banking.microfinance.index'],
            ['key' => 'insurance', 'label' => 'Insurance', 'permission' => 'banking.insurance.view', 'route' => 'banking.insurance.index'],
            ['key' => 'treasury', 'label' => 'Treasury & Liquidity', 'permission' => 'banking.treasury.view', 'route' => 'banking.treasury.index'],
            ['key' => 'risk', 'label' => 'Risk Management', 'permission' => 'banking.risk.view', 'route' => 'banking.risk.index'],
            ['key' => 'aml', 'label' => 'AML & Compliance', 'permission' => 'banking.aml.view', 'route' => 'banking.aml.index'],
            ['key' => 'audit', 'label' => 'Enterprise Audit', 'permission' => 'banking.audit.view', 'route' => 'banking.audit.index'],
            ['key' => 'settings', 'label' => 'Settings', 'permission' => 'banking.settings.view', 'route' => 'banking.settings.index'],
        ];
    }
}
