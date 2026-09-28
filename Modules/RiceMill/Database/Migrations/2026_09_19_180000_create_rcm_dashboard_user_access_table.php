<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Deprecated: Rice Mill now uses users.pump_operator_passcode as the shared
 * User Management passcode. No Rice-specific dashboard access table is needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // No-op by design.
    }

    public function down(): void
    {
        // No-op by design.
    }
};
