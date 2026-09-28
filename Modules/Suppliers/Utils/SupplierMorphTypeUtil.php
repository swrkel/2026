<?php

namespace Modules\Suppliers\Utils;

use Modules\Suppliers\Entities\Supplier;

class SupplierMorphTypeUtil
{
    public static function moduleSupplierType(): string
    {
        return Supplier::class;
    }

    /**
     * Existing Supplier records may have been written by the core Contact model
     * before the standalone Suppliers module was introduced. Support both the
     * standalone and legacy morph names without coupling module logic to them.
     */
    public static function supplierTypes(): array
    {
        return array_values(array_unique([
            self::moduleSupplierType(),
            'App\\Contact',
            'App\\Models\\Contact',
        ]));
    }
}
