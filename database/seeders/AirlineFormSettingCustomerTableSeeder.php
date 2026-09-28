<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AirlineFormSettingCustomerTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('airline_form_setting_customer')->delete();
        
        \DB::table('airline_form_setting_customer')->insert(array (
            0 => 
            array (
                'id' => 1,
                'created_by' => 38,
                'business_id' => 4,
                'location' => NULL,
                'name' => NULL,
                'vat_no' => 1,
                'credit_limit' => 1,
                'mobile' => 1,
                'address' => 1,
                'state' => 1,
                'tax_number' => 1,
                'confirm_password' => 1,
                'sub_customer' => 1,
                'passport_nic_no' => 1,
                'need_to_send_sms' => 1,
                'opening_balance' => NULL,
                'transaction_date' => NULL,
                'landline' => 1,
                'address_line_2' => 1,
                'country' => 1,
                'pay_term' => 1,
                'email' => 1,
                'vehicle_no' => 1,
                'passport_nic_image' => 1,
                'credit_notification_type' => 1,
                'customer_group' => 1,
                'add_more_mobile_numbers' => 1,
                'assigned_to' => NULL,
                'city' => 1,
                'landmark' => '1',
                'password' => 1,
                'alternate_contact_number' => 1,
                'address_line_3' => '1',
                'signature' => 1,
                'created_at' => '2024-12-16 11:04:20',
                'updated_at' => '2024-12-16 11:04:20',
            ),
            1 => 
            array (
                'id' => 2,
                'created_by' => 7,
                'business_id' => 4,
                'location' => NULL,
                'name' => NULL,
                'vat_no' => 1,
                'credit_limit' => 1,
                'mobile' => 1,
                'address' => 1,
                'state' => 1,
                'tax_number' => 1,
                'confirm_password' => 1,
                'sub_customer' => 1,
                'passport_nic_no' => 1,
                'need_to_send_sms' => 1,
                'opening_balance' => NULL,
                'transaction_date' => NULL,
                'landline' => 1,
                'address_line_2' => 1,
                'country' => 1,
                'pay_term' => 1,
                'email' => 1,
                'vehicle_no' => 1,
                'passport_nic_image' => 1,
                'credit_notification_type' => 1,
                'customer_group' => 1,
                'add_more_mobile_numbers' => 1,
                'assigned_to' => NULL,
                'city' => 1,
                'landmark' => '1',
                'password' => 1,
                'alternate_contact_number' => 1,
                'address_line_3' => '1',
                'signature' => 1,
                'created_at' => '2024-12-19 11:54:44',
                'updated_at' => '2024-12-19 11:54:44',
            ),
        ));
        
        
    }
}