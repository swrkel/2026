<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('mn_members')) {
            return;
        }

        if (!Schema::hasColumn('mn_members', 'full_name')) {
            Schema::table('mn_members', function (Blueprint $table) {
                $table->string('full_name', 255)->nullable()->after('last_name');
            });
        }

        if (!Schema::hasColumn('mn_members', 'full_name_second_language')) {
            Schema::table('mn_members', function (Blueprint $table) {
                $table->string('full_name_second_language', 255)->nullable()->after('full_name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('mn_members')) {
            return;
        }

        $columns = [];
        if (Schema::hasColumn('mn_members', 'full_name_second_language')) {
            $columns[] = 'full_name_second_language';
        }
        if (Schema::hasColumn('mn_members', 'full_name')) {
            $columns[] = 'full_name';
        }

        if ($columns) {
            Schema::table('mn_members', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
