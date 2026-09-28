<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banking_ui_role_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('role_key')->index();
            $table->string('permission_key')->index();
            $table->boolean('is_allowed')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'role_key', 'permission_key'], 'bkg_ui_role_perm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_ui_role_overrides');
    }
};
