<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales Agent Module - Migration for sales_agent_commissions table
 * Created per requirement 7743-5: Create Sales Agent Commission Table
 * 
 * This stores commission entries added via the "Add Commission" popup
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_agent_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_agent_id');
            $table->integer('business_id')->unsigned();
            $table->dateTime('commission_date'); // Date & Time (auto-filled, non-editable)
            $table->date('period_start')->nullable(); // Commission for the Period - start date
            $table->date('period_end')->nullable(); // Commission for the Period - end date
            $table->string('commission_for')->nullable(); // Commission for (text field)
            $table->string('ref_bill_no')->nullable(); // Ref Bill No (user entry)
            $table->decimal('amount', 22, 4)->default(0); // Commission amount
            $table->integer('created_by')->unsigned();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('sales_agent_id')
                  ->references('id')
                  ->on('sales_agents')
                  ->onDelete('cascade');

            // Indexes for performance
            $table->index('business_id');
            $table->index('commission_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_agent_commissions');
    }
};
