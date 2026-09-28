<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createStandardMasterTable('customer_types');
        $this->createStandardMasterTable('customer_categories');
        $this->createStandardMasterTable('customer_classifications');
        $this->createStandardMasterTable('customer_custom_fields');

        if (! Schema::hasTable('customer_module_settings')) {
            Schema::create('customer_module_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->string('name', 191);
                $table->text('value')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['business_id', 'name'], 'cus_module_settings_biz_name_idx');
            });
        }

        if (! Schema::hasTable('customer_opening_balance_adjustments')) {
            Schema::create('customer_opening_balance_adjustments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->string('name', 191);
                $table->string('reference_no', 100)->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->date('transaction_date')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['business_id', 'transaction_date'], 'cus_opening_biz_date_idx');
            });
        }

        if (! Schema::hasTable('customer_workflow_approvals')) {
            Schema::create('customer_workflow_approvals', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('contact_id')->index();
                $table->string('workflow_type', 50)->default('customer_approval');
                $table->string('status', 30)->default('pending');
                $table->string('current_value', 191)->nullable();
                $table->string('requested_value', 191)->nullable();
                $table->text('reason')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('requested_by')->nullable();
                $table->unsignedInteger('approved_by')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['business_id', 'workflow_type', 'status'], 'cus_workflow_biz_type_status_idx');
            });
        }

        if (! Schema::hasTable('customer_workflow_histories')) {
            Schema::create('customer_workflow_histories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('contact_id')->index();
                $table->string('workflow_type', 50);
                $table->string('action', 50);
                $table->string('old_value', 191)->nullable();
                $table->string('new_value', 191)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'workflow_type'], 'cus_history_biz_type_idx');
            });
        }
    }

    public function down(): void
    {
        // Non-destructive rollback: tenant databases may already have contained
        // these tables before this migration was introduced.
    }

    private function createStandardMasterTable(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->string('name', 191);
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'name'], substr($tableName . '_biz_name_idx', 0, 64));
        });
    }
};
