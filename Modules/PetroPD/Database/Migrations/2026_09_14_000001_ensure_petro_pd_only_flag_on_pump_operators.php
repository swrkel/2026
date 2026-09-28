<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pump_operators')
            || Schema::hasColumn('pump_operators', 'is_petro_pd_only')) {
            return;
        }

        Schema::table('pump_operators', function (Blueprint $table): void {
            // Shared compatibility flag. 0 = normal/shared operator,
            // 1 = PetroPD-only operator (must be excluded outside PetroPD).
            $table->boolean('is_petro_pd_only')->default(false);
        });
    }

    public function down(): void
    {
        // Additive shared compatibility data is intentionally retained.
        // Other modules may depend on this flag once deployed.
    }
};
