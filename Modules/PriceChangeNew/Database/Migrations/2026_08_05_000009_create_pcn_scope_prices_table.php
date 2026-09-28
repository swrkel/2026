<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pcn_price_change_scope_prices')) {
            return;
        }

        Schema::create('pcn_price_change_scope_prices', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('price_change_id')->index();
            $table->unsignedBigInteger('price_change_line_id')->index();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->index();
            $table->unsignedInteger('price_group_id')->nullable()->index();
            $table->decimal('current_group_price_inc_tax', 22, 8)->nullable();
            $table->decimal('new_group_price_inc_tax', 22, 8)->nullable();
            $table->decimal('applied_from_price_inc_tax', 22, 8)->nullable();
            $table->decimal('applied_to_price_inc_tax', 22, 8)->nullable();
            $table->string('apply_status', 30)->nullable()->index();
            $table->text('apply_message')->nullable();
            $table->dateTime('applied_at')->nullable();
            $table->timestamps();
            $table->unique(['price_change_line_id', 'location_id'], 'pcn_scope_prices_line_location_unique');
            $table->index(['business_id', 'price_group_id'], 'pcn_scope_prices_business_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pcn_price_change_scope_prices');
    }
};
