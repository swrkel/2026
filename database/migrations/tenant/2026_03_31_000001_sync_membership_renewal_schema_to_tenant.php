<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('membership_members')) {
            Schema::table('membership_members', function (Blueprint $table) {
                if (!Schema::hasColumn('membership_members', 'renewal_period')) {
                    $table->string('renewal_period', 20)->nullable()->after('membership_status_id');
                }

                if (!Schema::hasColumn('membership_members', 'renewal_cycles')) {
                    $table->unsignedInteger('renewal_cycles')->nullable()->after('renewal_period');
                }

                if (!Schema::hasColumn('membership_members', 'renewal_date')) {
                    $table->date('renewal_date')->nullable()->after('renewal_cycles');
                }

                if (!Schema::hasColumn('membership_members', 'registration_renewal_amount')) {
                    $table->decimal('registration_renewal_amount', 15, 2)->nullable()->after('renewal_date');
                }
            });

            $hasRenewalIndex = DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'membership_members')
                ->where('index_name', 'idx_mm_business_renewal_date')
                ->exists();

            if (! $hasRenewalIndex
                && Schema::hasColumn('membership_members', 'business_id')
                && Schema::hasColumn('membership_members', 'renewal_date')) {
                Schema::table('membership_members', function (Blueprint $table) {
                    $table->index(['business_id', 'renewal_date'], 'idx_mm_business_renewal_date');
                });
            }
        }

        if (!Schema::hasTable('membership_member_renewals')) {
            Schema::create('membership_member_renewals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->unsignedBigInteger('membership_member_id');
                $table->string('renewal_period', 20);
                $table->unsignedInteger('renewal_cycles');
                $table->date('next_renewal_date');
                $table->decimal('renewal_amount', 15, 2)->nullable();
                $table->unsignedBigInteger('renewed_by');
                $table->timestamps();

                $table->index(['business_id', 'membership_member_id'], 'idx_membership_member_renewals_member');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('membership_member_renewals')) {
            Schema::drop('membership_member_renewals');
        }

        if (Schema::hasTable('membership_members')) {
            $hasRenewalIndex = DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'membership_members')
                ->where('index_name', 'idx_mm_business_renewal_date')
                ->exists();

            Schema::table('membership_members', function (Blueprint $table) use ($hasRenewalIndex) {
                if ($hasRenewalIndex) {
                    $table->dropIndex('idx_mm_business_renewal_date');
                }

                if (Schema::hasColumn('membership_members', 'registration_renewal_amount')) {
                    $table->dropColumn('registration_renewal_amount');
                }

                if (Schema::hasColumn('membership_members', 'renewal_date')) {
                    $table->dropColumn('renewal_date');
                }

                if (Schema::hasColumn('membership_members', 'renewal_cycles')) {
                    $table->dropColumn('renewal_cycles');
                }

                if (Schema::hasColumn('membership_members', 'renewal_period')) {
                    $table->dropColumn('renewal_period');
                }
            });
        }
    }
};
