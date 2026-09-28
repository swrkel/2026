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
        Schema::create('vat_customer_statements', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('customer_id')->nullable()->index('customer_id');
            $table->string('statement_no', 20);
            $table->date('print_date');
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedInteger('added_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('reprint_no')->default(0);
            $table->integer('is_converted')->default(0);
            $table->integer('converted_by')->nullable();
            $table->integer('linked_vat_statement')->nullable();
            $table->integer('logo')->nullable();
            $table->decimal('price_adjustment', 22, 5)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_customer_statements');
    }
};
