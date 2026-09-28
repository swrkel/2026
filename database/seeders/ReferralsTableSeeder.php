<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReferralsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('referrals')->delete();
        
        \DB::table('referrals')->insert(array (
            0 => 
            array (
                'id' => 1,
                'referral_code' => '0',
                'model_type' => 'patient',
                'resource_id' => 2,
                'package_id' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            1 => 
            array (
                'id' => 2,
                'referral_code' => '0',
                'model_type' => 'patient',
                'resource_id' => 3,
                'package_id' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
        ));
        
        
    }
}