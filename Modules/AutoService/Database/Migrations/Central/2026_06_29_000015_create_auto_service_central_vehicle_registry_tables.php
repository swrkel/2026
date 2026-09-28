<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected function schema()
    {
        return Schema::connection(config('autoservice.central_connection', config('database.default')));
    }

    public function up()
    {
        $schema = $this->schema();

        if (!$schema->hasTable('auto_service_central_vehicles')) {
            $schema->create('auto_service_central_vehicles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('registration_no', 80)->nullable()->index();
                $table->string('vin', 120)->nullable()->index();
                $table->string('chassis_no', 120)->nullable()->index();
                $table->string('engine_no', 120)->nullable()->index();
                $table->string('make', 120)->nullable();
                $table->string('model', 120)->nullable();
                $table->string('variant', 120)->nullable();
                $table->string('year', 20)->nullable();
                $table->string('colour', 80)->nullable();
                $table->string('fuel_type', 80)->nullable();
                $table->string('transmission', 80)->nullable();
                $table->decimal('last_mileage', 15, 3)->default(0);
                $table->unsignedBigInteger('last_registered_owner_id')->nullable()->index();
                $table->unsignedBigInteger('created_by_business_id')->nullable()->index();
                $table->string('created_by_tenant', 191)->nullable()->index();
                $table->string('status', 30)->default('active')->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['registration_no', 'chassis_no'], 'as_central_vehicle_reg_chassis_unique');
            });
        }

        if (!$schema->hasTable('auto_service_central_vehicle_owners')) {
            $schema->create('auto_service_central_vehicle_owners', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('central_vehicle_id')->index();
                $table->string('owner_name', 191)->nullable();
                $table->string('mobile', 80)->nullable()->index();
                $table->string('email', 191)->nullable();
                $table->string('nic_no', 80)->nullable()->index();
                $table->string('address', 500)->nullable();
                $table->date('owned_from')->nullable();
                $table->date('owned_to')->nullable();
                $table->boolean('is_current_owner')->default(true)->index();
                $table->boolean('is_verified')->default(false)->index();
                $table->timestamp('verified_at')->nullable();
                $table->unsignedBigInteger('created_by_business_id')->nullable()->index();
                $table->string('created_by_tenant', 191)->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!$schema->hasTable('auto_service_central_vehicle_verifications')) {
            $schema->create('auto_service_central_vehicle_verifications', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('central_vehicle_id')->nullable()->index();
                $table->unsignedBigInteger('central_owner_id')->nullable()->index();
                $table->string('mobile', 80)->index();
                $table->string('otp_hash', 191); // never store plain OTP
                $table->string('purpose', 40)->default('vehicle_login')->index();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('verified_at')->nullable();
                $table->string('ip_address', 80)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('auto_service_central_vehicle_service_records')) {
            $schema->create('auto_service_central_vehicle_service_records', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('central_vehicle_id')->index();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('tenant_key', 191)->nullable()->index();
                $table->string('business_name', 191)->nullable();
                $table->unsignedBigInteger('local_vehicle_id')->nullable()->index();
                $table->unsignedBigInteger('local_job_id')->nullable()->index();
                $table->unsignedBigInteger('local_invoice_id')->nullable()->index();
                $table->string('job_no', 80)->nullable()->index();
                $table->string('invoice_no', 80)->nullable()->index();
                $table->date('service_date')->nullable()->index();
                $table->decimal('mileage', 15, 3)->default(0);
                $table->string('service_type', 191)->nullable();
                $table->longText('complaints')->nullable();
                $table->longText('diagnosis')->nullable();
                $table->longText('work_done')->nullable();
                $table->json('parts_used')->nullable();
                $table->json('oils_used')->nullable();
                $table->decimal('labour_total', 22, 4)->default(0);
                $table->decimal('parts_total', 22, 4)->default(0);
                $table->decimal('oil_total', 22, 4)->default(0);
                $table->decimal('discount_total', 22, 4)->default(0);
                $table->decimal('tax_total', 22, 4)->default(0);
                $table->decimal('grand_total', 22, 4)->default(0);
                $table->string('advisor_name', 191)->nullable();
                $table->string('mechanic_names', 500)->nullable();
                $table->json('documents')->nullable();
                $table->json('photos')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('auto_service_central_vehicle_ownership_transfers')) {
            $schema->create('auto_service_central_vehicle_ownership_transfers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('central_vehicle_id')->index();
                $table->unsignedBigInteger('previous_owner_id')->nullable()->index();
                $table->string('previous_owner_mobile', 80)->nullable()->index();
                $table->unsignedBigInteger('new_owner_id')->nullable()->index();
                $table->string('new_owner_name', 191)->nullable();
                $table->string('new_owner_mobile', 80)->nullable()->index();
                $table->string('new_owner_email', 191)->nullable();
                $table->string('new_owner_nic_no', 80)->nullable()->index();
                $table->string('new_owner_address', 500)->nullable();
                $table->unsignedBigInteger('requested_by_business_id')->nullable()->index();
                $table->string('requested_by_tenant', 191)->nullable()->index();
                $table->string('status', 60)->default('pending_previous_owner_approval')->index();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('previous_owner_approved_at')->nullable();
                $table->timestamp('new_owner_verified_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->longText('reject_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        $schema = $this->schema();
        $schema->dropIfExists('auto_service_central_vehicle_ownership_transfers');
        $schema->dropIfExists('auto_service_central_vehicle_service_records');
        $schema->dropIfExists('auto_service_central_vehicle_verifications');
        $schema->dropIfExists('auto_service_central_vehicle_owners');
        $schema->dropIfExists('auto_service_central_vehicles');
    }
};
