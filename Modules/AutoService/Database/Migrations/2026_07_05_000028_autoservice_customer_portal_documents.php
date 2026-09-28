<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('auto_service_documents')) {
            Schema::table('auto_service_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_documents', 'uploaded_by_customer')) { $table->boolean('uploaded_by_customer')->default(false)->after('customer_download_allowed'); }
                if (!Schema::hasColumn('auto_service_documents', 'customer_name')) { $table->string('customer_name')->nullable()->after('uploaded_by_customer'); }
                if (!Schema::hasColumn('auto_service_documents', 'customer_mobile')) { $table->string('customer_mobile', 50)->nullable()->after('customer_name'); }
                if (!Schema::hasColumn('auto_service_documents', 'customer_upload_ip')) { $table->string('customer_upload_ip', 64)->nullable()->after('customer_mobile'); }
                if (!Schema::hasColumn('auto_service_documents', 'document_status')) { $table->string('document_status', 50)->default('active')->after('customer_upload_ip')->index(); }
            });
        }

        if (Schema::hasTable('auto_service_settings')) {
            $defaults = [
                'allow_customer_document_upload' => '1',
                'notify_service_advisor_on_customer_upload' => '1',
                'allow_customer_photo_upload' => '1',
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
        // Safe forward-only tenant upgrade. Do not drop customer uploaded document fields automatically.
    }
};
