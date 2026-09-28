<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Facades\Schema;

class SettlementPostingTableGuard
{
    public function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function hasColumns(string $table, array $columns): bool
    {
        if (! $this->hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
}
