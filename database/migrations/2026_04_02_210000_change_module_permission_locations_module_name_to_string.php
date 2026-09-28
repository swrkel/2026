<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_permission_locations')) {
            return;
        }

        DB::statement("ALTER TABLE module_permission_locations MODIFY module_name VARCHAR(191) NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('module_permission_locations')) {
            return;
        }

        DB::statement("ALTER TABLE module_permission_locations MODIFY module_name ENUM('mf_module','hr_module','accounting_module','restaurant_module','number_of_pumps') NOT NULL");
    }
};
