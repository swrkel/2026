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
        Schema::create('patient_medicines', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('pharmacy_name')->nullable();
            $table->integer('qty')->nullable();
            $table->float('amount', 10, 0)->nullable()->default(0);
            $table->date('date')->nullable();
            $table->string('medicine_name');
            $table->string('description')->nullable();
            $table->text('pharmacy_file')->nullable();
            $table->boolean('is_upload')->nullable()->default(false);
            $table->string('notes')->nullable();
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
        Schema::dropIfExists('patient_medicines');
    }
};
