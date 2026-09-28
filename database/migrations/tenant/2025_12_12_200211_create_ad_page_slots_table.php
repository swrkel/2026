<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_page_slots', function (Blueprint $table) {
            $table->id();
            $table->string('slot', 255);
            $table->string('slot_no');
            $table->integer('ad_page_id')->index();
            $table->integer('width');
            $table->integer('height');
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_page_slots');
    }
};
