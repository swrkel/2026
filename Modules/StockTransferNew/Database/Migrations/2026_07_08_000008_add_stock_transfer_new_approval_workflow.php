<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stnew_approval_matrices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('from_location_id')->nullable()->index();
            $table->unsignedBigInteger('to_location_id')->nullable()->index();
            $table->unsignedBigInteger('from_store_id')->nullable()->index();
            $table->unsignedBigInteger('to_store_id')->nullable()->index();
            $table->decimal('min_amount', 22, 4)->default(0);
            $table->decimal('max_amount', 22, 4)->nullable();
            $table->enum('approval_mode', ['sequential', 'parallel'])->default('sequential');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stnew_approval_matrix_steps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('matrix_id')->index();
            $table->unsignedInteger('step_order')->default(1);
            $table->string('role_name')->nullable();
            $table->unsignedBigInteger('approver_user_id')->nullable()->index();
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedInteger('sla_hours')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stnew_transfer_approval_steps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('transfer_id')->index();
            $table->unsignedBigInteger('matrix_step_id')->nullable()->index();
            $table->unsignedInteger('step_order')->default(1);
            $table->string('role_name')->nullable();
            $table->unsignedBigInteger('approver_user_id')->nullable()->index();
            $table->enum('status', ['pending', 'approved', 'rejected', 'returned', 'skipped'])->default('pending')->index();
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->unsignedBigInteger('acted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stnew_approval_delegations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('from_user_id')->index();
            $table->unsignedBigInteger('to_user_id')->index();
            $table->date('valid_from')->index();
            $table->date('valid_to')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('stnew_stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stnew_stock_transfers', 'approval_mode')) {
                $table->string('approval_mode')->nullable()->after('status');
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'approval_matrix_id')) {
                $table->unsignedBigInteger('approval_matrix_id')->nullable()->after('approval_mode')->index();
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'current_approval_step')) {
                $table->unsignedInteger('current_approval_step')->default(0)->after('approval_matrix_id');
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'returned_at')) {
                $table->timestamp('returned_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'returned_by')) {
                $table->unsignedBigInteger('returned_by')->nullable()->after('returned_at');
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('returned_by');
            }
            if (!Schema::hasColumn('stnew_stock_transfers', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stnew_approval_delegations');
        Schema::dropIfExists('stnew_transfer_approval_steps');
        Schema::dropIfExists('stnew_approval_matrix_steps');
        Schema::dropIfExists('stnew_approval_matrices');
    }
};
