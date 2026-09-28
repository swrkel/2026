<?php

$root = dirname(__DIR__, 3);
$moduleRoot = dirname(__DIR__);
$moduleJson = $moduleRoot . '/module.json';
$statusFile = $root . '/modules_statuses.json';

function line($label, $value)
{
    echo str_pad($label, 44, '.') . ' ' . $value . PHP_EOL;
}

echo "=== SIMPLE AUDIT CENTRAL-ONLY CHECK v1.0.21 ===" . PHP_EOL;
echo "Project: {$root}" . PHP_EOL . PHP_EOL;

$name = 'SimpleAudit';
$json = [];
if (is_file($moduleJson)) {
    $json = json_decode((string) file_get_contents($moduleJson), true);
    if (is_array($json) && !empty($json['name'])) {
        $name = $json['name'];
    }
}

line('Module folder', is_dir($moduleRoot) ? 'YES' : 'NO');
line('module.json', is_file($moduleJson) ? 'YES' : 'NO');
line('Module name', $name);
line('Module version', is_array($json) ? ($json['version'] ?? 'UNKNOWN') : 'UNKNOWN');
line('Routes file', is_file($moduleRoot . '/Routes/web.php') ? 'YES' : 'NO');
line('Sidebar partial', is_file($moduleRoot . '/Resources/views/layouts/partials/sidebar.blade.php') ? 'YES' : 'NO');
line('V2 sidebar partial', is_file($moduleRoot . '/Resources/views/layouts_v2/partials/sidebar.blade.php') ? 'YES' : 'NO');
line('Module permission manifest', is_file($moduleRoot . '/Config/module_permissions.php') ? 'YES' : 'NO');

$providerFile = $moduleRoot . '/Providers/SimpleAuditServiceProvider.php';
$providerText = is_file($providerFile) ? (string) file_get_contents($providerFile) : '';
line('Low-memory route loader', strpos($providerText, 'moduleRoutesRegistered') !== false && strpos($providerText, 'namedRouteExists') === false ? 'YES' : 'NO');
$tenantManagerFile = $moduleRoot . '/Services/TenantConnectionManager.php';
$tenantManagerText = is_file($tenantManagerFile) ? (string) file_get_contents($tenantManagerFile) : '';
line('Canonical system central DB priority', strpos($tenantManagerText, 'database.connections.system.database') !== false ? 'YES' : 'NO');
line('Tenant registry diagnostic command', is_file($moduleRoot . '/Console/TenantsStatusCommand.php') ? 'YES' : 'NO');

$hostProvider = $root . '/app/Providers/AppServiceProvider.php';
$hostProviderText = is_file($hostProvider) ? (string) file_get_contents($hostProvider) : '';
line('Host SimpleAudit bootstrap bridge', strpos($hostProviderText, 'Simple Audit standalone bootstrap bridge') !== false ? 'YES' : 'NO');

$registryFile = $root . '/app/Services/AutomaticModuleRegistry.php';
$registryText = is_file($registryFile) ? (string) file_get_contents($registryFile) : '';
line('Registry /superadmin/simple-audit URL mapping', strpos($registryText, "'simple_audit' => '/superadmin/simple-audit'") !== false ? 'YES' : 'NO');

$enabled = null;
if (is_file($statusFile)) {
    $statuses = json_decode((string) file_get_contents($statusFile), true);
    if (is_array($statuses) && array_key_exists($name, $statuses)) {
        $enabled = (bool) $statuses[$name];
    }
}
line('modules_statuses.json entry', $enabled === null ? 'MISSING' : ($enabled ? 'true' : 'false'));
line('Globally enabled', $enabled === true ? 'YES' : 'NO');

$centralOnly = is_array($json) && !empty($json['central_only']);
line('Central-only module flag', $centralOnly ? 'YES' : 'NO');

$centralSidebarFile = $root . '/resources/views/layouts/partials/sidebar-superadmin.blade.php';
$centralSidebarText = is_file($centralSidebarFile) ? (string) @file_get_contents($centralSidebarFile) : '';
$dedicatedSidebarFile = $root . '/resources/views/layouts/partials/sidebar-sections/sidebar-simple-audit.blade.php';
$dedicatedSidebarText = is_file($dedicatedSidebarFile) ? (string) @file_get_contents($dedicatedSidebarFile) : '';
$normalSidebarFile = $root . '/resources/views/layouts/partials/sidebar.blade.php';
$normalSidebarText = is_file($normalSidebarFile) ? (string) @file_get_contents($normalSidebarFile) : '';
line('Central-only registry support', is_file($registryFile) && strpos($registryText, 'central_only') !== false ? 'YES' : 'NO');
line('Dedicated sidebar-section file', is_file($dedicatedSidebarFile) ? 'YES' : 'NO');
line('Central sidebar includes section', strpos($centralSidebarText, "sidebar-sections.sidebar-simple-audit") !== false ? 'YES' : 'NO');
line('Normal host sidebar includes section', strpos($normalSidebarText, "sidebar-sections.sidebar-simple-audit") !== false ? 'YES' : 'NO');
line('Dedicated Simple Audit menu markup', strpos($dedicatedSidebarText, 'central-simple-audit-sidebar-menu') !== false ? 'YES' : 'NO');
line('Dedicated menu is Central route', strpos($dedicatedSidebarText, 'superadmin/simple-audit') !== false ? 'YES' : 'NO');
line('Dedicated menu genuine-admin gate', strpos($dedicatedSidebarText, 'isGenuineSuperAdmin') !== false ? 'YES' : 'NO');
line('Purchase Audit child link', strpos($dedicatedSidebarText, '>\n                    Purchase Audit\n') !== false || strpos($dedicatedSidebarText, 'Purchase Audit') !== false ? 'YES' : 'NO');
line('Simple Audit submenu markup', strpos($dedicatedSidebarText, 'central-simple-audit-pages') !== false ? 'YES' : 'NO');
line('Simple Audit submenu default open', strpos($dedicatedSidebarText, 'class=\"collapse show\"') !== false ? 'YES' : 'NO');
line('Business catalogue excludes central-only', is_file($registryFile) && strpos($registryText, "if (!empty(\$module['central_only']))") !== false ? 'YES' : 'NO');

