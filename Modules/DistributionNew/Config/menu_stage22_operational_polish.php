<?php

return [
    'distribution_new_operational_polish' => [
        'label' => 'Operational Polish',
        'permission' => 'distribution_new.operational_polish.view',
        'items' => [
            ['label' => 'Profitability', 'route' => 'distribution-new.operational-polish.profitability.index', 'permission' => 'distribution_new.profitability.view'],
            ['label' => 'Collection Controls', 'route' => 'distribution-new.operational-polish.collection-controls.index', 'permission' => 'distribution_new.collection_controls.view'],
            ['label' => 'Reconciliation Exceptions', 'route' => 'distribution-new.operational-polish.reconciliation-exceptions.index', 'permission' => 'distribution_new.reconciliation_exceptions.view'],
            ['label' => 'Deployment Verification', 'route' => 'distribution-new.operational-polish.deployment-verification.index', 'permission' => 'distribution_new.deployment_verification.view'],
        ],
    ],
];
