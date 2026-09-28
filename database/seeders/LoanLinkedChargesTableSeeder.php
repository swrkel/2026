<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LoanLinkedChargesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('loan_linked_charges')->delete();
        
        
        
    }
}