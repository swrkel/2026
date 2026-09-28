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
        Schema::create('doc_management_uploads', function (Blueprint $table) {
            $table->integer('doc_no', true);
            $table->string('originator', 50);
            $table->string('document_type', 50);
            $table->string('purpose', 50);
            $table->string('note', 200);
            $table->string('referred_to', 50);
            $table->string('image', 200);
            $table->string('status', 50);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
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
        Schema::dropIfExists('doc_management_uploads');
    }
};
