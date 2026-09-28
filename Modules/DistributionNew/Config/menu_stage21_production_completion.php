<?php

return [
    'production_completion' => [
        'title' => 'Production Completion',
        'icon' => 'fa fa-check-circle',
        'permission' => 'distribution_new.management_dashboard.view',
        'items' => [
            ['title' => 'Workflow Validation', 'route' => 'distribution-new.production-completion.workflow-validation.index', 'permission' => 'distribution_new.workflow_validation.view'],
            ['title' => 'Stock Reconciliation', 'route' => 'distribution-new.production-completion.stock-reconciliation.index', 'permission' => 'distribution_new.stock_reconciliation.view'],
            ['title' => 'Visit Plans', 'route' => 'distribution-new.production-completion.visit-plans.index', 'permission' => 'distribution_new.visit_plan.view'],
            ['title' => 'Management Dashboard', 'route' => 'distribution-new.production-completion.management-dashboard.index', 'permission' => 'distribution_new.management_dashboard.view'],
        ],
    ],
];
