<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reo_share_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('token', 64)->unique();
            $table->string('channel', 20)->default('link');
            $table->string('report_key', 100);
            $table->json('payload')->nullable();
            $table->string('relative_path', 255);
            $table->string('download_name', 255);
            $table->string('mime_type', 120)->default('application/octet-stream');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'report_key'], 'reo_share_report_idx');
            $table->index('expires_at', 'reo_share_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reo_share_links');
    }
};
