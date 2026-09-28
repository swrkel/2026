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
        if (!Schema::hasColumn('doc_management_uploads', 'location')) {
            Schema::table('doc_management_uploads', function (Blueprint $table) {
                $table->string('location', 191)->nullable()->after('document_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('doc_management_uploads', 'location')) {
            Schema::table('doc_management_uploads', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }
    }
};
