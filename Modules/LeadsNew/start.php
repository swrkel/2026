<?php

/*
|--------------------------------------------------------------------------
| Leads-New Module Start File
|--------------------------------------------------------------------------
|
| This file is loaded by the ERP module loader on some installations before
| the module service provider is fully booted. Keep only safe bootstrap work
| here. Do not register routes from this file because tenant routes must be
| loaded through the ERP tenant route stack.
|
*/

if (function_exists('module_path')) {
    $views = module_path('LeadsNew', 'Resources/views');
    $lang = module_path('LeadsNew', 'Resources/lang');
    $config = module_path('LeadsNew', 'Config/config.php');

    try {
        if (is_dir($views)) {
            app('view')->addNamespace('leadsnew', $views);
            app('view')->addNamespace('leads_new', $views);
        }
        if (is_dir($lang)) {
            app('translator')->addNamespace('leadsnew', $lang);
            app('translator')->addNamespace('leads_new', $lang);
        }
        if (file_exists($config)) {
            config(['leadsnew' => array_replace_recursive(config('leadsnew', []), require $config)]);
        }
    } catch (Throwable $e) {
        // Never break the host ERP while the module loader is bootstrapping.
    }
}
