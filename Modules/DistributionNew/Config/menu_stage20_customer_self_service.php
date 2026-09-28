<?php
return [
    'customer_self_service' => [
        'name' => 'Customer Self-Service',
        'icon' => 'fa fa-user-circle',
        'permission' => 'distributionnew.customer_portal.view',
        'items' => [
            ['label' => 'Dashboard', 'route' => 'distribution-new.customer.dashboard'],
            ['label' => 'Place Orders', 'route' => 'distribution-new.customer.orders.index'],
            ['label' => 'Invoices', 'route' => 'distribution-new.customer.invoices.index'],
            ['label' => 'Statements', 'route' => 'distribution-new.customer.statements.index'],
            ['label' => 'Track Deliveries', 'route' => 'distribution-new.customer.deliveries.index'],
            ['label' => 'Return Requests', 'route' => 'distribution-new.customer.return-requests.index'],
            ['label' => 'Complaints', 'route' => 'distribution-new.customer.complaints.index'],
        ],
    ],
];
