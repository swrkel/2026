<?php

return [
    'release' => 'RC6',
    'title' => 'Production Completion Milestone',
    'ui_standard' => 'communication_hub',
    'sql_package' => 'LEADS_NEW_PRODUCTION_CONSOLIDATION_RC6_SQL_20260705.zip',
    'module_package' => 'LEADS_NEW_PRODUCTION_CONSOLIDATION_RC6_MODULE_20260705.zip',
    'notes' => [
        'Continue Customers-style standalone module architecture.',
        'Keep route loading inside the module only.',
        'Keep tenant database usage isolated from the central database.',
        'Use RC6 folder for incremental SQL and MASTER folder for cumulative SQL.',
    ],
];
