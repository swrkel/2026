<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerActivityLogsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('customer_activity_logs')) {
            return;
        }

        Schema::create('customer_activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->index();
            $table->string('action', 100)->index();
            $table->text('description')->nullable();
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_activity_logs');
    }
}
