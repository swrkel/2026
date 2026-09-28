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
        Schema::create('essentials_leaves', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('essentials_leave_type_id')->nullable()->index('essentials_leave_type_id');
            $table->integer('business_id')->index('business_id');
            $table->integer('user_id')->index();
            $table->string('which_half')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('ref_no')->nullable();
            $table->enum('status', ['pending', 'approved', 'cancelled'])->nullable();
            $table->text('reason')->nullable();
            $table->text('status_note')->nullable();
            $table->timestamps();

            $table->index(['business_id']);
            $table->index(['essentials_leave_type_id']);
            $table->index(['user_id'], 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_leaves');
    }
};
