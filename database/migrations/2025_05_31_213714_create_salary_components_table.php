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
        Schema::create('salary_components', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('component_name', 128);
            $table->decimal('component_amount', 15);
            $table->boolean('override');
            $table->boolean('statutory_fund');
            $table->integer('type')->comment('1= Earning; 2= Deduction ');
            $table->integer('total_payable')->default(0);
            $table->integer('cost_company')->default(0);
            $table->integer('value_type')->comment('1= Amount ; 2= Percentage ');
            $table->tinyInteger('flag')->default(0);
            $table->text('statutory_payment');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_superadmin_default')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salary_components');
    }
};