$indexView = $moduleRoot . '/Resources/views/purchase-audit/index.blade.php';
$indexText = is_file($indexView) ? (string) @file_get_contents($indexView) : '';
$assetController = $moduleRoot . '/Http/Controllers/AssetController.php';
$assetText = is_file($assetController) ? (string) @file_get_contents($assetController) : '';
$reportService = $moduleRoot . '/Services/PurchaseAuditService.php';
$reportText = is_file($reportService) ? (string) @file_get_contents($reportService) : '';
line('Purchase Audit asset versioning', strpos($indexText, 'sauAssetVersion') !== false ? 'YES' : 'NO');
line('Simple Audit assets no-store', strpos($assetText, 'no-store') !== false ? 'YES' : 'NO');
line('Older account location compatibility', strpos($reportText, 'hasAtLocation') !== false ? 'YES' : 'NO');
line('Low-memory report test command', is_file($moduleRoot . '/Console/TestPurchaseAuditCommand.php') ? 'YES' : 'NO');
$auditJsFile = $moduleRoot . '/Resources/assets/js/simple-audit.js';
$auditJsText = is_file($auditJsFile) ? (string) @file_get_contents($auditJsFile) : '';
line('Selected combo opens full option list', strpos($auditJsText, "this.hidden.value ? '' : this.text.value") !== false ? 'YES' : 'NO');
line('All context fields type + auto filter', strpos($auditJsText, "this.text.addEventListener('input'") !== false && strpos($auditJsText, 'this.text.select()') !== false ? 'YES' : 'NO');
line('System standard date range picker', strpos($auditJsText, '$.fn.daterangepicker') !== false && strpos($auditJsText, 'dateRangeSettings') !== false ? 'YES' : 'NO');
line('Date range instant auto filter', strpos($auditJsText, 'apply.daterangepicker.sauDateRange') !== false && strpos($auditJsText, 'scheduleLoad(30)') !== false ? 'YES' : 'NO');

// Bootstrap the real Laravel application and inspect the live route collection.
echo PHP_EOL . "Runtime route check:" . PHP_EOL;
$autoload = $root . '/vendor/autoload.php';
$bootstrap = $root . '/bootstrap/app.php';
if (!is_file($autoload) || !is_file($bootstrap)) {
    line('Laravel bootstrap', 'UNAVAILABLE');
} else {
    try {
        require_once $autoload;
        $app = require $bootstrap;
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $routes = $app['router']->getRoutes();
        $wanted = [
            'simpleaudit.home',
            'simpleaudit.purchase-audit',
            'simpleaudit.purchase-audit.data',
        ];
        foreach ($wanted as $routeName) {
            $route = method_exists($routes, 'getByName') ? $routes->getByName($routeName) : null;
            if ($route) {
                $uri = method_exists($route, 'uri') ? $route->uri() : '';
                line('Route ' . $routeName, 'YES  /' . ltrim($uri, '/'));
            } else {
                line('Route ' . $routeName, 'NO');
            }
        }

        line('Configured route prefix', (string) config('simpleaudit.route_prefix', '[missing]'));
    } catch (\Throwable $e) {
        line('Laravel runtime route check', 'ERROR: ' . $e->getMessage());
    }
}

echo "\nLow-memory route verification command:\n";
echo "  php artisan simple-audit:routes\n";
echo "  php artisan simple-audit:tenants\n";
echo "  php artisan simple-audit:test-report --tenant=100\n";
echo "  (Do not use the global php artisan route:list for Simple Audit verification on this large ERP.)\n";

echo "\nExpected behaviour:\n";
echo "  CENTRAL /superadmin pages: Simple Audit > Purchase Audit is visible.\n";
echo "  TENANT/BUSINESS sidebar: Simple Audit is NOT visible.\n";
echo "  TENANT/BUSINESS direct URL /simple-audit: HTTP 403.\n";
