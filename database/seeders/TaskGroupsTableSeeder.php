<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TaskGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('task_groups')->delete();
        
        
        
    }
}