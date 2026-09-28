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
        Schema::create('gold_grades', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->dateTime('date_and_time');
            $table->string('grade_name', 100);
            $table->decimal('last_gold_purity', 15)->default(0);
            $table->float('grade_price', 10, 0);
            $table->float('gold_purity', 10, 0);
            $table->unsignedInteger('created_by');
            $table->boolean('status')->default(true);
            $table->boolean('trash')->default(false);
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
        Schema::dropIfExists('gold_grades');
    }
};
