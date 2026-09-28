<?php
return [
    'key' => 'graphs',
    'label' => 'Graphs',
    'icon' => 'fa fa-line-chart',
    'route' => 'graphs.index',
    'order' => 845,
    'children' => [
        [
            'label' => 'Stocks & Sales Analytical',
            'route' => 'graphs.index',
        ],
        [
            'label' => 'Financial & Profitability Analytics',
            'route' => 'graphs.financial',
        ],
        [
            'label' => 'Operational & Loss Analytics',
            'route' => 'graphs.operational',
        ],
        [
            'label' => 'Customer & Payment Analytics',
            'route' => 'graphs.customer-payment',
        ],
        [
            'label' => 'Management Dashboard Structure',
            'route' => 'graphs.management-dashboard',
        ],
    ],
];
