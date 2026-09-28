<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHeightFieldsToMyhealthMembersTable extends Migration
{
    public function up(): void
    {
        $connection = config('myhealthmembers.central_connection');

        if (!Schema::connection($connection)->hasTable('myhealth_members')) {
            return;
        }

        Schema::connection($connection)->table('myhealth_members', function (Blueprint $table) use ($connection) {
            if (!Schema::connection($connection)->hasColumn('myhealth_members', 'height_feet')) {
                $table->unsignedTinyInteger('height_feet')->nullable()->after('address');
            }

            if (!Schema::connection($connection)->hasColumn('myhealth_members', 'height_inches')) {
                $table->unsignedTinyInteger('height_inches')->nullable()->after('height_feet');
            }

            if (!Schema::connection($connection)->hasColumn('myhealth_members', 'height')) {
                $table->string('height', 30)->nullable()->after('height_inches');
            }

            if (!Schema::connection($connection)->hasColumn('myhealth_members', 'weight')) {
                $table->decimal('weight', 8, 2)->nullable()->after('height');
            }

            if (!Schema::connection($connection)->hasColumn('myhealth_members', 'guardian_name')) {
                $table->string('guardian_name')->nullable()->after('weight');
            }

            if (!Schema::connection($connection)->hasColumn('myhealth_members', 'known_allergies')) {
                $table->text('known_allergies')->nullable()->after('guardian_name');
            }

            if (!Schema::connection($connection)->hasColumn('myhealth_members', 'notes')) {
                $table->text('notes')->nullable()->after('known_allergies');
            }
        });
    }

    public function down(): void
    {
        $connection = config('myhealthmembers.central_connection');

        if (!Schema::connection($connection)->hasTable('myhealth_members')) {
            return;
        }

        Schema::connection($connection)->table('myhealth_members', function (Blueprint $table) use ($connection) {
            foreach (['notes', 'known_allergies', 'guardian_name', 'weight', 'height', 'height_inches', 'height_feet'] as $column) {
                if (Schema::connection($connection)->hasColumn('myhealth_members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
