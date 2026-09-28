<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S716 - Required SW shift schema repair.
 *
 * sw_shifts is a REQUIRED SW module table. This migration does not bypass it
 * and does not add controller fallbacks. It creates the required core shift
 * tables only when they are genuinely missing from a tenant database.
 *
 * This repair migration is intentionally non-destructive. Its down() method is
 * a no-op so a rollback cannot remove live SW shift data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sw_shifts')) {
            Schema::create('sw_shifts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('location_id')->index();
                $table->string('sw_shift_no', 60);
                $table->date('shift_date')->index();
                $table->string('shift_name', 100)->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->unsignedTinyInteger('status')->default(0)->index();
                $table->timestamp('closed_at')->nullable();
                $table->unsignedInteger('closed_by')->nullable();
                $table->timestamp('reopened_at')->nullable();
                $table->unsignedInteger('reopened_by')->nullable();
                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'location_id', 'sw_shift_no'], 'sw_shifts_unique_no');
                $table->index(['location_id', 'shift_date', 'status'], 'sw_shifts_loc_date_status');
            });
        }

        if (! Schema::hasTable('sw_shift_operators')) {
            Schema::create('sw_shift_operators', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sw_shift_id')->index();
                $table->unsignedInteger('pump_operator_id')->index();
                $table->timestamps();

                $table->unique(['sw_shift_id', 'pump_operator_id'], 'sw_shift_operator_unique');
                $table->foreign('sw_shift_id')
                    ->references('id')
                    ->on('sw_shifts')
                    ->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('sw_number_sequences')) {
            Schema::create('sw_number_sequences', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('location_id')->index();
                $table->string('document_type', 40)->default('shift');
                $table->string('prefix', 20)->default('SW');
                $table->unsignedBigInteger('next_number')->default(1);
                $table->timestamps();

                $table->unique(['business_id', 'location_id', 'document_type'], 'sw_seq_unique');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: these are required live SW tables.
    }
};
