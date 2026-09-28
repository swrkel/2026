<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixDomainsIdAutoIncrement extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('domains') || DB::getDriverName() !== 'mysql') {
            return;
        }

        $database = DB::getDatabaseName();
        $column = DB::selectOne(
            'SELECT EXTRA, COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$database, 'domains', 'id']
        );

        if (! $column || stripos((string) $column->EXTRA, 'auto_increment') !== false) {
            return;
        }

        DB::statement('SET @domain_row_number := 0');
        DB::statement('UPDATE domains SET id = (@domain_row_number := @domain_row_number + 1) ORDER BY tenant_id, domain');

        if ($column->COLUMN_KEY !== 'PRI') {
            DB::statement('ALTER TABLE domains ADD PRIMARY KEY (id)');
        }

        DB::statement('ALTER TABLE domains MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down()
    {
        //
    }
}
