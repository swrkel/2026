<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('rcm_dashboard_user_access');
    }

    public function down(): void
    {
        // The removed table stored a duplicate Rice-specific passcode and is
        // intentionally not recreated. Shared passcode lives on users.
    }
};
