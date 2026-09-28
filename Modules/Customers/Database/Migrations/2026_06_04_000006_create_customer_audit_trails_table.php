<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerAuditTrailsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('customer_audit_trails')) {
            return;
        }

        Schema::create('customer_audit_trails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->index();
            $table->string('action', 100)->index();
            $table->text('description')->nullable();
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->index(['business_id', 'customer_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_audit_trails');
    }
}
