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
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('date');
            $table->integer('department_id')->index('department_id');
            $table->integer('employee_id')->index('employee_id');
            $table->string('employee_name', 100);
            $table->integer('manager_id')->nullable()->index('manager_id');
            $table->string('manager_name', 100);
            $table->text('memo');
            $table->decimal('amount', 13);
            $table->enum('approved_manager', ['Pending', 'Approved', 'Reject', ''])->default('Pending');
            $table->text('manager_comment');
            $table->enum('approved_admin', ['Pending', 'Reject', 'Approved', ''])->default('Pending');
            $table->text('admin_comment')->nullable();
            $table->integer('created_by');
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
        Schema::dropIfExists('reimbursements');
    }
};
