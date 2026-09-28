<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TempDataTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('temp_data')->delete();
        
        \DB::table('temp_data')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 4,
                'sale_create_data' => '{"store_id":null,"price_later":"0","location_id":"2","price_group":"0","default_price_group":null,"contact_id":"4","pay_term_number":null,"pay_term_type":null,"transaction_date":"05\\/15\\/2024 10:07","status":null,"need_to_reserve":null,"invoice_scheme_id":"4","search_product":null,"sell_price_tax":"includes","discount_type":"percentage","discount_amount":"0.00","rp_redeemed":"0","rp_redeemed_amount":"0","tax_rate_id":null,"tax_calculation_amount":"0.00","shipping_details":null,"shipping_address":null,"shipping_charges":"0.00","shipping_status":null,"delivered_to":null,"final_total":"0.00","sale_note":null,"is_direct_sale":"1","payment":[{"payment_id":null,"amount":"0.00","method":null,"account_id":null,"cheque_number":null,"cheque_date":null,"card_transaction_number":null,"bank_name":null,"transaction_no_1":null,"transaction_no_2":null,"transaction_no_3":null,"note":null}],"order_no":null,"order_date":"05\\/15\\/2024","customer_ref":"Please Select","submit_type":null,"recur_interval":null,"recur_interval_type":"days","recur_repetitions":null,"is_duplicate":"0"}',
                'pos_create_data' => '{"invoice_no":"PO1256","contact_id":"30","ref_no":"1256","transaction_date":"05\\/07\\/2025 14:28","invoice_date":"2025-05-07","status":"received","is_vat":"1","location_id":"2","store_id":"3","exchange_rate":"1","pay_term_number":"1","pay_term_type":"days","search_products":null,"purchases":[{"product_id":"298","variation_id":"298","quantity":"2","sub_unit_id":"5","product_unit_id":"5","pp_without_discount":"94.740000","discount_percent":"0","purchase_price":"94.740000","purchase_line_tax_id":null,"item_tax":"0","purchase_price_inc_tax":"94.740000","profit_percent":"26.662445","default_sell_price":"120.000000"}],"is_fuel_category":"0","product_id":"298","current_stock":"1.0000","total_before_tax":"189.480000","discount_amount":"0","tax_amount":"0","shipping_details":null,"shipping_charges":"0","price_adjustment":"0","final_total":"189.48","additional_notes":null,"payment":[{"payment_id":null,"amount":"189.48","method":"cash","account_id":"137","cheque_number":null,"cheque_date":null,"card_transaction_number":null,"bank_name":null,"transaction_no_1":null,"transaction_no_2":null,"transaction_no_3":null,"note":null}],"transaction_date_range_cheque_deposit":"05\\/07\\/2025 - 05\\/07\\/2025"}',
                'sale_return_data' => NULL,
                'stock_transfer_data' => NULL,
                'stock_adjustment_data' => NULL,
                'add_expense_data' => '{"location_id":"2","expense_category_id":null,"ref_no":"EP2025\\/0548","transaction_date":"05\\/05\\/2025 13:27","expense_for":null,"contact_id":null,"additional_notes":null,"payee":"Walk-In Customer","tax_id":null,"tax_calculation_amount":"0","is_vat":"0","final_total":null,"expense_account":null,"recur_interval":null,"recur_interval_type":"days","recur_repetitions":null,"subscription_repeat_on":null,"payment":[{"payment_id":null,"amount":"0.00","method":null,"account_id":null,"cheque_number":null,"cheque_date":null,"card_transaction_number":null,"bank_name":null,"transaction_no_1":null,"transaction_no_2":null,"transaction_no_3":null,"note":null}],"transaction_date_range_cheque_deposit":"05\\/05\\/2025 - 05\\/05\\/2025","is_print":"0"}',
                'add_pos_data' => '{"invoice_no":"0245","price_later":"0","location_id":"2","transaction_date":"2025-05-28T17:53","ref_no":null,"price_group":"0","autoservice":"repair","pay_term_number":null,"pay_term_type":null,"search_product":null,"job_sheet_id":"1","job_sheet_no":"PX2025\\/0001","vehicle_no":"1212","sell_price_tax":"includes","cat_id_suggestion":"0","brand_id_suggestion":"0","is_pos":"1","is_duplicate":"0","was_customer_wallet":"0","in_customer_wallet":"0","size":"all","discount_type":"percentage","discount_amount":"0","rp_redeemed":"0","rp_redeemed_amount":"0","tax_rate_id":null,"tax_calculation_amount":"0","shipping_details":null,"shipping_address":null,"shipping_status":null,"delivered_to":null,"shipping_charges":"0","final_total":"0","is_credit_sale":"0","discount_type_modal":"percentage","discount_amount_modal":"0.00","order_tax_modal":null,"shipping_details_modal":null,"shipping_address_modal":null,"shipping_charges_modal":"0","shipping_status_modal":null,"delivered_to_modal":null,"payment":[{"payment_id":null,"amount":"0","method":"cash","account_id":null,"cheque_number":null,"cheque_date":null,"card_transaction_number":null,"bank_name":null,"transaction_no_1":null,"transaction_no_2":null,"transaction_no_3":null,"note":null}],"sale_note":null,"staff_note":null,"additional_notes":null,"is_suspend":"0","recur_interval":null,"recur_interval_type":"days","recur_repetitions":null}',
                'created_at' => '2024-02-29 14:56:49',
                'updated_at' => '2025-05-29 14:06:57',
                'settlement_sw_data' => '{"settlement_no":"SET-SW1","location_id":"2","pump_operator_id":"2","transaction_date":"05\\/29\\/2025","work_shift":["1"],"note":null,"pump_no":null,"pump_starting_meter":null,"pump_closing_meter":null,"meter_sale_unit_price":null,"testing_qty":"0.00","meter_sale_discount_type":null,"meter_sale_discount":"0.00","store_id":"3","item":null,"balance_stock":null,"other_sale_price":null,"other_sale_qty":null,"other_sale_discount_type":null,"other_sale_discount":null,"other_sale_total":"0","sw_credit_sale_customer_iddelete_expense_payment":"6","sw_order_number":null,"sw_order_date":"05\\/29\\/2025","sw_customer_reference":null,"sw_credit_sale_product_id":null,"sw_credit_sale_qty":null,"credit_sale_qty_hidden":"0","sw_unit_price":null,"credit_sale_amount_hidden":"0","note_temp":null,"other_income_product_id":null,"other_income_reason":null,"other_income_qty":null,"other_income_total":"0","settlement_customer_payment_no":"SW-CP1","customer_payment_customer_id":"4","customer_payment_payment_method":null,"customer_payment_account_module":null,"customer_payment_bank_name":null,"customer_payment_cheque_date":"05\\/29\\/2025","customer_payment_cheque_number":null,"customer_payment_amount":null,"customer_payment_total":"0","meter_sales":[{"code":"99","product_name":"Lanka Petrol 92","pump_no":"P3","pump_start":"1,372,500.000","pump_close":"1,372,590.000","unit_price":"10.000","sold_qty":"80.000","discount_type":"null","discount_val":"0.000","testing_qty":"10.000","total_qty":"90.000","before_discount":"800.000","after_discount":"800.000","meter_sale_total":null,"id":"5944"}]}',
            ),
        ));
        
        
    }
}