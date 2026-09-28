<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StockTransferRequestsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('stock_transfer_requests')->delete();
        
        
        
    }
}