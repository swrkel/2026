<?php

namespace Modules\PetroPD\Services;

/**
 * Canonical PetroPD payment-method catalogue used by amount-adjustment reports.
 * Unknown historical methods remain displayable through the report fallback.
 */
class SettlementAmountAdjustmentService
{
    public const METHODS = [
        'cash' => ['label' => 'Cash'],
        'cash_deposit' => ['label' => 'Cash Deposit'],
        'card' => ['label' => 'Cards'],
        'cheque' => ['label' => 'Cheques'],
        'credit_sale' => ['label' => 'Credit Sales'],
        'expense' => ['label' => 'Expenses'],
        'shortage' => ['label' => 'Shortage'],
        'excess' => ['label' => 'Excess'],
        'loan_payment' => ['label' => 'Loan Payments'],
        'customer_loan' => ['label' => 'Loan to Customer'],
        'owners_drawing' => ['label' => 'Owners Drawings'],
        'pos' => ['label' => 'POS Payments'],
    ];
}
