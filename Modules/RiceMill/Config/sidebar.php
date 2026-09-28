<?php
return [
    'module' => 'Rice Mill Module',
    'key' => 'ricemill',
    'icon' => 'fa fa-industry',
    'route' => 'rice-mill.dashboard',
    'permission' => 'rice_mill.dashboard.view',

    // These aliases allow different generations of the host automatic sidebar
    // discovery code to locate the module-owned menu without editing core files.
    'view' => 'ricemill::layouts_v2.partials.sidebar',
    'sidebar_view' => 'ricemill::layouts_v2.partials.sidebar',
    'partial' => 'ricemill::layouts_v2.partials.sidebar',

    // Flat list retained for sidebar/menu managers that read Config/sidebar.php.
    'items' => [
        ['section' => 'Dashboard', 'label' => 'Dashboard', 'route' => 'rice-mill.dashboard', 'permission' => 'rice_mill.dashboard.view'],

        ['section' => 'Paddy Operations', 'label' => 'Purchase Orders', 'route' => 'rice-mill.purchases.index', 'permission' => 'rice_mill.paddy_purchase.view'],
        ['section' => 'Paddy Operations', 'label' => 'Paddy Receiving', 'route' => 'rice-mill.receipts.index', 'permission' => 'rice_mill.paddy_receipt.view'],
        ['section' => 'Paddy Operations', 'label' => 'Weighbridge', 'route' => 'rice-mill.weighbridge.index', 'permission' => 'rice_mill.paddy_receipt.view'],
        ['section' => 'Paddy Operations', 'label' => 'Paddy Stock', 'route' => 'rice-mill.paddy-stock.index', 'permission' => 'rice_mill.paddy_stock.view'],

        ['section' => 'Production & Stock', 'label' => 'Production / Milling', 'route' => 'rice-mill.production.entry', 'permission' => 'rice_mill.production.view'],
        ['section' => 'Production & Stock', 'label' => 'By-Products', 'route' => 'rice-mill.byproducts.index', 'permission' => 'rice_mill.production.view'],
        ['section' => 'Production & Stock', 'label' => 'Packing', 'route' => 'rice-mill.packing.index', 'permission' => 'rice_mill.packing.view'],
        ['section' => 'Production & Stock', 'label' => 'Packaging Materials', 'route' => 'rice-mill.packaging-materials.index', 'permission' => 'rice_mill.packing.view'],
        ['section' => 'Production & Stock', 'label' => 'Material Usage Mapping', 'route' => 'rice-mill.packaging-material-mappings.index', 'permission' => 'rice_mill.packing.view'],
        ['section' => 'Production & Stock', 'label' => 'Finished Rice Stock', 'route' => 'rice-mill.finished-stock.index', 'permission' => 'rice_mill.finished_stock.view'],

        ['section' => 'Sales & Dispatch', 'label' => 'Sales / Dispatch', 'route' => 'rice-mill.dispatch.index', 'permission' => 'rice_mill.dispatch.view'],

        ['section' => 'Reports', 'label' => 'Rice Mill Reports', 'route' => 'rice-mill.reports.index', 'permission' => 'rice_mill.reports.view'],

        ['section' => 'Administration', 'label' => 'Settings', 'route' => 'rice-mill.settings.index', 'permission' => 'rice_mill.settings.view'],
    ],
];
