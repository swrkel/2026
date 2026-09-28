<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_wallets', function (Blueprint $table) {
            if (! Schema::hasColumn('digital_wallets', 'parent_wallet_id')) {
                $table->unsignedBigInteger('parent_wallet_id')->nullable()->index()->after('owner_id');
            }
            if (! Schema::hasColumn('digital_wallets', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->index()->after('location_id');
            }
            if (! Schema::hasColumn('digital_wallets', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index()->after('department_id');
            }
            if (! Schema::hasColumn('digital_wallets', 'hierarchy_level')) {
                $table->string('hierarchy_level', 50)->default('business')->index()->after('wallet_type');
            }
            if (! Schema::hasColumn('digital_wallets', 'credit_limit')) {
                $table->decimal('credit_limit', 22, 6)->default(0)->after('reserved_balance');
            }
            if (! Schema::hasColumn('digital_wallets', 'daily_spend_limit')) {
                $table->decimal('daily_spend_limit', 22, 6)->nullable()->after('low_balance_threshold');
            }
            if (! Schema::hasColumn('digital_wallets', 'monthly_spend_limit')) {
                $table->decimal('monthly_spend_limit', 22, 6)->nullable()->after('daily_spend_limit');
            }
            if (! Schema::hasColumn('digital_wallets', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('status');
            }
            if (! Schema::hasColumn('digital_wallets', 'locked_reason')) {
                $table->text('locked_reason')->nullable()->after('is_locked');
            }
        });

        Schema::create('digital_wallet_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_code')->unique();
            $table->string('type_name');
            $table->string('channel')->nullable()->index();
            $table->string('currency', 10)->default('LKR');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('digital_wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique();
            $table->unsignedBigInteger('from_wallet_id')->index();
            $table->unsignedBigInteger('to_wallet_id')->index();
            $table->decimal('amount', 22, 6);
            $table->string('currency', 10)->default('LKR');
            $table->string('status')->default('completed')->index();
            $table->string('approval_status')->default('not_required')->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('digital_wallet_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('approval_no')->unique();
            $table->string('request_type')->index();
            $table->unsignedBigInteger('wallet_id')->nullable()->index();
            $table->unsignedBigInteger('transfer_id')->nullable()->index();
            $table->decimal('amount', 22, 6)->default(0);
            $table->string('currency', 10)->default('LKR');
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_wallet_approval_requests');
        Schema::dropIfExists('digital_wallet_transfers');
        Schema::dropIfExists('digital_wallet_types');

        Schema::table('digital_wallets', function (Blueprint $table) {
            foreach (['parent_wallet_id', 'department_id', 'user_id', 'hierarchy_level', 'credit_limit', 'daily_spend_limit', 'monthly_spend_limit', 'is_locked', 'locked_reason'] as $column) {
                if (Schema::hasColumn('digital_wallets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
