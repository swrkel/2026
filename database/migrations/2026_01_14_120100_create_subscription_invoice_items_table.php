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
        if (Schema::hasTable('subscription_invoice_items')) {
            return;
        }

        Schema::create('subscription_invoice_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('invoice_id');
            $table->string('description');
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->enum('source', ['system', 'manual'])->default('system');
            $table->timestamps();
        });

        if (Schema::hasTable('subscription_invoices')) {
            try {
                Schema::table('subscription_invoice_items', function (Blueprint $table) {
                    $table->foreign('invoice_id')
                        ->references('id')
                        ->on('subscription_invoices')
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
        if (! Schema::hasTable('subscription_invoice_items')) {
            return;
        }

        try {
            Schema::table('subscription_invoice_items', function (Blueprint $table) {
                $table->dropForeign(['invoice_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if FK does not exist.
        }

        Schema::dropIfExists('subscription_invoice_items');
    }
};
