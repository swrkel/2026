<?php

return [
    'dashboard' => [
        'purchase.dashboard.view',
    ],

    'entries' => [
        'purchase.entry.view',
        'purchase.entry.create',
        'purchase.entry.edit',
        'purchase.entry.delete',
        'purchase.entry.print',
    ],

    'orders' => [
        'purchase.order.view',
        'purchase.order.create',
        'purchase.order.edit',
        'purchase.order.delete',
        'purchase.order.print',
    ],

    'returns' => [
        'purchase.return.view',
        'purchase.return.create',
        'purchase.return.edit',
        'purchase.return.delete',
        'purchase.return.print',
    ],

    'bills' => [
        'purchase.bill.view',
        'purchase.bill.create',
        'purchase.bill.edit',
        'purchase.bill.delete',
        'purchase.bill.print',
    ],

    'supplier_payments' => [
        'purchase.supplier_payment.view',
        'purchase.supplier_payment.create',
        'purchase.supplier_payment.edit',
        'purchase.supplier_payment.delete',
        'purchase.supplier_payment.print',
    ],

    'reports' => [
        'purchase.report.view',
        'purchase.report.purchase_register',
        'purchase.report.purchase_payment',
        'purchase.report.product_purchase',
        'purchase.report.purchase_sell',
        'purchase.report.stock_purchase_sale',
        'purchase.report.supplier_outstanding',
    ],

    'settings' => [
        'purchase.settings.view',
        'purchase.settings.manage',
        'purchase.settings.numbering',
        'purchase.settings.approval',
        'purchase.settings.tax',
        'purchase.settings.supplier',
        'purchase.settings.general',
    ],
];
