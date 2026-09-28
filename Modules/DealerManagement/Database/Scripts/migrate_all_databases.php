<?php
/**
 * Dealer Management / Dealer Hub all-database migration runner.
 * Run from project root:
 *   php Modules/DealerManagement/Database/Scripts/migrate_all_databases.php
 */
$base = dirname(__DIR__, 4);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$prefix = env('TENANT_DATABASE_PREFIX', 'nivasa_');
$original = config('database.connections.mysql.database');
$central = env('DEALER_HUB_CENTRAL_DATABASE', $original);
$databases = [$central, $original];

foreach (DB::select(
    'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE ? ORDER BY SCHEMA_NAME',
    [$prefix . '%']
) as $row) {
    $databases[] = $row->SCHEMA_NAME;
}

foreach (array_values(array_unique(array_filter($databases))) as $database) {
    try {
        $check = DB::select(
            'SELECT COUNT(*) cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, 'business']
        );
        if (($database !== $central) && (empty($check) || (int) $check[0]->cnt === 0)) {
            echo "[SKIP] {$database} - not an ERP database\n";
            continue;
        }

        DB::purge('mysql');
        config(['database.connections.mysql.database' => $database]);
        DB::reconnect('mysql');

        echo "\n========================================\n[START] {$database}\n========================================\n";
        Artisan::call('migrate', [
            '--path' => 'Modules/DealerManagement/Database/Migrations',
            '--force' => true,
        ]);
        echo Artisan::output();
        echo "[OK] {$database}\n";
    } catch (Throwable $e) {
        echo "[ERROR] {$database} => {$e->getMessage()}\n";
    }
}

DB::purge('mysql');
config(['database.connections.mysql.database' => $original]);
DB::reconnect('mysql');
echo "\nDealer Management / Dealer Hub migrations completed.\n";
