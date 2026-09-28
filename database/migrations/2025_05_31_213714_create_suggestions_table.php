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
        Schema::create('suggestions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->nullable()->index('member_id');
            $table->date('date');
            $table->integer('balamandalaya_id')->index('balamandalaya_id');
            $table->integer('service_area_id')->index('service_area_id');
            $table->string('heading');
            $table->text('details');
            $table->enum('is_common_problem', ['yes', 'no'])->nullable();
            $table->string('area_name');
            $table->enum('state_of_urgency', ['normal', 'medium', 'high'])->default('normal');
            $table->enum('solution_given', ['solved', 'Pending', 'Rejected'])->nullable();
            $table->text('upload_document');
            $table->text('remarks');
            $table->enum('status', ['closed', 'waiting_for_reponse', 'assigned_to'])->nullable();
            $table->integer('assigned_to_member_id')->nullable()->index('assigned_to_member_id');
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
        Schema::dropIfExists('suggestions');
    }
};
