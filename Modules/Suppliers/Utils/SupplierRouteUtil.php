<?php

namespace Modules\Suppliers\Utils;

class SupplierRouteUtil
{
    public static function index(): string
    {
        return route('suppliers.records.index');
    }

    public static function dashboard(): string
    {
        return route('suppliers.dashboard');
    }

    public static function profile($supplierId): string
    {
        return route('suppliers.profile.index', $supplierId);
    }
}
