<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('petro_pd_payment_integrity_repair_runs')) {
            Schema::create('petro_pd_payment_integrity_repair_runs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('tenant_reference', 191)->nullable()->index();
                $table->boolean('apply_changes')->default(false)->index();
                $table->string('status', 30)->default('running')->index();
                $table->unsignedInteger('scanned_rows')->default(0);
                $table->unsignedInteger('issues_found')->default(0);
                $table->unsignedInteger('safe_repairs_found')->default(0);
                $table->unsignedInteger('repairs_applied')->default(0);
                $table->longText('options_json')->nullable();
                $table->longText('summary_json')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('petro_pd_payment_integrity_repair_actions')) {
            Schema::create('petro_pd_payment_integrity_repair_actions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('repair_run_id')->index();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('table_name', 100)->index();
                $table->unsignedBigInteger('row_id')->nullable()->index();
                $table->unsignedBigInteger('pump_payment_id')->nullable()->index();
                $table->unsignedBigInteger('shift_id')->nullable()->index();
                $table->string('action_type', 100)->index();
                $table->string('severity', 20)->default('warning')->index();
                $table->boolean('is_safe_repair')->default(false)->index();
                $table->boolean('was_applied')->default(false)->index();
                $table->text('message');
                $table->longText('before_json')->nullable();
                $table->longText('after_json')->nullable();
                $table->timestamps();

                $table->index(['repair_run_id', 'was_applied'], 'petro_pd_repair_run_applied_idx');
            });
        }
    }

    public function down(): void
    {
        // Historical financial repair evidence must be retained. No destructive down.
    }
};
