<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberManagementColumnsToMyhealthMembers extends Migration
{
    public function up(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->table('myhealth_members', function (Blueprint $table) use ($connection) {
            if (! Schema::connection($connection)->hasColumn('myhealth_members', 'status')) {
                $table->string('status')->default('active')->after('is_active')->index();
            }
            if (! Schema::connection($connection)->hasColumn('myhealth_members', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('address');
            }
            if (! Schema::connection($connection)->hasColumn('myhealth_members', 'height_feet')) {
                $table->unsignedTinyInteger('height_feet')->nullable()->after('blood_group');
            }
            if (! Schema::connection($connection)->hasColumn('myhealth_members', 'height_inches')) {
                $table->unsignedTinyInteger('height_inches')->nullable()->after('height_feet');
            }
            if (! Schema::connection($connection)->hasColumn('myhealth_members', 'weight_kg')) {
                $table->decimal('weight_kg', 8, 2)->nullable()->after('height_inches');
            }
            if (! Schema::connection($connection)->hasColumn('myhealth_members', 'guardian_name')) {
                $table->string('guardian_name')->nullable()->after('emergency_contact_mobile');
            }
            if (! Schema::connection($connection)->hasColumn('myhealth_members', 'guardian_mobile')) {
                $table->string('guardian_mobile')->nullable()->after('guardian_name');
            }
        });
    }

    public function down(): void
    {
        $connection = config('myhealthmembers.central_connection');
        $columns = ['status', 'photo_path', 'height_feet', 'height_inches', 'weight_kg', 'guardian_name', 'guardian_mobile'];

        Schema::connection($connection)->table('myhealth_members', function (Blueprint $table) use ($connection, $columns) {
            foreach ($columns as $column) {
                if (Schema::connection($connection)->hasColumn('myhealth_members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
