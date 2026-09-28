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
        Schema::create('loan_collateral', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('created_by_id')->nullable()->index('created_by_id');
            $table->unsignedBigInteger('loan_id')->index();
            $table->unsignedBigInteger('loan_collateral_type_id')->index('loan_collateral_type_id');
            $table->text('description')->nullable();
            $table->decimal('value', 65, 6)->nullable();
            $table->text('link')->nullable();
            $table->enum('status', ['deposited_into_branch', 'collateral_with_borrower', 'returned_to_borrower', 'repossession_initiated', 'repossessed', 'under_auction', 'sold', 'lost'])->default('collateral_with_borrower');
            $table->string('product_name');
            $table->string('registration_date');
            $table->string('serial_number')->nullable();
            $table->string('model_name')->nullable();
            $table->string('model_number')->nullable();
            $table->string('color')->nullable();
            $table->string('manufacture_date')->nullable();
            $table->string('condition')->nullable();
            $table->string('address')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('mileage')->nullable();
            $table->string('engine_number')->nullable();
            $table->timestamps();

            $table->index(['loan_id'], 'loan_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_collateral');
    }
};
