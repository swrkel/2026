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
        Schema::create('loan_repayment_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('created_by_id')->nullable()->index('created_by_id');
            $table->unsignedBigInteger('loan_id')->nullable()->index('loan_id');
            $table->date('paid_by_date')->nullable();
            $table->date('from_date')->nullable();
            $table->date('due_date');
            $table->integer('installment')->nullable();
            $table->decimal('principal', 65, 6)->default(0);
            $table->decimal('principal_repaid_derived', 65, 6)->default(0);
            $table->decimal('principal_written_off_derived', 65, 6)->default(0);
            $table->decimal('interest', 65, 6)->default(0);
            $table->decimal('interest_repaid_derived', 65, 6)->default(0);
            $table->decimal('interest_written_off_derived', 65, 6)->default(0);
            $table->decimal('interest_waived_derived', 65, 6)->default(0);
            $table->decimal('fees', 65, 6)->default(0);
            $table->decimal('fees_repaid_derived', 65, 6)->default(0);
            $table->decimal('fees_written_off_derived', 65, 6)->default(0);
            $table->decimal('fees_waived_derived', 65, 6)->default(0);
            $table->decimal('penalties', 65, 6)->default(0);
            $table->decimal('penalties_repaid_derived', 65, 6)->default(0);
            $table->decimal('penalties_written_off_derived', 65, 6)->default(0);
            $table->decimal('penalties_waived_derived', 65, 6)->default(0);
            $table->decimal('total_due', 65, 6)->default(0);
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->timestamps();

            $table->index(['loan_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loan_repayment_schedules');
    }
};
