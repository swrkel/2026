<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('doc_management_referred_tos')) {
            Schema::table('doc_management_referred_tos', function (Blueprint $table) {
                if (! Schema::hasColumn('doc_management_referred_tos', 'designation')) {
                    $table->string('designation')->nullable()->after('department');
                }

                if (! Schema::hasColumn('doc_management_referred_tos', 'officer_name')) {
                    $table->string('officer_name')->nullable()->after('designation');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('doc_management_referred_tos')) {
            Schema::table('doc_management_referred_tos', function (Blueprint $table) {
                if (Schema::hasColumn('doc_management_referred_tos', 'officer_name')) {
                    $table->dropColumn('officer_name');
                }

                if (Schema::hasColumn('doc_management_referred_tos', 'designation')) {
                    $table->dropColumn('designation');
                }
            });
        }
    }
};
