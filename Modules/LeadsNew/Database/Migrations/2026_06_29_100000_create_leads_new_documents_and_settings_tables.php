<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('leads_new_documents')) {
            Schema::create('leads_new_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('lead_id')->index();
                $table->string('original_name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('leads_new_settings')) {
            Schema::create('leads_new_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('key')->index();
                $table->json('value')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads_new_documents');
        Schema::dropIfExists('leads_new_settings');
    }
};
