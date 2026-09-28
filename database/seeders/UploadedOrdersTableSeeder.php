<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UploadedOrdersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('uploaded_orders')->delete();
        
        
        
    }
}