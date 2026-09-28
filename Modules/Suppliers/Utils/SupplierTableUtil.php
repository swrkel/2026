<?php

namespace Modules\Suppliers\Utils;

use Illuminate\Support\Facades\Schema;

class SupplierTableUtil
{
    public static function has(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
