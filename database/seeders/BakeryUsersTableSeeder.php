<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BakeryUsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('bakery_users')->delete();
        
        
        
    }
}