<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Settlement SW Permissions
    |--------------------------------------------------------------------------
    | SW_SEP_006: permission keys live in this module so menus, buttons and
    | future seeders can use Settlement SW specific permissions instead of external-module
    | permission names.
    */
    'settlement_sw.access',
    'settlement_sw.create',
    'settlement_sw.update',
    'settlement_sw.delete',
    'settlement_sw.print',
    'settlement_sw.reports.view',
    'settlement_sw.payments.cash',
    'settlement_sw.payments.card',
    'settlement_sw.payments.cheque',
    'settlement_sw.payments.credit_sale',
    'settlement_sw.payments.loan',
    'settlement_sw.payments.drawing',
    'settlement_sw.payments.expense',
    'settlement_sw.payments.shortage_excess',
];
