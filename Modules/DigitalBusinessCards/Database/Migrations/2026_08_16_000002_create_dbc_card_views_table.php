<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function connection(): ?string
    {
        return config('digital-business-cards.connection');
    }

    protected function prefix(): string
    {
        return config('digital-business-cards.table_prefix', 'dbc_');
    }

    public function up(): void
    {
        Schema::connection($this->connection())->create($this->prefix().'card_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')
                  ->constrained($this->prefix().'cards')
                  ->cascadeOnDelete();

            // "view" | "save" (vCard downloaded) | "qr"
            $table->string('event', 16)->default('view')->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('user_agent', 255)->nullable();
            $table->string('referrer', 255)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection())->dropIfExists($this->prefix().'card_views');
    }
};
