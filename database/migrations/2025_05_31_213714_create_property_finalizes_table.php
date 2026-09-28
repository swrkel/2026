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
        Schema::create('property_finalizes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('transaction_id')->index('transaction_id');
            $table->unsignedInteger('property_sell_line_id')->index('property_sell_line_id');
            $table->unsignedInteger('property_id')->index('property_id');
            $table->unsignedInteger('block_id')->index('block_id');
            $table->date('date');
            $table->decimal('balance_amount', 15, 6);
            $table->unsignedInteger('finance_option_id')->index('finance_option_id');
            $table->decimal('other_payment', 15, 6);
            $table->decimal('down_payment', 15, 6);
            $table->enum('easy_payment', ['yes', 'no'])->default('no');
            $table->unsignedInteger('no_of_installment');
            $table->decimal('installment_amount', 15, 6);
            $table->date('first_installment_date');
            $table->unsignedInteger('installment_cycle_id')->index('installment_cycle_id');
            $table->decimal('loan_capital', 15, 6);
            $table->decimal('total_interest', 15, 6);
            $table->string('attachment')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by');
            $table->boolean('is_closed')->default(false);
            $table->unsignedInteger('closed_by')->nullable();
            $table->string('reason_id')->nullable()->index('reason_id');
            $table->boolean('all_payments_completed')->default(false);
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('property_finalizes');
    }
};
