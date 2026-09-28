<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('customer_attachments')) {
            Schema::create('customer_attachments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('customer_id')->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->string('title')->nullable();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('customer_attachments');
    }
};
