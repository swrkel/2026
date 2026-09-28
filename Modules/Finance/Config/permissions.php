<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Finance Module Permission Keys
    |--------------------------------------------------------------------------
    | FIN-007R keeps Finance permissions inside Modules/Finance so the module
    | can move away from main-system permission arrays. These keys are
    | intentionally namespaced with finance.* and do not remove legacy keys yet.
    */

    'finance.dashboard' => 'Finance Dashboard',

    'finance.accounts.view' => 'View Accounts',
    'finance.accounts.create' => 'Create Accounts',
    'finance.accounts.update' => 'Update Accounts',
    'finance.accounts.delete' => 'Delete Accounts',

    'finance.account_groups.view' => 'View Account Groups',
    'finance.account_groups.create' => 'Create Account Groups',
    'finance.account_groups.update' => 'Update Account Groups',
    'finance.account_groups.delete' => 'Delete Account Groups',

    'finance.account_types.view' => 'View Account Types',
    'finance.account_types.create' => 'Create Account Types',
    'finance.account_types.update' => 'Update Account Types',
    'finance.account_types.delete' => 'Delete Account Types',

    'finance.account_settings.view' => 'View Account Settings',
    'finance.account_settings.update' => 'Update Account Settings',

    'finance.journal.view' => 'View Journal Entries',
    'finance.journal.create' => 'Create Journal Entries',
    'finance.journal.update' => 'Update Journal Entries',
    'finance.journal.delete' => 'Delete Journal Entries',

    'finance.bank_reconciliation.view' => 'View Bank Reconciliation',
    'finance.bank_reconciliation.create' => 'Create Bank Reconciliation',
    'finance.bank_reconciliation.update' => 'Edit Bank Reconciliation',
    'finance.bank_reconciliation.finalize' => 'Finalize Bank Reconciliation',
    'finance.bank_reconciliation.reopen' => 'Reopen Bank Reconciliation',

    'finance.reports.dashboard' => 'Finance Reports Dashboard',
    'finance.reports.trial_balance' => 'Trial Balance Report',
    'finance.reports.general_ledger' => 'General Ledger Report',
    'finance.reports.account_ledger' => 'Account Ledger Report',
    'finance.reports.day_book' => 'Day Book Report',
    'finance.reports.income_statement' => 'Income Statement Report',
    'finance.reports.balance_sheet' => 'Balance Sheet Report',
    'finance.reports.profit_loss' => 'Profit and Loss Report',
    'finance.reports.account_book' => 'Account Book Report',
    'finance.reports.cash_flow' => 'Cash Flow Statement',

    'finance.cheques.view' => 'View Cheques',
    'finance.cheques.create' => 'Create Cheques',
    'finance.cheques.update' => 'Update Cheques',
    'finance.cheques.delete' => 'Delete Cheques',
    'finance.cheques.print' => 'Print Cheques',
    'finance.cheques.cancel' => 'Cancel Cheques',

    'finance.deposits.view' => 'View Cheque Deposits',
    'finance.deposits.create' => 'Create Cheque Deposits',
    'finance.deposits.update' => 'Update Cheque Deposits',
    'finance.deposits.delete' => 'Delete Cheque Deposits',

    'finance.payments.view' => 'View Finance Payments',
    'finance.payments.create' => 'Create Finance Payments',
    'finance.payments.update' => 'Update Finance Payments',
    'finance.payments.delete' => 'Delete Finance Payments',

    'finance.expenses.view' => 'View Finance Expenses',
    'finance.expenses.create' => 'Create Finance Expenses',
    'finance.expenses.update' => 'Update Finance Expenses',
    'finance.expenses.delete' => 'Delete Finance Expenses',
];
