<?php

namespace Modules\Suppliers\Services\Profile;

use Modules\Suppliers\Entities\Supplier;

class SupplierProfileService
{
    public function getSummary(Supplier $supplier): array
    {
        return [
            'supplier_no' => $supplier->contact_id,
            'name' => $supplier->name,
            'business_name' => $supplier->supplier_business_name,
            'mobile' => $supplier->mobile,
            'email' => $supplier->email,
            'tax_number' => $supplier->tax_number,
            'created_at' => optional($supplier->created_at)->format('Y-m-d H:i'),
            'updated_at' => optional($supplier->updated_at)->format('Y-m-d H:i'),
        ];
    }

    public function getAddress(Supplier $supplier): array
    {
        return [
            'address_line_1' => $supplier->address_line_1,
            'address_line_2' => $supplier->address_line_2,
            'city' => $supplier->city,
            'state' => $supplier->state,
            'country' => $supplier->country,
            'zip_code' => $supplier->zip_code,
            'landmark' => $supplier->landmark,
        ];
    }

    public function getFinancial(Supplier $supplier): array
    {
        return [
            'pay_term_number' => $supplier->pay_term_number,
            'pay_term_type' => $supplier->pay_term_type,
            'credit_limit' => $supplier->credit_limit,
            'opening_balance' => $supplier->opening_balance,
            'balance' => $supplier->balance,
        ];
    }
}
