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
        $schema = Schema::connection($this->connection());

        // Guard so re-running on a partially migrated database is safe.
        if ($schema->hasColumn($this->table(), 'theme')) {
            return;
        }

        $schema->table($this->table(), function (Blueprint $table) {
            $table->string('theme', 24)->default('classic')->after('accent_color');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection());

        if (! $schema->hasColumn($this->table(), 'theme')) {
            return;
        }

        $schema->table($this->table(), function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
