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
        Schema::create('vat_expenses', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->integer('location_id');
            $table->integer('expense_category_id');
            $table->string('ref_no', 11);
            $table->timestamp('transaction_date')->useCurrentOnUpdate()->useCurrent();
            $table->integer('contact_id');
            $table->integer('tax_id');
            $table->integer('is_vat');
            $table->decimal('final_total', 15, 5);
            $table->integer('created_by');
            $table->string('payment_status', 30);
            $table->decimal('total_before_tax', 15, 5);
            $table->decimal('tax_amount', 15, 5);
            $table->text('additional_notes');
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_expenses');
    }
};
