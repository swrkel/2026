<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CancelChequeTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('cancel_cheque')->delete();
        
        
        
    }
}