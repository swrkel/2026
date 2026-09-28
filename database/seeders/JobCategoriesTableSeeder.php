<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class JobCategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('job_categories')->delete();
        
        
        
    }
}