<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('doc_management_referred_to_status_histories')) {
            Schema::create('doc_management_referred_to_status_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('doc_management_referred_to_id');
                $table->dateTime('date_time')->nullable();
                $table->string('changed_by')->nullable();
                $table->string('status_from')->nullable();
                $table->string('status_to')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('doc_management_referred_to_status_histories');
    }
};
