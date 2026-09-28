<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UnitsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('units')->delete();
        
        \DB::table('units')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 2,
                'actual_name' => 'Pieces',
            'short_name' => 'Pc(s)',
                'allow_decimal' => 0,
                'base_unit_id' => NULL,
                'base_unit_multiplier' => NULL,
                'is_property' => 0,
                'show_in_add_product_unit' => 0,
                'show_in_add_pos_unit' => 0,
                'show_in_add_sale_unit' => 0,
                'show_in_add_project_unit' => 0,
                'show_in_sell_land_block_unit' => 0,
                'created_by' => 2,
                'deleted_at' => NULL,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 3,
                'actual_name' => 'Pieces',
            'short_name' => 'Pc(s)',
                'allow_decimal' => 0,
                'base_unit_id' => NULL,
                'base_unit_multiplier' => NULL,
                'is_property' => 0,
                'show_in_add_product_unit' => 0,
                'show_in_add_pos_unit' => 0,
                'show_in_add_sale_unit' => 0,
                'show_in_add_project_unit' => 0,
                'show_in_sell_land_block_unit' => 0,
                'created_by' => 3,
                'deleted_at' => NULL,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 4,
                'actual_name' => 'Pieces',
            'short_name' => 'Pc(s)',
                'allow_decimal' => 0,
                'base_unit_id' => NULL,
                'base_unit_multiplier' => NULL,
                'is_property' => 0,
                'show_in_add_product_unit' => 0,
                'show_in_add_pos_unit' => 0,
                'show_in_add_sale_unit' => 0,
                'show_in_add_project_unit' => 0,
                'show_in_sell_land_block_unit' => 0,
                'created_by' => 7,
                'deleted_at' => NULL,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 4,
                'actual_name' => 'Ltrs',
                'short_name' => 'Ltrs',
                'allow_decimal' => 1,
                'base_unit_id' => NULL,
                'base_unit_multiplier' => NULL,
                'is_property' => 0,
                'show_in_add_product_unit' => 0,
                'show_in_add_pos_unit' => 0,
                'show_in_add_sale_unit' => 0,
                'show_in_add_project_unit' => 0,
                'show_in_sell_land_block_unit' => 0,
                'created_by' => 7,
                'deleted_at' => NULL,
                'created_at' => '2024-02-27 02:30:04',
                'updated_at' => '2024-02-27 02:30:04',
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 4,
                'actual_name' => 'Nos',
                'short_name' => 'Nos',
                'allow_decimal' => 0,
                'base_unit_id' => NULL,
                'base_unit_multiplier' => NULL,
                'is_property' => 0,
                'show_in_add_product_unit' => 0,
                'show_in_add_pos_unit' => 0,
                'show_in_add_sale_unit' => 0,
                'show_in_add_project_unit' => 0,
                'show_in_sell_land_block_unit' => 0,
                'created_by' => 7,
                'deleted_at' => NULL,
                'created_at' => '2024-02-27 02:30:18',
                'updated_at' => '2024-02-27 02:30:18',
            ),
        ));
        
        
    }
}