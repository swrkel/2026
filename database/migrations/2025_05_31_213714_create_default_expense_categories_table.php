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
        Schema::create('default_expense_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('code')->nullable();
            $table->unsignedInteger('expense_account');
            $table->boolean('is_sub_category')->default(false);
            $table->unsignedInteger('parent_id')->nullable()->index('parent_id');
            $table->softDeletes();
            $table->timestamps();
            $table->integer('payee_id')->nullable()->index('payee_id');

            $table->index(['business_id'], 'expense_categories_business_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('default_expense_categories');
    }
};
