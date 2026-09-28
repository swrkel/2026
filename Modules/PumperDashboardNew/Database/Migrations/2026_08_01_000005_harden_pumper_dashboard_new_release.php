<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $archives = $this->archiveLegacyFoundationTables();

        if ($archives !== []) {
            // The first Pumper Dashboard-New foundation used several of the same
            // table names with incompatible columns. Recreate the current schema
            // after retaining the complete old tables under pone_v1_* names.
            $create = require __DIR__ . '/2026_08_01_000001_create_pumper_dashboard_new_tables.php';
            $create->up();

            $extend = require __DIR__ . '/2026_08_01_000003_extend_pumper_dashboard_new_full_operations.php';
            $extend->up();

            $this->restoreLegacyOperatorProfiles($archives['pone_pd_operators'] ?? null);
        }

        $this->ensureOperatorProfileColumns();
        $this->ensureOperatorSessionColumns();
        $this->ensureShiftCompatibilityColumns();
        $this->backfillCompatibilityColumns();
        $this->alterIntegrationLinkStatus(['pending', 'synced', 'failed', 'retired']);
    }

    public function down(): void
    {
        // This is a production-hardening migration. Deliberately do not remove
        // compatibility columns or archived data during rollback.
        if (Schema::hasTable('pone_integration_links') && Schema::hasColumn('pone_integration_links', 'status')) {
            DB::table('pone_integration_links')->where('status', 'retired')->update(['status' => 'failed']);
        }

        $this->alterIntegrationLinkStatus(['pending', 'synced', 'failed']);
    }

    /**
     * @return array<string,string> original table => archived table
     */
    private function archiveLegacyFoundationTables(): array
    {
        if (! $this->legacyFoundationDetected()) {
            return [];
        }

        $tables = [
            'pone_pd_operators',
            'pone_operator_sessions',
            'pone_number_sequences',
            'pone_shifts',
            'pone_pump_assignments',
            'pone_payments',
            'pone_other_sales',
            'pone_unload_stocks',
            'pone_integration_outbox',
            'pone_audit_logs',
        ];

        $archives = [];
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $base = 'pone_v1_' . substr($table, 5);
            $backup = $base;
            $suffix = 2;
            while (Schema::hasTable($backup)) {
                $backup = $base . '_' . $suffix;
                $suffix++;
            }

            Schema::rename($table, $backup);
            $archives[$table] = $backup;
        }

        return $archives;
    }

    private function legacyFoundationDetected(): bool
    {
        $legacySession = Schema::hasTable('pone_operator_sessions')
            && Schema::hasColumn('pone_operator_sessions', 'session_uuid')
            && ! Schema::hasColumn('pone_operator_sessions', 'session_key');

        $legacyProfile = Schema::hasTable('pone_pd_operators')
            && Schema::hasColumn('pone_pd_operators', 'petro_pd_operator_id')
            && ! Schema::hasColumn('pone_pd_operators', 'pd_operator_id');

        $legacyShift = Schema::hasTable('pone_shifts')
            && Schema::hasColumn('pone_shifts', 'shift_uuid')
            && ! Schema::hasColumn('pone_shifts', 'uuid');

        return $legacySession || $legacyProfile || $legacyShift;
    }

    private function restoreLegacyOperatorProfiles(?string $archive): void
    {
        if (! $archive || ! Schema::hasTable($archive) || ! Schema::hasTable('pone_pd_operators')) {
            return;
        }

        foreach (DB::table($archive)->orderBy('id')->cursor() as $legacy) {
            $operatorId = (int) ($legacy->petro_pd_operator_id ?? $legacy->pd_operator_id ?? 0);
            $businessId = (int) ($legacy->business_id ?? 0);
            if ($operatorId <= 0 || $businessId <= 0) {
                continue;
            }

            $enabled = (int) ($legacy->enabled ?? 1) === 1
                && (int) ($legacy->can_login ?? 1) === 1;

            DB::table('pone_pd_operators')->updateOrInsert(
                [
                    'business_id' => $businessId,
                    'pd_operator_id' => $operatorId,
                ],
                [
                    'location_id' => isset($legacy->force_location_id) && (int) $legacy->force_location_id > 0
                        ? (int) $legacy->force_location_id
                        : ($legacy->location_id ?? null),
                    'user_id' => $legacy->user_id ?? null,
                    'display_name' => trim((string) ($legacy->operator_code ?? ''))
                        ?: 'Pump Operator #' . $operatorId,
                    'login_enabled' => $enabled ? 1 : 0,
                    'status' => $enabled ? 'active' : 'inactive',
                    'settings' => $legacy->settings ?? null,
                    'created_at' => $legacy->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function ensureOperatorProfileColumns(): void
    {
        if (! Schema::hasTable('pone_pd_operators')) {
            return;
        }

        $this->addColumn('pone_pd_operators', 'pd_operator_id', fn (Blueprint $table) => $table->unsignedInteger('pd_operator_id')->nullable());
        $this->addColumn('pone_pd_operators', 'display_name', fn (Blueprint $table) => $table->string('display_name', 191)->nullable());
        $this->addColumn('pone_pd_operators', 'login_enabled', fn (Blueprint $table) => $table->boolean('login_enabled')->default(true));
        $this->addColumn('pone_pd_operators', 'status', fn (Blueprint $table) => $table->string('status', 20)->default('active'));
        $this->addColumn('pone_pd_operators', 'last_login_at', fn (Blueprint $table) => $table->timestamp('last_login_at')->nullable());
        $this->addColumn('pone_pd_operators', 'last_login_ip', fn (Blueprint $table) => $table->string('last_login_ip', 64)->nullable());
    }

    private function ensureOperatorSessionColumns(): void
    {
        if (! Schema::hasTable('pone_operator_sessions')) {
            return;
        }

        // Do not use AFTER clauses here. Earlier installations did not contain
        // shift_id, which caused SQLSTATE[42S22] during the login-page auto repair.
        $this->addColumn('pone_operator_sessions', 'session_key', fn (Blueprint $table) => $table->string('session_key', 64)->nullable());
        $this->addColumn('pone_operator_sessions', 'pd_operator_id', fn (Blueprint $table) => $table->unsignedInteger('pd_operator_id')->nullable());
        $this->addColumn('pone_operator_sessions', 'shift_id', fn (Blueprint $table) => $table->unsignedBigInteger('shift_id')->nullable());
        $this->addColumn('pone_operator_sessions', 'shift_number', fn (Blueprint $table) => $table->string('shift_number', 80)->nullable());
        $this->addColumn('pone_operator_sessions', 'last_seen_at', fn (Blueprint $table) => $table->timestamp('last_seen_at')->nullable());
    }

    private function ensureShiftCompatibilityColumns(): void
    {
        if (! Schema::hasTable('pone_shifts')) {
            return;
        }

        $this->addColumn('pone_shifts', 'petropd_shift_id', fn (Blueprint $table) => $table->unsignedBigInteger('petropd_shift_id')->nullable());
        $this->addColumn('pone_shifts', 'petropd_shift_number', fn (Blueprint $table) => $table->unsignedBigInteger('petropd_shift_number')->nullable());
    }

    private function backfillCompatibilityColumns(): void
    {
        if (Schema::hasTable('pone_pd_operators')) {
            if (Schema::hasColumn('pone_pd_operators', 'petro_pd_operator_id')
                && Schema::hasColumn('pone_pd_operators', 'pd_operator_id')) {
                DB::statement('UPDATE `pone_pd_operators` SET `pd_operator_id` = `petro_pd_operator_id` WHERE `pd_operator_id` IS NULL');
            }

            if (Schema::hasColumn('pone_pd_operators', 'operator_code')
                && Schema::hasColumn('pone_pd_operators', 'display_name')) {
                DB::statement("UPDATE `pone_pd_operators` SET `display_name` = COALESCE(NULLIF(TRIM(`operator_code`), ''), CONCAT('Pump Operator #', `id`)) WHERE `display_name` IS NULL OR TRIM(`display_name`) = ''");
            }

            if (Schema::hasColumn('pone_pd_operators', 'can_login')
                && Schema::hasColumn('pone_pd_operators', 'login_enabled')) {
                DB::statement('UPDATE `pone_pd_operators` SET `login_enabled` = IF(`can_login` = 1, 1, 0)');
            }
        }

        if (Schema::hasTable('pone_operator_sessions')) {
            if (Schema::hasColumn('pone_operator_sessions', 'session_uuid')
                && Schema::hasColumn('pone_operator_sessions', 'session_key')) {
                DB::statement("UPDATE `pone_operator_sessions` SET `session_key` = SHA2(CONCAT(COALESCE(`session_uuid`, ''), '|', `id`), 256) WHERE `session_key` IS NULL OR `session_key` = ''");
            }

            if (Schema::hasColumn('pone_operator_sessions', 'petro_pd_operator_id')
                && Schema::hasColumn('pone_operator_sessions', 'pd_operator_id')) {
                DB::statement('UPDATE `pone_operator_sessions` SET `pd_operator_id` = `petro_pd_operator_id` WHERE `pd_operator_id` IS NULL');
            }

            if (Schema::hasColumn('pone_operator_sessions', 'last_activity_at')
                && Schema::hasColumn('pone_operator_sessions', 'last_seen_at')) {
                DB::statement('UPDATE `pone_operator_sessions` SET `last_seen_at` = `last_activity_at` WHERE `last_seen_at` IS NULL');
            }
        }
    }

    private function addColumn(string $table, string $column, callable $definition): void
    {
        if (! Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $blueprint) use ($definition): void {
                $definition($blueprint);
            });
        }
    }

    private function alterIntegrationLinkStatus(array $values): void
    {
        if (! Schema::hasTable('pone_integration_links')
            || ! Schema::hasColumn('pone_integration_links', 'status')
            || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $quoted = implode(',', array_map(
            static fn (string $value): string => "'" . str_replace("'", "''", $value) . "'",
            $values
        ));
        DB::statement("ALTER TABLE `pone_integration_links` MODIFY `status` ENUM({$quoted}) NOT NULL DEFAULT 'pending'");
    }
};
