<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hm_folios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('guest_id')->nullable()->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->string('folio_no')->index();
            $table->string('folio_type')->default('guest');
            $table->date('folio_date')->index();
            $table->decimal('total_charges', 18, 4)->default(0);
            $table->decimal('total_payments', 18, 4)->default(0);
            $table->decimal('balance', 18, 4)->default(0);
            $table->string('status')->default('open')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hm_folio_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('folio_id')->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->string('line_type')->index();
            $table->string('description');
            $table->date('charge_date')->index();
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('discount', 18, 4)->default(0);
            $table->decimal('tax', 18, 4)->default(0);
            $table->decimal('amount', 18, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('hm_guest_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('folio_id')->index();
            $table->string('payment_ref')->nullable()->index();
            $table->string('payment_method')->index();
            $table->decimal('amount', 18, 4)->default(0);
            $table->date('payment_date')->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('hm_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->unsignedBigInteger('guest_id')->nullable()->index();
            $table->string('deposit_ref')->nullable()->index();
            $table->decimal('amount', 18, 4)->default(0);
            $table->decimal('used_amount', 18, 4)->default(0);
            $table->decimal('refunded_amount', 18, 4)->default(0);
            $table->string('status')->default('available')->index();
            $table->date('deposit_date')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_deposits');
        Schema::dropIfExists('hm_guest_payments');
        Schema::dropIfExists('hm_folio_lines');
        Schema::dropIfExists('hm_folios');
    }
};
