<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReimbursementsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('reimbursements')->delete();
        
        
        
    }
}