<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_payment_reference_prefixes')) {
            return;
        }

        Schema::create('customer_payment_reference_prefixes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->string('context_key', 80);
            $table->string('context_label', 191);
            $table->string('prefix', 20);
            $table->unsignedBigInteger('starting_number')->default(1);
            $table->unsignedTinyInteger('number_length')->default(4);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('used_count')->default(0);
            $table->timestamp('first_used_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'context_key', 'is_active'], 'cprp_business_context_active_idx');
            $table->index(['business_id', 'prefix'], 'cprp_business_prefix_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_reference_prefixes');
    }
};
