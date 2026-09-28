<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('auto_service_bay_allocations')) {
            Schema::create('auto_service_bay_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('bay_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->unsignedBigInteger('mechanic_id')->nullable()->index();
                $table->timestamp('allocated_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->string('status')->default('allocated')->index();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('auto_service_communications')) {
            Schema::create('auto_service_communications', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->string('channel')->default('internal')->index();
                $table->string('direction')->default('outbound')->index();
                $table->string('recipient')->nullable();
                $table->string('subject')->nullable();
                $table->longText('message')->nullable();
                $table->string('status')->default('draft')->index();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('auto_service_documents')) {
            Schema::table('auto_service_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_documents', 'document_group')) { $table->string('document_group')->nullable()->after('document_type'); }
                if (!Schema::hasColumn('auto_service_documents', 'customer_download_allowed')) { $table->boolean('customer_download_allowed')->default(false)->after('visible_to_customer'); }
            });
        }

        if (Schema::hasTable('auto_service_settings')) {
            $defaults = [
                'allow_customer_download_receipts' => '1',
                'allow_customer_view_estimates' => '1',
                'allow_customer_view_job_cards' => '0',
                'allow_customer_view_technician_notes' => '0',
                'enable_workshop_whiteboard' => '1',
                'enable_mechanic_dashboard' => '1',
                'enable_bay_management' => '1',
                'enable_communication_centre' => '1',
            ];
            foreach ($defaults as $key => $value) {
                if (!DB::table('auto_service_settings')->where('key', $key)->exists()) {
                    DB::table('auto_service_settings')->insert(['business_id'=>null,'key'=>$key,'value'=>$value,'created_at'=>now(),'updated_at'=>now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_service_communications');
        Schema::dropIfExists('auto_service_bay_allocations');
    }
};
