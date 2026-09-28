<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE pump_operator_assignments MODIFY shift_number VARCHAR(50) NOT NULL DEFAULT "0"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE pump_operator_assignments MODIFY shift_number INT NOT NULL DEFAULT 0');
    }
};
