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
        Schema::create('patient_tests', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->string('laboratory_name')->nullable();
            $table->date('date')->nullable();
            $table->text('test_file')->nullable();
            $table->text('bill_file')->nullable();
            $table->float('amount', 10, 0)->nullable()->default(0);
            $table->boolean('is_upload')->default(false);
            $table->string('test_name');
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('patient_tests');
    }
};
