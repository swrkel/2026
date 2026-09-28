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
        Schema::create('holidays', function (Blueprint $table) {
            $table->string('event_name', 128);
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedInteger('id');
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_superadmin_default')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};
