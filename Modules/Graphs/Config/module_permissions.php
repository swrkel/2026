<?php
return [
    [
        'key' => 'graphs_module',
        'label' => 'Graphs Module',
        'type' => 'module',
    ],
    [
        'key' => 'graphs_stock_sales_analytics',
        'label' => 'Stocks & Sales Analytical',
        'type' => 'page',
        'route_paths' => [
            'graphs',
            'graphs/data/tanks',
            'graphs/data/fuel-sales',
            'graphs/data/non-fuel-sales',
        ],
    ],
    [
        'key' => 'graphs_financial_profitability_analytics',
        'label' => 'Financial & Profitability Analytics',
        'type' => 'page',
        'route_paths' => [
            'graphs/financial-profitability',
            'graphs/data/reconciliation',
            'graphs/data/profitability',
            'graphs/data/debtors-ageing',
            'graphs/data/debtors-ageing/customers',
        ],
    ],
    [
        'key' => 'graphs_operational_loss_analytics',
        'label' => 'Operational & Loss Analytics',
        'type' => 'page',
        'route_paths' => [
            'graphs/operational-loss',
            'graphs/data/dip-variance',
            'graphs/data/pump-shift-sales',
        ],
    ],
    [
        'key' => 'graphs_customer_payment_analytics',
        'label' => 'Customer & Payment Analytics',
        'type' => 'page',
        'route_paths' => [
            'graphs/customer-payment',
            'graphs/data/payment-method-split',
            'graphs/data/customer-pump-shift-sales',
            'graphs/data/top-credit-customers',
        ],
    ],
    [
        'key' => 'graphs_management_dashboard_structure',
        'label' => 'Management Dashboard Structure',
        'type' => 'page',
        'route_paths' => [
            'graphs/management-dashboard',
            'graphs/data/management-dashboard',
        ],
    ],
];
