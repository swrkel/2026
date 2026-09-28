<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cheq_print_calibrations')) {
            Schema::create('cheq_print_calibrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('bank_account_id')->nullable()->index();
                $table->unsignedBigInteger('cheq_template_id')->nullable()->index();
                $table->string('profile_name');
                $table->string('printer_name')->nullable();
                $table->decimal('x_offset_mm', 10, 2)->default(0);
                $table->decimal('y_offset_mm', 10, 2)->default(0);
                $table->decimal('date_x_offset_mm', 10, 2)->default(0);
                $table->decimal('date_y_offset_mm', 10, 2)->default(0);
                $table->decimal('payee_x_offset_mm', 10, 2)->default(0);
                $table->decimal('payee_y_offset_mm', 10, 2)->default(0);
                $table->decimal('amount_x_offset_mm', 10, 2)->default(0);
                $table->decimal('amount_y_offset_mm', 10, 2)->default(0);
                $table->decimal('words_x_offset_mm', 10, 2)->default(0);
                $table->decimal('words_y_offset_mm', 10, 2)->default(0);
                $table->decimal('signature_x_offset_mm', 10, 2)->default(0);
                $table->decimal('signature_y_offset_mm', 10, 2)->default(0);
                $table->decimal('scale_percent', 10, 2)->default(100);
                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cheq_print_history')) {
            Schema::create('cheq_print_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('cheq_cheque_id')->nullable()->index();
                $table->string('print_action', 80)->nullable();
                $table->string('printer_name')->nullable();
                $table->string('template_name')->nullable();
                $table->string('calibration_profile')->nullable();
                $table->unsignedInteger('reprint_count')->default(0);
                $table->unsignedInteger('printed_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cheq_print_history');
        Schema::dropIfExists('cheq_print_calibrations');
    }
};
