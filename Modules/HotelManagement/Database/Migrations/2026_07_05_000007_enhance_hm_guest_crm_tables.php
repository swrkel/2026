<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hm_guests')) {
            Schema::table('hm_guests', function (Blueprint $table) {
                if (!Schema::hasColumn('hm_guests', 'nationality')) { $table->string('nationality', 100)->nullable()->after('id_no'); }
                if (!Schema::hasColumn('hm_guests', 'date_of_birth')) { $table->date('date_of_birth')->nullable()->after('nationality'); }
                if (!Schema::hasColumn('hm_guests', 'gender')) { $table->string('gender', 30)->nullable()->after('date_of_birth'); }
                if (!Schema::hasColumn('hm_guests', 'address')) { $table->text('address')->nullable()->after('gender'); }
                if (!Schema::hasColumn('hm_guests', 'vip_level')) { $table->string('vip_level', 50)->nullable()->after('address'); }
                if (!Schema::hasColumn('hm_guests', 'marketing_consent')) { $table->boolean('marketing_consent')->default(false)->after('vip_level'); }
            });
        }
        if (Schema::hasTable('hm_guest_preferences')) {
            Schema::table('hm_guest_preferences', function (Blueprint $table) {
                if (!Schema::hasColumn('hm_guest_preferences', 'deleted_at')) { $table->softDeletes(); }
            });
        }
        if (Schema::hasTable('hm_guest_notes')) {
            Schema::table('hm_guest_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('hm_guest_notes', 'deleted_at')) { $table->softDeletes(); }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hm_guest_notes') && Schema::hasColumn('hm_guest_notes', 'deleted_at')) {
            Schema::table('hm_guest_notes', function (Blueprint $table) { $table->dropSoftDeletes(); });
        }
        if (Schema::hasTable('hm_guest_preferences') && Schema::hasColumn('hm_guest_preferences', 'deleted_at')) {
            Schema::table('hm_guest_preferences', function (Blueprint $table) { $table->dropSoftDeletes(); });
        }
    }
};
