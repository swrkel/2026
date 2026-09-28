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
        Schema::create('shipments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->integer('location_id')->nullable()->index('location_id');
            $table->integer('transaction_id')->nullable()->index('transaction_id');
            $table->timestamp('operation_date')->useCurrent();
            $table->string('tracking_no')->nullable();
            $table->integer('agent_id')->nullable()->index('agent_id');
            $table->integer('customer_id')->nullable()->index('customer_id');
            $table->integer('recipient_id')->nullable()->index('recipient_id');
            $table->integer('shipping_mode')->nullable();
            $table->integer('package_type_id')->nullable()->index('package_type_id');
            $table->integer('schedule_id')->nullable()->index('schedule_id');
            $table->timestamp('delivery_time')->nullable();
            $table->integer('shipping_partner')->nullable();
            $table->integer('delivery_status')->nullable();
            $table->integer('driver_id')->nullable()->index('driver_id');
            $table->string('package_img')->nullable();
            $table->decimal('total', 22, 5)->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->integer('commission_status')->default(0);
            $table->string('shipper_tracking_no', 255)->nullable();
            $table->integer('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipments');
    }
};
