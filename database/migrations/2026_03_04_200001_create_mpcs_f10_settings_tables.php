<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mpcs_f10_opening_numbers')) {
            Schema::create('mpcs_f10_opening_numbers', function (Blueprint $table) {
                $table->id();
                $table->integer('business_id');
                $table->date('opening_date')->nullable();
                $table->string('f10_number')->nullable();
                $table->string('document_no')->nullable();
                $table->string('currency_prefix')->nullable();
                $table->integer('created_by');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mpcs_f10_managers')) {
            Schema::create('mpcs_f10_managers', function (Blueprint $table) {
                $table->id();
                $table->integer('business_id');
                $table->string('manager_name');
                $table->enum('status', ['Active', 'Inactive'])->default('Active');
                $table->integer('created_by');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mpcs_f10_opening_numbers');
        Schema::dropIfExists('mpcs_f10_managers');
    }
};
