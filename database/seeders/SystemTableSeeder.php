<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SystemTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('system')->delete();
        
        \DB::table('system')->insert(array (
            0 => 
            array (
                'id' => 1,
                'key' => 'db_version',
                'value' => '2.19',
            ),
            1 => 
            array (
                'id' => 2,
                'key' => 'default_business_active_status',
                'value' => '1',
            ),
            2 => 
            array (
                'id' => 3,
                'key' => 'superadmin_version',
                'value' => '1.6',
            ),
            3 => 
            array (
                'id' => 4,
                'key' => 'app_currency_id',
                'value' => '2',
            ),
            4 => 
            array (
                'id' => 5,
                'key' => 'invoice_business_name',
                'value' => 'SYZYGY EzyPetRo',
            ),
            5 => 
            array (
                'id' => 6,
                'key' => 'invoice_business_landmark',
                'value' => 'Landmark',
            ),
            6 => 
            array (
                'id' => 7,
                'key' => 'invoice_business_zip',
                'value' => 'Zip',
            ),
            7 => 
            array (
                'id' => 8,
                'key' => 'invoice_business_state',
                'value' => 'State',
            ),
            8 => 
            array (
                'id' => 9,
                'key' => 'invoice_business_city',
                'value' => 'City',
            ),
            9 => 
            array (
                'id' => 10,
                'key' => 'invoice_business_country',
                'value' => 'Country',
            ),
            10 => 
            array (
                'id' => 11,
                'key' => 'email',
                'value' => 'support@syzygyfirst.systems',
            ),
            11 => 
            array (
                'id' => 12,
                'key' => 'package_expiry_alert_days',
                'value' => '5',
            ),
            12 => 
            array (
                'id' => 13,
                'key' => 'enable_business_based_username',
                'value' => '1',
            ),
            13 => 
            array (
                'id' => 14,
                'key' => 'manufacturing_version',
                'value' => '1.2',
            ),
            14 => 
            array (
                'id' => 15,
                'key' => 'superadmin_register_tc',
                'value' => NULL,
            ),
            15 => 
            array (
                'id' => 16,
                'key' => 'welcome_email_subject',
                'value' => 'Email Subject',
            ),
            16 => 
            array (
                'id' => 17,
                'key' => 'welcome_email_body',
                'value' => '<table style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0; background: #ffffff;" role="presentation">
<tbody>
<tr>
<td style="padding: 0;" align="center">
<table style="width: 602px; border-collapse: collapse; border: 1px solid #cccccc; border-spacing: 0; text-align: left;" role="presentation">
<tbody>
<tr>
<td style="padding: 40px 0 30px 0; background: #70bbd9;" align="center"><img style="height: auto; display: block;" src="https://syzygy.healthcare/ezypetro logo.png" alt="" width="300" /></td>
</tr>
<tr>
<td style="padding: 36px 30px 42px 30px;">
<table style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0;" role="presentation">
<tbody>
<tr>
<td style="padding: 0 0 36px 0; color: #153643;">
<p style="margin: 0 0 12px 0; font-size: 16px; line-height: 24px; font-family: Arial,sans-serif;">Dear {first_name}, {last_name}<br />We, SYZYGY Consultancy Group warmly welcome you to our SYZYGY My Health System to manage your Health and records.</p>
<p style="margin: 0; font-size: 16px; line-height: 24px; font-family: Arial,sans-serif;">Hope, that you would get all what you need in the Right &amp; Smart way to Manage your health and health Records with SYZYGY Software.</p>
<h4 style="font-family: Arial,sans-serif; text-align: center;">MyHealth Code: {username}</h4>
<p style="font-family: Arial,sans-serif;">Please send your payment slip image by email to <a href="mailto:{system_email}">{system_email}</a> for the system admin to activate your account, after verifying the payment.</p>
<p style="font-family: Arial,sans-serif;">Then please login to the System in {system_url}</p>
<p style="font-family: Arial,sans-serif;">For any help, please contact us. <br />We wish you a long Healthy Life.</p>
<p style="font-family: Arial,sans-serif;">Best Regards,<br />SYZYGY Management</p>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>',
            ),
            17 => 
            array (
                'id' => 18,
                'key' => 'superadmin_enable_register_tc',
                'value' => '0',
            ),
            18 => 
            array (
                'id' => 19,
                'key' => 'allow_email_settings_to_businesses',
                'value' => '0',
            ),
            19 => 
            array (
                'id' => 20,
                'key' => 'enable_new_business_registration_notification',
                'value' => '1',
            ),
            20 => 
            array (
                'id' => 21,
                'key' => 'enable_new_subscription_notification',
                'value' => '1',
            ),
            21 => 
            array (
                'id' => 22,
                'key' => 'enable_welcome_email',
                'value' => '1',
            ),
            22 => 
            array (
                'id' => 23,
                'key' => 'enable_lang_btn_login_page',
                'value' => '0',
            ),
            23 => 
            array (
                'id' => 24,
                'key' => 'enable_pricing_btn_login_page',
                'value' => '0',
            ),
            24 => 
            array (
                'id' => 25,
                'key' => 'enable_register_btn_login_page',
                'value' => '0',
            ),
            25 => 
            array (
                'id' => 26,
                'key' => 'patient_prefix',
                'value' => 'PT-',
            ),
            26 => 
            array (
                'id' => 27,
                'key' => 'patient_code_start_from',
                'value' => '0012',
            ),
            27 => 
            array (
                'id' => 28,
                'key' => 'hospital_prefix',
                'value' => 'HO-',
            ),
            28 => 
            array (
                'id' => 29,
                'key' => 'hospital_code_start_from',
                'value' => '0005',
            ),
            29 => 
            array (
                'id' => 30,
                'key' => 'laboratory_prefix',
                'value' => 'LB-',
            ),
            30 => 
            array (
                'id' => 31,
                'key' => 'laboratory_code_start_from',
                'value' => '0007',
            ),
            31 => 
            array (
                'id' => 32,
                'key' => 'pharmacy_prefix',
                'value' => 'PH-',
            ),
            32 => 
            array (
                'id' => 33,
                'key' => 'pharmacy_code_start_from',
                'value' => '0003',
            ),
            33 => 
            array (
                'id' => 34,
                'key' => 'upload_image_width',
                'value' => '680',
            ),
            34 => 
            array (
                'id' => 35,
                'key' => 'upload_image_height',
                'value' => '900',
            ),
            35 => 
            array (
                'id' => 37,
                'key' => 'default_number_of_customers',
                'value' => '30',
            ),
            36 => 
            array (
                'id' => 38,
                'key' => 'enable_customer_login',
                'value' => '0',
            ),
            37 => 
            array (
                'id' => 39,
                'key' => 'footer_top_margin',
                'value' => NULL,
            ),
            38 => 
            array (
                'id' => 40,
                'key' => 'admin_invoice_footer',
                'value' => 'Thank You! Come Again.

This Software is developed by SYZYGY Technologies.   
Contact: 077 4055 434 / 071 1616 192',
            ),
            39 => 
            array (
                'id' => 41,
                'key' => 'sms_on_password_change',
                'value' => NULL,
            ),
            40 => 
            array (
                'id' => 42,
                'key' => 'company_number_prefix',
                'value' => 'RA-',
            ),
            41 => 
            array (
                'id' => 43,
                'key' => 'company_starting_number',
                'value' => '7',
            ),
            42 => 
            array (
                'id' => 44,
                'key' => 'customer_welcome_email_body',
                'value' => NULL,
            ),
            43 => 
            array (
                'id' => 45,
                'key' => 'customer_welcome_email_subject',
                'value' => NULL,
            ),
            44 => 
            array (
                'id' => 46,
                'key' => 'enable_customer_welcome_email',
                'value' => '0',
            ),
            45 => 
            array (
                'id' => 47,
                'key' => 'default_payment_accounts',
                'value' => '{"cash":{"is_enabled":"1","account":"1"},"card":{"is_enabled":"1","account":"9"},"cheque":{"is_enabled":"1","account":"3"},"direct_bank_deposit":{"is_enabled":"1","account":"2"},"bank_transfer":{"is_enabled":"1","account":"2"}}',
            ),
            46 => 
            array (
                'id' => 48,
                'key' => 'upload_image_quality',
                'value' => '100',
            ),
            47 => 
            array (
                'id' => 49,
                'key' => 'enable_member_register_btn_login_page',
                'value' => '0',
            ),
            48 => 
            array (
                'id' => 50,
                'key' => 'enable_employee_login',
                'value' => '0',
            ),
            49 => 
            array (
                'id' => 51,
                'key' => 'visitor_business_name',
                'value' => 'V',
            ),
            50 => 
            array (
                'id' => 52,
                'key' => 'visitor_site_url',
                'value' => 'Visitor Site Url',
            ),
            51 => 
            array (
                'id' => 53,
                'key' => 'visitor_site_name',
                'value' => 'Visitor Site Name',
            ),
            52 => 
            array (
                'id' => 54,
                'key' => 'admin_msg_visitor_qr',
                'value' => NULL,
            ),
            53 => 
            array (
                'id' => 55,
                'key' => 'visitor_code_color',
                'value' => NULL,
            ),
            54 => 
            array (
                'id' => 56,
                'key' => 'not_enalbed_module_user_font_size',
                'value' => '14',
            ),
            55 => 
            array (
                'id' => 57,
                'key' => 'not_enalbed_module_user_color',
                'value' => '#C40202',
            ),
            56 => 
            array (
                'id' => 58,
                'key' => 'not_enalbed_module_user_message',
                'value' => 'You have not subscribed to this Module. If need to enable, Please contact SYZYGY on 077 4055 434 or 071 1616 192. Thank You.',
            ),
            57 => 
            array (
                'id' => 59,
                'key' => 'welcome_msg_body',
                'value' => NULL,
            ),
            58 => 
            array (
                'id' => 60,
                'key' => 'visitor_welcome_email_subject',
                'value' => NULL,
            ),
            59 => 
            array (
                'id' => 61,
                'key' => 'visitor_welcome_email_body',
                'value' => NULL,
            ),
            60 => 
            array (
                'id' => 62,
                'key' => 'business_or_entity',
                'value' => 'buisness',
            ),
            61 => 
            array (
                'id' => 63,
                'key' => 'enable_visitor_register_btn_login_page',
                'value' => '0',
            ),
            62 => 
            array (
                'id' => 64,
                'key' => 'enable_welcome_msg',
                'value' => '0',
            ),
            63 => 
            array (
                'id' => 65,
                'key' => 'enable_individual_register_btn_login_page',
                'value' => '0',
            ),
            64 => 
            array (
                'id' => 66,
                'key' => 'enable_visitor_welcome_email',
                'value' => '0',
            ),
            65 => 
            array (
                'id' => 67,
                'key' => 'enable_admin_login',
                'value' => '1',
            ),
            66 => 
            array (
                'id' => 68,
                'key' => 'enable_member_login',
                'value' => '0',
            ),
            67 => 
            array (
                'id' => 69,
                'key' => 'enable_visitor_login',
                'value' => '0',
            ),
            68 => 
            array (
                'id' => 70,
                'key' => 'customer_supplier_security_deposit_current_liability_font_size',
                'value' => '14',
            ),
            69 => 
            array (
                'id' => 71,
                'key' => 'customer_supplier_security_deposit_current_liability_color',
                'value' => '#C40202',
            ),
            70 => 
            array (
                'id' => 72,
                'key' => 'customer_supplier_security_deposit_current_liability_message',
                'value' => 'Accounting Module Not Subscribed.',
            ),
            71 => 
            array (
                'id' => 73,
                'key' => 'patient_register_success_msg',
                'value' => NULL,
            ),
            72 => 
            array (
                'id' => 74,
                'key' => 'patient_register_success_title',
                'value' => NULL,
            ),
            73 => 
            array (
                'id' => 75,
                'key' => 'new_subscription_email_subject',
                'value' => NULL,
            ),
            74 => 
            array (
                'id' => 76,
                'key' => 'new_subscription_email_body',
                'value' => NULL,
            ),
            75 => 
            array (
                'id' => 77,
                'key' => 'customer_secrity_deposit_current_liability_checkbox',
                'value' => '1',
            ),
            76 => 
            array (
                'id' => 78,
                'key' => 'supplier_secrity_deposit_current_liability_checkbox',
                'value' => '0',
            ),
            77 => 
            array (
                'id' => 79,
                'key' => 'general_message_pump_operator_dashbaord',
                'value' => '0',
            ),
            78 => 
            array (
                'id' => 80,
                'key' => 'enable_patient_register_btn_login_page',
                'value' => '0',
            ),
            79 => 
            array (
                'id' => 81,
                'key' => 'show_referrals_in_register_page',
                'value' => '["my_health"]',
            ),
            80 => 
            array (
                'id' => 82,
                'key' => 'show_give_away_gift_in_register_page',
                'value' => '[]',
            ),
            81 => 
            array (
                'id' => 83,
                'key' => 'company_register_success_title',
                'value' => NULL,
            ),
            82 => 
            array (
                'id' => 84,
                'key' => 'company_register_success_msg',
                'value' => NULL,
            ),
            83 => 
            array (
                'id' => 85,
                'key' => 'customer_register_success_title',
                'value' => NULL,
            ),
            84 => 
            array (
                'id' => 86,
                'key' => 'customer_register_success_msg',
                'value' => NULL,
            ),
            85 => 
            array (
                'id' => 87,
                'key' => 'visitor_register_success_title',
                'value' => NULL,
            ),
            86 => 
            array (
                'id' => 88,
                'key' => 'visitor_register_success_msg',
                'value' => NULL,
            ),
            87 => 
            array (
                'id' => 89,
                'key' => 'member_register_success_title',
                'value' => NULL,
            ),
            88 => 
            array (
                'id' => 90,
                'key' => 'member_register_success_msg',
                'value' => NULL,
            ),
            89 => 
            array (
                'id' => 91,
                'key' => 'enable_login_banner_image',
                'value' => '0',
            ),
            90 => 
            array (
                'id' => 92,
                'key' => 'enable_login_banner_html',
                'value' => '0',
            ),
            91 => 
            array (
                'id' => 93,
                'key' => 'login_banner_html',
                'value' => NULL,
            ),
            92 => 
            array (
                'id' => 94,
                'key' => 'login_banner_image',
                'value' => NULL,
            ),
            93 => 
            array (
                'id' => 95,
                'key' => 'general_message_pump_operator_dashbaord_checkbox',
                'value' => '1',
            ),
            94 => 
            array (
                'id' => 96,
                'key' => 'PAY_ONLINE_CURRENCY_TYPE',
                'value' => '[]',
            ),
            95 => 
            array (
                'id' => 97,
                'key' => 'helpdesk_system_url',
                'value' => 'https://helpdeskpetro.ezypetro.xyz/',
            ),
            96 => 
            array (
                'id' => 98,
                'key' => 'create_individual_company_package',
                'value' => NULL,
            ),
            97 => 
            array (
                'id' => 99,
                'key' => 'new_subscription_email_subject_offline',
                'value' => NULL,
            ),
            98 => 
            array (
                'id' => 100,
                'key' => 'new_subscription_email_body_offline',
                'value' => NULL,
            ),
            99 => 
            array (
                'id' => 101,
                'key' => 'subscription_message_online_success_title',
                'value' => NULL,
            ),
            100 => 
            array (
                'id' => 102,
                'key' => 'subscription_message_online_success_msg',
                'value' => '<table style="border-collapse: collapse; border: 0; border-spacing: 0; background: #ffffff;" role="presentation">
<tbody>
<tr>
<td style="padding: 0;" align="center">
<table style="border-collapse: collapse; border: 1px solid #cccccc; border-spacing: 0; text-align: left;" role="presentation">
<tbody>
<tr>
<td style="padding: 40px 0 30px 0; background: #70bbd9;" align="center"><img style="height: auto; display: block;" src="https://syzygy.healthcare/ezypetro logo.png" alt="" width="300" /></td>
</tr>
<tr>
<td style="padding: 36px 30px 42px 30px;">
<table style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0;" role="presentation">
<tbody>
<tr>
<td style="padding: 0 0 36px 0; color: #153643;">
<p style="margin: 0 0 12px 0; font-size: 16px; line-height: 24px; font-family: Arial,sans-serif;">Dear {first_name}, {last_name}<br />We, SYZYGY Consultancy Group warmly welcome you to our SYZYGY My Health System to manage your Health and records.</p>
<p style="margin: 0; font-size: 16px; line-height: 24px; font-family: Arial,sans-serif;">Hope, that you would get all what you need in the Right &amp; Smart way to Manage your health and health Records with SYZYGY Software.</p>
<h4 style="font-family: Arial,sans-serif; text-align: center;">MyHealth Code: {username}</h4>
<p style="font-family: Arial,sans-serif;">Please send your payment slip image by email to <a href="mailto:{system_email}">{system_email}</a> for the system admin to activate your account, after verifying the payment.</p>
<p style="font-family: Arial,sans-serif;">Then please login to the System in {system_url}</p>
<p style="font-family: Arial,sans-serif;">For any help, please contact us. <br />We wish you a long Healthy Life.</p>
<p style="font-family: Arial,sans-serif;">Best Regards,<br />SYZYGY Management</p>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>',
            ),
            101 => 
            array (
                'id' => 103,
                'key' => 'subscription_message_offline_success_title',
                'value' => NULL,
            ),
            102 => 
            array (
                'id' => 104,
                'key' => 'subscription_message_offline_success_msg',
                'value' => '<table style="border-collapse: collapse; border: 0; border-spacing: 0; background: #ffffff;" role="presentation">
<tbody>
<tr>
<td style="padding: 0;" align="center">
<table style="border-collapse: collapse; border: 1px solid #cccccc; border-spacing: 0; text-align: left;" role="presentation">
<tbody>
<tr>
<td style="padding: 40px 0 30px 0; background: #70bbd9;" align="center"><img style="height: auto; display: block;" src="https://syzygy.healthcare/ezypetro logo.png" alt="" width="300" /></td>
</tr>
<tr>
<td style="padding: 36px 30px 42px 30px;">
<table style="width: 100%; border-collapse: collapse; border: 0; border-spacing: 0;" role="presentation">
<tbody>
<tr>
<td style="padding: 0 0 36px 0; color: #153643;">
<p style="margin: 0 0 12px 0; font-size: 16px; line-height: 24px; font-family: Arial,sans-serif;">Dear {first_name}, {last_name}<br />We, SYZYGY Consultancy Group warmly welcome you to our SYZYGY My Health System to manage your Health and records.</p>
<p style="margin: 0; font-size: 16px; line-height: 24px; font-family: Arial,sans-serif;">Hope, that you would get all what you need in the Right &amp; Smart way to Manage your health and health Records with SYZYGY Software.</p>
<h4 style="font-family: Arial,sans-serif; text-align: center;">MyHealth Code: {username}</h4>
<p style="font-family: Arial,sans-serif;">Please send your payment slip image by email to <a href="mailto:{system_email}">{system_email}</a> for the system admin to activate your account, after verifying the payment.</p>
<p style="font-family: Arial,sans-serif;">Then please login to the System in {system_url}</p>
<p style="font-family: Arial,sans-serif;">For any help, please contact us. <br />We wish you a long Healthy Life.</p>
<p style="font-family: Arial,sans-serif;">Best Regards,<br />SYZYGY Management</p>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>',
            ),
            103 => 
            array (
                'id' => 105,
                'key' => 'agent_register_success_title',
                'value' => NULL,
            ),
            104 => 
            array (
                'id' => 106,
                'key' => 'agent_register_success_msg',
                'value' => NULL,
            ),
            105 => 
            array (
                'id' => 107,
                'key' => 'agent_welcome_email_subject',
                'value' => NULL,
            ),
            106 => 
            array (
                'id' => 108,
                'key' => 'agent_welcome_email_body',
                'value' => NULL,
            ),
            107 => 
            array (
                'id' => 109,
                'key' => 'enable_agent_login',
                'value' => '0',
            ),
            108 => 
            array (
                'id' => 110,
                'key' => 'enable_agent_register_btn_login_page',
                'value' => '0',
            ),
            109 => 
            array (
                'id' => 111,
                'key' => 'general_message_petro_dashboard_checkbox',
                'value' => '0',
            ),
            110 => 
            array (
                'id' => 112,
                'key' => 'general_message_tank_management_checkbox',
                'value' => '0',
            ),
            111 => 
            array (
                'id' => 113,
                'key' => 'general_message_pump_management_checkbox',
                'value' => '0',
            ),
            112 => 
            array (
                'id' => 114,
                'key' => 'general_message_pumper_management_checkbox',
                'value' => '0',
            ),
            113 => 
            array (
                'id' => 115,
                'key' => 'general_message_daily_collection_checkbox',
                'value' => '0',
            ),
            114 => 
            array (
                'id' => 116,
                'key' => 'general_message_settlement_checkbox',
                'value' => '0',
            ),
            115 => 
            array (
                'id' => 117,
                'key' => 'general_message_list_settlement_checkbox',
                'value' => '0',
            ),
            116 => 
            array (
                'id' => 118,
                'key' => 'general_message_dip_management_checkbox',
                'value' => '0',
            ),
            117 => 
            array (
                'id' => 119,
                'key' => 'main_page_refresh_interval_minute',
                'value' => '5',
            ),
            118 => 
            array (
                'id' => 120,
                'key' => 'app_footer',
                'value' => 'SYZYGY EzyPetRo International- V12.80 | Copyright © 2024 All rights reserved.',
            ),
            119 => 
            array (
                'id' => 121,
                'key' => 'admin_reports_footer',
                'value' => 'This Software is developed by SYZYGY Technologies.   
Contact: 077 4055 434 / 071 1616 192',
            ),
            120 => 
            array (
                'id' => 122,
                'key' => 'tax_label_1',
                'value' => 'VAT',
            ),
            121 => 
            array (
                'id' => 123,
                'key' => 'tax_number_1',
                'value' => '1000928728-7000',
            ),
            122 => 
            array (
                'id' => 124,
                'key' => 'tax_label_2',
                'value' => NULL,
            ),
            123 => 
            array (
                'id' => 125,
                'key' => 'tax_number_2',
                'value' => NULL,
            ),
            124 => 
            array (
                'id' => 126,
                'key' => 'enable_landing_page',
                'value' => '0',
            ),
            125 => 
            array (
                'id' => 127,
                'key' => 'enable_repair_btn_login_page',
                'value' => '0',
            ),
            126 => 
            array (
                'id' => 128,
                'key' => 'enable_inline_tax',
                'value' => '1',
            ),
            127 => 
            array (
                'id' => 130,
                'key' => 'sms_on_verification',
                'value' => 'Your\'s verification code is {CODE} ',
            ),
        ));
        
        
    }
}