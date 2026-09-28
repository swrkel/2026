<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SavedRepliesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('saved_replies')->delete();
        
        
        
    }
}