<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewCrmLoyaltyTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_customer_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('contact_id')->nullable()->index();
            $table->string('customer_code', 80)->nullable()->index();
            $table->string('customer_name', 191)->index();
            $table->string('mobile', 50)->nullable()->index();
            $table->string('email', 191)->nullable()->index();
            $table->string('preferred_table', 80)->nullable();
            $table->unsignedBigInteger('preferred_waiter_id')->nullable()->index();
            $table->json('favourite_items')->nullable();
            $table->json('dietary_preferences')->nullable();
            $table->json('allergies')->nullable();
            $table->decimal('lifetime_spend', 22, 4)->default(0);
            $table->decimal('average_bill_value', 22, 4)->default(0);
            $table->integer('visit_count')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->string('crm_status', 40)->default('active')->index();
            $table->string('vip_level', 40)->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_customer_visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('customer_profile_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('visit_type', 40)->default('dine_in')->index();
            $table->string('table_no', 80)->nullable();
            $table->unsignedBigInteger('waiter_id')->nullable()->index();
            $table->integer('guest_count')->default(1);
            $table->decimal('gross_total', 22, 4)->default(0);
            $table->decimal('discount_total', 22, 4)->default(0);
            $table->decimal('net_total', 22, 4)->default(0);
            $table->timestamp('visited_at')->nullable()->index();
            $table->json('ordered_items_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_loyalty_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('customer_profile_id')->index();
            $table->unsignedBigInteger('membership_member_id')->nullable()->index();
            $table->string('loyalty_number', 100)->nullable()->index();
            $table->string('tier_name', 100)->nullable()->index();
            $table->decimal('available_points', 22, 4)->default(0);
            $table->decimal('lifetime_points', 22, 4)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('linked_at')->nullable();
            $table->json('integration_meta')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_crm_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('campaign_name', 191);
            $table->string('campaign_type', 60)->default('sms')->index();
            $table->string('segment_code', 80)->nullable()->index();
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable()->index();
            $table->string('status', 40)->default('draft')->index();
            $table->text('message_body')->nullable();
            $table->json('filters')->nullable();
            $table->json('communication_meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_crm_feedback_cases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->tinyInteger('food_rating')->nullable();
            $table->tinyInteger('service_rating')->nullable();
            $table->tinyInteger('waiter_rating')->nullable();
            $table->tinyInteger('kitchen_rating')->nullable();
            $table->tinyInteger('delivery_rating')->nullable();
            $table->string('case_status', 40)->default('open')->index();
            $table->string('priority', 40)->default('normal')->index();
            $table->text('customer_comments')->nullable();
            $table->text('resolution_note')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_crm_feedback_cases');
        Schema::dropIfExists('restaurant_new_crm_campaigns');
        Schema::dropIfExists('restaurant_new_loyalty_links');
        Schema::dropIfExists('restaurant_new_customer_visits');
        Schema::dropIfExists('restaurant_new_customer_profiles');
    }
}
