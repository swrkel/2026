<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bs_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('customer_code')->nullable()->index();
            $table->string('full_name');
            $table->string('mobile')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('nic_no')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('customer_type')->default('regular')->index();
            $table->string('preferred_staff_id')->nullable();
            $table->string('preferred_service_id')->nullable();
            $table->string('skin_type')->nullable();
            $table->string('hair_type')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_notes')->nullable();
            $table->boolean('sms_enabled')->default(true);
            $table->boolean('email_enabled')->default(false);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->boolean('loyalty_enabled')->default(true);
            $table->decimal('opening_balance', 22, 4)->default(0);
            $table->decimal('credit_limit', 22, 4)->default(0);
            $table->integer('credit_days')->default(0);
            $table->string('status')->default('active')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bs_customer_visit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_profile_id')->index();
            $table->date('visit_date')->index();
            $table->string('visit_type')->nullable();
            $table->text('note')->nullable();
            $table->text('recommended_services')->nullable();
            $table->text('recommended_products')->nullable();
            $table->date('next_followup_date')->nullable()->index();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bs_customer_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_profile_id')->index();
            $table->string('consent_type')->index();
            $table->boolean('is_accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->string('accepted_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_customer_consents');
        Schema::dropIfExists('bs_customer_visit_notes');
        Schema::dropIfExists('bs_customer_profiles');
    }
};
