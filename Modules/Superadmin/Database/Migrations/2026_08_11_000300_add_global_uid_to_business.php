<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give every business a GLOBALLY unique identifier.
 *
 * -----------------------------------------------------------------------
 * Why
 * -----------------------------------------------------------------------
 * `business.id` is a per-database auto-increment, and `company_number` is
 * derived from it (id 7 -> RA-13, id 15 -> RA-21). Neither identifies a
 * business across databases.
 *
 * This is not theoretical. Central business 15 and nivasa_sonali business 15
 * were two different companies, with different owners, both carrying
 * company_number RA-21. Any cross-database operation matching on id or
 * company_number - the tenant sync, and any future group-of-companies
 * membership lookup - can therefore pair unrelated companies together.
 *
 * -----------------------------------------------------------------------
 * Why a new column instead of renumbering ids
 * -----------------------------------------------------------------------
 * `business.id` is a foreign key in dozens of tables in each of the ~45
 * databases (transactions, contacts, products, users, ...). Renumbering it
 * would mean rewriting every one of those references in every database, with
 * no safe rollback. Adding an identifier alongside the id is non-destructive:
 * nothing that exists today changes behaviour, and new code can match on a key
 * that is actually unique.
 *
 * -----------------------------------------------------------------------
 * What this migration does
 * -----------------------------------------------------------------------
 *   1. Adds `business.global_uid` (CHAR(36), nullable, unique) if missing.
 *   2. Backfills a UUID for every row that has none IN THIS DATABASE ONLY.
 *
 * It deliberately does NOT try to work out which tenant row corresponds to
 * which central row. That mapping is ambiguous wherever ids have collided and
 * needs a human decision - see SQL/global_uid_rollout.sh, which produces a
 * match report for review rather than guessing.
 */
class AddGlobalUidToBusiness extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business')) {
            return;
        }

        if (! Schema::hasColumn('business', 'global_uid')) {
            Schema::table('business', function (Blueprint $table): void {
                $table->char('global_uid', 36)->nullable()->after('id');
                $table->unique('global_uid', 'business_global_uid_unique');
            });
        }

        // Backfill only rows in this database that have no uid yet.
        DB::table('business')
            ->whereNull('global_uid')
            ->orderBy('id')
            ->select('id')
            ->chunk(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('business')
                        ->where('id', $row->id)
                        ->whereNull('global_uid')
                        ->update(['global_uid' => (string) \Illuminate\Support\Str::uuid()]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('business') || ! Schema::hasColumn('business', 'global_uid')) {
            return;
        }

        Schema::table('business', function (Blueprint $table): void {
            $table->dropUnique('business_global_uid_unique');
            $table->dropColumn('global_uid');
        });
    }
}
