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
        // Alter contacts register_module
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE contacts MODIFY COLUMN register_module VARCHAR(255) DEFAULT 'other'");

        // Create separate loan_bank_customers table
        Schema::create('loan_bank_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('contact_id');
            $table->string('passport_number')->nullable();
            $table->string('passport_image')->nullable();
            $table->string('nic_image')->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_bank_customers');
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE contacts MODIFY COLUMN register_module ENUM('airline', 'shipping', 'other') DEFAULT 'other'");
    }
};
