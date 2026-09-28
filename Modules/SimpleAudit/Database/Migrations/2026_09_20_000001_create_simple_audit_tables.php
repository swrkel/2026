<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\SimpleAudit\Services\SchemaInstaller;

return new class extends Migration
{
    public function up()
    {
        $connection = config('database.default');
        $schema = Schema::connection($connection);

        // This migration is tenant-only. A central database normally has the
        // tenants/domains tables but not the operational purchase tables.
        if (!$schema->hasTable('business') || !$schema->hasTable('transactions') || !$schema->hasTable('purchase_lines')) {
            return;
        }

        app(SchemaInstaller::class)->install($connection, true, true);
    }

    public function down()
    {
        $connection = config('database.default');
        $schema = Schema::connection($connection);
        if (!$schema->hasTable('sau_settings')) {
            return;
        }

        $installer = app(SchemaInstaller::class);
        $installer->dropTriggers($connection);

        foreach (['sau_activity_logs','sau_report_shares','sau_stock_snapshots','sau_change_events','sau_settings'] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
