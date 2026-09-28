<?php

$source = dirname(__DIR__, 2) . '/bootstrap/deployment_cache_guard.php';
if (! is_file($source)) {
    fwrite(STDERR, "FAIL: guard missing\n"); exit(1);
}
$text = (string) file_get_contents($source);
$forbidden = ['app()->register', 'PortableModuleBootstrap', 'Illuminate\\Support\\Facades'];
foreach ($forbidden as $needle) {
    if (strpos($text, $needle) !== false) {
        fwrite(STDERR, "FAIL: forbidden runtime/provider dependency in guard: {$needle}\n"); exit(1);
    }
}
foreach (['modules_statuses.json', '*_module.php', 'automatic_module_registry_manifest.php'] as $needle) {
    if (strpos($text, $needle) === false) {
        fwrite(STDERR, "FAIL: expected portability protection missing: {$needle}\n"); exit(1);
    }
}
echo "PASS: filesystem-only portability guard contract\n";
