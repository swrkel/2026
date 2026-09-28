<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDocumentCategoriesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('customer_document_categories')) {
            return;
        }

        Schema::create('customer_document_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->index(['business_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_document_categories');
    }
}
