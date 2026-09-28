<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('doc_management_uploads', function (Blueprint $table) {
            if (!Schema::hasColumn('doc_management_uploads', 'from_location')) {
                $table->string('from_location', 191)->nullable()->after('location');
            }

            if (!Schema::hasColumn('doc_management_uploads', 'to_location')) {
                $table->string('to_location', 191)->nullable()->after('from_location');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('doc_management_uploads', function (Blueprint $table) {
            if (Schema::hasColumn('doc_management_uploads', 'to_location')) {
                $table->dropColumn('to_location');
            }

            if (Schema::hasColumn('doc_management_uploads', 'from_location')) {
                $table->dropColumn('from_location');
            }
        });
    }
};
