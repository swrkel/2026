<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BusinessLocationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('business_locations')->delete();
        
        \DB::table('business_locations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 1,
                'location_id' => 'BL0001',
                'name' => 'SYZYGY',
                'landmark' => '1',
                'address_1' => NULL,
                'address_2' => NULL,
                'address_3' => NULL,
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Malabe',
                'zip_code' => '10115',
                'latitude' => NULL,
                'longitude' => NULL,
                'timezone' => NULL,
                'location_access_type' => NULL,
                'invoice_scheme_id' => 1,
                'invoice_layout_id' => 1,
                'selling_price_group_id' => NULL,
                'print_receipt_on_invoice' => 1,
                'receipt_printer_type' => 'browser',
                'printer_id' => NULL,
                'mobile' => 'Mobile',
                'alternate_number' => 'Alternate',
                'email' => NULL,
                'website' => NULL,
                'default_payment_accounts' => '{"cash":{"is_enabled":"1","account":1},"card":{"is_enabled":"1","account":9},"cheque":{"is_enabled":"1","account":3},"direct_bank_deposit":{"is_enabled":"1","account":2},"bank_transfer":{"is_enabled":"1","account":2}}',
                'is_active' => 1,
                'custom_field1' => NULL,
                'custom_field2' => NULL,
                'custom_field3' => NULL,
                'custom_field4' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2024-06-05 14:11:21',
                'currency_id' => 111,
                'district' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 4,
                'location_id' => 'BL0001',
                'name' => '130',
                'landmark' => 'No.296/3, High Level Road, Makumbura, Pannipitiya',
                'address_1' => NULL,
                'address_2' => NULL,
                'address_3' => NULL,
                'country' => 'SL',
                'state' => 'WP',
                'city' => 'Pannipitiya',
                'zip_code' => '0',
                'latitude' => NULL,
                'longitude' => NULL,
                'timezone' => NULL,
                'location_access_type' => NULL,
                'invoice_scheme_id' => 4,
                'invoice_layout_id' => 4,
                'selling_price_group_id' => NULL,
                'print_receipt_on_invoice' => 1,
                'receipt_printer_type' => 'browser',
                'printer_id' => NULL,
                'mobile' => '',
                'alternate_number' => '011 2178303',
                'email' => '',
                'website' => 'https://www.syzygylk.com',
                'default_payment_accounts' => '{"cash":{"is_enabled":"1","is_purchase_enabled":"1","is_sale_enabled":"1","is_expense_enabled":"1","is_purchase_return_enabled":"1","is_sale_return_enabled":"1","is_custom":"0","account":"62"},"Bank":{"is_enabled":"1","is_purchase_enabled":"1","is_sale_enabled":"0","is_expense_enabled":"1","is_purchase_return_enabled":"0","is_sale_return_enabled":"0","is_custom":"1","account":"61"},"card":{"is_enabled":"1","is_purchase_enabled":"0","is_sale_enabled":"1","is_expense_enabled":"0","is_purchase_return_enabled":"0","is_sale_return_enabled":"1","is_custom":"0","account":"64"},"cheque":{"is_enabled":"1","is_purchase_enabled":"0","is_sale_enabled":"1","is_expense_enabled":"0","is_purchase_return_enabled":"0","is_sale_return_enabled":"1","is_custom":"0","account":"63"},"direct_bank_deposit":{"is_enabled":"1","is_purchase_enabled":"1","is_sale_enabled":"1","is_expense_enabled":"1","is_purchase_return_enabled":"1","is_sale_return_enabled":"1","is_custom":"0","account":"61"},"bank_transfer":{"is_enabled":"1","is_purchase_enabled":"1","is_sale_enabled":"1","is_expense_enabled":"1","is_purchase_return_enabled":"1","is_sale_return_enabled":"1","is_custom":"0","account":"61"},"credit_sale":{"is_enabled":"1","is_purchase_enabled":"0","is_sale_enabled":"1","is_expense_enabled":"0","is_purchase_return_enabled":"0","is_sale_return_enabled":"1","is_custom":"0","account":"70"},"own_cards":{"is_enabled":"1","is_purchase_enabled":"1","is_sale_enabled":"0","is_expense_enabled":"1","is_purchase_return_enabled":"1","is_sale_return_enabled":"0","is_custom":"0","account":"75"},"pre_payments":{"is_enabled":"0","is_purchase_enabled":"1","is_sale_enabled":"1","is_expense_enabled":"1","is_purchase_return_enabled":"1","is_sale_return_enabled":"1","is_custom":"0","account":81}}',
                'is_active' => 1,
                'custom_field1' => NULL,
                'custom_field2' => NULL,
                'custom_field3' => NULL,
                'custom_field4' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2025-05-28 11:46:48',
                'currency_id' => 111,
                'district' => NULL,
            ),
        ));
        
        
    }
}