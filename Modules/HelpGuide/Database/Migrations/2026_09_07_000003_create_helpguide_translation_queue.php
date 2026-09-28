<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function connection(): string
    {
        return (string) config('helpguide.central_connection', config('tenancy.database.central_connection', 'mysql'));
    }

    public function up(): void
    {
        $schema = Schema::connection($this->connection());

        if (!$schema->hasTable('hg_translation_queue')) {
            $schema->create('hg_translation_queue', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('article_id');
                $table->string('language_code', 12);
                $table->char('source_hash', 64)->default('');
                $table->string('status', 20)->default('pending');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->timestamp('available_at')->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();

                $table->unique(['article_id', 'language_code'], 'hg_translation_queue_unique');
                $table->index(['status', 'available_at'], 'hg_translation_queue_run_idx');
                $table->index(['article_id', 'status'], 'hg_translation_queue_article_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection())->dropIfExists('hg_translation_queue');
    }
};
