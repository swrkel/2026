<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MpcsFormSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mpcs_form_settings')->delete();
        
        \DB::table('mpcs_form_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'F9C_sn' => 1,
                'F9C_tdate' => NULL,
                'F159ABC_form_sn' => 1,
                'F159ABC_form_tdate' => NULL,
                'F159ABC_first_day_after_stock_taking' => 0,
                'F159ABC_first_day_of_next_month' => 0,
                'F159ABC_first_day_of_next_month_selected' => NULL,
                'F16A_form_sn' => 1,
                'F16A_form_tdate' => NULL,
                'F21C_form_sn' => 1,
                'F21C_form_tdate' => NULL,
                'F14_form_sn' => 2,
                'F14_form_tdate' => NULL,
                'F17_form_sn' => 3,
                'F17_form_tdate' => NULL,
                'F20_form_sn' => 2,
                'F20_form_tdate' => NULL,
                'F21_form_sn' => 3,
                'F21_form_tdate' => NULL,
                'F22_form_sn' => 223,
                'F22_form_tdate' => '2025-01-01',
                'F22_no_of_product_per_page' => 10,
                'current_stock_aa_onstocktaking' => 0,
                'f16a_first_day_after_stock_taking' => 0,
                'f16a_first_day_of_next_month' => 0,
                'f16a_first_day_of_next_month_selected' => NULL,
                'created_at' => '2024-02-26 15:39:48',
                'updated_at' => '2025-05-14 06:32:36',
                'F16A_total_pp' => '0.00',
                'F16A_total_sp' => '0.00',
                'F21C_first_day_after_stock_taking' => 0,
                'F21C_first_day_of_next_month_selected' => NULL,
                'F21C_first_day_of_next_month' => 0,
                'F9C_first_day_after_stock_taking' => 0,
                'F9C_first_day_of_next_month_selected' => NULL,
                'F9C_first_day_of_next_month' => 0,
            ),
        ));
        
        
    }
}