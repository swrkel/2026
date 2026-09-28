<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('auto_service_feedback')) {
            Schema::create('auto_service_feedback', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->unsignedTinyInteger('service_rating')->nullable();
                $table->unsignedTinyInteger('mechanic_rating')->nullable();
                $table->unsignedTinyInteger('workshop_rating')->nullable();
                $table->text('comments')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_mobile')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('auto_service_settings')) {
            $defaults = [
                'allow_customer_invoice_pdf' => '1',
                'allow_customer_job_documents' => '1',
                'allow_customer_feedback' => '1',
                'allow_customer_fleet_view' => '1',
                'allow_customer_current_invoice_view' => DB::table('auto_service_settings')->where('key','allow_customer_current_invoice_view')->value('value') ?: '0',
            ];
            foreach ($defaults as $key => $value) {
                if (!DB::table('auto_service_settings')->where('key', $key)->exists()) {
                    DB::table('auto_service_settings')->insert([
                        'business_id' => null,
                        'key' => $key,
                        'value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_service_feedback');
    }
};
