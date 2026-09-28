<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rn_staff_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('staff_code')->nullable()->index();
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->default('waiter')->index();
            $table->decimal('service_charge_share_percent', 8, 4)->default(0);
            $table->boolean('can_take_orders')->default(false);
            $table->boolean('can_cashier')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'staff_code'], 'rn_staff_business_code_unique');
        });

        Schema::create('rn_cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('staff_member_id')->nullable()->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('shift_no')->index();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->decimal('opening_cash', 22, 4)->default(0);
            $table->decimal('cash_sales', 22, 4)->default(0);
            $table->decimal('card_sales', 22, 4)->default(0);
            $table->decimal('other_sales', 22, 4)->default(0);
            $table->decimal('cash_in', 22, 4)->default(0);
            $table->decimal('cash_out', 22, 4)->default(0);
            $table->decimal('expected_cash', 22, 4)->default(0);
            $table->decimal('counted_cash', 22, 4)->nullable();
            $table->decimal('shortage_excess', 22, 4)->default(0);
            $table->string('status')->default('open')->index();
            $table->text('opening_note')->nullable();
            $table->text('closing_note')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'location_id', 'shift_no'], 'rn_cashier_shift_scope_no_unique');
        });

        Schema::create('rn_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('cashier_shift_id')->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('movement_type')->index();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('rn_tip_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('cashier_shift_id')->nullable()->index();
            $table->unsignedBigInteger('staff_member_id')->nullable()->index();
            $table->unsignedBigInteger('bill_id')->nullable()->index();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('payment_method')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('rn_service_charge_distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('cashier_shift_id')->nullable()->index();
            $table->unsignedBigInteger('staff_member_id')->index();
            $table->decimal('base_amount', 22, 4)->default(0);
            $table->decimal('share_percent', 8, 4)->default(0);
            $table->decimal('distributed_amount', 22, 4)->default(0);
            $table->string('status')->default('pending')->index();
            $table->date('distribution_date')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rn_service_charge_distributions');
        Schema::dropIfExists('rn_tip_entries');
        Schema::dropIfExists('rn_cash_movements');
        Schema::dropIfExists('rn_cashier_shifts');
        Schema::dropIfExists('rn_staff_members');
    }
};
