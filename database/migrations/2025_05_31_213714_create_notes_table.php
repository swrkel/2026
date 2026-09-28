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
        Schema::create('notes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('note_id')->index('note_id');
            $table->unsignedInteger('group_id')->index('group_id');
            $table->timestamp('date_and_time')->useCurrentOnUpdate()->useCurrent();
            $table->string('note_heading')->nullable();
            $table->text('note_details')->nullable();
            $table->text('note_footer')->nullable();
            $table->enum('show_on_top_section', ['no', 'yes'])->default('no');
            $table->text('shared_with_users')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('created_by');
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
        Schema::dropIfExists('notes');
    }
};
