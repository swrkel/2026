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

        if (!$schema->hasTable('hg_business_module_visibility')) {
            $schema->create('hg_business_module_visibility', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('tenant_id', 191)->default('');
                $table->string('tenant_database', 191)->default('');
                $table->unsignedBigInteger('business_id');
                $table->string('business_uid', 191)->default('');
                $table->string('business_name', 191)->default('');
                $table->string('module_key', 191);
                $table->boolean('business_assigned')->default(false);
                $table->boolean('help_enabled')->default(false);
                $table->timestamps();
                $table->unique(['tenant_id', 'business_id', 'module_key'], 'hg_visibility_unique');
                $table->index(['business_uid', 'module_key'], 'hg_visibility_uid_idx');
            });
        }

        if (!$schema->hasTable('hg_articles')) {
            $schema->create('hg_articles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('module_key', 191);
                $table->string('title', 191);
                $table->string('slug', 191);
                $table->text('summary')->nullable();
                $table->longText('content')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();
                $table->unique(['module_key', 'slug'], 'hg_article_module_slug_unique');
                $table->index(['module_key', 'status', 'sort_order'], 'hg_article_lookup_idx');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection());
        $schema->dropIfExists('hg_articles');
        $schema->dropIfExists('hg_business_module_visibility');
    }
};
