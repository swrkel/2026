<?php

namespace Modules\Finance\Services;

/**
 * FIN-002 bridge: central place to resolve Finance module entity classes.
 * Existing main-system models remain untouched until controller migration is completed.
 */
class FinanceEntityResolver
{
    public static function account(): string { return \Modules\Finance\Entities\Account::class; }
    public static function accountGroup(): string { return \Modules\Finance\Entities\AccountGroup::class; }
    public static function accountType(): string { return \Modules\Finance\Entities\AccountType::class; }
    public static function accountTransaction(): string { return \Modules\Finance\Entities\AccountTransaction::class; }
    public static function paymentAccount(): string { return \Modules\Finance\Entities\PaymentAccount::class; }
    public static function settlementChequePayment(): string { return \Modules\Finance\Entities\SettlementChequePayments::class; }
}
