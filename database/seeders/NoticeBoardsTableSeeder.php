<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NoticeBoardsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('notice_boards')->delete();
        
        
        
    }
}