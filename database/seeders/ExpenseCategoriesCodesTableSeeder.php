<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ExpenseCategoriesCodesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('expense_categories_codes')->delete();
        
        \DB::table('expense_categories_codes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'prefix' => 'EXP',
                'date' => '2024-02-26 15:49:00',
                'starting_no' => 1,
                'created_by' => 7,
                'created_at' => '2024-02-26 15:49:41',
                'updated_at' => '2024-02-26 15:49:41',
            ),
        ));
        
        
    }
}