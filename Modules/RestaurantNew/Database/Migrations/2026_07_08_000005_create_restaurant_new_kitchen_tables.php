<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewKitchenTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_kitchen_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('ticket_no', 50)->index();
            $table->unsignedBigInteger('kitchen_section_id')->nullable()->index();
            $table->string('ticket_type', 30)->default('kot');
            $table->string('status', 30)->default('new')->index();
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id', 'business_location_id', 'ticket_no'], 'restaurant_new_kot_unique');
        });

        Schema::create('restaurant_new_kitchen_ticket_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('kitchen_ticket_id')->index();
            $table->unsignedBigInteger('order_line_id')->nullable()->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->string('item_name');
            $table->decimal('quantity', 22, 4)->default(0);
            $table->text('modifiers_text')->nullable();
            $table->text('special_instruction')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_kitchen_ticket_lines');
        Schema::dropIfExists('restaurant_new_kitchen_tickets');
    }
}
