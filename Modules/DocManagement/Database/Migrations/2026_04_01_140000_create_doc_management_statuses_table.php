<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('doc_management_statuses')) {
            Schema::create('doc_management_statuses', function (Blueprint $table) {
                $table->id();
                $table->dateTime('date_time')->nullable();
                $table->string('status')->nullable();
                $table->string('user')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('doc_management_statuses');
    }
};
