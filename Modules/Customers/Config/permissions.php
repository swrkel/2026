<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Customers Module Permissions - CUS_SEP_009
    |--------------------------------------------------------------------------
    |
    | Customers-owned permission list. Keep these permission keys inside the
    | Customers module so routes, middleware, menus and future role screens can
    | use the same names without depending on Contact module permission files.
    |
    */

    'module' => [
        'customers.access',
        'customers.dashboard',
    ],

    'register' => [
        'customers.view',
        'customers.create',
        'customers.edit',
        'customers.delete',
    ],

    'ledger_statement' => [
        'customers.ledger',
        'customers.statement',
        'customers.balance',
    ],

    'payments' => [
        'customers.payment',
        'customers.advance_payment',
        'customers.deposit',
        'customers.refund',
        'customers.cheque_return',
    ],

    'reports' => [
        'customers.reports',
        'customers.export',
    ],

    'settings' => [
        'customers.settings',
        'customers.master_data',
    ],

    'portal' => [
        'customers.portal',
        'customers.portal.login',
        'customers.portal.settings',
    ],

    'workflow' => [
        'customers.approve',
        'customers.credit',
        'customers.status',
        'customers.workflow',
    ],

    'documents_audit' => [
        'customers.documents',
        'customers.notes',
        'customers.audit',
        'customers.activity',
    ],

    'roles' => [
        'Customer Manager' => [
            'customers.access', 'customers.dashboard', 'customers.view', 'customers.create',
            'customers.edit', 'customers.ledger', 'customers.statement', 'customers.reports',
            'customers.export', 'customers.documents', 'customers.notes', 'customers.activity',
        ],
        'Customer Officer' => [
            'customers.access', 'customers.dashboard', 'customers.view', 'customers.create',
            'customers.edit', 'customers.ledger', 'customers.statement', 'customers.documents',
            'customers.notes',
        ],
        'Customer Viewer' => [
            'customers.access', 'customers.dashboard', 'customers.view', 'customers.ledger',
            'customers.statement', 'customers.reports',
        ],
        'Customer Approver' => [
            'customers.access', 'customers.dashboard', 'customers.view', 'customers.approve',
            'customers.workflow', 'customers.audit',
        ],
        'Customer Credit Officer' => [
            'customers.access', 'customers.dashboard', 'customers.view', 'customers.credit',
            'customers.balance', 'customers.reports',
        ],
        'Customer Portal Administrator' => [
            'customers.access', 'customers.dashboard', 'customers.portal', 'customers.portal.settings',
            'customers.view', 'customers.settings',
        ],
    ],
];
