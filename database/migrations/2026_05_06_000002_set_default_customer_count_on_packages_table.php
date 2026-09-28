<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::connection('system')->statement('ALTER TABLE packages MODIFY customer_count INT NOT NULL DEFAULT 0');
    }

    public function down()
    {
        DB::connection('system')->statement('ALTER TABLE packages MODIFY customer_count INT NOT NULL');
    }
};
