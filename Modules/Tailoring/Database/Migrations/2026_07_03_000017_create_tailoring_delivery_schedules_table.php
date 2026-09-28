<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTailoringDeliverySchedulesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tailoring_delivery_schedules')) {
            Schema::create('tailoring_delivery_schedules', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('job_card_id')->nullable()->index();
                $table->date('delivery_date')->index();
                $table->time('delivery_time')->nullable();
                $table->string('delivery_status', 30)->default('scheduled')->index();
                $table->decimal('balance_to_collect', 22, 4)->default(0);
                $table->text('delivery_notes')->nullable();
                $table->unsignedInteger('delivered_by')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down() { Schema::dropIfExists('tailoring_delivery_schedules'); }
}
