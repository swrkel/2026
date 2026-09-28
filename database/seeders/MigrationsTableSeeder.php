<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MigrationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('migrations')->delete();
        
        \DB::table('migrations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'migration' => '2014_10_12_000000_create_users_table',
                'batch' => 1,
            ),
            1 => 
            array (
                'id' => 2,
                'migration' => '2014_10_12_100000_create_password_resets_table',
                'batch' => 1,
            ),
            2 => 
            array (
                'id' => 3,
                'migration' => '2017_07_05_071953_create_currencies_table',
                'batch' => 1,
            ),
            3 => 
            array (
                'id' => 4,
                'migration' => '2017_07_05_073658_create_business_table',
                'batch' => 1,
            ),
            4 => 
            array (
                'id' => 5,
                'migration' => '2017_07_22_075923_add_business_id_users_table',
                'batch' => 1,
            ),
            5 => 
            array (
                'id' => 6,
                'migration' => '2017_07_23_113209_create_brands_table',
                'batch' => 1,
            ),
            6 => 
            array (
                'id' => 7,
                'migration' => '2017_07_26_083429_create_permission_tables',
                'batch' => 1,
            ),
            7 => 
            array (
                'id' => 8,
                'migration' => '2017_07_26_110000_create_tax_rates_table',
                'batch' => 1,
            ),
            8 => 
            array (
                'id' => 9,
                'migration' => '2017_07_26_122313_create_units_table',
                'batch' => 1,
            ),
            9 => 
            array (
                'id' => 10,
                'migration' => '2017_07_27_075706_create_contacts_table',
                'batch' => 1,
            ),
            10 => 
            array (
                'id' => 11,
                'migration' => '2017_08_04_071038_create_categories_table',
                'batch' => 1,
            ),
            11 => 
            array (
                'id' => 12,
                'migration' => '2017_08_08_115903_create_products_table',
                'batch' => 1,
            ),
            12 => 
            array (
                'id' => 13,
                'migration' => '2017_08_09_061616_create_variation_templates_table',
                'batch' => 1,
            ),
            13 => 
            array (
                'id' => 14,
                'migration' => '2017_08_09_061638_create_variation_value_templates_table',
                'batch' => 1,
            ),
            14 => 
            array (
                'id' => 15,
                'migration' => '2017_08_10_061146_create_product_variations_table',
                'batch' => 1,
            ),
            15 => 
            array (
                'id' => 16,
                'migration' => '2017_08_10_061216_create_variations_table',
                'batch' => 1,
            ),
            16 => 
            array (
                'id' => 17,
                'migration' => '2017_08_19_054827_create_transactions_table',
                'batch' => 1,
            ),
            17 => 
            array (
                'id' => 18,
                'migration' => '2017_08_31_073533_create_purchase_lines_table',
                'batch' => 1,
            ),
            18 => 
            array (
                'id' => 19,
                'migration' => '2017_10_15_064638_create_transaction_payments_table',
                'batch' => 1,
            ),
            19 => 
            array (
                'id' => 20,
                'migration' => '2017_10_31_065621_add_default_sales_tax_to_business_table',
                'batch' => 1,
            ),
            20 => 
            array (
                'id' => 21,
                'migration' => '2017_11_20_051930_create_table_group_sub_taxes',
                'batch' => 1,
            ),
            21 => 
            array (
                'id' => 22,
                'migration' => '2017_11_20_063603_create_transaction_sell_lines',
                'batch' => 1,
            ),
            22 => 
            array (
                'id' => 23,
                'migration' => '2017_11_21_064540_create_barcodes_table',
                'batch' => 1,
            ),
            23 => 
            array (
                'id' => 24,
                'migration' => '2017_11_23_181237_create_invoice_schemes_table',
                'batch' => 1,
            ),
            24 => 
            array (
                'id' => 25,
                'migration' => '2017_12_25_122822_create_business_locations_table',
                'batch' => 1,
            ),
            25 => 
            array (
                'id' => 26,
                'migration' => '2017_12_25_160253_add_location_id_to_transactions_table',
                'batch' => 1,
            ),
            26 => 
            array (
                'id' => 27,
                'migration' => '2017_12_25_163227_create_variation_location_details_table',
                'batch' => 1,
            ),
            27 => 
            array (
                'id' => 28,
                'migration' => '2018_01_04_115627_create_sessions_table',
                'batch' => 1,
            ),
            28 => 
            array (
                'id' => 29,
                'migration' => '2018_01_05_112817_create_invoice_layouts_table',
                'batch' => 1,
            ),
            29 => 
            array (
                'id' => 30,
                'migration' => '2018_01_06_112303_add_invoice_scheme_id_and_invoice_layout_id_to_business_locations',
                'batch' => 1,
            ),
            30 => 
            array (
                'id' => 31,
                'migration' => '2018_01_08_104124_create_expense_categories_table',
                'batch' => 1,
            ),
            31 => 
            array (
                'id' => 32,
                'migration' => '2018_01_08_123327_modify_transactions_table_for_expenses',
                'batch' => 1,
            ),
            32 => 
            array (
                'id' => 33,
                'migration' => '2018_01_09_111005_modify_payment_status_in_transactions_table',
                'batch' => 1,
            ),
            33 => 
            array (
                'id' => 34,
                'migration' => '2018_01_09_111109_add_paid_on_column_to_transaction_payments_table',
                'batch' => 1,
            ),
            34 => 
            array (
                'id' => 35,
                'migration' => '2018_01_25_172439_add_printer_related_fields_to_business_locations_table',
                'batch' => 1,
            ),
            35 => 
            array (
                'id' => 36,
                'migration' => '2018_01_27_184322_create_printers_table',
                'batch' => 1,
            ),
            36 => 
            array (
                'id' => 37,
                'migration' => '2018_01_30_181442_create_cash_registers_table',
                'batch' => 1,
            ),
            37 => 
            array (
                'id' => 38,
                'migration' => '2018_01_31_125836_create_cash_register_transactions_table',
                'batch' => 1,
            ),
            38 => 
            array (
                'id' => 39,
                'migration' => '2018_02_07_173326_modify_business_table',
                'batch' => 1,
            ),
            39 => 
            array (
                'id' => 40,
                'migration' => '2018_02_08_105425_add_enable_product_expiry_column_to_business_table',
                'batch' => 1,
            ),
            40 => 
            array (
                'id' => 41,
                'migration' => '2018_02_08_111027_add_expiry_period_and_expiry_period_type_columns_to_products_table',
                'batch' => 1,
            ),
            41 => 
            array (
                'id' => 42,
                'migration' => '2018_02_08_131118_add_mfg_date_and_exp_date_purchase_lines_table',
                'batch' => 1,
            ),
            42 => 
            array (
                'id' => 43,
                'migration' => '2018_02_08_155348_add_exchange_rate_to_transactions_table',
                'batch' => 1,
            ),
            43 => 
            array (
                'id' => 44,
                'migration' => '2018_02_09_124945_modify_transaction_payments_table_for_contact_payments',
                'batch' => 1,
            ),
            44 => 
            array (
                'id' => 45,
                'migration' => '2018_02_12_113640_create_transaction_sell_lines_purchase_lines_table',
                'batch' => 1,
            ),
            45 => 
            array (
                'id' => 46,
                'migration' => '2018_02_12_114605_add_quantity_sold_in_purchase_lines_table',
                'batch' => 1,
            ),
            46 => 
            array (
                'id' => 47,
                'migration' => '2018_02_13_183323_alter_decimal_fields_size',
                'batch' => 1,
            ),
            47 => 
            array (
                'id' => 48,
                'migration' => '2018_02_14_161928_add_transaction_edit_days_to_business_table',
                'batch' => 1,
            ),
            48 => 
            array (
                'id' => 49,
                'migration' => '2018_02_15_161032_add_document_column_to_transactions_table',
                'batch' => 1,
            ),
            49 => 
            array (
                'id' => 50,
                'migration' => '2018_02_17_124709_add_more_options_to_invoice_layouts',
                'batch' => 1,
            ),
            50 => 
            array (
                'id' => 51,
                'migration' => '2018_02_19_111517_add_keyboard_shortcut_column_to_business_table',
                'batch' => 1,
            ),
            51 => 
            array (
                'id' => 52,
                'migration' => '2018_02_19_121537_stock_adjustment_move_to_transaction_table',
                'batch' => 1,
            ),
            52 => 
            array (
                'id' => 53,
                'migration' => '2018_02_20_165505_add_is_direct_sale_column_to_transactions_table',
                'batch' => 1,
            ),
            53 => 
            array (
                'id' => 54,
                'migration' => '2018_02_21_105329_create_system_table',
                'batch' => 1,
            ),
            54 => 
            array (
                'id' => 55,
                'migration' => '2018_02_23_100549_version_1_2',
                'batch' => 1,
            ),
            55 => 
            array (
                'id' => 56,
                'migration' => '2018_02_23_125648_add_enable_editing_sp_from_purchase_column_to_business_table',
                'batch' => 1,
            ),
            56 => 
            array (
                'id' => 57,
                'migration' => '2018_02_26_103612_add_sales_commission_agent_column_to_business_table',
                'batch' => 1,
            ),
            57 => 
            array (
                'id' => 58,
                'migration' => '2018_02_26_130519_modify_users_table_for_sales_cmmsn_agnt',
                'batch' => 1,
            ),
            58 => 
            array (
                'id' => 59,
                'migration' => '2018_02_26_134500_add_commission_agent_to_transactions_table',
                'batch' => 1,
            ),
            59 => 
            array (
                'id' => 60,
                'migration' => '2018_02_27_121422_add_item_addition_method_to_business_table',
                'batch' => 1,
            ),
            60 => 
            array (
                'id' => 61,
                'migration' => '2018_02_27_170232_modify_transactions_table_for_stock_transfer',
                'batch' => 1,
            ),
            61 => 
            array (
                'id' => 62,
                'migration' => '2018_03_05_153510_add_enable_inline_tax_column_to_business_table',
                'batch' => 1,
            ),
            62 => 
            array (
                'id' => 63,
                'migration' => '2018_03_06_210206_modify_product_barcode_types',
                'batch' => 1,
            ),
            63 => 
            array (
                'id' => 64,
                'migration' => '2018_03_13_181541_add_expiry_type_to_business_table',
                'batch' => 1,
            ),
            64 => 
            array (
                'id' => 65,
                'migration' => '2018_03_16_113446_product_expiry_setting_for_business',
                'batch' => 1,
            ),
            65 => 
            array (
                'id' => 66,
                'migration' => '2018_03_19_113601_add_business_settings_options',
                'batch' => 1,
            ),
            66 => 
            array (
                'id' => 67,
                'migration' => '2018_03_26_125334_add_pos_settings_to_business_table',
                'batch' => 1,
            ),
            67 => 
            array (
                'id' => 68,
                'migration' => '2018_03_26_165350_create_customer_groups_table',
                'batch' => 1,
            ),
            68 => 
            array (
                'id' => 69,
                'migration' => '2018_03_27_122720_customer_group_related_changes_in_tables',
                'batch' => 1,
            ),
            69 => 
            array (
                'id' => 70,
                'migration' => '2018_03_29_110138_change_tax_field_to_nullable_in_business_table',
                'batch' => 1,
            ),
            70 => 
            array (
                'id' => 71,
                'migration' => '2018_03_29_115502_add_changes_for_sr_number_in_products_and_sale_lines_table',
                'batch' => 1,
            ),
            71 => 
            array (
                'id' => 72,
                'migration' => '2018_03_29_134340_add_inline_discount_fields_in_purchase_lines',
                'batch' => 1,
            ),
            72 => 
            array (
                'id' => 73,
                'migration' => '2018_03_31_140921_update_transactions_table_exchange_rate',
                'batch' => 1,
            ),
            73 => 
            array (
                'id' => 74,
                'migration' => '2018_04_03_103037_add_contact_id_to_contacts_table',
                'batch' => 1,
            ),
            74 => 
            array (
                'id' => 75,
                'migration' => '2018_04_03_122709_add_changes_to_invoice_layouts_table',
                'batch' => 1,
            ),
            75 => 
            array (
                'id' => 76,
                'migration' => '2018_04_09_135320_change_exchage_rate_size_in_business_table',
                'batch' => 1,
            ),
            76 => 
            array (
                'id' => 77,
                'migration' => '2018_04_17_123122_add_lot_number_to_business',
                'batch' => 1,
            ),
            77 => 
            array (
                'id' => 78,
                'migration' => '2018_04_17_160845_add_product_racks_table',
                'batch' => 1,
            ),
            78 => 
            array (
                'id' => 79,
                'migration' => '2018_04_20_182015_create_res_tables_table',
                'batch' => 1,
            ),
            79 => 
            array (
                'id' => 80,
                'migration' => '2018_04_24_105246_restaurant_fields_in_transaction_table',
                'batch' => 1,
            ),
            80 => 
            array (
                'id' => 81,
                'migration' => '2018_04_24_114149_add_enabled_modules_business_table',
                'batch' => 1,
            ),
            81 => 
            array (
                'id' => 82,
                'migration' => '2018_04_24_133704_add_modules_fields_in_invoice_layout_table',
                'batch' => 1,
            ),
            82 => 
            array (
                'id' => 83,
                'migration' => '2018_04_27_132653_quotation_related_change',
                'batch' => 1,
            ),
            83 => 
            array (
                'id' => 84,
                'migration' => '2018_05_02_104439_add_date_format_and_time_format_to_business',
                'batch' => 1,
            ),
            84 => 
            array (
                'id' => 85,
                'migration' => '2018_05_02_111939_add_sell_return_to_transaction_payments',
                'batch' => 1,
            ),
            85 => 
            array (
                'id' => 86,
                'migration' => '2018_05_14_114027_add_rows_positions_for_products',
                'batch' => 1,
            ),
            86 => 
            array (
                'id' => 87,
                'migration' => '2018_05_14_125223_add_weight_to_products_table',
                'batch' => 1,
            ),
            87 => 
            array (
                'id' => 88,
                'migration' => '2018_05_14_164754_add_opening_stock_permission',
                'batch' => 1,
            ),
            88 => 
            array (
                'id' => 89,
                'migration' => '2018_05_15_134729_add_design_to_invoice_layouts',
                'batch' => 1,
            ),
            89 => 
            array (
                'id' => 90,
                'migration' => '2018_05_16_183307_add_tax_fields_invoice_layout',
                'batch' => 1,
            ),
            90 => 
            array (
                'id' => 91,
                'migration' => '2018_05_18_191956_add_sell_return_to_transaction_table',
                'batch' => 1,
            ),
            91 => 
            array (
                'id' => 92,
                'migration' => '2018_05_21_131349_add_custom_fileds_to_contacts_table',
                'batch' => 1,
            ),
            92 => 
            array (
                'id' => 93,
                'migration' => '2018_05_21_131607_invoice_layout_fields_for_sell_return',
                'batch' => 1,
            ),
            93 => 
            array (
                'id' => 94,
                'migration' => '2018_05_21_131949_add_custom_fileds_and_website_to_business_locations_table',
                'batch' => 1,
            ),
            94 => 
            array (
                'id' => 95,
                'migration' => '2018_05_22_123527_create_reference_counts_table',
                'batch' => 1,
            ),
            95 => 
            array (
                'id' => 96,
                'migration' => '2018_05_22_154540_add_ref_no_prefixes_column_to_business_table',
                'batch' => 1,
            ),
            96 => 
            array (
                'id' => 97,
                'migration' => '2018_05_24_132620_add_ref_no_column_to_transaction_payments_table',
                'batch' => 1,
            ),
            97 => 
            array (
                'id' => 98,
                'migration' => '2018_05_24_161026_add_location_id_column_to_business_location_table',
                'batch' => 1,
            ),
            98 => 
            array (
                'id' => 99,
                'migration' => '2018_05_25_180603_create_modifiers_related_table',
                'batch' => 1,
            ),
            99 => 
            array (
                'id' => 100,
                'migration' => '2018_05_29_121714_add_purchase_line_id_to_stock_adjustment_line_table',
                'batch' => 1,
            ),
            100 => 
            array (
                'id' => 101,
                'migration' => '2018_05_31_114645_add_res_order_status_column_to_transactions_table',
                'batch' => 1,
            ),
            101 => 
            array (
                'id' => 102,
                'migration' => '2018_06_05_103530_rename_purchase_line_id_in_stock_adjustment_lines_table',
                'batch' => 1,
            ),
            102 => 
            array (
                'id' => 103,
                'migration' => '2018_06_05_111905_modify_products_table_for_modifiers',
                'batch' => 1,
            ),
            103 => 
            array (
                'id' => 104,
                'migration' => '2018_06_06_110524_add_parent_sell_line_id_column_to_transaction_sell_lines_table',
                'batch' => 1,
            ),
            104 => 
            array (
                'id' => 105,
                'migration' => '2018_06_07_152443_add_is_service_staff_to_roles_table',
                'batch' => 1,
            ),
            105 => 
            array (
                'id' => 106,
                'migration' => '2018_06_07_182258_add_image_field_to_products_table',
                'batch' => 1,
            ),
            106 => 
            array (
                'id' => 107,
                'migration' => '2018_06_13_133705_create_bookings_table',
                'batch' => 1,
            ),
            107 => 
            array (
                'id' => 108,
                'migration' => '2018_06_15_173636_add_email_column_to_contacts_table',
                'batch' => 1,
            ),
            108 => 
            array (
                'id' => 109,
                'migration' => '2018_06_27_182835_add_superadmin_related_fields_business',
                'batch' => 1,
            ),
            109 => 
            array (
                'id' => 110,
                'migration' => '2018_07_10_101913_add_custom_fields_to_products_table',
                'batch' => 1,
            ),
            110 => 
            array (
                'id' => 111,
                'migration' => '2018_07_17_103434_add_sales_person_name_label_to_invoice_layouts_table',
                'batch' => 1,
            ),
            111 => 
            array (
                'id' => 112,
                'migration' => '2018_07_17_163920_add_theme_skin_color_column_to_business_table',
                'batch' => 1,
            ),
            112 => 
            array (
                'id' => 113,
                'migration' => '2018_07_24_160319_add_lot_no_line_id_to_transaction_sell_lines_table',
                'batch' => 1,
            ),
            113 => 
            array (
                'id' => 114,
                'migration' => '2018_07_25_110004_add_show_expiry_and_show_lot_colums_to_invoice_layouts_table',
                'batch' => 1,
            ),
            114 => 
            array (
                'id' => 115,
                'migration' => '2018_07_25_172004_add_discount_columns_to_transaction_sell_lines_table',
                'batch' => 1,
            ),
            115 => 
            array (
                'id' => 116,
                'migration' => '2018_07_26_124720_change_design_column_type_in_invoice_layouts_table',
                'batch' => 1,
            ),
            116 => 
            array (
                'id' => 117,
                'migration' => '2018_07_26_170424_add_unit_price_before_discount_column_to_transaction_sell_line_table',
                'batch' => 1,
            ),
            117 => 
            array (
                'id' => 118,
                'migration' => '2018_07_28_103614_add_credit_limit_column_to_contacts_table',
                'batch' => 1,
            ),
            118 => 
            array (
                'id' => 119,
                'migration' => '2018_08_08_110755_add_new_payment_methods_to_transaction_payments_table',
                'batch' => 1,
            ),
            119 => 
            array (
                'id' => 120,
                'migration' => '2018_08_08_122225_modify_cash_register_transactions_table_for_new_payment_methods',
                'batch' => 1,
            ),
            120 => 
            array (
                'id' => 121,
                'migration' => '2018_08_14_104036_add_opening_balance_type_to_transactions_table',
                'batch' => 1,
            ),
            121 => 
            array (
                'id' => 122,
                'migration' => '2018_09_04_155900_create_accounts_table',
                'batch' => 1,
            ),
            122 => 
            array (
                'id' => 123,
                'migration' => '2018_09_06_114438_create_selling_price_groups_table',
                'batch' => 1,
            ),
            123 => 
            array (
                'id' => 124,
                'migration' => '2018_09_06_154057_create_variation_group_prices_table',
                'batch' => 1,
            ),
            124 => 
            array (
                'id' => 125,
                'migration' => '2018_09_07_102413_add_permission_to_access_default_selling_price',
                'batch' => 1,
            ),
            125 => 
            array (
                'id' => 126,
                'migration' => '2018_09_07_134858_add_selling_price_group_id_to_transactions_table',
                'batch' => 1,
            ),
            126 => 
            array (
                'id' => 127,
                'migration' => '2018_09_10_112448_update_product_type_to_single_if_null_in_products_table',
                'batch' => 1,
            ),
            127 => 
            array (
                'id' => 128,
                'migration' => '2018_09_10_152703_create_account_transactions_table',
                'batch' => 1,
            ),
            128 => 
            array (
                'id' => 129,
                'migration' => '2018_09_10_173656_add_account_id_column_to_transaction_payments_table',
                'batch' => 1,
            ),
            129 => 
            array (
                'id' => 130,
                'migration' => '2018_09_19_123914_create_notification_templates_table',
                'batch' => 1,
            ),
            130 => 
            array (
                'id' => 131,
                'migration' => '2018_09_22_110504_add_sms_and_email_settings_columns_to_business_table',
                'batch' => 1,
            ),
            131 => 
            array (
                'id' => 132,
                'migration' => '2018_09_24_134942_add_lot_no_line_id_to_stock_adjustment_lines_table',
                'batch' => 1,
            ),
            132 => 
            array (
                'id' => 133,
                'migration' => '2018_09_26_105557_add_transaction_payments_for_existing_expenses',
                'batch' => 1,
            ),
            133 => 
            array (
                'id' => 134,
                'migration' => '2018_09_27_111609_modify_transactions_table_for_purchase_return',
                'batch' => 1,
            ),
            134 => 
            array (
                'id' => 135,
                'migration' => '2018_09_27_131154_add_quantity_returned_column_to_purchase_lines_table',
                'batch' => 1,
            ),
            135 => 
            array (
                'id' => 136,
                'migration' => '2018_10_02_131401_add_return_quantity_column_to_transaction_sell_lines_table',
                'batch' => 1,
            ),
            136 => 
            array (
                'id' => 137,
                'migration' => '2018_10_03_104918_add_qty_returned_column_to_transaction_sell_lines_purchase_lines_table',
                'batch' => 1,
            ),
            137 => 
            array (
                'id' => 138,
                'migration' => '2018_10_03_185947_add_default_notification_templates_to_database',
                'batch' => 1,
            ),
            138 => 
            array (
                'id' => 139,
                'migration' => '2018_10_09_153105_add_business_id_to_transaction_payments_table',
                'batch' => 1,
            ),
            139 => 
            array (
                'id' => 140,
                'migration' => '2018_10_16_135229_create_permission_for_sells_and_purchase',
                'batch' => 1,
            ),
            140 => 
            array (
                'id' => 141,
                'migration' => '2018_10_22_114441_add_columns_for_variable_product_modifications',
                'batch' => 1,
            ),
            141 => 
            array (
                'id' => 142,
                'migration' => '2018_10_22_134428_modify_variable_product_data',
                'batch' => 1,
            ),
            142 => 
            array (
                'id' => 143,
                'migration' => '2018_10_30_181558_add_table_tax_headings_to_invoice_layout',
                'batch' => 1,
            ),
            143 => 
            array (
                'id' => 144,
                'migration' => '2018_10_31_122619_add_pay_terms_field_transactions_table',
                'batch' => 1,
            ),
            144 => 
            array (
                'id' => 145,
                'migration' => '2018_10_31_161328_add_new_permissions_for_pos_screen',
                'batch' => 1,
            ),
            145 => 
            array (
                'id' => 146,
                'migration' => '2018_10_31_174752_add_access_selected_contacts_only_to_users_table',
                'batch' => 1,
            ),
            146 => 
            array (
                'id' => 147,
                'migration' => '2018_10_31_175627_add_user_contact_access',
                'batch' => 1,
            ),
            147 => 
            array (
                'id' => 148,
                'migration' => '2018_10_31_180559_add_auto_send_sms_column_to_notification_templates_table',
                'batch' => 1,
            ),
            148 => 
            array (
                'id' => 149,
                'migration' => '2018_11_02_171949_change_card_type_column_to_varchar_in_transaction_payments_table',
                'batch' => 1,
            ),
            149 => 
            array (
                'id' => 150,
                'migration' => '2018_11_08_105621_add_role_permissions',
                'batch' => 1,
            ),
            150 => 
            array (
                'id' => 151,
                'migration' => '2018_11_26_114135_add_is_suspend_column_to_transactions_table',
                'batch' => 1,
            ),
            151 => 
            array (
                'id' => 152,
                'migration' => '2018_11_28_104410_modify_units_table_for_multi_unit',
                'batch' => 1,
            ),
            152 => 
            array (
                'id' => 153,
                'migration' => '2018_11_28_170952_add_sub_unit_id_to_purchase_lines_and_sell_lines',
                'batch' => 1,
            ),
            153 => 
            array (
                'id' => 154,
                'migration' => '2018_11_29_115918_add_primary_key_in_system_table',
                'batch' => 1,
            ),
            154 => 
            array (
                'id' => 155,
                'migration' => '2018_12_03_185546_add_product_description_column_to_products_table',
                'batch' => 1,
            ),
            155 => 
            array (
                'id' => 156,
                'migration' => '2018_12_06_114937_modify_system_table_and_users_table',
                'batch' => 1,
            ),
            156 => 
            array (
                'id' => 157,
                'migration' => '2018_12_13_160007_add_custom_fields_display_options_to_invoice_layouts_table',
                'batch' => 1,
            ),
            157 => 
            array (
                'id' => 158,
                'migration' => '2018_12_14_103307_modify_system_table',
                'batch' => 1,
            ),
            158 => 
            array (
                'id' => 159,
                'migration' => '2018_12_18_133837_add_prev_balance_due_columns_to_invoice_layouts_table',
                'batch' => 1,
            ),
            159 => 
            array (
                'id' => 160,
                'migration' => '2018_12_18_170656_add_invoice_token_column_to_transaction_table',
                'batch' => 1,
            ),
            160 => 
            array (
                'id' => 161,
                'migration' => '2018_12_20_133639_add_date_time_format_column_to_invoice_layouts_table',
                'batch' => 1,
            ),
            161 => 
            array (
                'id' => 162,
                'migration' => '2018_12_21_120659_add_recurring_invoice_fields_to_transactions_table',
                'batch' => 1,
            ),
            162 => 
            array (
                'id' => 163,
                'migration' => '2018_12_24_154933_create_notifications_table',
                'batch' => 1,
            ),
            163 => 
            array (
                'id' => 164,
                'migration' => '2019_01_08_112015_add_document_column_to_transaction_payments_table',
                'batch' => 1,
            ),
            164 => 
            array (
                'id' => 165,
                'migration' => '2019_01_10_124645_add_account_permission',
                'batch' => 1,
            ),
            165 => 
            array (
                'id' => 166,
                'migration' => '2019_01_16_125825_add_subscription_no_column_to_transactions_table',
                'batch' => 1,
            ),
            166 => 
            array (
                'id' => 167,
                'migration' => '2019_01_28_111647_add_order_addresses_column_to_transactions_table',
                'batch' => 1,
            ),
            167 => 
            array (
                'id' => 168,
                'migration' => '2019_02_13_173821_add_is_inactive_column_to_products_table',
                'batch' => 1,
            ),
            168 => 
            array (
                'id' => 169,
                'migration' => '2019_02_19_103118_create_discounts_table',
                'batch' => 1,
            ),
            169 => 
            array (
                'id' => 170,
                'migration' => '2019_02_21_120324_add_discount_id_column_to_transaction_sell_lines_table',
                'batch' => 1,
            ),
            170 => 
            array (
                'id' => 171,
                'migration' => '2019_02_21_134324_add_permission_for_discount',
                'batch' => 1,
            ),
            171 => 
            array (
                'id' => 172,
                'migration' => '2019_03_04_170832_add_service_staff_columns_to_transaction_sell_lines_table',
                'batch' => 1,
            ),
            172 => 
            array (
                'id' => 173,
                'migration' => '2019_03_09_102425_add_sub_type_column_to_transactions_table',
                'batch' => 1,
            ),
            173 => 
            array (
                'id' => 174,
                'migration' => '2019_03_09_124457_add_indexing_transaction_sell_lines_purchase_lines_table',
                'batch' => 1,
            ),
            174 => 
            array (
                'id' => 175,
                'migration' => '2019_03_12_120336_create_activity_log_table',
                'batch' => 1,
            ),
            175 => 
            array (
                'id' => 176,
                'migration' => '2019_03_15_132925_create_media_table',
                'batch' => 1,
            ),
            176 => 
            array (
                'id' => 177,
                'migration' => '2019_05_08_130339_add_indexing_to_parent_id_in_transaction_payments_table',
                'batch' => 1,
            ),
            177 => 
            array (
                'id' => 178,
                'migration' => '2019_05_10_132311_add_missing_column_indexing',
                'batch' => 1,
            ),
            178 => 
            array (
                'id' => 179,
                'migration' => '2019_05_14_091812_add_show_image_column_to_invoice_layouts_table',
                'batch' => 1,
            ),
            179 => 
            array (
                'id' => 180,
                'migration' => '2019_05_25_104922_add_view_purchase_price_permission',
                'batch' => 1,
            ),
            180 => 
            array (
                'id' => 181,
                'migration' => '2019_06_17_103515_add_profile_informations_columns_to_users_table',
                'batch' => 1,
            ),
            181 => 
            array (
                'id' => 182,
                'migration' => '2019_06_18_135524_add_permission_to_view_own_sales_only',
                'batch' => 1,
            ),
            182 => 
            array (
                'id' => 183,
                'migration' => '2019_06_19_112058_add_database_changes_for_reward_points',
                'batch' => 1,
            ),
            183 => 
            array (
                'id' => 184,
                'migration' => '2019_06_28_133732_change_type_column_to_string_in_transactions_table',
                'batch' => 1,
            ),
            184 => 
            array (
                'id' => 185,
                'migration' => '2019_07_13_111420_add_is_created_from_api_column_to_transactions_table',
                'batch' => 1,
            ),
            185 => 
            array (
                'id' => 186,
                'migration' => '2019_07_15_165136_add_fields_for_combo_product',
                'batch' => 1,
            ),
            186 => 
            array (
                'id' => 187,
                'migration' => '2019_07_19_103446_add_mfg_quantity_used_column_to_purchase_lines_table',
                'batch' => 1,
            ),
            187 => 
            array (
                'id' => 188,
                'migration' => '2019_07_22_152649_add_not_for_selling_in_product_table',
                'batch' => 1,
            ),
            188 => 
            array (
                'id' => 189,
                'migration' => '2019_07_29_185351_add_show_reward_point_column_to_invoice_layouts_table',
                'batch' => 1,
            ),
            189 => 
            array (
                'id' => 190,
                'migration' => '2019_08_08_162302_add_sub_units_related_fields',
                'batch' => 1,
            ),
            190 => 
            array (
                'id' => 191,
                'migration' => '2019_08_26_133419_update_price_fields_decimal_point',
                'batch' => 1,
            ),
            191 => 
            array (
                'id' => 192,
                'migration' => '2019_09_02_160054_remove_location_permissions_from_roles',
                'batch' => 1,
            ),
            192 => 
            array (
                'id' => 193,
                'migration' => '2019_09_03_185259_add_permission_for_pos_screen',
                'batch' => 1,
            ),
            193 => 
            array (
                'id' => 194,
                'migration' => '2019_09_17_122522_add_custom_labels_column_to_business_table',
                'batch' => 1,
            ),
            194 => 
            array (
                'id' => 195,
                'migration' => '2019_09_18_164319_add_shipping_fields_to_transactions_table',
                'batch' => 1,
            ),
            195 => 
            array (
                'id' => 196,
                'migration' => '2019_09_23_161906_add_media_description_cloumn_to_media_table',
                'batch' => 1,
            ),
            196 => 
            array (
                'id' => 197,
                'migration' => '2019_10_18_155633_create_account_types_table',
                'batch' => 1,
            ),
            197 => 
            array (
                'id' => 198,
                'migration' => '2019_10_22_163335_add_common_settings_column_to_business_table',
                'batch' => 1,
            ),
            198 => 
            array (
                'id' => 199,
                'migration' => '2019_11_09_110522_add_indexing_to_lot_number',
                'batch' => 1,
            ),
            199 => 
            array (
                'id' => 200,
                'migration' => '2019_11_19_170824_add_is_active_column_to_business_locations_table',
                'batch' => 1,
            ),
            200 => 
            array (
                'id' => 201,
                'migration' => '2019_11_21_162913_change_quantity_field_types_to_decimal',
                'batch' => 1,
            ),
            201 => 
            array (
                'id' => 202,
                'migration' => '2018_06_27_185405_create_packages_table',
                'batch' => 2,
            ),
            202 => 
            array (
                'id' => 203,
                'migration' => '2018_06_28_182803_create_subscriptions_table',
                'batch' => 2,
            ),
            203 => 
            array (
                'id' => 204,
                'migration' => '2018_07_17_182021_add_rows_to_system_table',
                'batch' => 2,
            ),
            204 => 
            array (
                'id' => 205,
                'migration' => '2018_07_19_131721_add_options_to_packages_table',
                'batch' => 2,
            ),
            205 => 
            array (
                'id' => 206,
                'migration' => '2018_08_17_155534_add_min_termination_alert_days',
                'batch' => 2,
            ),
            206 => 
            array (
                'id' => 207,
                'migration' => '2018_08_28_105945_add_business_based_username_settings_to_system_table',
                'batch' => 2,
            ),
            207 => 
            array (
                'id' => 208,
                'migration' => '2018_08_30_105906_add_superadmin_communicator_logs_table',
                'batch' => 2,
            ),
            208 => 
            array (
                'id' => 209,
                'migration' => '2018_11_02_130636_add_custom_permissions_to_packages_table',
                'batch' => 2,
            ),
            209 => 
            array (
                'id' => 210,
                'migration' => '2018_11_05_161848_add_more_fields_to_packages_table',
                'batch' => 2,
            ),
            210 => 
            array (
                'id' => 211,
                'migration' => '2018_12_10_124621_modify_system_table_values_null_default',
                'batch' => 2,
            ),
            211 => 
            array (
                'id' => 212,
                'migration' => '2019_05_10_135434_add_missing_database_column_indexes',
                'batch' => 2,
            ),
            212 => 
            array (
                'id' => 213,
                'migration' => '2019_08_16_115300_create_superadmin_frontend_pages_table',
                'batch' => 2,
            ),
            213 => 
            array (
                'id' => 214,
                'migration' => '2019_07_15_114211_add_manufacturing_module_version_to_system_table',
                'batch' => 3,
            ),
            214 => 
            array (
                'id' => 215,
                'migration' => '2019_07_15_114403_create_mfg_recipes_table',
                'batch' => 3,
            ),
            215 => 
            array (
                'id' => 216,
                'migration' => '2019_07_18_180217_add_production_columns_to_transactions_table',
                'batch' => 3,
            ),
            216 => 
            array (
                'id' => 217,
                'migration' => '2019_07_26_110753_add_manufacturing_settings_column_to_business_table',
                'batch' => 3,
            ),
            217 => 
            array (
                'id' => 218,
                'migration' => '2019_07_26_170450_add_manufacturing_permissions',
                'batch' => 3,
            ),
            218 => 
            array (
                'id' => 219,
                'migration' => '2019_08_08_110035_create_mfg_recipe_ingredients_table',
                'batch' => 3,
            ),
            219 => 
            array (
                'id' => 220,
                'migration' => '2019_08_08_172837_add_recipe_add_edit_permissions',
                'batch' => 3,
            ),
            220 => 
            array (
                'id' => 221,
                'migration' => '2019_08_12_114610_add_ingredient_waste_percent_columns',
                'batch' => 3,
            ),
            221 => 
            array (
                'id' => 222,
                'migration' => '2019_09_04_163141_add_location_id_to_cash_registers_table',
                'batch' => 4,
            ),
            222 => 
            array (
                'id' => 223,
                'migration' => '2019_09_04_184008_create_types_of_services_table',
                'batch' => 4,
            ),
            223 => 
            array (
                'id' => 224,
                'migration' => '2019_09_06_131445_add_types_of_service_fields_to_transactions_table',
                'batch' => 4,
            ),
            224 => 
            array (
                'id' => 225,
                'migration' => '2019_09_09_134810_add_default_selling_price_group_id_column_to_business_locations_table',
                'batch' => 4,
            ),
            225 => 
            array (
                'id' => 226,
                'migration' => '2019_09_12_105616_create_product_locations_table',
                'batch' => 4,
            ),
            226 => 
            array (
                'id' => 227,
                'migration' => '2019_09_19_170927_close_all_active_registers',
                'batch' => 4,
            ),
            227 => 
            array (
                'id' => 228,
                'migration' => '2019_10_29_132521_add_update_purchase_status_permission',
                'batch' => 4,
            ),
            228 => 
            array (
                'id' => 229,
                'migration' => '2019_12_02_105025_create_warranties_table',
                'batch' => 4,
            ),
            229 => 
            array (
                'id' => 230,
                'migration' => '2019_12_03_180342_add_common_settings_field_to_invoice_layouts_table',
                'batch' => 4,
            ),
            230 => 
            array (
                'id' => 231,
                'migration' => '2019_12_06_174904_add_change_return_label_column_to_invoice_layouts_table',
                'batch' => 4,
            ),
            231 => 
            array (
                'id' => 232,
                'migration' => '2019_12_11_121307_add_draft_and_quotation_list_permissions',
                'batch' => 4,
            ),
            232 => 
            array (
                'id' => 233,
                'migration' => '2019_12_12_180126_copy_expense_total_to_total_before_tax',
                'batch' => 4,
            ),
            233 => 
            array (
                'id' => 234,
                'migration' => '2019_12_19_181412_make_alert_quantity_field_nullable_on_products_table',
                'batch' => 4,
            ),
            234 => 
            array (
                'id' => 235,
                'migration' => '2019_11_05_115136_create_ingredient_groups_table',
                'batch' => 5,
            ),
            235 => 
            array (
                'id' => 236,
                'migration' => '2018_08_08_100000_create_telescope_entries_table',
                'batch' => 6,
            ),
            236 => 
            array (
                'id' => 237,
                'migration' => '2025_04_10_041321_create_essential_employee_payments_table',
                'batch' => 7,
            ),
            237 => 
            array (
                'id' => 238,
                'migration' => '2025_05_31_213714_create_account_groups_table',
                'batch' => 1,
            ),
            238 => 
            array (
                'id' => 239,
                'migration' => '2025_05_31_213714_create_account_numbers_table',
                'batch' => 1,
            ),
            239 => 
            array (
                'id' => 240,
                'migration' => '2025_05_31_213714_create_account_settings_table',
                'batch' => 1,
            ),
            240 => 
            array (
                'id' => 241,
                'migration' => '2025_05_31_213714_create_account_transactions_table',
                'batch' => 1,
            ),
            241 => 
            array (
                'id' => 242,
                'migration' => '2025_05_31_213714_create_account_types_table',
                'batch' => 1,
            ),
            242 => 
            array (
                'id' => 243,
                'migration' => '2025_05_31_213714_create_accounts_table',
                'batch' => 1,
            ),
            243 => 
            array (
                'id' => 244,
                'migration' => '2025_05_31_213714_create_activity_log_table',
                'batch' => 1,
            ),
            244 => 
            array (
                'id' => 245,
                'migration' => '2025_05_31_213714_create_ad_page_slots_table',
                'batch' => 1,
            ),
            245 => 
            array (
                'id' => 246,
                'migration' => '2025_05_31_213714_create_ad_pages_table',
                'batch' => 1,
            ),
            246 => 
            array (
                'id' => 247,
                'migration' => '2025_05_31_213714_create_additional_service_table',
                'batch' => 1,
            ),
            247 => 
            array (
                'id' => 248,
                'migration' => '2025_05_31_213714_create_ads_table',
                'batch' => 1,
            ),
            248 => 
            array (
                'id' => 249,
                'migration' => '2025_05_31_213714_create_agents_table',
                'batch' => 1,
            ),
            249 => 
            array (
                'id' => 250,
                'migration' => '2025_05_31_213714_create_air_ticket_invoices_table',
                'batch' => 1,
            ),
            250 => 
            array (
                'id' => 251,
                'migration' => '2025_05_31_213714_create_airline_add_commissions_table',
                'batch' => 1,
            ),
            251 => 
            array (
                'id' => 252,
                'migration' => '2025_05_31_213714_create_airline_agents_table',
                'batch' => 1,
            ),
            252 => 
            array (
                'id' => 253,
                'migration' => '2025_05_31_213714_create_airline_airports_table',
                'batch' => 1,
            ),
            253 => 
            array (
                'id' => 254,
                'migration' => '2025_05_31_213714_create_airline_classes_table',
                'batch' => 1,
            ),
            254 => 
            array (
                'id' => 255,
                'migration' => '2025_05_31_213714_create_airline_commision_types_table',
                'batch' => 1,
            ),
            255 => 
            array (
                'id' => 256,
                'migration' => '2025_05_31_213714_create_airline_commission_types_table',
                'batch' => 1,
            ),
            256 => 
            array (
                'id' => 257,
                'migration' => '2025_05_31_213714_create_airline_form_setting_customer_table',
                'batch' => 1,
            ),
            257 => 
            array (
                'id' => 258,
                'migration' => '2025_05_31_213714_create_airline_form_setting_passenger_table',
                'batch' => 1,
            ),
            258 => 
            array (
                'id' => 259,
                'migration' => '2025_05_31_213714_create_airline_form_setting_supplier_table',
                'batch' => 1,
            ),
            259 => 
            array (
                'id' => 260,
                'migration' => '2025_05_31_213714_create_airline_linked_accounts_table',
                'batch' => 1,
            ),
            260 => 
            array (
                'id' => 261,
                'migration' => '2025_05_31_213714_create_airline_passenger_type_table',
                'batch' => 1,
            ),
            261 => 
            array (
                'id' => 262,
                'migration' => '2025_05_31_213714_create_airline_passengers_table',
                'batch' => 1,
            ),
            262 => 
            array (
                'id' => 263,
                'migration' => '2025_05_31_213714_create_airline_prefix_starting_modes_table',
                'batch' => 1,
            ),
            263 => 
            array (
                'id' => 264,
                'migration' => '2025_05_31_213714_create_airline_prefix_startings_table',
                'batch' => 1,
            ),
            264 => 
            array (
                'id' => 265,
                'migration' => '2025_05_31_213714_create_airline_prefixes_table',
                'batch' => 1,
            ),
            265 => 
            array (
                'id' => 266,
                'migration' => '2025_05_31_213714_create_airline_suppliers_table',
                'batch' => 1,
            ),
            266 => 
            array (
                'id' => 267,
                'migration' => '2025_05_31_213714_create_airlines_table',
                'batch' => 1,
            ),
            267 => 
            array (
                'id' => 268,
                'migration' => '2025_05_31_213714_create_areas_table',
                'batch' => 1,
            ),
            268 => 
            array (
                'id' => 269,
                'migration' => '2025_05_31_213714_create_article_rate_table',
                'batch' => 1,
            ),
            269 => 
            array (
                'id' => 270,
                'migration' => '2025_05_31_213714_create_article_tag_table',
                'batch' => 1,
            ),
            270 => 
            array (
                'id' => 271,
                'migration' => '2025_05_31_213714_create_article_translations_table',
                'batch' => 1,
            ),
            271 => 
            array (
                'id' => 272,
                'migration' => '2025_05_31_213714_create_articles_table',
                'batch' => 1,
            ),
            272 => 
            array (
                'id' => 273,
                'migration' => '2025_05_31_213714_create_articlesettings_table',
                'batch' => 1,
            ),
            273 => 
            array (
                'id' => 274,
                'migration' => '2025_05_31_213714_create_asset_maintenances_table',
                'batch' => 1,
            ),
            274 => 
            array (
                'id' => 275,
                'migration' => '2025_05_31_213714_create_asset_transactions_table',
                'batch' => 1,
            ),
            275 => 
            array (
                'id' => 276,
                'migration' => '2025_05_31_213714_create_asset_warranties_table',
                'batch' => 1,
            ),
            276 => 
            array (
                'id' => 277,
                'migration' => '2025_05_31_213714_create_assets_table',
                'batch' => 1,
            ),
            277 => 
            array (
                'id' => 278,
                'migration' => '2025_05_31_213714_create_attendances_table',
                'batch' => 1,
            ),
            278 => 
            array (
                'id' => 279,
                'migration' => '2025_05_31_213714_create_audits_table',
                'batch' => 1,
            ),
            279 => 
            array (
                'id' => 280,
                'migration' => '2025_05_31_213714_create_bakery_drivers_table',
                'batch' => 1,
            ),
            280 => 
            array (
                'id' => 281,
                'migration' => '2025_05_31_213714_create_bakery_fleets_table',
                'batch' => 1,
            ),
            281 => 
            array (
                'id' => 282,
                'migration' => '2025_05_31_213714_create_bakery_invoice_numbers_table',
                'batch' => 1,
            ),
            282 => 
            array (
                'id' => 283,
                'migration' => '2025_05_31_213714_create_bakery_loading_products_table',
                'batch' => 1,
            ),
            283 => 
            array (
                'id' => 284,
                'migration' => '2025_05_31_213714_create_bakery_loading_return_products_table',
                'batch' => 1,
            ),
            284 => 
            array (
                'id' => 285,
                'migration' => '2025_05_31_213714_create_bakery_loading_returns_table',
                'batch' => 1,
            ),
            285 => 
            array (
                'id' => 286,
                'migration' => '2025_05_31_213714_create_bakery_loadings_table',
                'batch' => 1,
            ),
            286 => 
            array (
                'id' => 287,
                'migration' => '2025_05_31_213714_create_bakery_opening_balance_table',
                'batch' => 1,
            ),
            287 => 
            array (
                'id' => 288,
                'migration' => '2025_05_31_213714_create_bakery_products_table',
                'batch' => 1,
            ),
            288 => 
            array (
                'id' => 289,
                'migration' => '2025_05_31_213714_create_bakery_routes_table',
                'batch' => 1,
            ),
            289 => 
            array (
                'id' => 290,
                'migration' => '2025_05_31_213714_create_bakery_users_table',
                'batch' => 1,
            ),
            290 => 
            array (
                'id' => 291,
                'migration' => '2025_05_31_213714_create_balamandalayas_table',
                'batch' => 1,
            ),
            291 => 
            array (
                'id' => 292,
                'migration' => '2025_05_31_213714_create_barcodes_table',
                'batch' => 1,
            ),
            292 => 
            array (
                'id' => 293,
                'migration' => '2025_05_31_213714_create_base_change_log_table',
                'batch' => 1,
            ),
            293 => 
            array (
                'id' => 294,
                'migration' => '2025_05_31_213714_create_basic_salaries_table',
                'batch' => 1,
            ),
            294 => 
            array (
                'id' => 295,
                'migration' => '2025_05_31_213714_create_block_close_reasons_table',
                'batch' => 1,
            ),
            295 => 
            array (
                'id' => 296,
                'migration' => '2025_05_31_213714_create_boat_trips_table',
                'batch' => 1,
            ),
            296 => 
            array (
                'id' => 297,
                'migration' => '2025_05_31_213714_create_bookings_table',
                'batch' => 1,
            ),
            297 => 
            array (
                'id' => 298,
                'migration' => '2025_05_31_213714_create_brands_table',
                'batch' => 1,
            ),
            298 => 
            array (
                'id' => 299,
                'migration' => '2025_05_31_213714_create_business_table',
                'batch' => 1,
            ),
            299 => 
            array (
                'id' => 300,
                'migration' => '2025_05_31_213714_create_business_account_numbers_table',
                'batch' => 1,
            ),
            300 => 
            array (
                'id' => 301,
                'migration' => '2025_05_31_213714_create_business_dsr_officers_table',
                'batch' => 1,
            ),
            301 => 
            array (
                'id' => 302,
                'migration' => '2025_05_31_213714_create_business_locations_table',
                'batch' => 1,
            ),
            302 => 
            array (
                'id' => 303,
                'migration' => '2025_05_31_213714_create_business_reports_configurations_table',
                'batch' => 1,
            ),
            303 => 
            array (
                'id' => 304,
                'migration' => '2025_05_31_213714_create_bussines_categories_table',
                'batch' => 1,
            ),
            304 => 
            array (
                'id' => 305,
                'migration' => '2025_05_31_213714_create_cancel_cheque_table',
                'batch' => 1,
            ),
            305 => 
            array (
                'id' => 306,
                'migration' => '2025_05_31_213714_create_cash_register_transactions_table',
                'batch' => 1,
            ),
            306 => 
            array (
                'id' => 307,
                'migration' => '2025_05_31_213714_create_cash_registers_table',
                'batch' => 1,
            ),
            307 => 
            array (
                'id' => 308,
                'migration' => '2025_05_31_213714_create_categories_table',
                'batch' => 1,
            ),
            308 => 
            array (
                'id' => 309,
                'migration' => '2025_05_31_213714_create_categorizables_table',
                'batch' => 1,
            ),
            309 => 
            array (
                'id' => 310,
                'migration' => '2025_05_31_213714_create_cheque_deposit_bank_table',
                'batch' => 1,
            ),
            310 => 
            array (
                'id' => 311,
                'migration' => '2025_05_31_213714_create_cheque_number_maintains_table',
                'batch' => 1,
            ),
            311 => 
            array (
                'id' => 312,
                'migration' => '2025_05_31_213714_create_cheque_numbers_table',
                'batch' => 1,
            ),
            312 => 
            array (
                'id' => 313,
                'migration' => '2025_05_31_213714_create_cheque_numbers_m_entries_table',
                'batch' => 1,
            ),
            313 => 
            array (
                'id' => 314,
                'migration' => '2025_05_31_213714_create_cheque_templates_table',
                'batch' => 1,
            ),
            314 => 
            array (
                'id' => 315,
                'migration' => '2025_05_31_213714_create_chequer_bank_accounts_table',
                'batch' => 1,
            ),
            315 => 
            array (
                'id' => 316,
                'migration' => '2025_05_31_213714_create_chequer_currencies_table',
                'batch' => 1,
            ),
            316 => 
            array (
                'id' => 317,
                'migration' => '2025_05_31_213714_create_chequer_default_settings_table',
                'batch' => 1,
            ),
            317 => 
            array (
                'id' => 318,
                'migration' => '2025_05_31_213714_create_chequer_purchase_orders_table',
                'batch' => 1,
            ),
            318 => 
            array (
                'id' => 319,
                'migration' => '2025_05_31_213714_create_chequer_stamps_table',
                'batch' => 1,
            ),
            319 => 
            array (
                'id' => 320,
                'migration' => '2025_05_31_213714_create_chequer_suppliers_table',
                'batch' => 1,
            ),
            320 => 
            array (
                'id' => 321,
                'migration' => '2025_05_31_213714_create_client_responses_table',
                'batch' => 1,
            ),
            321 => 
            array (
                'id' => 322,
                'migration' => '2025_05_31_213714_create_close_current_sales_table',
                'batch' => 1,
            ),
            322 => 
            array (
                'id' => 323,
                'migration' => '2025_05_31_213714_create_collection_officers_table',
                'batch' => 1,
            ),
            323 => 
            array (
                'id' => 324,
                'migration' => '2025_05_31_213714_create_company_package_variables_table',
                'batch' => 1,
            ),
            324 => 
            array (
                'id' => 325,
                'migration' => '2025_05_31_213714_create_components_table',
                'batch' => 1,
            ),
            325 => 
            array (
                'id' => 326,
                'migration' => '2025_05_31_213714_create_config_table',
                'batch' => 1,
            ),
            326 => 
            array (
                'id' => 327,
                'migration' => '2025_05_31_213714_create_contact_groups_table',
                'batch' => 1,
            ),
            327 => 
            array (
                'id' => 328,
                'migration' => '2025_05_31_213714_create_contact_ledgers_table',
                'batch' => 1,
            ),
            328 => 
            array (
                'id' => 329,
                'migration' => '2025_05_31_213714_create_contact_linked_accounts_table',
                'batch' => 1,
            ),
            329 => 
            array (
                'id' => 330,
                'migration' => '2025_05_31_213714_create_contacts_table',
                'batch' => 1,
            ),
            330 => 
            array (
                'id' => 331,
                'migration' => '2025_05_31_213714_create_countries_table',
                'batch' => 1,
            ),
            331 => 
            array (
                'id' => 332,
                'migration' => '2025_05_31_213714_create_crews_table',
                'batch' => 1,
            ),
            332 => 
            array (
                'id' => 333,
                'migration' => '2025_05_31_213714_create_crm_activities_table',
                'batch' => 1,
            ),
            333 => 
            array (
                'id' => 334,
                'migration' => '2025_05_31_213714_create_crm_activity_details_table',
                'batch' => 1,
            ),
            334 => 
            array (
                'id' => 335,
                'migration' => '2025_05_31_213714_create_crm_call_logs_table',
                'batch' => 1,
            ),
            335 => 
            array (
                'id' => 336,
                'migration' => '2025_05_31_213714_create_crm_campaigns_table',
                'batch' => 1,
            ),
            336 => 
            array (
                'id' => 337,
                'migration' => '2025_05_31_213714_create_crm_comments_table',
                'batch' => 1,
            ),
            337 => 
            array (
                'id' => 338,
                'migration' => '2025_05_31_213714_create_crm_contact_person_commissions_table',
                'batch' => 1,
            ),
            338 => 
            array (
                'id' => 339,
                'migration' => '2025_05_31_213714_create_crm_followup_invoices_table',
                'batch' => 1,
            ),
            339 => 
            array (
                'id' => 340,
                'migration' => '2025_05_31_213714_create_crm_groups_table',
                'batch' => 1,
            ),
            340 => 
            array (
                'id' => 341,
                'migration' => '2025_05_31_213714_create_crm_lead_users_table',
                'batch' => 1,
            ),
            341 => 
            array (
                'id' => 342,
                'migration' => '2025_05_31_213714_create_crm_marketplaces_table',
                'batch' => 1,
            ),
            342 => 
            array (
                'id' => 343,
                'migration' => '2025_05_31_213714_create_crm_proposal_templates_table',
                'batch' => 1,
            ),
            343 => 
            array (
                'id' => 344,
                'migration' => '2025_05_31_213714_create_crm_proposals_table',
                'batch' => 1,
            ),
            344 => 
            array (
                'id' => 345,
                'migration' => '2025_05_31_213714_create_crm_schedule_logs_table',
                'batch' => 1,
            ),
            345 => 
            array (
                'id' => 346,
                'migration' => '2025_05_31_213714_create_crm_schedule_users_table',
                'batch' => 1,
            ),
            346 => 
            array (
                'id' => 347,
                'migration' => '2025_05_31_213714_create_crm_schedules_table',
                'batch' => 1,
            ),
            347 => 
            array (
                'id' => 348,
                'migration' => '2025_05_31_213714_create_crms_table',
                'batch' => 1,
            ),
            348 => 
            array (
                'id' => 349,
                'migration' => '2025_05_31_213714_create_currencies_table',
                'batch' => 1,
            ),
            349 => 
            array (
                'id' => 350,
                'migration' => '2025_05_31_213714_create_current_meters_table',
                'batch' => 1,
            ),
            350 => 
            array (
                'id' => 351,
                'migration' => '2025_05_31_213714_create_custom_fields_table',
                'batch' => 1,
            ),
            351 => 
            array (
                'id' => 352,
                'migration' => '2025_05_31_213714_create_customer_bill_vat_prefixes_table',
                'batch' => 1,
            ),
            352 => 
            array (
                'id' => 353,
                'migration' => '2025_05_31_213714_create_customer_groups_table',
                'batch' => 1,
            ),
            353 => 
            array (
                'id' => 354,
                'migration' => '2025_05_31_213714_create_customer_payments_table',
                'batch' => 1,
            ),
            354 => 
            array (
                'id' => 355,
                'migration' => '2025_05_31_213714_create_customer_purchases_table',
                'batch' => 1,
            ),
            355 => 
            array (
                'id' => 356,
                'migration' => '2025_05_31_213714_create_customer_references_table',
                'batch' => 1,
            ),
            356 => 
            array (
                'id' => 357,
                'migration' => '2025_05_31_213714_create_customer_sms_settings_table',
                'batch' => 1,
            ),
            357 => 
            array (
                'id' => 358,
                'migration' => '2025_05_31_213714_create_customer_statement_details_table',
                'batch' => 1,
            ),
            358 => 
            array (
                'id' => 359,
                'migration' => '2025_05_31_213714_create_customer_statement_font_settings_table',
                'batch' => 1,
            ),
            359 => 
            array (
                'id' => 360,
                'migration' => '2025_05_31_213714_create_customer_statement_logos_table',
                'batch' => 1,
            ),
            360 => 
            array (
                'id' => 361,
                'migration' => '2025_05_31_213714_create_customer_statement_settings_table',
                'batch' => 1,
            ),
            361 => 
            array (
                'id' => 362,
                'migration' => '2025_05_31_213714_create_customer_statements_table',
                'batch' => 1,
            ),
            362 => 
            array (
                'id' => 363,
                'migration' => '2025_05_31_213714_create_customers_table',
                'batch' => 1,
            ),
            363 => 
            array (
                'id' => 364,
                'migration' => '2025_05_31_213714_create_daily_cards_table',
                'batch' => 1,
            ),
            364 => 
            array (
                'id' => 365,
                'migration' => '2025_05_31_213714_create_daily_cheque_payments_table',
                'batch' => 1,
            ),
            365 => 
            array (
                'id' => 366,
                'migration' => '2025_05_31_213714_create_daily_collections_table',
                'batch' => 1,
            ),
            366 => 
            array (
                'id' => 367,
                'migration' => '2025_05_31_213714_create_daily_report_review_status_table',
                'batch' => 1,
            ),
            367 => 
            array (
                'id' => 368,
                'migration' => '2025_05_31_213714_create_daily_voucher_items_table',
                'batch' => 1,
            ),
            368 => 
            array (
                'id' => 369,
                'migration' => '2025_05_31_213714_create_daily_vouchers_table',
                'batch' => 1,
            ),
            369 => 
            array (
                'id' => 370,
                'migration' => '2025_05_31_213714_create_day_count_settings_table',
                'batch' => 1,
            ),
            370 => 
            array (
                'id' => 371,
                'migration' => '2025_05_31_213714_create_day_ends_table',
                'batch' => 1,
            ),
            371 => 
            array (
                'id' => 372,
                'migration' => '2025_05_31_213714_create_default_account_groups_table',
                'batch' => 1,
            ),
            372 => 
            array (
                'id' => 373,
                'migration' => '2025_05_31_213714_create_default_account_types_table',
                'batch' => 1,
            ),
            373 => 
            array (
                'id' => 374,
                'migration' => '2025_05_31_213714_create_default_accounts_table',
                'batch' => 1,
            ),
            374 => 
            array (
                'id' => 375,
                'migration' => '2025_05_31_213714_create_default_expense_categories_table',
                'batch' => 1,
            ),
            375 => 
            array (
                'id' => 376,
                'migration' => '2025_05_31_213714_create_default_fonts_table',
                'batch' => 1,
            ),
            376 => 
            array (
                'id' => 377,
                'migration' => '2025_05_31_213714_create_default_group_sub_taxes_table',
                'batch' => 1,
            ),
            377 => 
            array (
                'id' => 378,
                'migration' => '2025_05_31_213714_create_default_notification_templates_table',
                'batch' => 1,
            ),
            378 => 
            array (
                'id' => 379,
                'migration' => '2025_05_31_213714_create_default_product_categories_table',
                'batch' => 1,
            ),
            379 => 
            array (
                'id' => 380,
                'migration' => '2025_05_31_213714_create_default_tax_rates_table',
                'batch' => 1,
            ),
            380 => 
            array (
                'id' => 381,
                'migration' => '2025_05_31_213714_create_denominations_table',
                'batch' => 1,
            ),
            381 => 
            array (
                'id' => 382,
                'migration' => '2025_05_31_213714_create_departments_table',
                'batch' => 1,
            ),
            382 => 
            array (
                'id' => 383,
                'migration' => '2025_05_31_213714_create_designated_dsr_officers_table',
                'batch' => 1,
            ),
            383 => 
            array (
                'id' => 384,
                'migration' => '2025_05_31_213714_create_dip_readings_table',
                'batch' => 1,
            ),
            384 => 
            array (
                'id' => 385,
                'migration' => '2025_05_31_213714_create_dip_resettings_table',
                'batch' => 1,
            ),
            385 => 
            array (
                'id' => 386,
                'migration' => '2025_05_31_213714_create_discountlevels_table',
                'batch' => 1,
            ),
            386 => 
            array (
                'id' => 387,
                'migration' => '2025_05_31_213714_create_discounts_table',
                'batch' => 1,
            ),
            387 => 
            array (
                'id' => 388,
                'migration' => '2025_05_31_213714_create_distribution_areas_table',
                'batch' => 1,
            ),
            388 => 
            array (
                'id' => 389,
                'migration' => '2025_05_31_213714_create_distribution_districts_table',
                'batch' => 1,
            ),
            389 => 
            array (
                'id' => 390,
                'migration' => '2025_05_31_213714_create_distribution_provinces_table',
                'batch' => 1,
            ),
            390 => 
            array (
                'id' => 391,
                'migration' => '2025_05_31_213714_create_distribution_routes_table',
                'batch' => 1,
            ),
            391 => 
            array (
                'id' => 392,
                'migration' => '2025_05_31_213714_create_districts_table',
                'batch' => 1,
            ),
            392 => 
            array (
                'id' => 393,
                'migration' => '2025_05_31_213714_create_doc_management_categories_table',
                'batch' => 1,
            ),
            393 => 
            array (
                'id' => 394,
                'migration' => '2025_05_31_213714_create_doc_management_forward_withs_table',
                'batch' => 1,
            ),
            394 => 
            array (
                'id' => 395,
                'migration' => '2025_05_31_213714_create_doc_management_logos_table',
                'batch' => 1,
            ),
            395 => 
            array (
                'id' => 396,
                'migration' => '2025_05_31_213714_create_doc_management_mandatory_signatures_table',
                'batch' => 1,
            ),
            396 => 
            array (
                'id' => 397,
                'migration' => '2025_05_31_213714_create_doc_management_purposes_table',
                'batch' => 1,
            ),
            397 => 
            array (
                'id' => 398,
                'migration' => '2025_05_31_213714_create_doc_management_signatures_table',
                'batch' => 1,
            ),
            398 => 
            array (
                'id' => 399,
                'migration' => '2025_05_31_213714_create_doc_management_types_table',
                'batch' => 1,
            ),
            399 => 
            array (
                'id' => 400,
                'migration' => '2025_05_31_213714_create_doc_management_uploads_table',
                'batch' => 1,
            ),
            400 => 
            array (
                'id' => 401,
                'migration' => '2025_05_31_213714_create_document_and_notes_table',
                'batch' => 1,
            ),
            401 => 
            array (
                'id' => 402,
                'migration' => '2025_05_31_213714_create_domains_table',
                'batch' => 1,
            ),
            402 => 
            array (
                'id' => 403,
                'migration' => '2025_05_31_213714_create_drivers_table',
                'batch' => 1,
            ),
            403 => 
            array (
                'id' => 404,
                'migration' => '2025_05_31_213714_create_dsr_settings_table',
                'batch' => 1,
            ),
            404 => 
            array (
                'id' => 405,
                'migration' => '2025_05_31_213714_create_edit_account_entries_table',
                'batch' => 1,
            ),
            405 => 
            array (
                'id' => 406,
                'migration' => '2025_05_31_213714_create_edit_contact_entries_table',
                'batch' => 1,
            ),
            406 => 
            array (
                'id' => 407,
                'migration' => '2025_05_31_213714_create_electrorates_table',
                'batch' => 1,
            ),
            407 => 
            array (
                'id' => 408,
                'migration' => '2025_05_31_213714_create_employee_awards_table',
                'batch' => 1,
            ),
            408 => 
            array (
                'id' => 409,
                'migration' => '2025_05_31_213714_create_employees_table',
                'batch' => 1,
            ),
            409 => 
            array (
                'id' => 410,
                'migration' => '2025_05_31_213714_create_employment_statuses_table',
                'batch' => 1,
            ),
            410 => 
            array (
                'id' => 411,
                'migration' => '2025_05_31_213714_create_essentials_allowances_and_deductions_table',
                'batch' => 1,
            ),
            411 => 
            array (
                'id' => 412,
                'migration' => '2025_05_31_213714_create_essentials_attendances_table',
                'batch' => 1,
            ),
            412 => 
            array (
                'id' => 413,
                'migration' => '2025_05_31_213714_create_essentials_document_shares_table',
                'batch' => 1,
            ),
            413 => 
            array (
                'id' => 414,
                'migration' => '2025_05_31_213714_create_essentials_documents_table',
                'batch' => 1,
            ),
            414 => 
            array (
                'id' => 415,
                'migration' => '2025_05_31_213714_create_essentials_employee_advances_table',
                'batch' => 1,
            ),
            415 => 
            array (
                'id' => 416,
                'migration' => '2025_05_31_213714_create_essentials_employee_payment_settings_table',
                'batch' => 1,
            ),
            416 => 
            array (
                'id' => 417,
                'migration' => '2025_05_31_213714_create_essentials_employees_table',
                'batch' => 1,
            ),
            417 => 
            array (
                'id' => 418,
                'migration' => '2025_05_31_213714_create_essentials_employees_salary_details_table',
                'batch' => 1,
            ),
            418 => 
            array (
                'id' => 419,
                'migration' => '2025_05_31_213714_create_essentials_holidays_table',
                'batch' => 1,
            ),
            419 => 
            array (
                'id' => 420,
                'migration' => '2025_05_31_213714_create_essentials_kb_table',
                'batch' => 1,
            ),
            420 => 
            array (
                'id' => 421,
                'migration' => '2025_05_31_213714_create_essentials_kb_users_table',
                'batch' => 1,
            ),
            421 => 
            array (
                'id' => 422,
                'migration' => '2025_05_31_213714_create_essentials_leave_types_table',
                'batch' => 1,
            ),
            422 => 
            array (
                'id' => 423,
                'migration' => '2025_05_31_213714_create_essentials_leaves_table',
                'batch' => 1,
            ),
            423 => 
            array (
                'id' => 424,
                'migration' => '2025_05_31_213714_create_essentials_messages_table',
                'batch' => 1,
            ),
            424 => 
            array (
                'id' => 425,
                'migration' => '2025_05_31_213714_create_essentials_payroll_group_transactions_table',
                'batch' => 1,
            ),
            425 => 
            array (
                'id' => 426,
                'migration' => '2025_05_31_213714_create_essentials_payroll_groups_table',
                'batch' => 1,
            ),
            426 => 
            array (
                'id' => 427,
                'migration' => '2025_05_31_213714_create_essentials_reminders_table',
                'batch' => 1,
            ),
            427 => 
            array (
                'id' => 428,
                'migration' => '2025_05_31_213714_create_essentials_shifts_table',
                'batch' => 1,
            ),
            428 => 
            array (
                'id' => 429,
                'migration' => '2025_05_31_213714_create_essentials_to_dos_table',
                'batch' => 1,
            ),
            429 => 
            array (
                'id' => 430,
                'migration' => '2025_05_31_213714_create_essentials_todo_comments_table',
                'batch' => 1,
            ),
            430 => 
            array (
                'id' => 431,
                'migration' => '2025_05_31_213714_create_essentials_todos_users_table',
                'batch' => 1,
            ),
            431 => 
            array (
                'id' => 432,
                'migration' => '2025_05_31_213714_create_essentials_user_allowance_and_deductions_table',
                'batch' => 1,
            ),
            432 => 
            array (
                'id' => 433,
                'migration' => '2025_05_31_213714_create_essentials_user_sales_targets_table',
                'batch' => 1,
            ),
            433 => 
            array (
                'id' => 434,
                'migration' => '2025_05_31_213714_create_essentials_user_shifts_table',
                'batch' => 1,
            ),
            434 => 
            array (
                'id' => 435,
                'migration' => '2025_05_31_213714_create_expense_categories_table',
                'batch' => 1,
            ),
            435 => 
            array (
                'id' => 436,
                'migration' => '2025_05_31_213714_create_expense_categories_codes_table',
                'batch' => 1,
            ),
            436 => 
            array (
                'id' => 437,
                'migration' => '2025_05_31_213714_create_ezyinvoice_credit_sale_payments_table',
                'batch' => 1,
            ),
            437 => 
            array (
                'id' => 438,
                'migration' => '2025_05_31_213714_create_ezyinvoices_table',
                'batch' => 1,
            ),
            438 => 
            array (
                'id' => 439,
                'migration' => '2025_05_31_213714_create_family_subscriptions_table',
                'batch' => 1,
            ),
            439 => 
            array (
                'id' => 440,
                'migration' => '2025_05_31_213714_create_fazol_table',
                'batch' => 1,
            ),
            440 => 
            array (
                'id' => 441,
                'migration' => '2025_05_31_213714_create_finance_options_table',
                'batch' => 1,
            ),
            441 => 
            array (
                'id' => 442,
                'migration' => '2025_05_31_213714_create_fixed_assets_table',
                'batch' => 1,
            ),
            442 => 
            array (
                'id' => 443,
                'migration' => '2025_05_31_213714_create_fleet_account_numbers_table',
                'batch' => 1,
            ),
            443 => 
            array (
                'id' => 444,
                'migration' => '2025_05_31_213714_create_fleet_contact_ledgers_table',
                'batch' => 1,
            ),
            444 => 
            array (
                'id' => 445,
                'migration' => '2025_05_31_213714_create_fleet_fuel_details_table',
                'batch' => 1,
            ),
            445 => 
            array (
                'id' => 446,
                'migration' => '2025_05_31_213714_create_fleet_fuel_types_table',
                'batch' => 1,
            ),
            446 => 
            array (
                'id' => 447,
                'migration' => '2025_05_31_213714_create_fleet_invoice_details_table',
                'batch' => 1,
            ),
            447 => 
            array (
                'id' => 448,
                'migration' => '2025_05_31_213714_create_fleet_invoices_table',
                'batch' => 1,
            ),
            448 => 
            array (
                'id' => 449,
                'migration' => '2025_05_31_213714_create_fleet_logos_table',
                'batch' => 1,
            ),
            449 => 
            array (
                'id' => 450,
                'migration' => '2025_05_31_213714_create_fleet_original_locations_table',
                'batch' => 1,
            ),
            450 => 
            array (
                'id' => 451,
                'migration' => '2025_05_31_213714_create_fleet_vat_invoice_details_2_table',
                'batch' => 1,
            ),
            451 => 
            array (
                'id' => 452,
                'migration' => '2025_05_31_213714_create_fleet_vat_invoice_payments_2_table',
                'batch' => 1,
            ),
            452 => 
            array (
                'id' => 453,
                'migration' => '2025_05_31_213714_create_fleet_vat_invoices_2_table',
                'batch' => 1,
            ),
            453 => 
            array (
                'id' => 454,
                'migration' => '2025_05_31_213714_create_fleets_table',
                'batch' => 1,
            ),
            454 => 
            array (
                'id' => 455,
                'migration' => '2025_05_31_213714_create_form9c_sub_categories_table',
                'batch' => 1,
            ),
            455 => 
            array (
                'id' => 456,
                'migration' => '2025_05_31_213714_create_form_f15_transaction_data_table',
                'batch' => 1,
            ),
            456 => 
            array (
                'id' => 457,
                'migration' => '2025_05_31_213714_create_form_f16_details_table',
                'batch' => 1,
            ),
            457 => 
            array (
                'id' => 458,
                'migration' => '2025_05_31_213714_create_form_f17_details_table',
                'batch' => 1,
            ),
            458 => 
            array (
                'id' => 459,
                'migration' => '2025_05_31_213714_create_form_f17_headers_table',
                'batch' => 1,
            ),
            459 => 
            array (
                'id' => 460,
                'migration' => '2025_05_31_213714_create_form_f22_details_table',
                'batch' => 1,
            ),
            460 => 
            array (
                'id' => 461,
                'migration' => '2025_05_31_213714_create_form_f22_headers_table',
                'batch' => 1,
            ),
            461 => 
            array (
                'id' => 462,
                'migration' => '2025_05_31_213714_create_form_f22_loss_gains_table',
                'batch' => 1,
            ),
            462 => 
            array (
                'id' => 463,
                'migration' => '2025_05_31_213714_create_fuel_providers_table',
                'batch' => 1,
            ),
            463 => 
            array (
                'id' => 464,
                'migration' => '2025_05_31_213714_create_fuel_tanks_table',
                'batch' => 1,
            ),
            464 => 
            array (
                'id' => 465,
                'migration' => '2025_05_31_213714_create_fuel_types_table',
                'batch' => 1,
            ),
            465 => 
            array (
                'id' => 466,
                'migration' => '2025_05_31_213714_create_galleries_table',
                'batch' => 1,
            ),
            466 => 
            array (
                'id' => 467,
                'migration' => '2025_05_31_213714_create_give_away_gifts_table',
                'batch' => 1,
            ),
            467 => 
            array (
                'id' => 468,
                'migration' => '2025_05_31_213714_create_gold_grades_table',
                'batch' => 1,
            ),
            468 => 
            array (
                'id' => 469,
                'migration' => '2025_05_31_213714_create_gold_prices_table',
                'batch' => 1,
            ),
            469 => 
            array (
                'id' => 470,
                'migration' => '2025_05_31_213714_create_gold_productions_table',
                'batch' => 1,
            ),
            470 => 
            array (
                'id' => 471,
                'migration' => '2025_05_31_213714_create_gold_smiths_table',
                'batch' => 1,
            ),
            471 => 
            array (
                'id' => 472,
                'migration' => '2025_05_31_213714_create_gramaseva_vasamas_table',
                'batch' => 1,
            ),
            472 => 
            array (
                'id' => 473,
                'migration' => '2025_05_31_213714_create_group_sub_taxes_table',
                'batch' => 1,
            ),
            473 => 
            array (
                'id' => 474,
                'migration' => '2025_05_31_213714_create_help_explanations_table',
                'batch' => 1,
            ),
            474 => 
            array (
                'id' => 475,
                'migration' => '2025_05_31_213714_create_helpers_table',
                'batch' => 1,
            ),
            475 => 
            array (
                'id' => 476,
                'migration' => '2025_05_31_213714_create_helpguide_categories_table',
                'batch' => 1,
            ),
            476 => 
            array (
                'id' => 477,
                'migration' => '2025_05_31_213714_create_helpguide_media_table',
                'batch' => 1,
            ),
            477 => 
            array (
                'id' => 478,
                'migration' => '2025_05_31_213714_create_helpguide_model_has_permissions_table',
                'batch' => 1,
            ),
            478 => 
            array (
                'id' => 479,
                'migration' => '2025_05_31_213714_create_helpguide_model_has_roles_table',
                'batch' => 1,
            ),
            479 => 
            array (
                'id' => 480,
                'migration' => '2025_05_31_213714_create_helpguide_permissions_table',
                'batch' => 1,
            ),
            480 => 
            array (
                'id' => 481,
                'migration' => '2025_05_31_213714_create_helpguide_role_has_permissions_table',
                'batch' => 1,
            ),
            481 => 
            array (
                'id' => 482,
                'migration' => '2025_05_31_213714_create_helpguide_roles_table',
                'batch' => 1,
            ),
            482 => 
            array (
                'id' => 483,
                'migration' => '2025_05_31_213714_create_helpguide_settings_table',
                'batch' => 1,
            ),
            483 => 
            array (
                'id' => 484,
                'migration' => '2025_05_31_213714_create_hms_booking_extras_table',
                'batch' => 1,
            ),
            484 => 
            array (
                'id' => 485,
                'migration' => '2025_05_31_213714_create_hms_booking_lines_table',
                'batch' => 1,
            ),
            485 => 
            array (
                'id' => 486,
                'migration' => '2025_05_31_213714_create_hms_coupons_table',
                'batch' => 1,
            ),
            486 => 
            array (
                'id' => 487,
                'migration' => '2025_05_31_213714_create_hms_customer_coupon_usage_table',
                'batch' => 1,
            ),
            487 => 
            array (
                'id' => 488,
                'migration' => '2025_05_31_213714_create_hms_extras_table',
                'batch' => 1,
            ),
            488 => 
            array (
                'id' => 489,
                'migration' => '2025_05_31_213714_create_hms_room_type_pricings_table',
                'batch' => 1,
            ),
            489 => 
            array (
                'id' => 490,
                'migration' => '2025_05_31_213714_create_hms_room_types_table',
                'batch' => 1,
            ),
            490 => 
            array (
                'id' => 491,
                'migration' => '2025_05_31_213714_create_hms_room_unavailables_table',
                'batch' => 1,
            ),
            491 => 
            array (
                'id' => 492,
                'migration' => '2025_05_31_213714_create_hms_rooms_table',
                'batch' => 1,
            ),
            492 => 
            array (
                'id' => 493,
                'migration' => '2025_05_31_213714_create_hms_transactions_table',
                'batch' => 1,
            ),
            493 => 
            array (
                'id' => 494,
                'migration' => '2025_05_31_213714_create_holidays_table',
                'batch' => 1,
            ),
            494 => 
            array (
                'id' => 495,
                'migration' => '2025_05_31_213714_create_hr_prefixes_table',
                'batch' => 1,
            ),
            495 => 
            array (
                'id' => 496,
                'migration' => '2025_05_31_213714_create_hr_settings_table',
                'batch' => 1,
            ),
            496 => 
            array (
                'id' => 497,
                'migration' => '2025_05_31_213714_create_hrm_departments_table',
                'batch' => 1,
            ),
            497 => 
            array (
                'id' => 498,
                'migration' => '2025_05_31_213714_create_hrm_designations_table',
                'batch' => 1,
            ),
            498 => 
            array (
                'id' => 499,
                'migration' => '2025_05_31_213714_create_hrm_employee_ledgers_table',
                'batch' => 1,
            ),
            499 => 
            array (
                'id' => 500,
                'migration' => '2025_05_31_213714_create_income_methods_table',
                'batch' => 1,
            ),
        ));
        \DB::table('migrations')->insert(array (
            0 => 
            array (
                'id' => 501,
                'migration' => '2025_05_31_213714_create_income_settings_table',
                'batch' => 1,
            ),
            1 => 
            array (
                'id' => 502,
                'migration' => '2025_05_31_213714_create_installment_cycles_table',
                'batch' => 1,
            ),
            2 => 
            array (
                'id' => 503,
                'migration' => '2025_05_31_213714_create_installment_db_table',
                'batch' => 1,
            ),
            3 => 
            array (
                'id' => 504,
                'migration' => '2025_05_31_213714_create_installment_systems_table',
                'batch' => 1,
            ),
            4 => 
            array (
                'id' => 505,
                'migration' => '2025_05_31_213714_create_installments_table',
                'batch' => 1,
            ),
            5 => 
            array (
                'id' => 506,
                'migration' => '2025_05_31_213714_create_installments_table_table',
                'batch' => 1,
            ),
            6 => 
            array (
                'id' => 507,
                'migration' => '2025_05_31_213714_create_interest_settings_table',
                'batch' => 1,
            ),
            7 => 
            array (
                'id' => 508,
                'migration' => '2025_05_31_213714_create_invoice_layouts_table',
                'batch' => 1,
            ),
            8 => 
            array (
                'id' => 509,
                'migration' => '2025_05_31_213714_create_invoice_schemes_table',
                'batch' => 1,
            ),
            9 => 
            array (
                'id' => 510,
                'migration' => '2025_05_31_213714_create_issue_customer_bill_details_table',
                'batch' => 1,
            ),
            10 => 
            array (
                'id' => 511,
                'migration' => '2025_05_31_213714_create_issue_customer_bill_with_vat_table',
                'batch' => 1,
            ),
            11 => 
            array (
                'id' => 512,
                'migration' => '2025_05_31_213714_create_issue_customer_bill_with_vat_details_table',
                'batch' => 1,
            ),
            12 => 
            array (
                'id' => 513,
                'migration' => '2025_05_31_213714_create_issue_customer_bills_table',
                'batch' => 1,
            ),
            13 => 
            array (
                'id' => 514,
                'migration' => '2025_05_31_213714_create_job_categories_table',
                'batch' => 1,
            ),
            14 => 
            array (
                'id' => 515,
                'migration' => '2025_05_31_213714_create_job_titles_table',
                'batch' => 1,
            ),
            15 => 
            array (
                'id' => 516,
                'migration' => '2025_05_31_213714_create_journals_table',
                'batch' => 1,
            ),
            16 => 
            array (
                'id' => 517,
                'migration' => '2025_05_31_213714_create_languages_table',
                'batch' => 1,
            ),
            17 => 
            array (
                'id' => 518,
                'migration' => '2025_05_31_213714_create_leads_table',
                'batch' => 1,
            ),
            18 => 
            array (
                'id' => 519,
                'migration' => '2025_05_31_213714_create_leads_categories_table',
                'batch' => 1,
            ),
            19 => 
            array (
                'id' => 520,
                'migration' => '2025_05_31_213714_create_leads_labels_table',
                'batch' => 1,
            ),
            20 => 
            array (
                'id' => 521,
                'migration' => '2025_05_31_213714_create_leads_settings_table',
                'batch' => 1,
            ),
            21 => 
            array (
                'id' => 522,
                'migration' => '2025_05_31_213714_create_leave_application_types_table',
                'batch' => 1,
            ),
            22 => 
            array (
                'id' => 523,
                'migration' => '2025_05_31_213714_create_leave_applications_table',
                'batch' => 1,
            ),
            23 => 
            array (
                'id' => 524,
                'migration' => '2025_05_31_213714_create_leave_requests_table',
                'batch' => 1,
            ),
            24 => 
            array (
                'id' => 525,
                'migration' => '2025_05_31_213714_create_loan_applications_table',
                'batch' => 1,
            ),
            25 => 
            array (
                'id' => 526,
                'migration' => '2025_05_31_213714_create_loan_approval_officers_table',
                'batch' => 1,
            ),
            26 => 
            array (
                'id' => 527,
                'migration' => '2025_05_31_213714_create_loan_charge_options_table',
                'batch' => 1,
            ),
            27 => 
            array (
                'id' => 528,
                'migration' => '2025_05_31_213714_create_loan_charge_types_table',
                'batch' => 1,
            ),
            28 => 
            array (
                'id' => 529,
                'migration' => '2025_05_31_213714_create_loan_charges_table',
                'batch' => 1,
            ),
            29 => 
            array (
                'id' => 530,
                'migration' => '2025_05_31_213714_create_loan_collateral_table',
                'batch' => 1,
            ),
            30 => 
            array (
                'id' => 531,
                'migration' => '2025_05_31_213714_create_loan_collateral_history_table',
                'batch' => 1,
            ),
            31 => 
            array (
                'id' => 532,
                'migration' => '2025_05_31_213714_create_loan_collateral_types_table',
                'batch' => 1,
            ),
            32 => 
            array (
                'id' => 533,
                'migration' => '2025_05_31_213714_create_loan_credit_checks_table',
                'batch' => 1,
            ),
            33 => 
            array (
                'id' => 534,
                'migration' => '2025_05_31_213714_create_loan_disbursement_channels_table',
                'batch' => 1,
            ),
            34 => 
            array (
                'id' => 535,
                'migration' => '2025_05_31_213714_create_loan_files_table',
                'batch' => 1,
            ),
            35 => 
            array (
                'id' => 536,
                'migration' => '2025_05_31_213714_create_loan_history_table',
                'batch' => 1,
            ),
            36 => 
            array (
                'id' => 537,
                'migration' => '2025_05_31_213714_create_loan_linked_charges_table',
                'batch' => 1,
            ),
            37 => 
            array (
                'id' => 538,
                'migration' => '2025_05_31_213714_create_loan_linked_credit_checks_table',
                'batch' => 1,
            ),
            38 => 
            array (
                'id' => 539,
                'migration' => '2025_05_31_213714_create_loan_notes_table',
                'batch' => 1,
            ),
            39 => 
            array (
                'id' => 540,
                'migration' => '2025_05_31_213714_create_loan_officer_history_table',
                'batch' => 1,
            ),
            40 => 
            array (
                'id' => 541,
                'migration' => '2025_05_31_213714_create_loan_product_approval_officers_table',
                'batch' => 1,
            ),
            41 => 
            array (
                'id' => 542,
                'migration' => '2025_05_31_213714_create_loan_product_linked_charges_table',
                'batch' => 1,
            ),
            42 => 
            array (
                'id' => 543,
                'migration' => '2025_05_31_213714_create_loan_product_linked_credit_checks_table',
                'batch' => 1,
            ),
            43 => 
            array (
                'id' => 544,
                'migration' => '2025_05_31_213714_create_loan_products_table',
                'batch' => 1,
            ),
            44 => 
            array (
                'id' => 545,
                'migration' => '2025_05_31_213714_create_loan_purposes_table',
                'batch' => 1,
            ),
            45 => 
            array (
                'id' => 546,
                'migration' => '2025_05_31_213714_create_loan_repayment_schedules_table',
                'batch' => 1,
            ),
            46 => 
            array (
                'id' => 547,
                'migration' => '2025_05_31_213714_create_loan_statuses_table',
                'batch' => 1,
            ),
            47 => 
            array (
                'id' => 548,
                'migration' => '2025_05_31_213714_create_loan_transaction_processing_strategies_table',
                'batch' => 1,
            ),
            48 => 
            array (
                'id' => 549,
                'migration' => '2025_05_31_213714_create_loan_transaction_types_table',
                'batch' => 1,
            ),
            49 => 
            array (
                'id' => 550,
                'migration' => '2025_05_31_213714_create_loan_transactions_table',
                'batch' => 1,
            ),
            50 => 
            array (
                'id' => 551,
                'migration' => '2025_05_31_213714_create_loans_table',
                'batch' => 1,
            ),
            51 => 
            array (
                'id' => 552,
                'migration' => '2025_05_31_213714_create_map_business_tanks_table',
                'batch' => 1,
            ),
            52 => 
            array (
                'id' => 553,
                'migration' => '2025_05_31_213714_create_media_table',
                'batch' => 1,
            ),
            53 => 
            array (
                'id' => 554,
                'migration' => '2025_05_31_213714_create_medias_table',
                'batch' => 1,
            ),
            54 => 
            array (
                'id' => 555,
                'migration' => '2025_05_31_213714_create_member_group_transfer_histories_table',
                'batch' => 1,
            ),
            55 => 
            array (
                'id' => 556,
                'migration' => '2025_05_31_213714_create_member_groups_table',
                'batch' => 1,
            ),
            56 => 
            array (
                'id' => 557,
                'migration' => '2025_05_31_213714_create_member_staff_table',
                'batch' => 1,
            ),
            57 => 
            array (
                'id' => 558,
                'migration' => '2025_05_31_213714_create_members_table',
                'batch' => 1,
            ),
            58 => 
            array (
                'id' => 559,
                'migration' => '2025_05_31_213714_create_merged_sub_categories_table',
                'batch' => 1,
            ),
            59 => 
            array (
                'id' => 560,
                'migration' => '2025_05_31_213714_create_metas_table',
                'batch' => 1,
            ),
            60 => 
            array (
                'id' => 561,
                'migration' => '2025_05_31_213714_create_meter_resettings_table',
                'batch' => 1,
            ),
            61 => 
            array (
                'id' => 562,
                'migration' => '2025_05_31_213714_create_meter_sales_table',
                'batch' => 1,
            ),
            62 => 
            array (
                'id' => 563,
                'migration' => '2025_05_31_213714_create_mfg_by_products_table',
                'batch' => 1,
            ),
            63 => 
            array (
                'id' => 564,
                'migration' => '2025_05_31_213714_create_mfg_byproducts_list_table',
                'batch' => 1,
            ),
            64 => 
            array (
                'id' => 565,
                'migration' => '2025_05_31_213714_create_mfg_ingredient_groups_table',
                'batch' => 1,
            ),
            65 => 
            array (
                'id' => 566,
                'migration' => '2025_05_31_213714_create_mfg_recipe_cost_table',
                'batch' => 1,
            ),
            66 => 
            array (
                'id' => 567,
                'migration' => '2025_05_31_213714_create_mfg_recipe_ingredients_table',
                'batch' => 1,
            ),
            67 => 
            array (
                'id' => 568,
                'migration' => '2025_05_31_213714_create_mfg_recipes_table',
                'batch' => 1,
            ),
            68 => 
            array (
                'id' => 569,
                'migration' => '2025_05_31_213714_create_mfg_settings_table',
                'batch' => 1,
            ),
            69 => 
            array (
                'id' => 570,
                'migration' => '2025_05_31_213714_create_model_has_permissions_table',
                'batch' => 1,
            ),
            70 => 
            array (
                'id' => 571,
                'migration' => '2025_05_31_213714_create_model_has_roles_table',
                'batch' => 1,
            ),
            71 => 
            array (
                'id' => 572,
                'migration' => '2025_05_31_213714_create_module_permission_locations_table',
                'batch' => 1,
            ),
            72 => 
            array (
                'id' => 573,
                'migration' => '2025_05_31_213714_create_mpcs_16a_form_settings_table',
                'batch' => 1,
            ),
            73 => 
            array (
                'id' => 574,
                'migration' => '2025_05_31_213714_create_mpcs_20_form_settings_table',
                'batch' => 1,
            ),
            74 => 
            array (
                'id' => 575,
                'migration' => '2025_05_31_213714_create_mpcs_21c_form_settings_table',
                'batch' => 1,
            ),
            75 => 
            array (
                'id' => 576,
                'migration' => '2025_05_31_213714_create_mpcs_9a_form_settings_table',
                'batch' => 1,
            ),
            76 => 
            array (
                'id' => 577,
                'migration' => '2025_05_31_213714_create_mpcs_9c_cash_form_settings_table',
                'batch' => 1,
            ),
            77 => 
            array (
                'id' => 578,
                'migration' => '2025_05_31_213714_create_mpcs_9c_credit_form_settings_table',
                'batch' => 1,
            ),
            78 => 
            array (
                'id' => 579,
                'migration' => '2025_05_31_213714_create_mpcs_9c_form_settings_table',
                'batch' => 1,
            ),
            79 => 
            array (
                'id' => 580,
                'migration' => '2025_05_31_213714_create_mpcs_form9c_cash_table',
                'batch' => 1,
            ),
            80 => 
            array (
                'id' => 581,
                'migration' => '2025_05_31_213714_create_mpcs_form_f15_details_table',
                'batch' => 1,
            ),
            81 => 
            array (
                'id' => 582,
                'migration' => '2025_05_31_213714_create_mpcs_form_f15_headers_table',
                'batch' => 1,
            ),
            82 => 
            array (
                'id' => 583,
                'migration' => '2025_05_31_213714_create_mpcs_form_settings_table',
                'batch' => 1,
            ),
            83 => 
            array (
                'id' => 584,
                'migration' => '2025_05_31_213714_create_myhealth_specializations_table',
                'batch' => 1,
            ),
            84 => 
            array (
                'id' => 585,
                'migration' => '2025_05_31_213714_create_new_vehicle_table',
                'batch' => 1,
            ),
            85 => 
            array (
                'id' => 586,
                'migration' => '2025_05_31_213714_create_note_groups_table',
                'batch' => 1,
            ),
            86 => 
            array (
                'id' => 587,
                'migration' => '2025_05_31_213714_create_notes_table',
                'batch' => 1,
            ),
            87 => 
            array (
                'id' => 588,
                'migration' => '2025_05_31_213714_create_notice_boards_table',
                'batch' => 1,
            ),
            88 => 
            array (
                'id' => 589,
                'migration' => '2025_05_31_213714_create_notification_templates_table',
                'batch' => 1,
            ),
            89 => 
            array (
                'id' => 590,
                'migration' => '2025_05_31_213714_create_notifications_table',
                'batch' => 1,
            ),
            90 => 
            array (
                'id' => 591,
                'migration' => '2025_05_31_213714_create_opening_balance_table',
                'batch' => 1,
            ),
            91 => 
            array (
                'id' => 592,
                'migration' => '2025_05_31_213714_create_opening_meters_table',
                'batch' => 1,
            ),
            92 => 
            array (
                'id' => 593,
                'migration' => '2025_05_31_213714_create_other_incomes_table',
                'batch' => 1,
            ),
            93 => 
            array (
                'id' => 594,
                'migration' => '2025_05_31_213714_create_other_sales_table',
                'batch' => 1,
            ),
            94 => 
            array (
                'id' => 595,
                'migration' => '2025_05_31_213714_create_package_variables_table',
                'batch' => 1,
            ),
            95 => 
            array (
                'id' => 596,
                'migration' => '2025_05_31_213714_create_packages_table',
                'batch' => 1,
            ),
            96 => 
            array (
                'id' => 597,
                'migration' => '2025_05_31_213714_create_pages_table',
                'batch' => 1,
            ),
            97 => 
            array (
                'id' => 598,
                'migration' => '2025_05_31_213714_create_password_resets_table',
                'batch' => 1,
            ),
            98 => 
            array (
                'id' => 599,
                'migration' => '2025_05_31_213714_create_patient_allergies_table',
                'batch' => 1,
            ),
            99 => 
            array (
                'id' => 600,
                'migration' => '2025_05_31_213714_create_patient_details_table',
                'batch' => 1,
            ),
            100 => 
            array (
                'id' => 601,
                'migration' => '2025_05_31_213714_create_patient_doctors_table',
                'batch' => 1,
            ),
            101 => 
            array (
                'id' => 602,
                'migration' => '2025_05_31_213714_create_patient_medicines_table',
                'batch' => 1,
            ),
            102 => 
            array (
                'id' => 603,
                'migration' => '2025_05_31_213714_create_patient_payments_table',
                'batch' => 1,
            ),
            103 => 
            array (
                'id' => 604,
                'migration' => '2025_05_31_213714_create_patient_prescriptions_table',
                'batch' => 1,
            ),
            104 => 
            array (
                'id' => 605,
                'migration' => '2025_05_31_213714_create_patient_sugar_readings_table',
                'batch' => 1,
            ),
            105 => 
            array (
                'id' => 606,
                'migration' => '2025_05_31_213714_create_patient_tests_table',
                'batch' => 1,
            ),
            106 => 
            array (
                'id' => 607,
                'migration' => '2025_05_31_213714_create_pay_onlines_table',
                'batch' => 1,
            ),
            107 => 
            array (
                'id' => 608,
                'migration' => '2025_05_31_213714_create_payhere_table',
                'batch' => 1,
            ),
            108 => 
            array (
                'id' => 609,
                'migration' => '2025_05_31_213714_create_payment_methods_table',
                'batch' => 1,
            ),
            109 => 
            array (
                'id' => 610,
                'migration' => '2025_05_31_213714_create_payment_options_table',
                'batch' => 1,
            ),
            110 => 
            array (
                'id' => 611,
                'migration' => '2025_05_31_213714_create_payrolls_table',
                'batch' => 1,
            ),
            111 => 
            array (
                'id' => 612,
                'migration' => '2025_05_31_213714_create_penalties_table',
                'batch' => 1,
            ),
            112 => 
            array (
                'id' => 613,
                'migration' => '2025_05_31_213714_create_permissions_table',
                'batch' => 1,
            ),
            113 => 
            array (
                'id' => 614,
                'migration' => '2025_05_31_213714_create_personal_access_tokens_table',
                'batch' => 1,
            ),
            114 => 
            array (
                'id' => 615,
                'migration' => '2025_05_31_213714_create_petro_daily_shifts_table',
                'batch' => 1,
            ),
            115 => 
            array (
                'id' => 616,
                'migration' => '2025_05_31_213714_create_petro_notification_templates_table',
                'batch' => 1,
            ),
            116 => 
            array (
                'id' => 617,
                'migration' => '2025_05_31_213714_create_petro_shifts_table',
                'batch' => 1,
            ),
            117 => 
            array (
                'id' => 618,
                'migration' => '2025_05_31_213714_create_petro_whats_app_templates_table',
                'batch' => 1,
            ),
            118 => 
            array (
                'id' => 619,
                'migration' => '2025_05_31_213714_create_pharmacy_products_table',
                'batch' => 1,
            ),
            119 => 
            array (
                'id' => 620,
                'migration' => '2025_05_31_213714_create_plans_table',
                'batch' => 1,
            ),
            120 => 
            array (
                'id' => 621,
                'migration' => '2025_05_31_213714_create_prescription_medicines_table',
                'batch' => 1,
            ),
            121 => 
            array (
                'id' => 622,
                'migration' => '2025_05_31_213714_create_prescription_tests_table',
                'batch' => 1,
            ),
            122 => 
            array (
                'id' => 623,
                'migration' => '2025_05_31_213714_create_price_change_settings_table',
                'batch' => 1,
            ),
            123 => 
            array (
                'id' => 624,
                'migration' => '2025_05_31_213714_create_price_changes_details_table',
                'batch' => 1,
            ),
            124 => 
            array (
                'id' => 625,
                'migration' => '2025_05_31_213714_create_price_changes_headers_table',
                'batch' => 1,
            ),
            125 => 
            array (
                'id' => 626,
                'migration' => '2025_05_31_213714_create_printed_cheque_details_table',
                'batch' => 1,
            ),
            126 => 
            array (
                'id' => 627,
                'migration' => '2025_05_31_213714_create_printers_table',
                'batch' => 1,
            ),
            127 => 
            array (
                'id' => 628,
                'migration' => '2025_05_31_213714_create_priorities_table',
                'batch' => 1,
            ),
            128 => 
            array (
                'id' => 629,
                'migration' => '2025_05_31_213714_create_product_locations_table',
                'batch' => 1,
            ),
            129 => 
            array (
                'id' => 630,
                'migration' => '2025_05_31_213714_create_product_racks_table',
                'batch' => 1,
            ),
            130 => 
            array (
                'id' => 631,
                'migration' => '2025_05_31_213714_create_product_variations_table',
                'batch' => 1,
            ),
            131 => 
            array (
                'id' => 632,
                'migration' => '2025_05_31_213714_create_products_table',
                'batch' => 1,
            ),
            132 => 
            array (
                'id' => 633,
                'migration' => '2025_05_31_213714_create_properties_table',
                'batch' => 1,
            ),
            133 => 
            array (
                'id' => 634,
                'migration' => '2025_05_31_213714_create_property_account_settings_table',
                'batch' => 1,
            ),
            134 => 
            array (
                'id' => 635,
                'migration' => '2025_05_31_213714_create_property_blocks_table',
                'batch' => 1,
            ),
            135 => 
            array (
                'id' => 636,
                'migration' => '2025_05_31_213714_create_property_finalizes_table',
                'batch' => 1,
            ),
            136 => 
            array (
                'id' => 637,
                'migration' => '2025_05_31_213714_create_property_sell_lines_table',
                'batch' => 1,
            ),
            137 => 
            array (
                'id' => 638,
                'migration' => '2025_05_31_213714_create_property_starting_nos_table',
                'batch' => 1,
            ),
            138 => 
            array (
                'id' => 639,
                'migration' => '2025_05_31_213714_create_property_taxes_table',
                'batch' => 1,
            ),
            139 => 
            array (
                'id' => 640,
                'migration' => '2025_05_31_213714_create_provinces_table',
                'batch' => 1,
            ),
            140 => 
            array (
                'id' => 641,
                'migration' => '2025_05_31_213714_create_pump_operator_assignments_table',
                'batch' => 1,
            ),
            141 => 
            array (
                'id' => 642,
                'migration' => '2025_05_31_213714_create_pump_operator_commission_table',
                'batch' => 1,
            ),
            142 => 
            array (
                'id' => 643,
                'migration' => '2025_05_31_213714_create_pump_operator_meter_sale_details_table',
                'batch' => 1,
            ),
            143 => 
            array (
                'id' => 644,
                'migration' => '2025_05_31_213714_create_pump_operator_meter_sales_table',
                'batch' => 1,
            ),
            144 => 
            array (
                'id' => 645,
                'migration' => '2025_05_31_213714_create_pump_operator_other_sale_details_table',
                'batch' => 1,
            ),
            145 => 
            array (
                'id' => 646,
                'migration' => '2025_05_31_213714_create_pump_operator_other_sales_table',
                'batch' => 1,
            ),
            146 => 
            array (
                'id' => 647,
                'migration' => '2025_05_31_213714_create_pump_operator_payments_table',
                'batch' => 1,
            ),
            147 => 
            array (
                'id' => 648,
                'migration' => '2025_05_31_213714_create_pump_operators_table',
                'batch' => 1,
            ),
            148 => 
            array (
                'id' => 649,
                'migration' => '2025_05_31_213714_create_pumper_login_attempts_table',
                'batch' => 1,
            ),
            149 => 
            array (
                'id' => 650,
                'migration' => '2025_05_31_213714_create_pumps_table',
                'batch' => 1,
            ),
            150 => 
            array (
                'id' => 651,
                'migration' => '2025_05_31_213714_create_purchase_land_accounts_table',
                'batch' => 1,
            ),
            151 => 
            array (
                'id' => 652,
                'migration' => '2025_05_31_213714_create_purchase_lines_table',
                'batch' => 1,
            ),
            152 => 
            array (
                'id' => 653,
                'migration' => '2025_05_31_213714_create_push_notification_tokens_table',
                'batch' => 1,
            ),
            153 => 
            array (
                'id' => 654,
                'migration' => '2025_05_31_213714_create_receive_work_orders_table',
                'batch' => 1,
            ),
            154 => 
            array (
                'id' => 655,
                'migration' => '2025_05_31_213714_create_reference_counts_table',
                'batch' => 1,
            ),
            155 => 
            array (
                'id' => 656,
                'migration' => '2025_05_31_213714_create_referral_groups_table',
                'batch' => 1,
            ),
            156 => 
            array (
                'id' => 657,
                'migration' => '2025_05_31_213714_create_referral_starting_codes_table',
                'batch' => 1,
            ),
            157 => 
            array (
                'id' => 658,
                'migration' => '2025_05_31_213714_create_referrals_table',
                'batch' => 1,
            ),
            158 => 
            array (
                'id' => 659,
                'migration' => '2025_05_31_213714_create_refill_business_table',
                'batch' => 1,
            ),
            159 => 
            array (
                'id' => 660,
                'migration' => '2025_05_31_213714_create_reimbursements_table',
                'batch' => 1,
            ),
            160 => 
            array (
                'id' => 661,
                'migration' => '2025_05_31_213714_create_religions_table',
                'batch' => 1,
            ),
            161 => 
            array (
                'id' => 662,
                'migration' => '2025_05_31_213714_create_reminders_table',
                'batch' => 1,
            ),
            162 => 
            array (
                'id' => 663,
                'migration' => '2025_05_31_213714_create_repair_device_models_table',
                'batch' => 1,
            ),
            163 => 
            array (
                'id' => 664,
                'migration' => '2025_05_31_213714_create_repair_job_sheets_table',
                'batch' => 1,
            ),
            164 => 
            array (
                'id' => 665,
                'migration' => '2025_05_31_213714_create_repair_statuses_table',
                'batch' => 1,
            ),
            165 => 
            array (
                'id' => 666,
                'migration' => '2025_05_31_213714_create_res_product_modifier_sets_table',
                'batch' => 1,
            ),
            166 => 
            array (
                'id' => 667,
                'migration' => '2025_05_31_213714_create_res_tables_table',
                'batch' => 1,
            ),
            167 => 
            array (
                'id' => 668,
                'migration' => '2025_05_31_213714_create_reviewed_changes_table',
                'batch' => 1,
            ),
            168 => 
            array (
                'id' => 669,
                'migration' => '2025_05_31_213714_create_reviewed_changes_description_table',
                'batch' => 1,
            ),
            169 => 
            array (
                'id' => 670,
                'migration' => '2025_05_31_213714_create_role_has_permissions_table',
                'batch' => 1,
            ),
            170 => 
            array (
                'id' => 671,
                'migration' => '2025_05_31_213714_create_roles_table',
                'batch' => 1,
            ),
            171 => 
            array (
                'id' => 672,
                'migration' => '2025_05_31_213714_create_route_invoice_numbers_table',
                'batch' => 1,
            ),
            172 => 
            array (
                'id' => 673,
                'migration' => '2025_05_31_213714_create_route_operations_table',
                'batch' => 1,
            ),
            173 => 
            array (
                'id' => 674,
                'migration' => '2025_05_31_213714_create_route_products_table',
                'batch' => 1,
            ),
            174 => 
            array (
                'id' => 675,
                'migration' => '2025_05_31_213714_create_routes_table',
                'batch' => 1,
            ),
            175 => 
            array (
                'id' => 676,
                'migration' => '2025_05_31_213714_create_salaries_table',
                'batch' => 1,
            ),
            176 => 
            array (
                'id' => 677,
                'migration' => '2025_05_31_213714_create_salary_components_table',
                'batch' => 1,
            ),
            177 => 
            array (
                'id' => 678,
                'migration' => '2025_05_31_213714_create_salary_grades_table',
                'batch' => 1,
            ),
            178 => 
            array (
                'id' => 679,
                'migration' => '2025_05_31_213714_create_sales_officers_table',
                'batch' => 1,
            ),
            179 => 
            array (
                'id' => 680,
                'migration' => '2025_05_31_213714_create_saved_replies_table',
                'batch' => 1,
            ),
            180 => 
            array (
                'id' => 681,
                'migration' => '2025_05_31_213714_create_sell_line_warranties_table',
                'batch' => 1,
            ),
            181 => 
            array (
                'id' => 682,
                'migration' => '2025_05_31_213714_create_selling_price_groups_table',
                'batch' => 1,
            ),
            182 => 
            array (
                'id' => 683,
                'migration' => '2025_05_31_213714_create_service_areas_table',
                'batch' => 1,
            ),
            183 => 
            array (
                'id' => 684,
                'migration' => '2025_05_31_213714_create_services_table',
                'batch' => 1,
            ),
            184 => 
            array (
                'id' => 685,
                'migration' => '2025_05_31_213714_create_sessions_table',
                'batch' => 1,
            ),
            185 => 
            array (
                'id' => 686,
                'migration' => '2025_05_31_213714_create_settings_table',
                'batch' => 1,
            ),
            186 => 
            array (
                'id' => 687,
                'migration' => '2025_05_31_213714_create_settlement_card_payments_table',
                'batch' => 1,
            ),
            187 => 
            array (
                'id' => 688,
                'migration' => '2025_05_31_213714_create_settlement_cash_deposits_table',
                'batch' => 1,
            ),
            188 => 
            array (
                'id' => 689,
                'migration' => '2025_05_31_213714_create_settlement_cash_payments_table',
                'batch' => 1,
            ),
            189 => 
            array (
                'id' => 690,
                'migration' => '2025_05_31_213714_create_settlement_cheque_payments_table',
                'batch' => 1,
            ),
            190 => 
            array (
                'id' => 691,
                'migration' => '2025_05_31_213714_create_settlement_credit_sale_payments_table',
                'batch' => 1,
            ),
            191 => 
            array (
                'id' => 692,
                'migration' => '2025_05_31_213714_create_settlement_customer_loans_table',
                'batch' => 1,
            ),
            192 => 
            array (
                'id' => 693,
                'migration' => '2025_05_31_213714_create_settlement_drawing_payments_table',
                'batch' => 1,
            ),
            193 => 
            array (
                'id' => 694,
                'migration' => '2025_05_31_213714_create_settlement_edit_history_table',
                'batch' => 1,
            ),
            194 => 
            array (
                'id' => 695,
                'migration' => '2025_05_31_213714_create_settlement_excess_payments_table',
                'batch' => 1,
            ),
            195 => 
            array (
                'id' => 696,
                'migration' => '2025_05_31_213714_create_settlement_expense_payments_table',
                'batch' => 1,
            ),
            196 => 
            array (
                'id' => 697,
                'migration' => '2025_05_31_213714_create_settlement_loan_payments_table',
                'batch' => 1,
            ),
            197 => 
            array (
                'id' => 698,
                'migration' => '2025_05_31_213714_create_settlement_shortage_payments_table',
                'batch' => 1,
            ),
            198 => 
            array (
                'id' => 699,
                'migration' => '2025_05_31_213714_create_settlements_table',
                'batch' => 1,
            ),
            199 => 
            array (
                'id' => 700,
                'migration' => '2025_05_31_213714_create_sheet_spreadsheet_shares_table',
                'batch' => 1,
            ),
            200 => 
            array (
                'id' => 701,
                'migration' => '2025_05_31_213714_create_sheet_spreadsheets_table',
                'batch' => 1,
            ),
            201 => 
            array (
                'id' => 702,
                'migration' => '2025_05_31_213714_create_shipment_packages_table',
                'batch' => 1,
            ),
            202 => 
            array (
                'id' => 703,
                'migration' => '2025_05_31_213714_create_shipments_table',
                'batch' => 1,
            ),
            203 => 
            array (
                'id' => 704,
                'migration' => '2025_05_31_213714_create_shipping_accounts_table',
                'batch' => 1,
            ),
            204 => 
            array (
                'id' => 705,
                'migration' => '2025_05_31_213714_create_shipping_agent_commission_table',
                'batch' => 1,
            ),
            205 => 
            array (
                'id' => 706,
                'migration' => '2025_05_31_213714_create_shipping_agent_ledger_table',
                'batch' => 1,
            ),
            206 => 
            array (
                'id' => 707,
                'migration' => '2025_05_31_213714_create_shipping_agents_table',
                'batch' => 1,
            ),
            207 => 
            array (
                'id' => 708,
                'migration' => '2025_05_31_213714_create_shipping_bar_qr_code_table',
                'batch' => 1,
            ),
            208 => 
            array (
                'id' => 709,
                'migration' => '2025_05_31_213714_create_shipping_change_status_table',
                'batch' => 1,
            ),
            209 => 
            array (
                'id' => 710,
                'migration' => '2025_05_31_213714_create_shipping_cities_table',
                'batch' => 1,
            ),
            210 => 
            array (
                'id' => 711,
                'migration' => '2025_05_31_213714_create_shipping_credit_days_table',
                'batch' => 1,
            ),
            211 => 
            array (
                'id' => 712,
                'migration' => '2025_05_31_213714_create_shipping_delivery_table',
                'batch' => 1,
            ),
            212 => 
            array (
                'id' => 713,
                'migration' => '2025_05_31_213714_create_shipping_delivery_days_table',
                'batch' => 1,
            ),
            213 => 
            array (
                'id' => 714,
                'migration' => '2025_05_31_213714_create_shipping_dimensions_table',
                'batch' => 1,
            ),
            214 => 
            array (
                'id' => 715,
                'migration' => '2025_05_31_213714_create_shipping_drivers_table',
                'batch' => 1,
            ),
            215 => 
            array (
                'id' => 716,
                'migration' => '2025_05_31_213714_create_shipping_invoice_send_table',
                'batch' => 1,
            ),
            216 => 
            array (
                'id' => 717,
                'migration' => '2025_05_31_213714_create_shipping_mode_table',
                'batch' => 1,
            ),
            217 => 
            array (
                'id' => 718,
                'migration' => '2025_05_31_213714_create_shipping_packages_table',
                'batch' => 1,
            ),
            218 => 
            array (
                'id' => 719,
                'migration' => '2025_05_31_213714_create_shipping_partner_commission_table',
                'batch' => 1,
            ),
            219 => 
            array (
                'id' => 720,
                'migration' => '2025_05_31_213714_create_shipping_partners_table',
                'batch' => 1,
            ),
            220 => 
            array (
                'id' => 721,
                'migration' => '2025_05_31_213714_create_shipping_prefix_table',
                'batch' => 1,
            ),
            221 => 
            array (
                'id' => 722,
                'migration' => '2025_05_31_213714_create_shipping_prices_table',
                'batch' => 1,
            ),
            222 => 
            array (
                'id' => 723,
                'migration' => '2025_05_31_213714_create_shipping_provinces_table',
                'batch' => 1,
            ),
            223 => 
            array (
                'id' => 724,
                'migration' => '2025_05_31_213714_create_shipping_recipients_table',
                'batch' => 1,
            ),
            224 => 
            array (
                'id' => 725,
                'migration' => '2025_05_31_213714_create_shipping_status_table',
                'batch' => 1,
            ),
            225 => 
            array (
                'id' => 726,
                'migration' => '2025_05_31_213714_create_site_settings_table',
                'batch' => 1,
            ),
            226 => 
            array (
                'id' => 727,
                'migration' => '2025_05_31_213714_create_sms_api_clients_table',
                'batch' => 1,
            ),
            227 => 
            array (
                'id' => 728,
                'migration' => '2025_05_31_213714_create_sms_campaigns_table',
                'batch' => 1,
            ),
            228 => 
            array (
                'id' => 729,
                'migration' => '2025_05_31_213714_create_sms_groups_table',
                'batch' => 1,
            ),
            229 => 
            array (
                'id' => 730,
                'migration' => '2025_05_31_213714_create_sms_list_interests_table',
                'batch' => 1,
            ),
            230 => 
            array (
                'id' => 731,
                'migration' => '2025_05_31_213714_create_sms_lists_table',
                'batch' => 1,
            ),
            231 => 
            array (
                'id' => 732,
                'migration' => '2025_05_31_213714_create_sms_logs_table',
                'batch' => 1,
            ),
            232 => 
            array (
                'id' => 733,
                'migration' => '2025_05_31_213714_create_sms_refill_packages_table',
                'batch' => 1,
            ),
            233 => 
            array (
                'id' => 734,
                'migration' => '2025_05_31_213714_create_sms_reminder_settings_table',
                'batch' => 1,
            ),
            234 => 
            array (
                'id' => 735,
                'migration' => '2025_05_31_213714_create_social_accounts_table',
                'batch' => 1,
            ),
            235 => 
            array (
                'id' => 736,
                'migration' => '2025_05_31_213714_create_stock_adjustment_lines_table',
                'batch' => 1,
            ),
            236 => 
            array (
                'id' => 737,
                'migration' => '2025_05_31_213714_create_stock_adjustment_settings_table',
                'batch' => 1,
            ),
            237 => 
            array (
                'id' => 738,
                'migration' => '2025_05_31_213714_create_stock_adjustments_temp_table',
                'batch' => 1,
            ),
            238 => 
            array (
                'id' => 739,
                'migration' => '2025_05_31_213714_create_stock_conversions_table',
                'batch' => 1,
            ),
            239 => 
            array (
                'id' => 740,
                'migration' => '2025_05_31_213714_create_stock_transfer_requests_table',
                'batch' => 1,
            ),
            240 => 
            array (
                'id' => 741,
                'migration' => '2025_05_31_213714_create_stocktaking_form_settings_table',
                'batch' => 1,
            ),
            241 => 
            array (
                'id' => 742,
                'migration' => '2025_05_31_213714_create_stores_table',
                'batch' => 1,
            ),
            242 => 
            array (
                'id' => 743,
                'migration' => '2025_05_31_213714_create_subscription_lists_table',
                'batch' => 1,
            ),
            243 => 
            array (
                'id' => 744,
                'migration' => '2025_05_31_213714_create_subscription_payments_table',
                'batch' => 1,
            ),
            244 => 
            array (
                'id' => 745,
                'migration' => '2025_05_31_213714_create_subscription_prices_table',
                'batch' => 1,
            ),
            245 => 
            array (
                'id' => 746,
                'migration' => '2025_05_31_213714_create_subscription_settings_table',
                'batch' => 1,
            ),
            246 => 
            array (
                'id' => 747,
                'migration' => '2025_05_31_213714_create_subscription_sms_templates_table',
                'batch' => 1,
            ),
            247 => 
            array (
                'id' => 748,
                'migration' => '2025_05_31_213714_create_subscription_user_activities_table',
                'batch' => 1,
            ),
            248 => 
            array (
                'id' => 749,
                'migration' => '2025_05_31_213714_create_subscriptions_table',
                'batch' => 1,
            ),
            249 => 
            array (
                'id' => 750,
                'migration' => '2025_05_31_213714_create_sugar_reading_breakfasts_table',
                'batch' => 1,
            ),
            250 => 
            array (
                'id' => 751,
                'migration' => '2025_05_31_213714_create_sugar_reading_dinners_table',
                'batch' => 1,
            ),
            251 => 
            array (
                'id' => 752,
                'migration' => '2025_05_31_213714_create_sugar_reading_lunchs_table',
                'batch' => 1,
            ),
            252 => 
            array (
                'id' => 753,
                'migration' => '2025_05_31_213714_create_suggestions_table',
                'batch' => 1,
            ),
            253 => 
            array (
                'id' => 754,
                'migration' => '2025_05_31_213714_create_superadmin_communicator_logs_table',
                'batch' => 1,
            ),
            254 => 
            array (
                'id' => 755,
                'migration' => '2025_05_31_213714_create_superadmin_frontend_pages_table',
                'batch' => 1,
            ),
            255 => 
            array (
                'id' => 756,
                'migration' => '2025_05_31_213714_create_supplier_product_mappings_table',
                'batch' => 1,
            ),
            256 => 
            array (
                'id' => 757,
                'migration' => '2025_05_31_213714_create_system_table',
                'batch' => 1,
            ),
            257 => 
            array (
                'id' => 758,
                'migration' => '2025_05_31_213714_create_tags_table',
                'batch' => 1,
            ),
            258 => 
            array (
                'id' => 759,
                'migration' => '2025_05_31_213714_create_tank_dip_chart_details_table',
                'batch' => 1,
            ),
            259 => 
            array (
                'id' => 760,
                'migration' => '2025_05_31_213714_create_tank_dip_charts_table',
                'batch' => 1,
            ),
            260 => 
            array (
                'id' => 761,
                'migration' => '2025_05_31_213714_create_tank_purchase_lines_table',
                'batch' => 1,
            ),
            261 => 
            array (
                'id' => 762,
                'migration' => '2025_05_31_213714_create_tank_sell_lines_table',
                'batch' => 1,
            ),
            262 => 
            array (
                'id' => 763,
                'migration' => '2025_05_31_213714_create_tank_transfers_table',
                'batch' => 1,
            ),
            263 => 
            array (
                'id' => 764,
                'migration' => '2025_05_31_213714_create_tanks_transaction_details_table',
                'batch' => 1,
            ),
            264 => 
            array (
                'id' => 765,
                'migration' => '2025_05_31_213714_create_task_groups_table',
                'batch' => 1,
            ),
            265 => 
            array (
                'id' => 766,
                'migration' => '2025_05_31_213714_create_tasks_table',
                'batch' => 1,
            ),
            266 => 
            array (
                'id' => 767,
                'migration' => '2025_05_31_213714_create_tax_rates_table',
                'batch' => 1,
            ),
            267 => 
            array (
                'id' => 768,
                'migration' => '2025_05_31_213714_create_taxes_table',
                'batch' => 1,
            ),
            268 => 
            array (
                'id' => 769,
                'migration' => '2025_05_31_213714_create_temp_data_table',
                'batch' => 1,
            ),
            269 => 
            array (
                'id' => 770,
                'migration' => '2025_05_31_213714_create_tenants_table',
                'batch' => 1,
            ),
            270 => 
            array (
                'id' => 771,
                'migration' => '2025_05_31_213714_create_themes_table',
                'batch' => 1,
            ),
            271 => 
            array (
                'id' => 772,
                'migration' => '2025_05_31_213714_create_ticket_conversation_table',
                'batch' => 1,
            ),
            272 => 
            array (
                'id' => 773,
                'migration' => '2025_05_31_213714_create_tickets_table',
                'batch' => 1,
            ),
            273 => 
            array (
                'id' => 774,
                'migration' => '2025_05_31_213714_create_towns_table',
                'batch' => 1,
            ),
            274 => 
            array (
                'id' => 775,
                'migration' => '2025_05_31_213714_create_tpos_sales_table',
                'batch' => 1,
            ),
            275 => 
            array (
                'id' => 776,
                'migration' => '2025_05_31_213714_create_tpos_sales_products_table',
                'batch' => 1,
            ),
            276 => 
            array (
                'id' => 777,
                'migration' => '2025_05_31_213714_create_transaction_payments_table',
                'batch' => 1,
            ),
            277 => 
            array (
                'id' => 778,
                'migration' => '2025_05_31_213714_create_transaction_sell_lines_purchase_lines_table',
                'batch' => 1,
            ),
            278 => 
            array (
                'id' => 779,
                'migration' => '2025_05_31_213714_create_transactions_table',
                'batch' => 1,
            ),
            279 => 
            array (
                'id' => 780,
                'migration' => '2025_05_31_213714_create_translations_table',
                'batch' => 1,
            ),
            280 => 
            array (
                'id' => 781,
                'migration' => '2025_05_31_213714_create_trip_categories_table',
                'batch' => 1,
            ),
            281 => 
            array (
                'id' => 782,
                'migration' => '2025_05_31_213714_create_types_table',
                'batch' => 1,
            ),
            282 => 
            array (
                'id' => 783,
                'migration' => '2025_05_31_213714_create_types_of_services_table',
                'batch' => 1,
            ),
            283 => 
            array (
                'id' => 784,
                'migration' => '2025_05_31_213714_create_units_table',
                'batch' => 1,
            ),
            284 => 
            array (
                'id' => 785,
                'migration' => '2025_05_31_213714_create_unload_stocks_table',
                'batch' => 1,
            ),
            285 => 
            array (
                'id' => 786,
                'migration' => '2025_05_31_213714_create_uploaded_orders_table',
                'batch' => 1,
            ),
            286 => 
            array (
                'id' => 787,
                'migration' => '2025_05_31_213714_create_user_contact_access_table',
                'batch' => 1,
            ),
            287 => 
            array (
                'id' => 788,
                'migration' => '2025_05_31_213714_create_user_locations_table',
                'batch' => 1,
            ),
            288 => 
            array (
                'id' => 789,
                'migration' => '2025_05_31_213714_create_user_settings_table',
                'batch' => 1,
            ),
            289 => 
            array (
                'id' => 790,
                'migration' => '2025_05_31_213714_create_user_store_permissions_table',
                'batch' => 1,
            ),
            290 => 
            array (
                'id' => 791,
                'migration' => '2025_05_31_213714_create_users_table',
                'batch' => 1,
            ),
            291 => 
            array (
                'id' => 792,
                'migration' => '2025_05_31_213714_create_variation_group_prices_table',
                'batch' => 1,
            ),
            292 => 
            array (
                'id' => 793,
                'migration' => '2025_05_31_213714_create_variation_location_details_table',
                'batch' => 1,
            ),
            293 => 
            array (
                'id' => 794,
                'migration' => '2025_05_31_213714_create_variation_store_details_table',
                'batch' => 1,
            ),
            294 => 
            array (
                'id' => 795,
                'migration' => '2025_05_31_213714_create_variation_templates_table',
                'batch' => 1,
            ),
            295 => 
            array (
                'id' => 796,
                'migration' => '2025_05_31_213714_create_variation_transfers_table',
                'batch' => 1,
            ),
            296 => 
            array (
                'id' => 797,
                'migration' => '2025_05_31_213714_create_variation_value_templates_table',
                'batch' => 1,
            ),
            297 => 
            array (
                'id' => 798,
                'migration' => '2025_05_31_213714_create_variations_table',
                'batch' => 1,
            ),
            298 => 
            array (
                'id' => 799,
                'migration' => '2025_05_31_213714_create_vat_bank_details_table',
                'batch' => 1,
            ),
            299 => 
            array (
                'id' => 800,
                'migration' => '2025_05_31_213714_create_vat_concerns_table',
                'batch' => 1,
            ),
            300 => 
            array (
                'id' => 801,
                'migration' => '2025_05_31_213714_create_vat_contacts_table',
                'batch' => 1,
            ),
            301 => 
            array (
                'id' => 802,
                'migration' => '2025_05_31_213714_create_vat_credit_bills_table',
                'batch' => 1,
            ),
            302 => 
            array (
                'id' => 803,
                'migration' => '2025_05_31_213714_create_vat_customer_statement_details_table',
                'batch' => 1,
            ),
            303 => 
            array (
                'id' => 804,
                'migration' => '2025_05_31_213714_create_vat_customer_statements_table',
                'batch' => 1,
            ),
            304 => 
            array (
                'id' => 805,
                'migration' => '2025_05_31_213714_create_vat_expense_categories_table',
                'batch' => 1,
            ),
            305 => 
            array (
                'id' => 806,
                'migration' => '2025_05_31_213714_create_vat_expense_payments_table',
                'batch' => 1,
            ),
            306 => 
            array (
                'id' => 807,
                'migration' => '2025_05_31_213714_create_vat_expenses_table',
                'batch' => 1,
            ),
            307 => 
            array (
                'id' => 808,
                'migration' => '2025_05_31_213714_create_vat_invoice2_prefixes_table',
                'batch' => 1,
            ),
            308 => 
            array (
                'id' => 809,
                'migration' => '2025_05_31_213714_create_vat_invoice2_settings_table',
                'batch' => 1,
            ),
            309 => 
            array (
                'id' => 810,
                'migration' => '2025_05_31_213714_create_vat_invoice_details_table',
                'batch' => 1,
            ),
            310 => 
            array (
                'id' => 811,
                'migration' => '2025_05_31_213714_create_vat_invoice_details_2_table',
                'batch' => 1,
            ),
            311 => 
            array (
                'id' => 812,
                'migration' => '2025_05_31_213714_create_vat_invoice_payments_table',
                'batch' => 1,
            ),
            312 => 
            array (
                'id' => 813,
                'migration' => '2025_05_31_213714_create_vat_invoice_payments_2_table',
                'batch' => 1,
            ),
            313 => 
            array (
                'id' => 814,
                'migration' => '2025_05_31_213714_create_vat_invoice_sms_types_table',
                'batch' => 1,
            ),
            314 => 
            array (
                'id' => 815,
                'migration' => '2025_05_31_213714_create_vat_invoices_table',
                'batch' => 1,
            ),
            315 => 
            array (
                'id' => 816,
                'migration' => '2025_05_31_213714_create_vat_invoices_2_table',
                'batch' => 1,
            ),
            316 => 
            array (
                'id' => 817,
                'migration' => '2025_05_31_213714_create_vat_meter_sales_table',
                'batch' => 1,
            ),
            317 => 
            array (
                'id' => 818,
                'migration' => '2025_05_31_213714_create_vat_other_sales_table',
                'batch' => 1,
            ),
            318 => 
            array (
                'id' => 819,
                'migration' => '2025_05_31_213714_create_vat_payable_to_accounts_table',
                'batch' => 1,
            ),
            319 => 
            array (
                'id' => 820,
                'migration' => '2025_05_31_213714_create_vat_payments_table',
                'batch' => 1,
            ),
            320 => 
            array (
                'id' => 821,
                'migration' => '2025_05_31_213714_create_vat_prefixes_table',
                'batch' => 1,
            ),
            321 => 
            array (
                'id' => 822,
                'migration' => '2025_05_31_213714_create_vat_product_variations_table',
                'batch' => 1,
            ),
            322 => 
            array (
                'id' => 823,
                'migration' => '2025_05_31_213714_create_vat_products_table',
                'batch' => 1,
            ),
            323 => 
            array (
                'id' => 824,
                'migration' => '2025_05_31_213714_create_vat_purchase_payments_table',
                'batch' => 1,
            ),
            324 => 
            array (
                'id' => 825,
                'migration' => '2025_05_31_213714_create_vat_purchase_products_table',
                'batch' => 1,
            ),
            325 => 
            array (
                'id' => 826,
                'migration' => '2025_05_31_213714_create_vat_purchases_table',
                'batch' => 1,
            ),
            326 => 
            array (
                'id' => 827,
                'migration' => '2025_05_31_213714_create_vat_settings_table',
                'batch' => 1,
            ),
            327 => 
            array (
                'id' => 828,
                'migration' => '2025_05_31_213714_create_vat_settlement_card_payments_table',
                'batch' => 1,
            ),
            328 => 
            array (
                'id' => 829,
                'migration' => '2025_05_31_213714_create_vat_settlement_cash_payments_table',
                'batch' => 1,
            ),
            329 => 
            array (
                'id' => 830,
                'migration' => '2025_05_31_213714_create_vat_settlement_credit_sale_payments_table',
                'batch' => 1,
            ),
            330 => 
            array (
                'id' => 831,
                'migration' => '2025_05_31_213714_create_vat_settlements_table',
                'batch' => 1,
            ),
            331 => 
            array (
                'id' => 832,
                'migration' => '2025_05_31_213714_create_vat_statement_logos_table',
                'batch' => 1,
            ),
            332 => 
            array (
                'id' => 833,
                'migration' => '2025_05_31_213714_create_vat_statement_prefixes_table',
                'batch' => 1,
            ),
            333 => 
            array (
                'id' => 834,
                'migration' => '2025_05_31_213714_create_vat_supply_from_table',
                'batch' => 1,
            ),
            334 => 
            array (
                'id' => 835,
                'migration' => '2025_05_31_213714_create_vat_units_table',
                'batch' => 1,
            ),
            335 => 
            array (
                'id' => 836,
                'migration' => '2025_05_31_213714_create_vat_user_invoice_prefixes_table',
                'batch' => 1,
            ),
            336 => 
            array (
                'id' => 837,
                'migration' => '2025_05_31_213714_create_vat_variations_table',
                'batch' => 1,
            ),
            337 => 
            array (
                'id' => 838,
                'migration' => '2025_05_31_213714_create_vehicle_categories_table',
                'batch' => 1,
            ),
            338 => 
            array (
                'id' => 839,
                'migration' => '2025_05_31_213714_create_vehicle_classifications_table',
                'batch' => 1,
            ),
            339 => 
            array (
                'id' => 840,
                'migration' => '2025_05_31_213714_create_vehicle_fuel_quota_table',
                'batch' => 1,
            ),
            340 => 
            array (
                'id' => 841,
                'migration' => '2025_05_31_213714_create_vehicles_table',
                'batch' => 1,
            ),
            341 => 
            array (
                'id' => 842,
                'migration' => '2025_05_31_213714_create_verification_codes_table',
                'batch' => 1,
            ),
            342 => 
            array (
                'id' => 843,
                'migration' => '2025_05_31_213714_create_verifybackup_table',
                'batch' => 1,
            ),
            343 => 
            array (
                'id' => 844,
                'migration' => '2025_05_31_213714_create_visitor_settings_table',
                'batch' => 1,
            ),
            344 => 
            array (
                'id' => 845,
                'migration' => '2025_05_31_213714_create_visitors_table',
                'batch' => 1,
            ),
            345 => 
            array (
                'id' => 846,
                'migration' => '2025_05_31_213714_create_visits_table',
                'batch' => 1,
            ),
            346 => 
            array (
                'id' => 847,
                'migration' => '2025_05_31_213714_create_warranties_table',
                'batch' => 1,
            ),
            347 => 
            array (
                'id' => 848,
                'migration' => '2025_05_31_213714_create_wastages_table',
                'batch' => 1,
            ),
            348 => 
            array (
                'id' => 849,
                'migration' => '2025_05_31_213714_create_work_order_items_table',
                'batch' => 1,
            ),
            349 => 
            array (
                'id' => 850,
                'migration' => '2025_05_31_213714_create_work_orders_table',
                'batch' => 1,
            ),
            350 => 
            array (
                'id' => 851,
                'migration' => '2025_05_31_213714_create_work_shifts_table',
                'batch' => 1,
            ),
            351 => 
            array (
                'id' => 852,
                'migration' => '2025_05_31_213714_create_working_days_table',
                'batch' => 1,
            ),
            352 => 
            array (
                'id' => 853,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_assets_table',
                'batch' => 1,
            ),
            353 => 
            array (
                'id' => 854,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_barcodes_table',
                'batch' => 1,
            ),
            354 => 
            array (
                'id' => 855,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_bookings_table',
                'batch' => 1,
            ),
            355 => 
            array (
                'id' => 856,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_brands_table',
                'batch' => 1,
            ),
            356 => 
            array (
                'id' => 857,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_business_table',
                'batch' => 1,
            ),
            357 => 
            array (
                'id' => 858,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_business_locations_table',
                'batch' => 1,
            ),
            358 => 
            array (
                'id' => 859,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_cash_register_transactions_table',
                'batch' => 1,
            ),
            359 => 
            array (
                'id' => 860,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_cash_registers_table',
                'batch' => 1,
            ),
            360 => 
            array (
                'id' => 861,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_categories_table',
                'batch' => 1,
            ),
            361 => 
            array (
                'id' => 862,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_contact_groups_table',
                'batch' => 1,
            ),
            362 => 
            array (
                'id' => 863,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_crm_campaigns_table',
                'batch' => 1,
            ),
            363 => 
            array (
                'id' => 864,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_crm_lead_users_table',
                'batch' => 1,
            ),
            364 => 
            array (
                'id' => 865,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_crm_proposal_templates_table',
                'batch' => 1,
            ),
            365 => 
            array (
                'id' => 866,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_crm_proposals_table',
                'batch' => 1,
            ),
            366 => 
            array (
                'id' => 867,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_crm_schedule_logs_table',
                'batch' => 1,
            ),
            367 => 
            array (
                'id' => 868,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_crm_schedule_users_table',
                'batch' => 1,
            ),
            368 => 
            array (
                'id' => 869,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_crm_schedules_table',
                'batch' => 1,
            ),
            369 => 
            array (
                'id' => 870,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_day_count_settings_table',
                'batch' => 1,
            ),
            370 => 
            array (
                'id' => 871,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_discountlevels_table',
                'batch' => 1,
            ),
            371 => 
            array (
                'id' => 872,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_distribution_areas_table',
                'batch' => 1,
            ),
            372 => 
            array (
                'id' => 873,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_distribution_districts_table',
                'batch' => 1,
            ),
            373 => 
            array (
                'id' => 874,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_essentials_employee_advances_table',
                'batch' => 1,
            ),
            374 => 
            array (
                'id' => 875,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_essentials_kb_table',
                'batch' => 1,
            ),
            375 => 
            array (
                'id' => 876,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_essentials_payroll_group_transactions_table',
                'batch' => 1,
            ),
            376 => 
            array (
                'id' => 877,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_hms_customer_coupon_usage_table',
                'batch' => 1,
            ),
            377 => 
            array (
                'id' => 878,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_pump_operator_payments_table',
                'batch' => 1,
            ),
            378 => 
            array (
                'id' => 879,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_sheet_spreadsheet_shares_table',
                'batch' => 1,
            ),
            379 => 
            array (
                'id' => 880,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_sheet_spreadsheets_table',
                'batch' => 1,
            ),
            380 => 
            array (
                'id' => 881,
                'migration' => '2025_05_31_213717_add_foreign_keys_to_users_table',
                'batch' => 1,
            ),
        ));
        
        
    }
}