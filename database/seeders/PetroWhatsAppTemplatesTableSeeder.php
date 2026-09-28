<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PetroWhatsAppTemplatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('petro_whats_app_templates')->delete();
        
        \DB::table('petro_whats_app_templates')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 2,
                'template_for' => 'settlements',
                'sms_body' => 'Daily Settlement
Date: {settlement_date}
Settlement No: {settlement_no}
Pumper: {pump_operator_name}
Pumps: {settlement_pumps}
Total Sale: {total_sale_amount}
Total Cash: {total_cash}
Total cards: {total_cards}
Total Credit Sale: {total_credit_sales}
Total Shortage:  {total_short}
Total Excess: {total_excess}
Total Expenses: {total_expenses}',
                'auto_send_sms' => 1,
                'created_at' => '2024-12-13 16:08:04',
                'updated_at' => '2024-12-13 16:08:04',
                'phone_nos' => '94768366178',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 2,
                'template_for' => 'edit_settlements',
                'sms_body' => 'Settlement Edited : {settlement_no}
Date: {editted_date}
Edited by: {user_editted}

Original Detals
{original_details}
----------------------

Editted Details
{editted_details}',
                'auto_send_sms' => 1,
                'created_at' => '2024-12-13 16:08:04',
                'updated_at' => '2024-12-13 16:08:04',
                'phone_nos' => '94768366178',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 2,
                'template_for' => 'day_end_settlement',
                'sms_body' => 'Day End Settlements
Date: {date}
Pumpers worked: {pumpers_worked}
Pumps worked: {pumps}
Total Sale: {total_sale}
Total Cash: {total_cash},
Total Credit Sales: {total_credit_sales}
Total Shortage:  {total_short}
Total Excess: {total_excess}
Total Expenses: {total_expenses}',
                'auto_send_sms' => 1,
                'created_at' => '2024-12-13 16:08:04',
                'updated_at' => '2024-12-13 16:08:04',
                'phone_nos' => '94768366178',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 2,
                'template_for' => 'stock_and_dip_details',
                'sms_body' => '{date_entered},
{time_entered}, 
{dip_details}',
                'auto_send_sms' => 1,
                'created_at' => '2024-12-13 16:08:04',
                'updated_at' => '2024-12-13 16:08:04',
                'phone_nos' => '94768366178',
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 2,
                'template_for' => 'load_received',
                'sms_body' => '{date},
{load_details}',
                'auto_send_sms' => 1,
                'created_at' => '2024-12-13 16:08:04',
                'updated_at' => '2024-12-13 16:08:04',
                'phone_nos' => '94768366178',
            ),
            5 => 
            array (
                'id' => 6,
                'business_id' => 2,
                'template_for' => 'daily_collection',
                'sms_body' => '{date},{time}, {pump_operator}, {amount}

Date : {date},
Time : {time},
Pumper : {pump_operator},
Amount :{amount}',
                'auto_send_sms' => 1,
                'created_at' => '2024-12-13 16:08:04',
                'updated_at' => '2024-12-13 16:08:04',
                'phone_nos' => '94768366178',
            ),
            6 => 
            array (
                'id' => 7,
                'business_id' => 2,
                'template_for' => 'pumper_dashboard_cash_deposit',
                'sms_body' => NULL,
                'auto_send_sms' => 0,
                'created_at' => '2024-12-13 16:08:04',
                'updated_at' => '2024-12-13 16:08:04',
                'phone_nos' => NULL,
            ),
            7 => 
            array (
                'id' => 8,
                'business_id' => 2,
                'template_for' => 'pumper_dashboard_credit_sales',
                'sms_body' => NULL,
                'auto_send_sms' => 0,
                'created_at' => '2024-12-13 16:08:04',
                'updated_at' => '2024-12-13 16:08:04',
                'phone_nos' => NULL,
            ),
            8 => 
            array (
                'id' => 9,
                'business_id' => 2,
                'template_for' => 'pumper_dashboard_credit_sales_customer',
                'sms_body' => NULL,
                'auto_send_sms' => 0,
                'created_at' => '2024-12-13 16:08:04',
                'updated_at' => '2024-12-13 16:08:04',
                'phone_nos' => NULL,
            ),
        ));
        
        
    }
}