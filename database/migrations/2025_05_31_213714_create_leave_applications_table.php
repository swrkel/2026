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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('employee_id')->index('employee_id');
            $table->integer('leave_ctegory_id')->index('leave_ctegory_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason', 250);
            $table->enum('status', ['Pending', 'Accepted', 'Rejected', ''])->default('Pending');
            $table->timestamp('application_date')->useCurrent();
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
        Schema::dropIfExists('leave_applications');
    }
};
