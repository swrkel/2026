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
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('created_by_id')->nullable()->index('created_by_id');
            $table->unsignedInteger('contact_id')->nullable()->index('contact_id');
            $table->unsignedBigInteger('loan_id')->nullable()->index('loan_id');
            $table->unsignedBigInteger('loan_product_id')->index('loan_product_id');
            $table->unsignedBigInteger('payment_type_id')->nullable()->index('payment_type_id');
            $table->integer('loan_term');
            $table->integer('repayment_frequency');
            $table->string('repayment_frequency_type', 11);
            $table->boolean('is_top_up')->default(false);
            $table->decimal('amount', 65, 4)->default(0);
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['contact_id']);
            $table->index(['location_id'], 'location_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_applications');
    }
};
