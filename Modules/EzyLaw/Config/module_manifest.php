<?php
return [
    'key'=>'ezylaw',
    'name'=>'EzyLaw',
    'aliases'=>['ezylaw_module','ezy_law','lawyer_management','lawyer_management_system'],
    'route_prefix'=>'ezylaw',
    'default_route'=>'ezylaw.dashboard',
    'permissions_file'=>__DIR__.'/module_permissions.php',
    'standalone'=>true,
    'version'=>'4.0.0',
    'completion_release'=>true,
    'tenant_database_tables_prefix'=>'law_',
];
