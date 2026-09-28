<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bs_staff')) {
            Schema::create('bs_staff', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('staff_code')->nullable()->index();
                $table->string('name');
                $table->string('mobile')->nullable();
                $table->string('email')->nullable();
                $table->string('designation')->nullable();
                $table->decimal('default_commission_percent', 8, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_staff');
    }
};
