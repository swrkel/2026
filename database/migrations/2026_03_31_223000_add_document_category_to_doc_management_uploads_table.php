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
        if (!Schema::hasColumn('doc_management_uploads', 'document_category')) {
            Schema::table('doc_management_uploads', function (Blueprint $table) {
                $table->string('document_category', 191)->nullable()->after('originator');
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
        if (Schema::hasColumn('doc_management_uploads', 'document_category')) {
            Schema::table('doc_management_uploads', function (Blueprint $table) {
                $table->dropColumn('document_category');
            });
        }
    }
};
