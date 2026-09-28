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
        if (Schema::hasTable('subscription_invoice_prefixes')) {
            return;
        }

        Schema::create('subscription_invoice_prefixes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('prefix')->unique();
            $table->integer('current_number')->default(1);
            $table->timestamps();
        });

        if (Schema::hasTable('users')) {
            try {
                Schema::table('subscription_invoice_prefixes', function (Blueprint $table) {
                    $table->foreign('user_id')
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
        if (! Schema::hasTable('subscription_invoice_prefixes')) {
            return;
        }

        try {
            Schema::table('subscription_invoice_prefixes', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if FK does not exist.
        }

        Schema::dropIfExists('subscription_invoice_prefixes');
    }
};
