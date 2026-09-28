<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WorkOrderItemsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('work_order_items')->delete();
        
        
        
    }
}