<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('doc_management_uploads')) {
            Schema::table('doc_management_uploads', function (Blueprint $table) {
                if (! Schema::hasColumn('doc_management_uploads', 'attachments')) {
                    $table->longText('attachments')->nullable()->after('image');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('doc_management_uploads')) {
            Schema::table('doc_management_uploads', function (Blueprint $table) {
                if (Schema::hasColumn('doc_management_uploads', 'attachments')) {
                    $table->dropColumn('attachments');
                }
            });
        }
    }
};
