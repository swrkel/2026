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

    protected function table(): string
    {
        return config('digital-business-cards.table_prefix', 'dbc_').'cards';
    }

    public function up(): void
    {
        Schema::connection($this->connection())->create($this->table(), function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 120)->unique();

            // Owner is stored as a bare id. The module never references the
            // host User model, so it stays portable across guards.
            $table->unsignedBigInteger('owner_id')->nullable()->index();

            $table->string('first_name', 80);
            $table->string('last_name', 80)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->string('company', 120)->nullable();
            $table->string('department', 120)->nullable();

            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('phone_alt', 40)->nullable();
            $table->string('website', 190)->nullable();

            $table->string('address_line', 190)->nullable();
            $table->string('city', 90)->nullable();
            $table->string('region', 90)->nullable();
            $table->string('postal_code', 24)->nullable();
            $table->string('country', 90)->nullable();

            $table->text('bio')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('accent_color', 9)->default('#2F6F62');

            // [{ "label": "LinkedIn", "url": "https://..." }, ...]
            $table->json('links')->nullable();

            $table->boolean('is_published')->default(false)->index();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('saves_count')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection())->dropIfExists($this->table());
    }
};
