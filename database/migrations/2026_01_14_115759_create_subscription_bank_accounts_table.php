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
        if (Schema::hasTable('subscription_bank_accounts')) {
            return;
        }

        Schema::create('subscription_bank_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('template_name');
            $table->string('ac_name');
            $table->string('ac_no');
            $table->string('bank');
            $table->string('branch');
            $table->enum('status', ['enabled', 'disabled'])->default('enabled');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        if (Schema::hasTable('users')) {
            try {
                Schema::table('subscription_bank_accounts', function (Blueprint $table) {
                    $table->foreign('created_by')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // Skip FK creation when schema types are incompatible in legacy/test DBs.
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('subscription_bank_accounts')) {
            return;
        }

        try {
            Schema::table('subscription_bank_accounts', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
            });
        } catch (\Throwable $e) {
            // Ignore if FK does not exist.
        }

        Schema::dropIfExists('subscription_bank_accounts');
    }
};
