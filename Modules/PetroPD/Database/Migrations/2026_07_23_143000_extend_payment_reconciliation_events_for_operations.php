<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('petro_pd_payment_reconciliation_events')) {
            return;
        }

        Schema::table('petro_pd_payment_reconciliation_events', function (Blueprint $table) {
            if (! Schema::hasColumn('petro_pd_payment_reconciliation_events', 'first_seen_at')) {
                $table->timestamp('first_seen_at')->nullable()->after('event_hash')->index();
            }
            if (! Schema::hasColumn('petro_pd_payment_reconciliation_events', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('first_seen_at')->index();
            }
            if (! Schema::hasColumn('petro_pd_payment_reconciliation_events', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()->after('last_seen_at')->index();
            }
            if (! Schema::hasColumn('petro_pd_payment_reconciliation_events', 'occurrence_count')) {
                $table->unsignedInteger('occurrence_count')->default(1)->after('last_checked_at');
            }
            if (! Schema::hasColumn('petro_pd_payment_reconciliation_events', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at')->index();
            }
            if (! Schema::hasColumn('petro_pd_payment_reconciliation_events', 'resolution_note')) {
                $table->text('resolution_note')->nullable()->after('resolved_by');
            }
        });

        DB::table('petro_pd_payment_reconciliation_events')
            ->whereNull('first_seen_at')
            ->update(['first_seen_at' => DB::raw('COALESCE(created_at, NOW())')]);

        DB::table('petro_pd_payment_reconciliation_events')
            ->whereNull('last_seen_at')
            ->update(['last_seen_at' => DB::raw('COALESCE(updated_at, created_at, NOW())')]);
    }

    public function down(): void
    {
        // Operational audit history is financial evidence. Do not remove it on rollback.
    }
};
