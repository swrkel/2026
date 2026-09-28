<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOTE:
        // - Prefix must allow "any number of characters" per Doc 7942 (practically, use VARCHAR(191)).
        // - Starting No must preserve leading zeros, so it must be stored as string.
        // - Use raw SQL to avoid requiring doctrine/dbal for column changes.

        if (Schema::hasTable('vat_prefixes')) {
            DB::statement("ALTER TABLE `vat_prefixes` MODIFY `prefix` VARCHAR(191) NULL");
            DB::statement("ALTER TABLE `vat_prefixes` MODIFY `starting_no` VARCHAR(191) NOT NULL DEFAULT '1'");
        }

        if (Schema::hasTable('vat_invoice2_prefixes')) {
            DB::statement("ALTER TABLE `vat_invoice2_prefixes` MODIFY `prefix` VARCHAR(191) NULL");
            DB::statement("ALTER TABLE `vat_invoice2_prefixes` MODIFY `starting_no` VARCHAR(191) NOT NULL DEFAULT '1'");
        }

        if (Schema::hasTable('vat_statement_prefixes')) {
            DB::statement("ALTER TABLE `vat_statement_prefixes` MODIFY `prefix` VARCHAR(191) NULL");
            DB::statement("ALTER TABLE `vat_statement_prefixes` MODIFY `starting_no` VARCHAR(191) NOT NULL DEFAULT '1'");
        }
    }

    public function down(): void
    {
        // Best-effort rollback (may drop leading zeros if you migrated string values like "0001").
        if (Schema::hasTable('vat_prefixes')) {
            DB::statement("ALTER TABLE `vat_prefixes` MODIFY `prefix` VARCHAR(10) NULL");
            DB::statement("ALTER TABLE `vat_prefixes` MODIFY `starting_no` INT NOT NULL DEFAULT 1");
        }

        if (Schema::hasTable('vat_invoice2_prefixes')) {
            DB::statement("ALTER TABLE `vat_invoice2_prefixes` MODIFY `prefix` VARCHAR(10) NULL");
            DB::statement("ALTER TABLE `vat_invoice2_prefixes` MODIFY `starting_no` INT NOT NULL DEFAULT 1");
        }

        if (Schema::hasTable('vat_statement_prefixes')) {
            DB::statement("ALTER TABLE `vat_statement_prefixes` MODIFY `prefix` VARCHAR(10) NULL");
            DB::statement("ALTER TABLE `vat_statement_prefixes` MODIFY `starting_no` INT NOT NULL DEFAULT 1");
        }
    }
};

