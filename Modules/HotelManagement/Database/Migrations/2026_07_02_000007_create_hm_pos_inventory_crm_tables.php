<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hm_room_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('folio_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->string('source_module')->default('hotel');
            $table->string('charge_ref')->nullable()->index();
            $table->string('charge_type')->index();
            $table->string('description');
            $table->decimal('amount', 18, 4)->default(0);
            $table->date('charge_date')->index();
            $table->string('status')->default('posted')->index();
            $table->timestamps();
        });

        Schema::create('hm_store_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('item_code')->nullable()->index();
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->string('unit')->default('unit');
            $table->decimal('reorder_level', 18, 4)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hm_store_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->string('movement_type')->index();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->date('movement_date')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('hm_guest_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_id')->index();
            $table->string('preference_type')->index();
            $table->string('preference_value')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hm_guest_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_id')->index();
            $table->string('note_type')->default('general')->index();
            $table->text('note');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_guest_notes');
        Schema::dropIfExists('hm_guest_preferences');
        Schema::dropIfExists('hm_store_movements');
        Schema::dropIfExists('hm_store_items');
        Schema::dropIfExists('hm_room_charges');
    }
};
