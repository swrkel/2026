<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hm_report_exports')) {
            Schema::create('hm_report_exports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('report_key', 100)->index();
                $table->string('export_type', 20)->default('csv');
                $table->date('date_from')->nullable();
                $table->date('date_to')->nullable();
                $table->json('filters_json')->nullable();
                $table->unsignedBigInteger('generated_by')->nullable();
                $table->timestamps();
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_report_exports');
    }
};
