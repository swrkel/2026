<?php

return [
    'toolbar' => [
        'search' => true,
        'date_range' => true,
        'exports' => ['csv', 'excel', 'pdf', 'print'],
        'column_visibility' => true,
    ],
    'groups' => [
        'core_deposits' => [
            'label' => 'Core Deposits',
            'reports' => ['deposit_register', 'new_accounts', 'closed_accounts', 'interest_accrual', 'maturity_register'],
        ],
        'teller' => [
            'label' => 'Teller Operations',
            'reports' => ['teller_summary', 'cash_drawer', 'cash_difference', 'eod_balancing'],
        ],
        'cheques' => [
            'label' => 'Cheque Management',
            'reports' => ['cheque_book_register', 'stop_payment', 'returned_cheques', 'clearing_summary'],
        ],
        'cards' => [
            'label' => 'ATM & Debit Cards',
            'reports' => ['card_inventory', 'issued_cards', 'hotlisted_cards', 'atm_transactions', 'card_disputes'],
        ],
        'microfinance' => [
            'label' => 'Microfinance',
            'reports' => ['portfolio_quality', 'aging', 'collections', 'officer_targets', 'npl_recovery'],
        ],
        'insurance' => [
            'label' => 'Insurance',
            'reports' => ['policy_register', 'premium_collection', 'claims_register'],
        ],
    ],
];
