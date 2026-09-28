<?php

namespace Modules\Distribution\Support;

/**
 * Central reference map for Distribution-owned compatibility classes.
 * Used by audits and staged cleanup scripts to avoid ad-hoc replacements.
 */
class DistributionCoreDependencyMap
{
    public static function replacements(): array
    {
        return [
            '\\App\\TransactionPayment' => '\\Modules\\Distribution\\Entities\\Core\\TransactionPayment',
            '\\App\\Transaction' => '\\Modules\\Distribution\\Entities\\Core\\Transaction',
            '\\App\\BusinessLocation' => '\\Modules\\Distribution\\Entities\\Core\\BusinessLocation',
            '\\App\\Business' => '\\Modules\\Distribution\\Entities\\Core\\Business',
            '\\App\\AccountTransaction' => '\\Modules\\Distribution\\Entities\\Core\\AccountTransaction',
            '\\App\\AccountType' => '\\Modules\\Distribution\\Entities\\Core\\AccountType',
            '\\App\\Account' => '\\Modules\\Distribution\\Entities\\Core\\Account',
            '\\App\\PurchaseLine' => '\\Modules\\Distribution\\Entities\\Core\\PurchaseLine',
            '\\App\\SalesAgent' => '\\Modules\\Distribution\\Entities\\Core\\SalesAgent',
            '\\App\\ContactLedger' => '\\Modules\\Distribution\\Entities\\Core\\ContactLedger',
            '\\App\\Contact' => '\\Modules\\Distribution\\Entities\\Core\\Contact',
            '\\App\\Customer' => '\\Modules\\Distribution\\Entities\\Core\\Customer',
            '\\App\\Product' => '\\Modules\\Distribution\\Entities\\Core\\Product',
            '\\App\\Category' => '\\Modules\\Distribution\\Entities\\Core\\Category',
            '\\App\\Unit' => '\\Modules\\Distribution\\Entities\\Core\\Unit',
            '\\App\\TaxRate' => '\\Modules\\Distribution\\Entities\\Core\\TaxRate',
            '\\App\\ExpenseCategory' => '\\Modules\\Distribution\\Entities\\Core\\ExpenseCategory',
            '\\App\\OpeningBalance' => '\\Modules\\Distribution\\Entities\\Core\\OpeningBalance',
            '\\App\\System' => '\\Modules\\Distribution\\Entities\\Core\\System',
            '\\App\\User' => '\\Modules\\Distribution\\Entities\\Core\\User',
        ];
    }
}
