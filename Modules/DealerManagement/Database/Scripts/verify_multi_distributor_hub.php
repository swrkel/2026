<?php
$base = dirname(__DIR__, 4);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$central = env('DEALER_HUB_CENTRAL_DATABASE', config('database.connections.mysql.database'));
$original = config('database.connections.mysql.database');
try {
    DB::purge('mysql'); config(['database.connections.mysql.database'=>$central]); DB::reconnect('mysql');
    foreach (['dlr_hub_dealers','dlr_hub_users','dlr_hub_distributors','dlr_hub_connections','dlr_hub_product_sources','dlr_hub_sales_entries','dlr_hub_sales_allocations','dlr_hub_orders','dlr_hub_split_orders'] as $table) {
        $r=DB::select('SELECT COUNT(*) cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?',[$central,$table]);
        echo $table.' => '.((int)$r[0]->cnt===1?'OK':'MISSING').PHP_EOL;
    }
} finally { DB::purge('mysql');config(['database.connections.mysql.database'=>$original]);DB::reconnect('mysql'); }
