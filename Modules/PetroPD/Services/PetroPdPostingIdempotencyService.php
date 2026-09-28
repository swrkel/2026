<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prevents duplicate account/customer ledger posting for the same Petro PD source.
 * Safe to call even when optional ledger columns do not exist.
 */
class PetroPdPostingIdempotencyService
{
    public function alreadyPosted(string $table, array $identity): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $query = DB::table($table);
        $appliedIdentityColumns = 0;

        foreach ($identity as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $query->where($column, $value);
                $appliedIdentityColumns++;
            }
        }

        // Never treat a row as already posted when none of the requested
        // identity columns exists in this tenant database. Otherwise a tenant
        // with a different schema could be falsely blocked by the first row in
        // the table.
        if ($appliedIdentityColumns === 0) {
            return false;
        }

        return $query->exists();
    }

    public function insertIfMissing(string $table, array $identity, array $payload): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $data = [];
        foreach (array_merge($identity, $payload) as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $data[$column] = $value;
            }
        }

        $identityData = [];
        foreach ($identity as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $identityData[$column] = $value;
            }
        }

        if (empty($data) || empty($identityData) || $this->alreadyPosted($table, $identity)) {
            return false;
        }

        DB::table($table)->insert($data);
        return true;
    }
}
