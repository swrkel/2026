<?php

namespace Modules\Suppliers\Utils;

use Modules\Suppliers\Entities\Supplier;

class SupplierCommunicationUtil
{
    public static function supplierDisplayName(Supplier $supplier): string
    {
        return trim($supplier->supplier_business_name ?: $supplier->name ?: ('Supplier #' . $supplier->id));
    }

    public static function communicationTypes(): array
    {
        return [
            'call' => __('suppliers::lang.call'),
            'email' => __('suppliers::lang.email'),
            'meeting' => __('suppliers::lang.meeting'),
            'whatsapp' => __('suppliers::lang.whatsapp'),
            'other' => __('suppliers::lang.other'),
        ];
    }
}
