<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositProductsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('deposit_products')) {
            Schema::create('deposit_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->string('name');
                $table->string('code')->nullable()->index();
                $table->string('type')->default('fixed_deposit');
                $table->decimal('interest_rate', 20, 6)->default(0);
                $table->integer('term_months')->nullable();
                $table->decimal('minimum_amount', 20, 4)->default(0);
                $table->string('interest_frequency')->default('monthly');
                $table->string('status')->default('active')->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('deposit_products');
    }
}
