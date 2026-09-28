<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangePcsToStringInFormF25DetailsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('form_f25_details') || !Schema::hasColumn('form_f25_details', 'pcs')) {
            return;
        }

        DB::statement("ALTER TABLE form_f25_details MODIFY pcs VARCHAR(191) NULL");
    }

    public function down()
    {
        if (!Schema::hasTable('form_f25_details') || !Schema::hasColumn('form_f25_details', 'pcs')) {
            return;
        }

        DB::statement("ALTER TABLE form_f25_details MODIFY pcs DECIMAL(22,4) NOT NULL DEFAULT 0");
    }
}
