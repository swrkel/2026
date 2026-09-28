<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('permissions')->delete();
        
        \DB::table('permissions')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'profit_loss_report.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:41',
                'updated_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'direct_sell.access',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:41',
                'updated_at' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'product.opening_stock',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'crud_all_bookings',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            4 => 
            array (
                'id' => 5,
                'name' => 'crud_own_bookings',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            5 => 
            array (
                'id' => 6,
                'name' => 'access_default_selling_price',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            6 => 
            array (
                'id' => 7,
                'name' => 'purchase.payments',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            7 => 
            array (
                'id' => 8,
                'name' => 'sell.payments',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            8 => 
            array (
                'id' => 9,
                'name' => 'edit_product_price_from_sale_screen',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            9 => 
            array (
                'id' => 10,
                'name' => 'edit_product_discount_from_sale_screen',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            10 => 
            array (
                'id' => 11,
                'name' => 'roles.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            11 => 
            array (
                'id' => 12,
                'name' => 'roles.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            12 => 
            array (
                'id' => 13,
                'name' => 'roles.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            13 => 
            array (
                'id' => 14,
                'name' => 'roles.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            14 => 
            array (
                'id' => 15,
                'name' => 'account.access',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:42',
                'updated_at' => '2019-12-30 12:19:42',
            ),
            15 => 
            array (
                'id' => 16,
                'name' => 'discount.access',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => '2019-12-30 12:19:43',
            ),
            16 => 
            array (
                'id' => 17,
                'name' => 'view_purchase_price',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => '2019-12-30 12:19:43',
            ),
            17 => 
            array (
                'id' => 18,
                'name' => 'view_own_sell_only',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => '2019-12-30 12:19:43',
            ),
            18 => 
            array (
                'id' => 19,
                'name' => 'edit_product_discount_from_pos_screen',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => '2019-12-30 12:19:43',
            ),
            19 => 
            array (
                'id' => 20,
                'name' => 'edit_product_price_from_pos_screen',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => '2019-12-30 12:19:43',
            ),
            20 => 
            array (
                'id' => 21,
                'name' => 'access_shipping',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => '2019-12-30 12:19:43',
            ),
            21 => 
            array (
                'id' => 22,
                'name' => 'user.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            22 => 
            array (
                'id' => 23,
                'name' => 'user.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            23 => 
            array (
                'id' => 24,
                'name' => 'user.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            24 => 
            array (
                'id' => 25,
                'name' => 'user.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            25 => 
            array (
                'id' => 26,
                'name' => 'supplier.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            26 => 
            array (
                'id' => 27,
                'name' => 'supplier.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            27 => 
            array (
                'id' => 28,
                'name' => 'supplier.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            28 => 
            array (
                'id' => 29,
                'name' => 'supplier.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            29 => 
            array (
                'id' => 30,
                'name' => 'customer.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            30 => 
            array (
                'id' => 31,
                'name' => 'customer.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            31 => 
            array (
                'id' => 32,
                'name' => 'customer.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            32 => 
            array (
                'id' => 33,
                'name' => 'customer.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            33 => 
            array (
                'id' => 34,
                'name' => 'product.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            34 => 
            array (
                'id' => 35,
                'name' => 'product.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            35 => 
            array (
                'id' => 36,
                'name' => 'product.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            36 => 
            array (
                'id' => 37,
                'name' => 'product.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            37 => 
            array (
                'id' => 38,
                'name' => 'purchase.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            38 => 
            array (
                'id' => 39,
                'name' => 'purchase.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            39 => 
            array (
                'id' => 40,
                'name' => 'purchase.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            40 => 
            array (
                'id' => 41,
                'name' => 'purchase.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            41 => 
            array (
                'id' => 42,
                'name' => 'sell.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            42 => 
            array (
                'id' => 43,
                'name' => 'sell.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            43 => 
            array (
                'id' => 44,
                'name' => 'sell.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            44 => 
            array (
                'id' => 45,
                'name' => 'sell.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            45 => 
            array (
                'id' => 46,
                'name' => 'purchase_n_sell_report.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            46 => 
            array (
                'id' => 47,
                'name' => 'contacts_report.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            47 => 
            array (
                'id' => 48,
                'name' => 'stock_report.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            48 => 
            array (
                'id' => 49,
                'name' => 'tax_report.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            49 => 
            array (
                'id' => 50,
                'name' => 'trending_product_report.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            50 => 
            array (
                'id' => 51,
                'name' => 'register_report.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            51 => 
            array (
                'id' => 52,
                'name' => 'sales_representative.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            52 => 
            array (
                'id' => 53,
                'name' => 'expense_report.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            53 => 
            array (
                'id' => 54,
                'name' => 'business_settings.access',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            54 => 
            array (
                'id' => 55,
                'name' => 'barcode_settings.access',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            55 => 
            array (
                'id' => 56,
                'name' => 'invoice_settings.access',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            56 => 
            array (
                'id' => 57,
                'name' => 'brand.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            57 => 
            array (
                'id' => 58,
                'name' => 'brand.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            58 => 
            array (
                'id' => 59,
                'name' => 'brand.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            59 => 
            array (
                'id' => 60,
                'name' => 'brand.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            60 => 
            array (
                'id' => 61,
                'name' => 'tax_rate.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            61 => 
            array (
                'id' => 62,
                'name' => 'tax_rate.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            62 => 
            array (
                'id' => 63,
                'name' => 'tax_rate.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            63 => 
            array (
                'id' => 64,
                'name' => 'tax_rate.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            64 => 
            array (
                'id' => 65,
                'name' => 'unit.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            65 => 
            array (
                'id' => 66,
                'name' => 'unit.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            66 => 
            array (
                'id' => 67,
                'name' => 'unit.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            67 => 
            array (
                'id' => 68,
                'name' => 'unit.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            68 => 
            array (
                'id' => 69,
                'name' => 'category.view',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            69 => 
            array (
                'id' => 70,
                'name' => 'category.create',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            70 => 
            array (
                'id' => 71,
                'name' => 'category.update',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            71 => 
            array (
                'id' => 72,
                'name' => 'category.delete',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            72 => 
            array (
                'id' => 73,
                'name' => 'expense.access',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            73 => 
            array (
                'id' => 74,
                'name' => 'access_all_locations',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            74 => 
            array (
                'id' => 75,
                'name' => 'dashboard.data',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:19:43',
                'updated_at' => NULL,
            ),
            75 => 
            array (
                'id' => 76,
                'name' => 'location.1',
                'guard_name' => 'web',
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
            ),
            76 => 
            array (
                'id' => 77,
                'name' => 'manufacturing.access_recipe',
                'guard_name' => 'web',
                'created_at' => '2019-12-31 06:09:04',
                'updated_at' => '2019-12-31 06:09:04',
            ),
            77 => 
            array (
                'id' => 78,
                'name' => 'manufacturing.access_production',
                'guard_name' => 'web',
                'created_at' => '2019-12-31 06:09:04',
                'updated_at' => '2019-12-31 06:09:04',
            ),
            78 => 
            array (
                'id' => 79,
                'name' => 'manufacturing.add_recipe',
                'guard_name' => 'web',
                'created_at' => '2019-12-31 06:09:04',
                'updated_at' => '2019-12-31 06:09:04',
            ),
            79 => 
            array (
                'id' => 80,
                'name' => 'manufacturing.edit_recipe',
                'guard_name' => 'web',
                'created_at' => '2019-12-31 06:09:04',
                'updated_at' => '2019-12-31 06:09:04',
            ),
            80 => 
            array (
                'id' => 81,
                'name' => 'purchase.update_status',
                'guard_name' => 'web',
                'created_at' => '2020-01-09 09:23:54',
                'updated_at' => '2020-01-09 09:23:54',
            ),
            81 => 
            array (
                'id' => 82,
                'name' => 'list_drafts',
                'guard_name' => 'web',
                'created_at' => '2020-01-09 09:23:54',
                'updated_at' => '2020-01-09 09:23:54',
            ),
            82 => 
            array (
                'id' => 83,
                'name' => 'list_quotations',
                'guard_name' => 'web',
                'created_at' => '2020-01-09 09:23:54',
                'updated_at' => '2020-01-09 09:23:54',
            ),
            83 => 
            array (
                'id' => 84,
                'name' => 'product.set_min_sell_price',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            84 => 
            array (
                'id' => 85,
                'name' => 'sales-commission-agents.create',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            85 => 
            array (
                'id' => 86,
                'name' => 'day_end.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            86 => 
            array (
                'id' => 87,
                'name' => 'day_end.bypass',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            87 => 
            array (
                'id' => 88,
                'name' => 'crm.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            88 => 
            array (
                'id' => 89,
                'name' => 'crm.create',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            89 => 
            array (
                'id' => 90,
                'name' => 'crm.update',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            90 => 
            array (
                'id' => 91,
                'name' => 'crm.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            91 => 
            array (
                'id' => 92,
                'name' => 'restaurant.access',
                'guard_name' => 'web',
                'created_at' => '2019-12-24 19:30:00',
                'updated_at' => '2019-12-24 19:30:00',
            ),
            92 => 
            array (
                'id' => 95,
                'name' => 'edit_product_price_below_purchase_price',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            93 => 
            array (
                'id' => 120,
                'name' => 'selling_price_group.1',
                'guard_name' => 'web',
                'created_at' => '2020-03-02 13:52:39',
                'updated_at' => '2020-03-02 13:52:39',
            ),
            94 => 
            array (
                'id' => 125,
                'name' => 'account.link_account',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            95 => 
            array (
                'id' => 126,
                'name' => 'sms.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            96 => 
            array (
                'id' => 129,
                'name' => 'settlement.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            97 => 
            array (
                'id' => 130,
                'name' => 'reset_dip',
                'guard_name' => 'web',
                'created_at' => '2020-04-16 10:01:00',
                'updated_at' => '2020-04-16 05:52:27',
            ),
            98 => 
            array (
                'id' => 137,
                'name' => 'account.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            99 => 
            array (
                'id' => 140,
                'name' => 'edit_customer_statement',
                'guard_name' => 'web',
                'created_at' => '2020-05-06 20:00:00',
                'updated_at' => '2020-05-06 20:00:00',
            ),
            100 => 
            array (
                'id' => 141,
                'name' => 'enable_separate_customer_statement_no',
                'guard_name' => 'web',
                'created_at' => '2020-05-06 20:00:00',
                'updated_at' => '2020-05-06 20:00:00',
            ),
            101 => 
            array (
                'id' => 142,
                'name' => 'backup',
                'guard_name' => 'web',
                'created_at' => '2020-05-06 20:00:00',
                'updated_at' => '2020-05-06 20:00:00',
            ),
            102 => 
            array (
                'id' => 143,
                'name' => 'edit_f22_stock_Taking_form',
                'guard_name' => 'web',
                'created_at' => '2020-05-07 20:00:00',
                'updated_at' => '2020-05-07 20:00:00',
            ),
            103 => 
            array (
                'id' => 145,
                'name' => 'edit_f17_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            104 => 
            array (
                'id' => 147,
                'name' => 'enable_cheque_writing',
                'guard_name' => 'web',
                'created_at' => '2020-05-14 20:00:00',
                'updated_at' => '2020-05-14 20:00:00',
            ),
            105 => 
            array (
                'id' => 148,
                'name' => 'f9c_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            106 => 
            array (
                'id' => 149,
                'name' => 'f15a9abc_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            107 => 
            array (
                'id' => 150,
                'name' => 'f16a_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            108 => 
            array (
                'id' => 151,
                'name' => 'f21c_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            109 => 
            array (
                'id' => 152,
                'name' => 'f17_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            110 => 
            array (
                'id' => 153,
                'name' => 'f14b_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            111 => 
            array (
                'id' => 154,
                'name' => 'f20_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            112 => 
            array (
                'id' => 155,
                'name' => 'f21_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            113 => 
            array (
                'id' => 156,
                'name' => 'f22_stock_taking_form',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            114 => 
            array (
                'id' => 157,
                'name' => 'unfinished_form.purchase',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            115 => 
            array (
                'id' => 158,
                'name' => 'unfinished_form.sale',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            116 => 
            array (
                'id' => 159,
                'name' => 'unfinished_form.pos',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            117 => 
            array (
                'id' => 160,
                'name' => 'unfinished_form.stock_adjustment',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            118 => 
            array (
                'id' => 161,
                'name' => 'unfinished_form.stock_transfer',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            119 => 
            array (
                'id' => 162,
                'name' => 'unfinished_form.expense',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            120 => 
            array (
                'id' => 163,
                'name' => 'hr.access',
                'guard_name' => 'web',
                'created_at' => '2020-05-26 20:00:00',
                'updated_at' => '2020-05-26 20:00:00',
            ),
            121 => 
            array (
                'id' => 165,
                'name' => 'pos_page_return',
                'guard_name' => 'web',
                'created_at' => '2020-05-26 20:00:00',
                'updated_at' => '2020-05-26 20:00:00',
            ),
            122 => 
            array (
                'id' => 166,
                'name' => 'expense.create',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            123 => 
            array (
                'id' => 167,
                'name' => 'expense.update',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            124 => 
            array (
                'id' => 168,
                'name' => 'expense.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            125 => 
            array (
                'id' => 169,
                'name' => 'expense.add_payment',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            126 => 
            array (
                'id' => 172,
                'name' => 'product.price_section',
                'guard_name' => 'web',
                'created_at' => '2020-05-31 20:00:00',
                'updated_at' => '2020-05-31 20:00:00',
            ),
            127 => 
            array (
                'id' => 173,
                'name' => 'fuel_tank.edit',
                'guard_name' => 'web',
                'created_at' => '2020-05-31 20:00:00',
                'updated_at' => '2020-05-31 20:00:00',
            ),
            128 => 
            array (
                'id' => 174,
                'name' => 'meter_resetting_tab',
                'guard_name' => 'web',
                'created_at' => '2020-05-31 20:00:00',
                'updated_at' => '2020-05-31 20:00:00',
            ),
            129 => 
            array (
                'id' => 175,
                'name' => 'add_dip_resetting',
                'guard_name' => 'web',
                'created_at' => '2020-05-31 20:00:00',
                'updated_at' => '2020-05-31 20:00:00',
            ),
            130 => 
            array (
                'id' => 177,
                'name' => 'issue_customer_bill.access',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            131 => 
            array (
                'id' => 178,
                'name' => 'issue_customer_bill.add',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            132 => 
            array (
                'id' => 179,
                'name' => 'issue_customer_bill.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            133 => 
            array (
                'id' => 180,
                'name' => 'edit_other_income_prices',
                'guard_name' => 'web',
                'created_at' => '2020-07-12 19:30:00',
                'updated_at' => '2020-07-12 19:30:00',
            ),
            134 => 
            array (
                'id' => 181,
                'name' => 'mpcs_form_settings',
                'guard_name' => 'web',
                'created_at' => '2020-07-12 19:30:00',
                'updated_at' => '2020-07-12 19:30:00',
            ),
            135 => 
            array (
                'id' => 182,
                'name' => 'list_opening_values',
                'guard_name' => 'web',
                'created_at' => '2020-07-12 19:30:00',
                'updated_at' => '2020-07-12 19:30:00',
            ),
            136 => 
            array (
                'id' => 183,
                'name' => 'tasks_management.access',
                'guard_name' => 'web',
                'created_at' => '2020-07-14 19:30:00',
                'updated_at' => '2020-07-14 19:30:00',
            ),
            137 => 
            array (
                'id' => 184,
                'name' => 'tasks_management.tasks',
                'guard_name' => 'web',
                'created_at' => '2020-07-14 19:30:00',
                'updated_at' => '2020-07-14 19:30:00',
            ),
            138 => 
            array (
                'id' => 186,
                'name' => 'add_remarks',
                'guard_name' => 'web',
                'created_at' => '2020-07-17 19:30:00',
                'updated_at' => '2020-07-17 19:30:00',
            ),
            139 => 
            array (
                'id' => 187,
                'name' => 'update_status_of_issue',
                'guard_name' => 'web',
                'created_at' => '2020-07-17 19:30:00',
                'updated_at' => '2020-07-17 19:30:00',
            ),
            140 => 
            array (
                'id' => 188,
                'name' => 'upload_images',
                'guard_name' => 'web',
                'created_at' => '2020-07-18 19:30:00',
                'updated_at' => '2020-07-18 19:30:00',
            ),
            141 => 
            array (
                'id' => 189,
                'name' => 'leads.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            142 => 
            array (
                'id' => 190,
                'name' => 'leads.create',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            143 => 
            array (
                'id' => 191,
                'name' => 'leads.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            144 => 
            array (
                'id' => 192,
                'name' => 'leads.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            145 => 
            array (
                'id' => 193,
                'name' => 'leads.import',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            146 => 
            array (
                'id' => 194,
                'name' => 'day_count',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            147 => 
            array (
                'id' => 195,
                'name' => 'leads.settings',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            148 => 
            array (
                'id' => 196,
                'name' => 'sms.access',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            149 => 
            array (
                'id' => 197,
                'name' => 'sms.list',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            150 => 
            array (
                'id' => 198,
                'name' => 'member_registration.access',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            151 => 
            array (
                'id' => 199,
                'name' => 'status_order',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            152 => 
            array (
                'id' => 200,
                'name' => 'customer_settings.access',
                'guard_name' => 'web',
                'created_at' => '2020-07-30 19:30:00',
                'updated_at' => '2020-07-30 19:30:00',
            ),
            153 => 
            array (
                'id' => 201,
                'name' => 'approve_sell_over_limit',
                'guard_name' => 'web',
                'created_at' => '2020-07-30 19:30:00',
                'updated_at' => '2020-07-30 19:30:00',
            ),
            154 => 
            array (
                'id' => 202,
                'name' => 'stock_adjustment_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            155 => 
            array (
                'id' => 203,
                'name' => 'item_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            156 => 
            array (
                'id' => 204,
                'name' => 'product_purchase_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            157 => 
            array (
                'id' => 205,
                'name' => 'product_sell_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            158 => 
            array (
                'id' => 206,
                'name' => 'product_transaction_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            159 => 
            array (
                'id' => 207,
                'name' => 'purchase_payment_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            160 => 
            array (
                'id' => 208,
                'name' => 'sell_payment_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            161 => 
            array (
                'id' => 209,
                'name' => 'outstanding_received_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            162 => 
            array (
                'id' => 210,
                'name' => 'aging_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            163 => 
            array (
                'id' => 211,
                'name' => 'daily_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            164 => 
            array (
                'id' => 212,
                'name' => 'daily_summary_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            165 => 
            array (
                'id' => 213,
                'name' => 'activity_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            166 => 
            array (
                'id' => 214,
                'name' => 'contact_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            167 => 
            array (
                'id' => 215,
                'name' => 'trending_products.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            168 => 
            array (
                'id' => 216,
                'name' => 'user_activity.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            169 => 
            array (
                'id' => 217,
                'name' => 'sales_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            170 => 
            array (
                'id' => 218,
                'name' => 'purchase_and_slae_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            171 => 
            array (
                'id' => 219,
                'name' => 'credit_status.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            172 => 
            array (
                'id' => 220,
                'name' => 'tasks_management.reminder',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            173 => 
            array (
                'id' => 221,
                'name' => 'employee.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            174 => 
            array (
                'id' => 222,
                'name' => 'attendance.approve_reject_lo',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            175 => 
            array (
                'id' => 223,
                'name' => 'leave_request.approve_reject',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            176 => 
            array (
                'id' => 224,
                'name' => 'leave_request.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            177 => 
            array (
                'id' => 225,
                'name' => 'leave_request.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            178 => 
            array (
                'id' => 228,
                'name' => 'web',
                'guard_name' => 'daily_collection.delete',
                'created_at' => '2020-09-29 19:50:00',
                'updated_at' => '2020-09-30 01:51:00',
            ),
            179 => 
            array (
                'id' => 230,
                'name' => 'account.reconcile',
                'guard_name' => 'web',
                'created_at' => '2020-10-04 19:30:00',
                'updated_at' => '2020-10-04 19:30:00',
            ),
            180 => 
            array (
                'id' => 231,
                'name' => 'daily_collection.delete',
                'guard_name' => 'web',
                'created_at' => '2020-09-29 19:50:00',
                'updated_at' => '2020-09-30 01:51:00',
            ),
            181 => 
            array (
                'id' => 236,
                'name' => 'customer_reference.edit',
                'guard_name' => 'web',
                'created_at' => '2020-10-14 19:30:00',
                'updated_at' => '2020-10-14 19:30:00',
            ),
            182 => 
            array (
                'id' => 238,
                'name' => 'cache_clear',
                'guard_name' => 'web',
                'created_at' => '2020-10-16 19:30:00',
                'updated_at' => '2020-10-16 19:30:00',
            ),
            183 => 
            array (
                'id' => 240,
                'name' => 'pum_operator.active_inactive',
                'guard_name' => 'web',
                'created_at' => '2020-10-21 19:30:00',
                'updated_at' => '2020-10-21 19:30:00',
            ),
            184 => 
            array (
                'id' => 241,
                'name' => 'pump_operator.dashboard',
                'guard_name' => 'web',
                'created_at' => '2020-10-21 19:30:00',
                'updated_at' => '2020-10-21 19:30:00',
            ),
            185 => 
            array (
                'id' => 242,
                'name' => 'petro.access',
                'guard_name' => 'web',
                'created_at' => '2020-10-22 19:30:00',
                'updated_at' => '2020-10-22 19:30:00',
            ),
            186 => 
            array (
                'id' => 243,
                'name' => 'mpcs.access',
                'guard_name' => 'web',
                'created_at' => '2020-10-22 19:30:00',
                'updated_at' => '2020-10-22 19:30:00',
            ),
            187 => 
            array (
                'id' => 244,
                'name' => 'ran.access',
                'guard_name' => 'web',
                'created_at' => '2020-10-22 19:30:00',
                'updated_at' => '2020-10-22 19:30:00',
            ),
            188 => 
            array (
                'id' => 245,
                'name' => 'report.access',
                'guard_name' => 'web',
                'created_at' => '2020-10-22 19:30:00',
                'updated_at' => '2020-10-22 19:30:00',
            ),
            189 => 
            array (
                'id' => 246,
                'name' => 'catalogue.access',
                'guard_name' => 'web',
                'created_at' => '2020-10-22 19:30:00',
                'updated_at' => '2020-10-22 19:30:00',
            ),
            190 => 
            array (
                'id' => 247,
                'name' => 'repair.access',
                'guard_name' => 'web',
                'created_at' => '2020-10-22 19:30:00',
                'updated_at' => '2020-10-22 19:30:00',
            ),
            191 => 
            array (
                'id' => 249,
                'name' => 'visitor.registration.create',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            192 => 
            array (
                'id' => 250,
                'name' => 'visitor.registration.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            193 => 
            array (
                'id' => 251,
                'name' => 'visitor.registration.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            194 => 
            array (
                'id' => 252,
                'name' => 'visitor.registration.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            195 => 
            array (
                'id' => 253,
                'name' => 'visitor.business.name.required',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            196 => 
            array (
                'id' => 254,
                'name' => 'visitor.business.name.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            197 => 
            array (
                'id' => 255,
                'name' => 'visitor.date.time.required',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            198 => 
            array (
                'id' => 256,
                'name' => 'visitor.date.time.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            199 => 
            array (
                'id' => 257,
                'name' => 'visitor.visited.date.required',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            200 => 
            array (
                'id' => 258,
                'name' => 'visitor.visited.date.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            201 => 
            array (
                'id' => 259,
                'name' => 'visitor.mobile.number.required',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            202 => 
            array (
                'id' => 260,
                'name' => 'visitor.mobile.number.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            203 => 
            array (
                'id' => 261,
                'name' => 'visitor.land.number.required',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            204 => 
            array (
                'id' => 262,
                'name' => 'visitor.land.number.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            205 => 
            array (
                'id' => 263,
                'name' => 'visitor.name.required',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            206 => 
            array (
                'id' => 264,
                'name' => 'visitor.name.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            207 => 
            array (
                'id' => 265,
                'name' => 'visitor.address.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            208 => 
            array (
                'id' => 266,
                'name' => 'visitor.district.required',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            209 => 
            array (
                'id' => 267,
                'name' => 'visitor.district.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            210 => 
            array (
                'id' => 268,
                'name' => 'visitor.district.add',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            211 => 
            array (
                'id' => 269,
                'name' => 'visitor.town.required',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            212 => 
            array (
                'id' => 270,
                'name' => 'visitor.town.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            213 => 
            array (
                'id' => 271,
                'name' => 'visitor.town.add',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            214 => 
            array (
                'id' => 272,
                'name' => 'visitor.details.required',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            215 => 
            array (
                'id' => 273,
                'name' => 'visitor.details.enable',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            216 => 
            array (
                'id' => 274,
                'name' => 'visitor.settings.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            217 => 
            array (
                'id' => 275,
                'name' => 'visitor.settings.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            218 => 
            array (
                'id' => 276,
                'name' => 'monthly_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            219 => 
            array (
                'id' => 277,
                'name' => 'comparison_report.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            220 => 
            array (
                'id' => 281,
                'name' => 'pump_operator.access_code',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            221 => 
            array (
                'id' => 282,
                'name' => 'daily_pump_status.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            222 => 
            array (
                'id' => 283,
                'name' => 'daily_pump_status.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            223 => 
            array (
                'id' => 288,
                'name' => 'property.purchase.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            224 => 
            array (
                'id' => 289,
                'name' => 'property.purchase.create',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            225 => 
            array (
                'id' => 290,
                'name' => 'property.list.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            226 => 
            array (
                'id' => 291,
                'name' => 'property.purchase.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            227 => 
            array (
                'id' => 292,
                'name' => 'property.purchase.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            228 => 
            array (
                'id' => 293,
                'name' => 'property.list.create',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            229 => 
            array (
                'id' => 294,
                'name' => 'property.list.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            230 => 
            array (
                'id' => 295,
                'name' => 'property.list.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            231 => 
            array (
                'id' => 296,
                'name' => 'property.settings.access',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            232 => 
            array (
                'id' => 297,
                'name' => 'property.settings.unit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            233 => 
            array (
                'id' => 298,
                'name' => 'property.settings.tax',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            234 => 
            array (
                'id' => 299,
                'name' => 'property.customer.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            235 => 
            array (
                'id' => 300,
                'name' => 'property.customer.create',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            236 => 
            array (
                'id' => 301,
                'name' => 'property.customer.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            237 => 
            array (
                'id' => 302,
                'name' => 'property.customer.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            238 => 
            array (
                'id' => 307,
                'name' => 'fleet.access',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            239 => 
            array (
                'id' => 308,
                'name' => 'pump_operator.main_system',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            240 => 
            array (
                'id' => 311,
                'name' => 'property_finalize.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            241 => 
            array (
                'id' => 312,
                'name' => 'property_account_settings.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            242 => 
            array (
                'id' => 313,
                'name' => 'property_penalty.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            243 => 
            array (
                'id' => 314,
                'name' => 'list_easy_payments.access',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            244 => 
            array (
                'id' => 316,
                'name' => 'account.settings',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            245 => 
            array (
                'id' => 319,
                'name' => 'property.current_sale.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            246 => 
            array (
                'id' => 320,
                'name' => 'property.current_sale.view',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            247 => 
            array (
                'id' => 321,
                'name' => 'property.current_sale.close.create',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            248 => 
            array (
                'id' => 322,
                'name' => 'property.add_new_sale',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            249 => 
            array (
                'id' => 323,
                'name' => 'property.current_sale.close.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            250 => 
            array (
                'id' => 324,
                'name' => 'fleet.routes.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            251 => 
            array (
                'id' => 325,
                'name' => 'fleet.routes.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            252 => 
            array (
                'id' => 326,
                'name' => 'fleet.drivers.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            253 => 
            array (
                'id' => 327,
                'name' => 'fleet.drivers.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            254 => 
            array (
                'id' => 328,
                'name' => 'fleet.helpers.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            255 => 
            array (
                'id' => 329,
                'name' => 'fleet.helpers.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            256 => 
            array (
                'id' => 331,
                'name' => 'account.settings.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            257 => 
            array (
                'id' => 339,
                'name' => 'account.deposit_transfer.edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            258 => 
            array (
                'id' => 364,
                'name' => 'property.update_sale_commission',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            259 => 
            array (
                'id' => 365,
                'name' => 'property.approve_commission',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            260 => 
            array (
                'id' => 366,
                'name' => 'property.update_commission_status',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            261 => 
            array (
                'id' => 367,
                'name' => 'payday',
                'guard_name' => 'web',
                'created_at' => '2022-03-10 00:42:08',
                'updated_at' => '2022-03-10 00:42:08',
            ),
            262 => 
            array (
                'id' => 368,
                'name' => 'add.payments',
                'guard_name' => 'web',
                'created_at' => '2022-03-16 11:12:41',
                'updated_at' => '2022-03-16 11:12:41',
            ),
            263 => 
            array (
                'id' => 372,
                'name' => 'journal.edit',
                'guard_name' => 'web',
                'created_at' => '2022-11-18 03:52:01',
                'updated_at' => '2022-11-18 03:52:01',
            ),
            264 => 
            array (
                'id' => 376,
                'name' => 'property.project_dashboard.sell_land_blocks',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:15:59',
                'updated_at' => '2023-02-22 06:15:59',
            ),
            265 => 
            array (
                'id' => 377,
                'name' => 'property.project_dashboard.customer_payments',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:15:59',
                'updated_at' => '2023-02-22 06:15:59',
            ),
            266 => 
            array (
                'id' => 378,
                'name' => 'dashboard.change',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:15:59',
                'updated_at' => '2023-02-22 06:15:59',
            ),
            267 => 
            array (
                'id' => 379,
                'name' => 'enable_cheque_templates',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:15:59',
                'updated_at' => '2023-02-22 06:15:59',
            ),
            268 => 
            array (
                'id' => 380,
                'name' => 'enable_cheque_add_new_template',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:15:59',
                'updated_at' => '2023-02-22 06:15:59',
            ),
            269 => 
            array (
                'id' => 381,
                'name' => 'enable_cheque_manage_stamps',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:15:59',
                'updated_at' => '2023-02-22 06:15:59',
            ),
            270 => 
            array (
                'id' => 382,
                'name' => 'enable_cheque_manage_payee',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:15:59',
                'updated_at' => '2023-02-22 06:15:59',
            ),
            271 => 
            array (
                'id' => 383,
                'name' => 'enable_cheque_number_list',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:16:00',
                'updated_at' => '2023-02-22 06:16:00',
            ),
            272 => 
            array (
                'id' => 384,
                'name' => 'enable_cheque_delete_numbers',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:16:00',
                'updated_at' => '2023-02-22 06:16:00',
            ),
            273 => 
            array (
                'id' => 385,
                'name' => 'enable_cheque_printed_details',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:16:00',
                'updated_at' => '2023-02-22 06:16:00',
            ),
            274 => 
            array (
                'id' => 386,
                'name' => 'enable_cheque_default_settings',
                'guard_name' => 'web',
                'created_at' => '2023-02-22 06:16:00',
                'updated_at' => '2023-02-22 06:16:00',
            ),
            275 => 
            array (
                'id' => 387,
                'name' => 'property.update_commission_status',
                'guard_name' => 'web',
                'created_at' => '2023-02-25 11:20:19',
                'updated_at' => NULL,
            ),
            276 => 
            array (
                'id' => 388,
                'name' => 'property.update_commission',
                'guard_name' => 'web',
                'created_at' => '2023-02-25 11:20:19',
                'updated_at' => NULL,
            ),
            277 => 
            array (
                'id' => 389,
                'name' => 'property.approve_commission',
                'guard_name' => 'web',
                'created_at' => '2023-02-25 11:20:19',
                'updated_at' => NULL,
            ),
            278 => 
            array (
                'id' => 390,
                'name' => 'property.update_sale_commission',
                'guard_name' => 'web',
                'created_at' => '2023-02-25 12:33:58',
                'updated_at' => NULL,
            ),
            279 => 
            array (
                'id' => 391,
                'name' => 'account.unreconcile',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            280 => 
            array (
                'id' => 392,
                'name' => 'stockAdjustment.list',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            281 => 
            array (
                'id' => 393,
                'name' => 'stockAdjustment.add',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            282 => 
            array (
                'id' => 394,
                'name' => 'stockAdjustment .edit',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            283 => 
            array (
                'id' => 395,
                'name' => 'stockAdjustment.delete',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            284 => 
            array (
                'id' => 396,
                'name' => 'restore',
                'guard_name' => 'web',
                'created_at' => '2020-05-06 20:00:00',
                'updated_at' => '2020-05-06 20:00:00',
            ),
            285 => 
            array (
                'id' => 398,
                'name' => 'DashboardSummaryCards',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            286 => 
            array (
                'id' => 399,
                'name' => 'DashboardPaymentMethods',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            287 => 
            array (
                'id' => 400,
                'name' => 'DashboardProductCategories',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            288 => 
            array (
                'id' => 401,
                'name' => 'DashboardGlance',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            289 => 
            array (
                'id' => 402,
                'name' => 'DashboardCurrentPastGraph',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            290 => 
            array (
                'id' => 403,
                'name' => 'DashboardCurrentPastPayments',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            291 => 
            array (
                'id' => 404,
                'name' => 'bypass.review',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            292 => 
            array (
                'id' => 405,
                'name' => 'backup.restore',
                'guard_name' => 'web',
                'created_at' => '2023-05-17 11:49:20',
                'updated_at' => '2023-05-17 11:49:20',
            ),
            293 => 
            array (
                'id' => 406,
                'name' => 'backup.upload',
                'guard_name' => 'web',
                'created_at' => '2023-05-17 11:49:20',
                'updated_at' => '2023-05-17 11:49:20',
            ),
            294 => 
            array (
                'id' => 407,
                'name' => 'purchase.edit.payment',
                'guard_name' => 'web',
                'created_at' => '2023-06-06 06:25:03',
                'updated_at' => '2023-06-06 06:25:03',
            ),
            295 => 
            array (
                'id' => 408,
                'name' => 'purchase.delete.payment',
                'guard_name' => 'web',
                'created_at' => '2023-06-06 06:25:03',
                'updated_at' => '2023-06-06 06:25:03',
            ),
            296 => 
            array (
                'id' => 409,
                'name' => 'dipmanagement.edit',
                'guard_name' => 'web',
                'created_at' => '2023-06-14 23:57:35',
                'updated_at' => '2023-06-14 23:57:35',
            ),
            297 => 
            array (
                'id' => 410,
                'name' => 'delete.cheque_ob',
                'guard_name' => 'web',
                'created_at' => '2023-06-26 01:12:45',
                'updated_at' => '2023-06-26 01:12:45',
            ),
            298 => 
            array (
                'id' => 411,
                'name' => 'edit.cheque_ob',
                'guard_name' => 'web',
                'created_at' => '2023-06-26 01:12:45',
                'updated_at' => '2023-06-26 01:12:45',
            ),
            299 => 
            array (
                'id' => 412,
                'name' => 'essentials.crud_all_attendance',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            300 => 
            array (
                'id' => 413,
                'name' => 'essentials.view_own_attendance',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            301 => 
            array (
                'id' => 414,
                'name' => 'essentials.allow_users_for_attendance_from_web',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '0000-00-00 00:00:00',
            ),
            302 => 
            array (
                'id' => 415,
                'name' => 'essentials.view_allowance_and_deduction',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            303 => 
            array (
                'id' => 416,
                'name' => 'essentials.crud_all_leave',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            304 => 
            array (
                'id' => 417,
                'name' => 'essentials.crud_own_leave',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            305 => 
            array (
                'id' => 418,
                'name' => 'essentials.crud_leave_type',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            306 => 
            array (
                'id' => 419,
                'name' => 'essentials.view_all_payroll',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            307 => 
            array (
                'id' => 420,
                'name' => 'essentials.create_payroll',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            308 => 
            array (
                'id' => 421,
                'name' => 'essentials.update_payroll',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            309 => 
            array (
                'id' => 422,
                'name' => 'essentials.delete_payroll',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            310 => 
            array (
                'id' => 423,
                'name' => 'essentials.access_sales_target',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            311 => 
            array (
                'id' => 424,
                'name' => 'essentials.edit_todos',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            312 => 
            array (
                'id' => 425,
                'name' => 'essentials.delete_todos',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            313 => 
            array (
                'id' => 426,
                'name' => 'essentials.add_todos',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            314 => 
            array (
                'id' => 427,
                'name' => 'edit_essentials_settings',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            315 => 
            array (
                'id' => 428,
                'name' => 'essentials.crud_department',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            316 => 
            array (
                'id' => 429,
                'name' => 'essentials.crud_designation',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            317 => 
            array (
                'id' => 430,
                'name' => 'essentials.view_all_payroll',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            318 => 
            array (
                'id' => 431,
                'name' => 'add_essentials_leave_type',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            319 => 
            array (
                'id' => 432,
                'name' => 'essentials.create_message',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            320 => 
            array (
                'id' => 433,
                'name' => 'essentials.view_message',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            321 => 
            array (
                'id' => 434,
                'name' => 'essentials.approve_leave',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            322 => 
            array (
                'id' => 435,
                'name' => 'essentials.assign_todos',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            323 => 
            array (
                'id' => 436,
                'name' => 'essentials.add_allowance_and_deduction',
                'guard_name' => 'web',
                'created_at' => '2023-06-21 05:01:24',
                'updated_at' => '2023-06-21 05:01:24',
            ),
            324 => 
            array (
                'id' => 437,
                'name' => 'pricechanges.access',
                'guard_name' => 'web',
                'created_at' => '2023-07-27 04:34:19',
                'updated_at' => '2023-07-27 04:34:19',
            ),
            325 => 
            array (
                'id' => 438,
                'name' => 'edit.fleet_opening_balance',
                'guard_name' => 'web',
                'created_at' => '2023-09-25 16:41:37',
                'updated_at' => '2023-09-25 16:41:37',
            ),
            326 => 
            array (
                'id' => 439,
                'name' => 'edit_fuel_type',
                'guard_name' => 'web',
                'created_at' => '2023-09-30 09:14:04',
                'updated_at' => '2023-09-30 09:14:04',
            ),
            327 => 
            array (
                'id' => 440,
                'name' => 'fuel_management',
                'guard_name' => 'web',
                'created_at' => '2023-09-30 09:14:04',
                'updated_at' => '2023-09-30 09:14:04',
            ),
            328 => 
            array (
                'id' => 441,
                'name' => 'airline_edit_invoice',
                'guard_name' => 'web',
                'created_at' => '2023-09-30 09:16:12',
                'updated_at' => '2023-09-30 09:16:12',
            ),
            329 => 
            array (
                'id' => 442,
                'name' => 'fleet_actual_meter',
                'guard_name' => 'web',
                'created_at' => '2023-09-30 09:16:12',
                'updated_at' => '2023-09-30 09:16:12',
            ),
            330 => 
            array (
                'id' => 443,
                'name' => 'fleet.add_actual_meter',
                'guard_name' => 'web',
                'created_at' => '2023-09-29 05:25:34',
                'updated_at' => '2023-09-29 05:25:34',
            ),
            331 => 
            array (
                'id' => 450,
                'name' => 'customer_pay_due',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:08:12',
                'updated_at' => '2023-11-25 16:08:12',
            ),
            332 => 
            array (
                'id' => 451,
                'name' => 'supplier_pay_due',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:08:12',
                'updated_at' => '2023-11-25 16:08:12',
            ),
            333 => 
            array (
                'id' => 452,
                'name' => 'deposits_module',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:08:55',
                'updated_at' => '2023-11-25 16:08:55',
            ),
            334 => 
            array (
                'id' => 453,
                'name' => 'deposits.cash_deposit',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:08:55',
                'updated_at' => '2023-11-24 19:30:00',
            ),
            335 => 
            array (
                'id' => 454,
                'name' => 'deposits.cheque_deposit',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:08:55',
                'updated_at' => '2023-11-24 19:30:00',
            ),
            336 => 
            array (
                'id' => 455,
                'name' => 'deposits.card_deposit',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:08:55',
                'updated_at' => '2023-11-24 19:30:00',
            ),
            337 => 
            array (
                'id' => 456,
                'name' => 'deposits.transfer',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:08:55',
                'updated_at' => '2023-11-24 19:30:00',
            ),
            338 => 
            array (
                'id' => 457,
                'name' => 'crm.access',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            339 => 
            array (
                'id' => 458,
                'name' => 'crm.access_all_leads',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            340 => 
            array (
                'id' => 459,
                'name' => 'crm.access_own_leads',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            341 => 
            array (
                'id' => 460,
                'name' => 'crm.access_all_schedule',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            342 => 
            array (
                'id' => 461,
                'name' => 'crm.access_own_schedule',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            343 => 
            array (
                'id' => 462,
                'name' => 'crm.access_all_campaigns',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            344 => 
            array (
                'id' => 463,
                'name' => 'crm.access_own_campaigns',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            345 => 
            array (
                'id' => 464,
                'name' => 'crm.access_contact_login',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            346 => 
            array (
                'id' => 465,
                'name' => 'crm.view_all_call_log',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            347 => 
            array (
                'id' => 466,
                'name' => 'crm.view_own_call_log',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            348 => 
            array (
                'id' => 467,
                'name' => 'crm.view_reports',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            349 => 
            array (
                'id' => 468,
                'name' => 'crm.access_resources',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            350 => 
            array (
                'id' => 469,
                'name' => 'crm.access_life_stage',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            351 => 
            array (
                'id' => 470,
                'name' => 'crm.add_proposal_template',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            352 => 
            array (
                'id' => 471,
                'name' => 'crm.access_proposal',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            353 => 
            array (
                'id' => 472,
                'name' => 'deposit.cash_deposit',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            354 => 
            array (
                'id' => 473,
                'name' => 'deposit.cheque_deposit',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            355 => 
            array (
                'id' => 474,
                'name' => 'deposit.card_deposit',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            356 => 
            array (
                'id' => 475,
                'name' => 'deposit.access',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            357 => 
            array (
                'id' => 476,
                'name' => 'deposit.transfer',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            358 => 
            array (
                'id' => 477,
                'name' => 'airline.view_setting',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            359 => 
            array (
                'id' => 478,
                'name' => 'airline.access',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            360 => 
            array (
                'id' => 479,
                'name' => 'asset.view_all_maintenance',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            361 => 
            array (
                'id' => 480,
                'name' => 'asset.update',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            362 => 
            array (
                'id' => 481,
                'name' => 'asset.delete',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            363 => 
            array (
                'id' => 482,
                'name' => 'asset.create',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            364 => 
            array (
                'id' => 483,
                'name' => 'asset.view_own_maintenance',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            365 => 
            array (
                'id' => 484,
                'name' => 'asset.view',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            366 => 
            array (
                'id' => 485,
                'name' => 'ezyinvoice.access',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            367 => 
            array (
                'id' => 486,
                'name' => 'hms.manage_amenities',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            368 => 
            array (
                'id' => 487,
                'name' => 'hms.manage_extra',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            369 => 
            array (
                'id' => 488,
                'name' => 'hms.edit_booking',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            370 => 
            array (
                'id' => 489,
                'name' => 'hms.add_booking',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            371 => 
            array (
                'id' => 490,
                'name' => 'hms.manage_coupon',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            372 => 
            array (
                'id' => 491,
                'name' => 'hms.manage_rooms',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            373 => 
            array (
                'id' => 492,
                'name' => 'hms.manage_price',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            374 => 
            array (
                'id' => 493,
                'name' => 'hms.manage_unavailable',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            375 => 
            array (
                'id' => 494,
                'name' => 'hms.access',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            376 => 
            array (
                'id' => 495,
                'name' => 'shipping.helpers.edit',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            377 => 
            array (
                'id' => 496,
                'name' => 'shipping.helpers.delete',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            378 => 
            array (
                'id' => 497,
                'name' => 'shipping.access',
                'guard_name' => 'web',
                'created_at' => '2023-11-25 16:12:41',
                'updated_at' => '2023-11-25 16:12:41',
            ),
            379 => 
            array (
                'id' => 498,
                'name' => 'purchase.zero',
                'guard_name' => 'web',
                'created_at' => '2023-11-01 10:35:56',
                'updated_at' => '2023-11-01 10:35:56',
            ),
            380 => 
            array (
                'id' => 499,
                'name' => 'list_customer_payments.delete',
                'guard_name' => 'web',
                'created_at' => '2023-12-18 01:47:33',
                'updated_at' => '2023-12-18 01:47:33',
            ),
            381 => 
            array (
                'id' => 500,
                'name' => 'account.realize_cheque',
                'guard_name' => 'web',
                'created_at' => '2024-01-28 11:48:26',
                'updated_at' => '2024-01-28 11:48:26',
            ),
            382 => 
            array (
                'id' => 501,
                'name' => 'deposit.realize_cheque',
                'guard_name' => 'web',
                'created_at' => '2024-01-28 11:48:26',
                'updated_at' => '2024-01-28 11:48:26',
            ),
            383 => 
            array (
                'id' => 502,
                'name' => 'vat_sale',
                'guard_name' => 'web',
                'created_at' => '2024-02-05 14:22:56',
                'updated_at' => '2024-02-05 14:22:56',
            ),
            384 => 
            array (
                'id' => 503,
                'name' => 'list_vat_sale',
                'guard_name' => 'web',
                'created_at' => '2024-02-05 14:22:56',
                'updated_at' => '2024-02-05 14:22:56',
            ),
            385 => 
            array (
                'id' => 504,
                'name' => 'vat_purchase',
                'guard_name' => 'web',
                'created_at' => '2024-02-05 14:22:56',
                'updated_at' => '2024-02-05 14:22:56',
            ),
            386 => 
            array (
                'id' => 505,
                'name' => 'list_vat_purchase',
                'guard_name' => 'web',
                'created_at' => '2024-02-05 14:22:56',
                'updated_at' => '2024-02-05 14:22:56',
            ),
            387 => 
            array (
                'id' => 506,
                'name' => 'vat_expense',
                'guard_name' => 'web',
                'created_at' => '2024-02-05 14:22:56',
                'updated_at' => '2024-02-05 14:22:56',
            ),
            388 => 
            array (
                'id' => 507,
                'name' => 'list_vat_expense',
                'guard_name' => 'web',
                'created_at' => '2024-02-05 14:22:56',
                'updated_at' => '2024-02-05 14:22:56',
            ),
            389 => 
            array (
                'id' => 508,
                'name' => 'vat_products',
                'guard_name' => 'web',
                'created_at' => '2024-02-05 14:22:56',
                'updated_at' => '2024-02-05 14:22:56',
            ),
            390 => 
            array (
                'id' => 509,
                'name' => 'vat_contacts',
                'guard_name' => 'web',
                'created_at' => '2024-02-05 14:22:56',
                'updated_at' => '2024-02-05 14:22:56',
            ),
            391 => 
            array (
                'id' => 510,
                'name' => 'pumper_dashboard_settings',
                'guard_name' => 'web',
                'created_at' => '2024-02-21 13:03:36',
                'updated_at' => '2024-02-21 13:03:36',
            ),
            392 => 
            array (
                'id' => 511,
                'name' => 'location.2',
                'guard_name' => 'web',
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
            ),
            393 => 
            array (
                'id' => 512,
                'name' => 'stockAdjustment.edit',
                'guard_name' => 'web',
                'created_at' => '2024-02-27 12:45:30',
                'updated_at' => '2024-02-27 12:45:30',
            ),
            394 => 
            array (
                'id' => 513,
                'name' => 'purchase.edit.payments',
                'guard_name' => 'web',
                'created_at' => '2024-02-27 12:47:49',
                'updated_at' => '2024-02-27 12:47:49',
            ),
            395 => 
            array (
                'id' => 514,
                'name' => 'edit_received_outstanding',
                'guard_name' => 'web',
                'created_at' => '2024-02-27 12:47:49',
                'updated_at' => '2024-02-27 12:47:49',
            ),
            396 => 
            array (
                'id' => 515,
                'name' => 'delete_received_outstanding',
                'guard_name' => 'web',
                'created_at' => '2024-02-27 12:47:49',
                'updated_at' => '2024-02-27 12:47:49',
            ),
            397 => 
            array (
                'id' => 516,
                'name' => 'add_received_outstanding',
                'guard_name' => 'web',
                'created_at' => '2024-02-27 12:51:51',
                'updated_at' => '2024-02-27 12:51:51',
            ),
            398 => 
            array (
                'id' => 517,
                'name' => 'manual_discount',
                'guard_name' => 'web',
                'created_at' => '2024-02-27 12:51:51',
                'updated_at' => '2024-02-27 12:51:51',
            ),
            399 => 
            array (
                'id' => 518,
                'name' => 'edit.vat_statement',
                'guard_name' => 'web',
                'created_at' => '2024-03-02 04:35:04',
                'updated_at' => '2024-03-02 04:35:04',
            ),
            400 => 
            array (
                'id' => 519,
                'name' => 'vat_edit_invoice127',
                'guard_name' => 'web',
                'created_at' => '2024-03-27 08:03:48',
                'updated_at' => '2024-03-27 08:03:48',
            ),
            401 => 
            array (
                'id' => 520,
                'name' => 'pos.edit_pos_tax',
                'guard_name' => 'web',
                'created_at' => '2024-04-17 19:30:00',
                'updated_at' => '2024-04-17 19:30:00',
            ),
            402 => 
            array (
                'id' => 521,
                'name' => 'pos.edit_total_tax',
                'guard_name' => 'web',
                'created_at' => '2024-04-17 19:30:00',
                'updated_at' => '2024-04-17 19:30:00',
            ),
            403 => 
            array (
                'id' => 522,
                'name' => 'pos.edit_discount',
                'guard_name' => 'web',
                'created_at' => '2024-04-17 19:30:00',
                'updated_at' => '2024-04-17 19:30:00',
            ),
            404 => 
            array (
                'id' => 523,
                'name' => 'pos.edit_shipping',
                'guard_name' => 'web',
                'created_at' => '2024-04-17 19:30:00',
                'updated_at' => '2024-04-17 19:30:00',
            ),
            405 => 
            array (
                'id' => 524,
                'name' => 'edit_pumper_opening_balance',
                'guard_name' => 'web',
                'created_at' => '2024-04-18 10:09:30',
                'updated_at' => '2024-04-18 10:09:30',
            ),
            406 => 
            array (
                'id' => 525,
                'name' => 'list_trip_operations',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            407 => 
            array (
                'id' => 526,
                'name' => 'edit_fleet',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL,
            ),
            408 => 
            array (
                'id' => 527,
                'name' => 'bulk_assign_pumps',
                'guard_name' => 'web',
                'created_at' => '2024-05-19 19:30:00',
                'updated_at' => '2024-05-19 19:30:00',
            ),
            409 => 
            array (
                'id' => 528,
                'name' => 'add_day_end_settlement',
                'guard_name' => 'web',
                'created_at' => '2024-05-20 00:22:34',
                'updated_at' => '2024-05-20 00:22:34',
            ),
            410 => 
            array (
                'id' => 529,
                'name' => 'edit_day_end_settlement',
                'guard_name' => 'web',
                'created_at' => '2024-05-20 00:22:34',
                'updated_at' => '2024-05-20 00:22:34',
            ),
            411 => 
            array (
                'id' => 530,
                'name' => 'petro_sms_notifications',
                'guard_name' => 'web',
                'created_at' => '2024-05-19 19:30:00',
                'updated_at' => '2024-05-20 00:22:34',
            ),
            412 => 
            array (
                'id' => 531,
                'name' => 'fleet.edit_trip_category',
                'guard_name' => 'web',
                'created_at' => '2024-05-30 19:30:00',
                'updated_at' => '2024-05-30 19:30:00',
            ),
            413 => 
            array (
                'id' => 532,
                'name' => 'web',
                'guard_name' => 'fleet.delete_trip_category',
                'created_at' => '2024-05-30 19:30:00',
                'updated_at' => '2024-05-30 19:30:00',
            ),
            414 => 
            array (
                'id' => 535,
                'name' => 'daily_shortage.edit',
                'guard_name' => 'web',
                'created_at' => '2024-06-18 19:30:00',
                'updated_at' => '2024-06-18 19:30:00',
            ),
            415 => 
            array (
                'id' => 536,
                'name' => 'daily_card.edit',
                'guard_name' => 'web',
                'created_at' => '2024-06-18 19:30:00',
                'updated_at' => '2024-06-18 19:30:00',
            ),
            416 => 
            array (
                'id' => 537,
                'name' => 'daily_collection.edit',
                'guard_name' => 'web',
                'created_at' => '2024-06-18 19:30:00',
                'updated_at' => '2024-06-18 19:30:00',
            ),
            417 => 
            array (
                'id' => 538,
                'name' => 'daily_shortage.edit',
                'guard_name' => 'web',
                'created_at' => '2024-06-22 19:30:00',
                'updated_at' => '2024-06-22 19:30:00',
            ),
            418 => 
            array (
                'id' => 539,
                'name' => 'daily_card.edit',
                'guard_name' => 'web',
                'created_at' => '2024-06-22 19:30:00',
                'updated_at' => '2024-06-22 19:30:00',
            ),
            419 => 
            array (
                'id' => 540,
                'name' => 'daily_collection.edit',
                'guard_name' => 'web',
                'created_at' => '2024-06-22 19:30:00',
                'updated_at' => '2024-06-22 19:30:00',
            ),
            420 => 
            array (
                'id' => 541,
                'name' => 'sms_ledger',
                'guard_name' => 'web',
                'created_at' => '2024-07-14 01:00:00',
                'updated_at' => '2024-07-14 01:00:00',
            ),
            421 => 
            array (
                'id' => 542,
                'name' => 'sms_delivery_report',
                'guard_name' => 'web',
                'created_at' => '2024-07-14 11:10:52',
                'updated_at' => '2024-07-14 01:00:00',
            ),
            422 => 
            array (
                'id' => 543,
                'name' => 'sms_list_sms',
                'guard_name' => 'web',
                'created_at' => '2024-07-14 11:10:52',
                'updated_at' => '2024-07-14 01:00:00',
            ),
            423 => 
            array (
                'id' => 544,
                'name' => 'sms_history',
                'guard_name' => 'web',
                'created_at' => '2024-07-14 11:10:52',
                'updated_at' => '2024-07-14 01:00:00',
            ),
            424 => 
            array (
                'id' => 545,
                'name' => 'sms_quick_send',
                'guard_name' => 'web',
                'created_at' => '2024-07-14 01:00:00',
                'updated_at' => '2024-07-14 01:00:00',
            ),
            425 => 
            array (
                'id' => 546,
                'name' => 'sms_campaign',
                'guard_name' => 'web',
                'created_at' => '2024-07-14 01:00:00',
                'updated_at' => '2024-07-14 01:00:00',
            ),
            426 => 
            array (
                'id' => 547,
                'name' => 'sms_from_file',
                'guard_name' => 'web',
                'created_at' => '2024-07-14 01:00:00',
                'updated_at' => '2024-07-14 01:00:00',
            ),
            427 => 
            array (
                'id' => 548,
                'name' => 'dipmanagement.delete',
                'guard_name' => 'web',
                'created_at' => '2024-07-16 19:30:00',
                'updated_at' => '2024-07-16 19:30:00',
            ),
            428 => 
            array (
                'id' => 549,
                'name' => 'dipmanagement.add_dip_chart',
                'guard_name' => 'web',
                'created_at' => '2024-07-16 19:30:00',
                'updated_at' => '2024-07-16 19:30:00',
            ),
            429 => 
            array (
                'id' => 550,
                'name' => 'dipmanagement.edit_dip_chart',
                'guard_name' => 'web',
                'created_at' => '2024-07-16 19:30:00',
                'updated_at' => '2024-07-16 19:30:00',
            ),
            430 => 
            array (
                'id' => 551,
                'name' => 'dipmanagement.delete_dip_chart',
                'guard_name' => 'web',
                'created_at' => '2024-07-16 19:30:00',
                'updated_at' => '2024-07-16 19:30:00',
            ),
            431 => 
            array (
                'id' => 552,
                'name' => 'bakery.loading_edit',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            432 => 
            array (
                'id' => 553,
                'name' => 'bakery_login',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            433 => 
            array (
                'id' => 554,
                'name' => 'bakery_add_loading',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            434 => 
            array (
                'id' => 555,
                'name' => 'bakery_edit_loading',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            435 => 
            array (
                'id' => 556,
                'name' => 'bakery_returns',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            436 => 
            array (
                'id' => 557,
                'name' => 'bakery_add_user',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            437 => 
            array (
                'id' => 558,
                'name' => 'bakery_edit_user',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            438 => 
            array (
                'id' => 559,
                'name' => 'bakery_add_due_amount',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            439 => 
            array (
                'id' => 560,
                'name' => 'bakery_make_payment',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            440 => 
            array (
                'id' => 561,
                'name' => 'bakery_list_loading',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            441 => 
            array (
                'id' => 562,
                'name' => 'bakery.loading_edit',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            442 => 
            array (
                'id' => 563,
                'name' => 'bakery_login',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            443 => 
            array (
                'id' => 564,
                'name' => 'bakery_add_loading',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            444 => 
            array (
                'id' => 565,
                'name' => 'bakery_edit_loading',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            445 => 
            array (
                'id' => 566,
                'name' => 'bakery_returns',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            446 => 
            array (
                'id' => 567,
                'name' => 'bakery_add_user',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            447 => 
            array (
                'id' => 568,
                'name' => 'bakery_edit_user',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            448 => 
            array (
                'id' => 569,
                'name' => 'bakery_add_due_amount',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            449 => 
            array (
                'id' => 570,
                'name' => 'bakery_make_payment',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            450 => 
            array (
                'id' => 571,
                'name' => 'bakery_list_loading',
                'guard_name' => 'web',
                'created_at' => '2024-08-03 19:30:00',
                'updated_at' => '2024-08-03 19:30:00',
            ),
            451 => 
            array (
                'id' => 572,
                'name' => 'vat.delete_customer_statement',
                'guard_name' => 'web',
                'created_at' => '2024-08-13 19:30:00',
                'updated_at' => '2024-08-13 19:30:00',
            ),
            452 => 
            array (
                'id' => 573,
                'name' => 'vat.delete_statement_payment',
                'guard_name' => 'web',
                'created_at' => '2024-08-13 19:30:00',
                'updated_at' => '2024-08-13 19:30:00',
            ),
            453 => 
            array (
                'id' => 574,
                'name' => 'contact.delete_customer_statement',
                'guard_name' => 'web',
                'created_at' => '2024-08-13 19:30:00',
                'updated_at' => '2024-08-13 19:30:00',
            ),
            454 => 
            array (
                'id' => 575,
                'name' => 'contact.delete_statement_payment',
                'guard_name' => 'web',
                'created_at' => '2024-08-13 19:30:00',
                'updated_at' => '2024-08-13 19:30:00',
            ),
        ));
        
        
    }
}