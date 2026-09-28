<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_module INT NOT NULL DEFAULT 0');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_todo INT NOT NULL DEFAULT 0');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_document INT NOT NULL DEFAULT 0');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_memos INT NOT NULL DEFAULT 0');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_messages INT NOT NULL DEFAULT 0');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_reminders INT NOT NULL DEFAULT 0');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_settings INT NOT NULL DEFAULT 0');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY allowance_deduction INT NOT NULL DEFAULT 0');
    }

    public function down()
    {
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_module INT NOT NULL');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_todo INT NOT NULL');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_document INT NOT NULL');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_memos INT NOT NULL');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_messages INT NOT NULL');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_reminders INT NOT NULL');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY essentials_settings INT NOT NULL');
        DB::connection('system')->statement('ALTER TABLE packages MODIFY allowance_deduction INT NOT NULL');
    }
};
