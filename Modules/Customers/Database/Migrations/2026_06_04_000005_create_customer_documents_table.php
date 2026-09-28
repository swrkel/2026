<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDocumentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('customer_documents')) {
            return;
        }

        Schema::create('customer_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('title');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->index(['business_id', 'customer_id']);
            $table->index(['business_id', 'business_location_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_documents');
    }
}
