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
        Schema::create('doc_management_signatures', function (Blueprint $table) {
            $table->integer('id', true);
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->string('location', 30);
            $table->string('user', 50);
            $table->string('designations', 50);
            $table->string('upload_signature', 200);
            $table->string('signature_levels', 20);
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doc_management_signatures');
    }
};
