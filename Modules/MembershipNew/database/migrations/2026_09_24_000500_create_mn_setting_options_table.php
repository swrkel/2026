<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('mn_setting_options')) {
            return;
        }

        Schema::create('mn_setting_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->string('setting_group', 60)->index();
            $table->string('setting_key', 191);
            $table->string('setting_value', 191)->nullable();
            $table->decimal('amount', 22, 4)->nullable();
            $table->string('added_by', 191)->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['business_id', 'setting_group', 'setting_key'],
                'mn_setting_options_business_group_key_unique'
            );
            $table->index(
                ['business_id', 'setting_group', 'deleted_at', 'id'],
                'mn_setting_options_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mn_setting_options');
    }
};
