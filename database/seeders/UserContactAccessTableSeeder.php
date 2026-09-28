<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserContactAccessTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('user_contact_access')->delete();
        
        
        
    }
}