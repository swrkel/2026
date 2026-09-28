<?php

namespace Modules\Suppliers\Utils;

class SupplierDocumentUtil
{
    public static function allowedDocumentTypes(): array
    {
        return [
            'br' => __('suppliers::lang.business_registration'),
            'tax' => __('suppliers::lang.tax_document'),
            'agreement' => __('suppliers::lang.agreement'),
            'bank' => __('suppliers::lang.bank_document'),
            'other' => __('suppliers::lang.other'),
        ];
    }
}
