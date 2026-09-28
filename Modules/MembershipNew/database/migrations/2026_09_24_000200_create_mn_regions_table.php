<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mn_regions')) {
            Schema::create('mn_regions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->date('date')->index();
                $table->string('region_no', 50);
                $table->string('region', 150);
                $table->string('added_by', 191)->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'region_no'], 'mn_regions_business_region_no_unique');
                $table->index(['business_id', 'date'], 'mn_regions_business_date_index');
                $table->index(['business_id', 'region'], 'mn_regions_business_region_index');
                $table->index(['business_id', 'deleted_at', 'date', 'id'], 'mn_regions_business_deleted_date_id_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mn_regions');
    }
};
