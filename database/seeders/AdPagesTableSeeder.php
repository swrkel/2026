<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdPagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('ad_pages')->delete();
        
        \DB::table('ad_pages')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Landing page',
                'code' => 'landing_page',
                'created_at' => '2022-08-29 22:24:38',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'Sign in page',
                'code' => 'sigin_in_page',
                'created_at' => '2022-08-29 22:24:38',
                'updated_at' => '0000-00-00 00:00:00',
            ),
        ));
        
        
    }
}