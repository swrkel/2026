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
        Schema::create('daily_report_review_status', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->integer('reviewed_by');
            $table->date('reiew_date')->nullable();
            $table->integer('status')->default(0)->comment('0= pending; 2=partial; 1=
complete');
            $table->text('reviewed_sections');
            $table->timestamp('date_reviewed')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_report_review_status');
    }
};
