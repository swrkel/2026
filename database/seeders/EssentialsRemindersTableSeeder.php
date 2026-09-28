<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EssentialsRemindersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('essentials_reminders')->delete();
        
        
        
    }
}