<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('pcn_price_change_applications')) {
            Schema::create('pcn_price_change_applications', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('price_change_id')->index();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('triggered_by')->nullable()->index();
                $table->string('trigger_type', 30)->default('manual');
                $table->string('status', 30)->default('running')->index();
                $table->unsignedInteger('success_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->text('message')->nullable();
                $table->longText('payload')->nullable();
                $table->dateTime('started_at')->nullable()->index();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'status', 'started_at'], 'pcn_app_business_status_started_idx');
            });
        }

        if (! Schema::hasTable('pcn_price_change_application_lines')) {
            Schema::create('pcn_price_change_application_lines', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('application_id')->index();
                $table->unsignedBigInteger('price_change_id')->index();
                $table->unsignedBigInteger('price_change_line_id')->index();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('product_id')->index();
                $table->unsignedInteger('variation_id')->index();
                $table->string('scope_type', 40)->default('business_base');
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedInteger('price_group_id')->nullable()->index();
                $table->string('status', 30)->default('pending')->index();
                $table->text('message')->nullable();
                $table->decimal('old_purchase_price_ex_tax', 22, 8)->nullable();
                $table->decimal('old_purchase_price_inc_tax', 22, 8)->nullable();
                $table->decimal('old_sell_price_ex_tax', 22, 8)->nullable();
                $table->decimal('old_sell_price_inc_tax', 22, 8)->nullable();
                $table->decimal('new_purchase_price_ex_tax', 22, 8)->nullable();
                $table->decimal('new_purchase_price_inc_tax', 22, 8)->nullable();
                $table->decimal('new_sell_price_ex_tax', 22, 8)->nullable();
                $table->decimal('new_sell_price_inc_tax', 22, 8)->nullable();
                $table->longText('payload')->nullable();
                $table->dateTime('applied_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'price_change_id', 'status'], 'pcn_app_lines_business_change_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pcn_price_change_application_lines');
        Schema::dropIfExists('pcn_price_change_applications');
    }
};
