<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MemberGroupsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('member_groups')->delete();
        
        
        
    }
}