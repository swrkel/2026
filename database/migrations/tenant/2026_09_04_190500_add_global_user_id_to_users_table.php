<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Universal user identity - central/base database schema.
 *
 * Deliberately nullable in phase 1. Existing live rows are backfilled by
 * `php artisan users:reconcile-identity --all`, which can audit collisions
 * across every tenant before assigning IDs. The User model guarantees newly
 * created/saved users receive an ID once this column exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'global_user_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->char('global_user_id', 36)->nullable()->after('id');
            });
        }

        if (! $this->indexExists('users_global_user_id_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('global_user_id', 'users_global_user_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'global_user_id')) {
            return;
        }

        $hasIndex = $this->indexExists('users_global_user_id_unique');

        Schema::table('users', function (Blueprint $table) use ($hasIndex): void {
            if ($hasIndex) {
                $table->dropUnique('users_global_user_id_unique');
            }
            $table->dropColumn('global_user_id');
        });
    }

    private function indexExists(string $index): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            ['users', $index]
        );

        return (int) ($row->c ?? 0) > 0;
    }
};
