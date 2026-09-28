<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Mpcs21cFormSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('mpcs_21c_form_settings')->delete();
        
        \DB::table('mpcs_21c_form_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'date' => '2025-04-14',
                'time' => '18:34:00',
                'starting_number' => '4',
                'ref_pre_form_number' => '5',
                'rec_sec_prev_day_amt' => '5.00',
                'rec_sec_opn_stock_amt' => '5.00',
                'issue_section_previous_day_amount' => '5.00',
                'manager_name' => 'ASD',
                'categories' => '{"4":{"previous_day":"1000","opening_stock":"15000","total_issues":"4000"},"5":{"previous_day":"2000","opening_stock":"18000","total_issues":"5000"},"6":{"previous_day":"3000","opening_stock":"13000","total_issues":"6000"},"7":{"previous_day":"4000","opening_stock":"21000","total_issues":"7000"},"8":{"previous_day":"5000","opening_stock":"3000","total_issues":"8000"},"9":{"previous_day":"6000","opening_stock":"8700","total_issues":"9000"}}',
                'pumps' => NULL,
                'meters' => NULL,
                'created_by' => NULL,
                'created_at' => '2025-04-14 14:05:29',
                'updated_at' => '2025-04-14 14:05:29',
            ),
        ));
        
        
    }
}