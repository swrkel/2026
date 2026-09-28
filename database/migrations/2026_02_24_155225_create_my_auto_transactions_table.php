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
        Schema::create('my_auto_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('my_auto_daily_log_id')
                  ->constrained('my_auto_daily_logs')
                  ->cascadeOnDelete();

            $table->enum('type', ['income', 'expense']);

            $table->decimal('amount', 15, 2);

            // For expense_1, expense_2, etc (nullable for income)
            $table->string('expense_field')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index(['my_auto_daily_log_id', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_auto_transactions');
    }
};
