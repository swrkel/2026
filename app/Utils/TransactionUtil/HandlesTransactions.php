<?php

namespace App\Utils\TransactionUtil;

use App\AccountTransaction;
use App\Account;
use App\AccountType;
use Modules\Fleet\Entities\Fleet;
use App\Business;
use App\BusinessLocation;
use App\Utils\Util;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\Currency;
use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Exceptions\PurchaseSellMismatch;
use App\Http\Controllers\Ecom\ContactController;
use App\InvoiceScheme;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\StockAdjustmentLine;
use App\TransactionSellLinesPurchaseLines;
use App\Variation;
use App\VariationLocationDetails;
use App\VariationStoreDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\PaymentMethod;
use App\System;;
use Illuminate\Support\Facades\Auth;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\TankSellLine;
use Modules\Petro\Entities\TankPurchaseLine;
use Modules\Property\Entities\Property;
use Modules\Property\Entities\PropertyBlock;
use Modules\Property\Entities\PropertySellLine;
use Modules\Property\Entities\PropertyAccountSetting;
use Modules\Petro\Entities\DipReading;
use Modules\Petro\Entities\PumpOperatorCommission;
use App\Variation_store_detail;
use App\ExpenseCategory;
use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\TankTransfer;
use Modules\Vat\Entities\VatCustomerStatement;
use Modules\Vat\Entities\VatCustomerStatementDetail;
use Modules\Shipping\Entities\ShippingAgentCommission;
use Modules\Shipping\Entities\ShippingPartnerCommission;
use Modules\SMS\Entities\SmsListInterest;
use Modules\Superadmin\Entities\RefillBusiness;
use App\SmsLog;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatSetting;
use Modules\Superadmin\Entities\SmsApiClient;
use Modules\Superadmin\Entities\SmsReminderSetting;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use App\ProductVariation;
use App\Unit;
use App\Brands;
use Modules\Petro\Entities\DailyVoucherItem;
use Modules\Petro\Entities\DailyVoucher;
use App\Http\Controllers\SellController;
use Illuminate\Http\Request;

/**
 * Creating, updating and deleting transactions and their lines.
 *
 * MA-002: split out of App\Utils\TransactionUtil, which was 11,666 lines in
 * a single file with 180 methods.
 *
 * THIS IS CORE, NOT A MODULE - it is used across the whole system, so the
 * split is deliberately the safest kind available: a trait. The class keeps
 * its name, its namespace and every one of its methods, so all 485 call sites
 * that reach into TransactionUtil resolve exactly as before. Nothing outside
 * this directory needed to change.
 *
 * Method bodies are byte-identical to the original.
 *
 * Methods here: createOrUpdatePriceAdjustment, createSellTransaction, updateSellTransaction, createOrUpdateSellLines, editSellLine, deleteSellLines, deleteSellLinesSettlement, createCreditPurchaseTransactions, createCreditSaleTransactions, createTransitTransactions, updateCreditTransactions, deleteAccountPaybleTransactionReverse, canBeEdited, createSellReturnTransaction, createOpeningBalanceTransactionForBakeryUser, updateOpeningBalanceTransactionForBakeryUser, createOpeningBalanceTransaction, isReturnExist, recalculateSellLineTotals, createRecurringInvoice, getTransactionTotals, updateCustomerRewardPoints, createOrUpdateSellLinesSettlement, createOrUpdateSellLinesVatBill, getTransactionProductDetail, updatePropertyStatus
 */
trait HandlesTransactions
{
public function createOrUpdatePriceAdjustment($sale, $id = null)
    {
        $final_total = $sale->price_adjustment;
        $total_before_tax = $sale->price_adjustment;


        if ($final_total != 0) {
            $ob_data = [
                'business_id' => $sale->business_id,
                'type' => 'vat_price_adjustment',
                'status' => 'final',
                'payment_status' => 'paid',
                'contact_id' => $sale->customer_id,
                'transaction_date' => !empty($sale->print_date) ? \Carbon::parse($sale->print_date)->format('Y-m-d') : \Carbon::parse($sale->date)->format('Y-m-d'),
                'total_before_tax' => $total_before_tax,
                'final_total' => $final_total,
                'tax_amount' => 0,
                'created_by' => request()->session()->get('user.id'),
                'invoice_no' => !empty($sale->statement_no) ? $sale->statement_no : $sale->customer_bill_no,

            ];

            $transaction = Transaction::where('invoice_no', $id)->where('type', 'vat_price_adjustment')->first();

            if (empty($transaction)) {
                //Create transaction
                $transaction = Transaction::create($ob_data);
            } else {
                Transaction::where('invoice_no', $id)->where('type', 'vat_price_adjustment')->update($ob_data);
            }

            if (!empty($transaction)) {
                // first delete previous account transactions
                AccountTransaction::where('transaction_id', $transaction->id)->forceDelete();
                $account_id = $this->account_exist_return_id('Accounts Receivable');

                if ($sale->price_adjustment > 0) {
                    $type = 'debit';
                } elseif ($sale->price_adjustment < 0) {
                    $type = 'credit';
                }

                $account_transaction_data = [
                    'amount' => abs($transaction->final_total),
                    'account_id' => $account_id,
                    'contact_id' => $transaction->contact_id,
                    'type' => $type,
                    'operation_date' => $transaction->transaction_date,
                    'created_by' => $transaction->created_by,
                    'transaction_id' => $transaction->id
                ];


                AccountTransaction::createAccountTransaction($account_transaction_data);
            }




            return $transaction;
        }

        return false;
    }

    public function createSellTransaction($business_id, $input, $invoice_total, $user_id, $uf_data = true)
    {
        try {

            $invoice_scheme_id = !empty($input['invoice_scheme_id']) ? $input['invoice_scheme_id'] : null;
            $invoice_no = !empty($input['invoice_no']) ? $input['invoice_no'] : $this->getInvoiceNumber($business_id, $input['status'], $input['location_id'], $invoice_scheme_id);

            $duplicate_invoice_count = Transaction::where('is_duplicate', 1)
                ->where('business_id', $business_id)
                ->count();

            $business = Business::where('id', $business_id)->first();
            $pos_settings = json_decode($business->pos_settings);

            if ($input['is_duplicate']) {
                $d_prefix = '';
                if (!empty($pos_settings->enable_prefix_duplicate_invoice)) {
                    $d_prefix = $pos_settings->duplicate_invoice_prefix;
                }
                if ($duplicate_invoice_count <= 0) {
                    $invoice_no = $d_prefix . '1';
                } else {
                    $duplicate_invoice_count++;
                    $invoice_no = $d_prefix . $duplicate_invoice_count;
                }
            }

            $final_total = $uf_data ? $this->num_uf($input['final_total']) : $input['final_total'];
            $ref_no = $input['is_quotation'] ? $this->getLastQuotationRefNo() : '';


            $transaction = Transaction::create([
                'business_id' => $business_id,
                'location_id' => $input['location_id'],
                'is_duplicate' => $input['is_duplicate'],
                'type' => $input['type'],
                'status' => $input['status'],
                'contact_id' => $input['contact_id'],
                'customer_group_id' => $input['customer_group_id'],
                'invoice_no' => $invoice_no,
                'ref_no' => $ref_no,
                'total_before_tax' => $invoice_total['total_before_tax'],
                'transaction_date' => $input['transaction_date'],
                'tax_id' => !empty($input['tax_rate_id']) ? $input['tax_rate_id'] : null,
                'order_tax_id' => !empty($input['order_tax_modal']) ? $input['order_tax_modal'] : null,
                'discount_type' => !empty($input['discount_type']) ? $input['discount_type'] : null,
                'discount_amount' => $uf_data ? $this->num_uf($input['discount_amount']) : $input['discount_amount'],
                'tax_amount' => $invoice_total['tax'],
                'final_total' => $final_total,
                'additional_notes' => !empty($input['sale_note']) ? $input['sale_note'] : null,
                'staff_note' => !empty($input['staff_note']) ? $input['staff_note'] : null,
                'created_by' => $user_id,
                'is_direct_sale' => !empty($input['is_direct_sale']) ? $input['is_direct_sale'] : 0,
                'commission_agent' => $input['commission_agent'],
                'is_quotation' => isset($input['is_quotation']) ? $input['is_quotation'] : 0,
                'is_customer_order' => isset($input['is_customer_order']) ? $input['is_customer_order'] : 0,
                'shipping_details' => isset($input['shipping_details']) ? $input['shipping_details'] : null,
                'shipping_address' => isset($input['shipping_address']) ? $input['shipping_address'] : null,
                'shipping_status' => isset($input['shipping_status']) ? $input['shipping_status'] : null,
                'delivered_to' => isset($input['delivered_to']) ? $input['delivered_to'] : null,
                'shipping_charges' => isset($input['shipping_charges']) ? ($uf_data ? $this->num_uf($input['shipping_charges']) : $input['shipping_charges']) : 0,
                'exchange_rate' => !empty($input['exchange_rate']) ? ($uf_data ? $this->num_uf($input['exchange_rate']) : $input['exchange_rate']) : 1,
                'selling_price_group_id' => isset($input['selling_price_group_id']) ? $input['selling_price_group_id'] : null,
                'pay_term_number' => isset($input['pay_term_number']) ? $input['pay_term_number'] : null,
                'pay_term_type' => isset($input['pay_term_type']) ? $input['pay_term_type'] : null,
                'is_suspend' => !empty($input['is_suspend']) ? 1 : 0,
                'is_recurring' => !empty($input['is_recurring']) ? $input['is_recurring'] : 0,
                'recur_interval' => !empty($input['recur_interval']) ? $input['recur_interval'] : null,
                'recur_interval_type' => !empty($input['recur_interval_type']) ? $input['recur_interval_type'] : null,
                'subscription_no' => !empty($input['subscription_no']) ? $input['subscription_no'] : null,
                'recur_repetitions' => !empty($input['recur_repetitions']) ? $input['recur_repetitions'] : 0,
                'order_addresses' => !empty($input['order_addresses']) ? $input['order_addresses'] : null,
                'sub_type' => !empty($input['sub_type']) ? $input['sub_type'] : null,
                'rp_earned' => $input['status'] == 'final' ? $this->calculateRewardPoints($business_id, $final_total) : 0,
                'rp_redeemed' => !empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0,
                'rp_redeemed_amount' => !empty($input['rp_redeemed_amount']) ? $input['rp_redeemed_amount'] : 0,
                'is_created_from_api' => !empty($input['is_created_from_api']) ? 1 : 0,
                'types_of_service_id' => !empty($input['types_of_service_id']) ? $input['types_of_service_id'] : null,
                'packing_charge' => !empty($input['packing_charge']) ? $input['packing_charge'] : 0,
                'packing_charge_type' => !empty($input['packing_charge_type']) ? $input['packing_charge_type'] : null,
                'service_custom_field_1' => !empty($input['service_custom_field_1']) ? $input['service_custom_field_1'] : null,
                'service_custom_field_2' => !empty($input['service_custom_field_2']) ? $input['service_custom_field_2'] : null,
                'service_custom_field_3' => !empty($input['service_custom_field_3']) ? $input['service_custom_field_3'] : null,
                'service_custom_field_4' => !empty($input['service_custom_field_4']) ? $input['service_custom_field_4'] : null,
                'order_status' => !empty($input['order_status']) ? $input['order_status'] : null,
                'order_no' => !empty($input['order_no']) ? $input['order_no'] : null,
                'order_date' => !empty($input['order_date']) ? $input['order_date'] : null,
                'customer_ref' => !empty($input['customer_ref']) ? $input['customer_ref'] : null,
                'repair_job_sheet_id' => !empty($input['job_sheet_id']) ? $input['job_sheet_id'] : null,
                'is_credit_sale' => !empty($input['is_credit_sale']) ? $input['is_credit_sale'] : 0,
                'need_to_reserve' => !empty($input['need_to_reserve']) ? $input['need_to_reserve'] : null,
                'is_over_limit_credit_sale' => !empty($input['is_over_limit_credit_sale']) ? $input['is_over_limit_credit_sale'] : 0,
                'approved_user' => !empty($input['approved_user']) ? $input['approved_user'] : null,
                'requested_by' => !empty($input['requested_by']) ? $input['requested_by'] : null,
                'over_limit_amount' => !empty($input['over_limit_amount']) ? $input['over_limit_amount'] : 0.00,
                'customer_limit' => !empty($input['customer_limit']) ? $input['customer_limit'] : 0.00,
                'price_later' => !empty($input['price_later']) ? $input['price_later'] : 0,
                'store_id' => $input['store_id']
            ]);


            return $transaction;
        } catch (\Exception $e) {
            \Log::error("Error creating sell transaction: " . $e->getMessage(), [
                'input' => $input,
                'invoice_total' => $invoice_total,
                'user_id' => $user_id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // optionally rethrow to handle it elsewhere
        }
    }

    /**
     * Add Sell transaction
     *
     * @param mixed $transaction_id
     * @param int $business_id
     * @param array $input
     * @param float $invoice_total
     * @param int $user_id
     *
     * @return Transaction
     */

    public function updateSellTransaction($transaction_id, $business_id, $input, $invoice_total, $user_id, $uf_data = true, $change_invoice_number = true)
    {
        $transaction = $transaction_id;
        if (!is_object($transaction)) {
            $transaction = Transaction::where('id', $transaction_id)
                ->where('business_id', $business_id)
                ->firstOrFail();
        }
        //Update invoice number if changed from draft to finalize or vice-versa
        $invoice_no = $transaction->invoice_no;
        if ($transaction->status != $input['status'] && $change_invoice_number) {
            // $invoice_scheme_id = !empty($input['invoice_scheme_id']) ? $input['invoice_scheme_id'] : null;
            // $invoice_no = $this->getInvoiceNumber($business_id, $input['status'], $transaction->location_id, $invoice_scheme_id);
            $sellController = app()->make(SellController::class);
            $request = new Request([
                'location_id' => 0,
                'type' => 'final'
            ]);
            $getInvoiveNo = $sellController->getInvoiveNo($request);
            $invoice_no = $getInvoiveNo['orignal_invoice_no'];
        }
        $final_total = $uf_data ? $this->num_uf($input['final_total']) : $input['final_total'];
        $update_date = [
            'status' => $input['status'],
            'invoice_no' => $invoice_no,
            'sale_ref' => $transaction->invoice_no,
            'contact_id' => $input['contact_id'],
            'customer_group_id' => $input['customer_group_id'],
            'total_before_tax' => $invoice_total['total_before_tax'],
            'tax_id' => $input['tax_rate_id'],
            'discount_type' => $input['discount_type'],
            'discount_amount' => $uf_data ? $this->num_uf($input['discount_amount']) : $input['discount_amount'],
            'tax_amount' => $invoice_total['tax'],
            'final_total' => $final_total,
            'additional_notes' => !empty($input['sale_note']) ? $input['sale_note'] : null,
            'staff_note' => !empty($input['staff_note']) ? $input['staff_note'] : null,
            'commission_agent' => $input['commission_agent'],
            'is_quotation' => isset($input['is_quotation']) ? $input['is_quotation'] : 0,
            'shipping_details' => isset($input['shipping_details']) ? $input['shipping_details'] : null,
            'shipping_charges' => isset($input['shipping_charges']) ? $uf_data ? $this->num_uf($input['shipping_charges']) : $input['shipping_charges'] : 0,
            'shipping_address' => isset($input['shipping_address']) ? $input['shipping_address'] : null,
            'shipping_status' => isset($input['shipping_status']) ? $input['shipping_status'] : null,
            'delivered_to' => isset($input['delivered_to']) ? $input['delivered_to'] : null,
            'exchange_rate' => !empty($input['exchange_rate']) ? $uf_data ? $this->num_uf($input['exchange_rate']) : $input['exchange_rate'] : 1,
            'selling_price_group_id' => isset($input['selling_price_group_id']) ? $input['selling_price_group_id'] : null,
            'pay_term_number' => isset($input['pay_term_number']) ? $input['pay_term_number'] : null,
            'pay_term_type' => isset($input['pay_term_type']) ? $input['pay_term_type'] : null,
            'is_suspend' => !empty($input['is_suspend']) ? 1 : 0,
            'is_recurring' => !empty($input['is_recurring']) ? $input['is_recurring'] : 0,
            'recur_interval' => !empty($input['recur_interval']) ? $input['recur_interval'] : null,
            'recur_interval_type' => !empty($input['recur_interval_type']) ? $input['recur_interval_type'] : null,
            'recur_repetitions' => !empty($input['recur_repetitions']) ? $input['recur_repetitions'] : 0,
            'order_addresses' => !empty($input['order_addresses']) ? $input['order_addresses'] : null,
            'rp_earned' => $input['status'] == 'final' ? $this->calculateRewardPoints($business_id, $final_total) : 0,
            'rp_redeemed' => !empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0,
            'repair_job_sheet_id' => !empty($input['job_sheet_id']) ? $input['job_sheet_id'] : ($transaction->repair_job_sheet_id ?? null),
            'rp_redeemed_amount' => !empty($input['rp_redeemed_amount']) ? $input['rp_redeemed_amount'] : 0,
            'types_of_service_id' => !empty($input['types_of_service_id']) ? $input['types_of_service_id'] : null,
            'packing_charge' => !empty($input['packing_charge']) ? $input['packing_charge'] : 0,
            'packing_charge_type' => !empty($input['packing_charge_type']) ? $input['packing_charge_type'] : null,
            'service_custom_field_1' => !empty($input['service_custom_field_1']) ? $input['service_custom_field_1'] : null,
            'service_custom_field_2' => !empty($input['service_custom_field_2']) ? $input['service_custom_field_2'] : null,
            'service_custom_field_3' => !empty($input['service_custom_field_3']) ? $input['service_custom_field_3'] : null,
            'service_custom_field_4' => !empty($input['service_custom_field_4']) ? $input['service_custom_field_4'] : null
        ];
        if (!empty($input['transaction_date'])) {
            $update_date['transaction_date'] = $input['transaction_date'];
        }
        $transaction->fill($update_date);
        $transaction->update();
        return $transaction;
    }
    /**
     * Add/Edit transaction sell lines
     *
     * @param object/int $transaction
     * @param array $products
     * @param array $location_id
     * @param boolean $return_deleted = false
     * @param array $extra_line_parameters = []
     *   Example: ['database_trasnaction_linekey' => 'products_line_key'];
     *
     * @return boolean/object
     */

    public function createOrUpdateSellLines($transaction, $products, $location_id, $return_deleted = false, $status_before = null, $extra_line_parameters = [], $uf_data = true)
    {
        $lines_formatted = [];
        $modifiers_array = [];
        $edit_ids = [0];
        $modifiers_formatted = [];
        $combo_lines = [];
        $products_modified_combo = [];
        foreach ($products as $product) {
            $multiplier = 1;
            if (isset($product['sub_unit_id']) && $product['sub_unit_id'] == $product['product_unit_id']) {
                unset($product['sub_unit_id']);
            }
            if (!empty($product['sub_unit_id']) && !empty($product['base_unit_multiplier'])) {
                $multiplier = $product['base_unit_multiplier'];
            }
            //Check if transaction_sell_lines_id is set, used when editing.
            if (!empty($product['transaction_sell_lines_id'])) {
                $edit_ids[] = $product['transaction_sell_lines_id'];
                $this->editSellLine($product, $location_id, $status_before, $multiplier);
                //update or create modifiers for existing sell lines
                if ($this->isModuleEnabled('modifiers')) {
                    if (!empty($product['modifier'])) {
                        foreach ($product['modifier'] as $key => $value) {
                            if (!empty($product['modifier_sell_line_id'][$key])) {
                                //Dont delete modifier sell line if exists
                                $edit_ids[] = $product['modifier_sell_line_id'][$key];
                            } else {
                                if (!empty($product['modifier_price'][$key])) {
                                    $this_price = $uf_data ? $this->num_uf($product['modifier_price'][$key]) : $product['modifier_price'][$key];
                                    $modifiers_formatted[] = new TransactionSellLine([
                                        'product_id' => $product['modifier_set_id'][$key],
                                        'variation_id' => $value,
                                        'quantity' => 1,
                                        'unit_price_before_discount' => $this_price,
                                        'unit_price' => $this_price,
                                        'unit_price_inc_tax' => $this_price,
                                        'parent_sell_line_id' => $product['transaction_sell_lines_id'],
                                        'children_type' => 'modifier'
                                    ]);
                                }
                            }
                        }
                    }
                }
            } else {
                $products_modified_combo[] = $product;
                //calculate unit price and unit price before discount
                $unit_price = $uf_data ? $this->num_uf($product['unit_price']) : $product['unit_price'];
                $unit_price = $unit_price / $multiplier;

                $uf_quantity = $uf_data ? $this->num_uf($product['quantity']) : $product['quantity'];
                $uf_item_tax = $uf_data ? $this->num_uf($product['item_tax']) : $product['item_tax'];
                $item_tax = $uf_item_tax / $multiplier;

                $uf_unit_price_inc_tax = $uf_data ? $this->num_uf($product['unit_price_inc_tax']) : $product['unit_price_inc_tax'];
                $line = [
                    'product_id' => $product['product_id'],
                    'variation_id' => $product['variation_id'],
                    'quantity' => $uf_quantity * $multiplier,
                    'unit_price_before_discount' => $unit_price,
                    'unit_price' => $unit_price + $item_tax,
                    'line_discount_type' => !empty($product['line_discount_type']) ? $product['line_discount_type'] : null,
                    'line_discount_amount' => !empty($product['line_discount_amount']) ? $uf_data ? $this->num_uf($product['line_discount_amount']) : $product['line_discount_amount'] : 0,
                    'item_tax' => $item_tax,
                    'tax_id' => $product['tax_id'],
                    'unit_price_inc_tax' => $uf_unit_price_inc_tax / $multiplier,
                    'sell_line_note' => !empty($product['sell_line_note']) ? $product['sell_line_note'] : '',
                    'sub_unit_id' => !empty($product['sub_unit_id']) ? $product['sub_unit_id'] : null,
                    'discount_id' => !empty($product['discount_id']) ? $product['discount_id'] : null,
                    'res_service_staff_id' => !empty($product['res_service_staff_id']) ? $product['res_service_staff_id'] : null,
                    'res_line_order_status' => !empty($product['res_service_staff_id']) ? 'received' : null,
                    'weight_loss' => !empty($product['weight_loss']) ? $this->num_uf($product['weight_loss']) : null,
                    'weight_excess' => !empty($product['weight_excess']) ? $this->num_uf($product['weight_excess']) : null,
                    'last_purchased_price' => !empty($product['last_purchased_price']) ? $this->num_uf($product['last_purchased_price']) : null
                ];
                foreach ($extra_line_parameters as $key => $value) {
                    $line[$key] = isset($product[$value]) ? $product[$value] : '';
                }
                if (!empty($product['lot_no_line_id'])) {
                    $line['lot_no_line_id'] = $product['lot_no_line_id'];
                }
                //Check if restaurant module is enabled then add more data related to that.
                if ($this->isModuleEnabled('modifiers')) {
                    $sell_line_modifiers = [];
                    if (!empty($product['modifier'])) {
                        foreach ($product['modifier'] as $key => $value) {
                            if (!empty($product['modifier_price'][$key])) {
                                $this_price = $uf_data ? $this->num_uf($product['modifier_price'][$key]) : $product['modifier_price'][$key];
                                $sell_line_modifiers[] = [
                                    'product_id' => $product['modifier_set_id'][$key],
                                    'variation_id' => $value,
                                    'quantity' => 1,
                                    'unit_price_before_discount' => $this_price,
                                    'unit_price' => $this_price,
                                    'unit_price_inc_tax' => $this_price,
                                    'children_type' => 'modifier'
                                ];
                            }
                        }
                    }
                    $modifiers_array[] = $sell_line_modifiers;
                }
                $lines_formatted[] = new TransactionSellLine($line);
                $sell_line_warranties[] = !empty($product['warranty_id']) ? $product['warranty_id'] : 0;
            }
        }
        if (!is_object($transaction)) {
            $transaction = Transaction::findOrFail($transaction);
        }
        //Delete the products removed and increment product stock.
        $deleted_lines = [];
        if (!empty($edit_ids)) {
            $deleted_lines = TransactionSellLine::where('transaction_id', $transaction->id)
                ->whereNotIn('id', $edit_ids)
                ->whereNull('parent_sell_line_id')
                ->select('id')->get()->toArray();
            $combo_delete_lines = TransactionSellLine::whereIn('parent_sell_line_id', $deleted_lines)->where('children_type', 'combo')->select('id')->get()->toArray();
            $deleted_lines = array_merge($deleted_lines, $combo_delete_lines);
            $adjust_qty = $status_before == 'draft' ? false : true;
            $this->deleteSellLines($deleted_lines, $location_id, $adjust_qty);
        }
        $combo_lines = [];
        if (!empty($lines_formatted)) {
            $transaction->sell_lines()->saveMany($lines_formatted);
            //Add corresponding modifier sell lines if exists
            if ($this->isModuleEnabled('modifiers')) {
                foreach ($lines_formatted as $key => $value) {
                    if (!empty($modifiers_array[$key])) {
                        foreach ($modifiers_array[$key] as $modifier) {
                            $modifier['parent_sell_line_id'] = $value->id;
                            $modifiers_formatted[] = new TransactionSellLine($modifier);
                        }
                    }
                }
            }
            //Combo product lines.
            //$products_value = array_values($products);
            foreach ($lines_formatted as $key => $value) {
                if (!empty($products_modified_combo[$key]['product_type']) && $products_modified_combo[$key]['product_type'] == 'combo') {
                    $combo_lines = array_merge($combo_lines, $this->__makeLinesForComboProduct($products_modified_combo[$key]['combo'], $value));
                }
                //Save sell line warranty if set
                if (!empty($sell_line_warranties[$key])) {
                    $value->warranties()->sync([$sell_line_warranties[$key]]);
                }
            }
        }
        if (!empty($combo_lines)) {
            $transaction->sell_lines()->saveMany($combo_lines);
        }
        if (!empty($modifiers_formatted)) {
            $transaction->sell_lines()->saveMany($modifiers_formatted);
        }
        if ($return_deleted) {
            return $deleted_lines;
        }
        return true;
    }
    /**
     * Returns the line for combo product
     *
     * @param array $combo_items
     * @param object $parent_sell_line
     *
     * @return array
     */

    public function editSellLine($product, $location_id, $status_before, $multiplier = 1)
    {
        //Get the old order quantity
        $sell_line = TransactionSellLine::with(['product', 'warranties'])
            ->find($product['transaction_sell_lines_id']);
        //Adjust quanity
        if ($status_before != 'draft') {
            $new_qty = $this->num_uf($product['quantity']) * $multiplier;
            $difference = $sell_line->quantity - $new_qty;
            // Adjust quantity back to the same store where the sell line originated
            $store_id = optional($sell_line->transaction)->store_id;
            $this->adjustQuantity($location_id, $product['product_id'], $product['variation_id'], $difference, $store_id);
        }
        $unit_price_before_discount = $this->num_uf($product['unit_price']) / $multiplier;
        $unit_price = $unit_price_before_discount;
        if (!empty($product['line_discount_type']) && $product['line_discount_amount']) {
            $discount_amount = $this->num_uf($product['line_discount_amount']);
            if ($product['line_discount_type'] == 'fixed') {
                $unit_price = $unit_price_before_discount - $discount_amount;
            } elseif ($product['line_discount_type'] == 'percentage') {
                $unit_price = ((100 - $discount_amount) * $unit_price_before_discount) / 100;
            }
        }
        //Update sell lines.
        $sell_line->fill([
            'product_id' => $product['product_id'],
            'variation_id' => $product['variation_id'],
            'quantity' => $this->num_uf($product['quantity']) * $multiplier,
            'unit_price_before_discount' => $unit_price_before_discount,
            'unit_price' => $unit_price,
            'line_discount_type' => !empty($product['line_discount_type']) ? $product['line_discount_type'] : null,
            'line_discount_amount' => !empty($product['line_discount_amount']) ? $this->num_uf($product['line_discount_amount']) : 0,
            'item_tax' => $this->num_uf($product['item_tax']) / $multiplier,
            'tax_id' => $product['tax_id'],
            'unit_price_inc_tax' => $this->num_uf($product['unit_price_inc_tax']) / $multiplier,
            'sell_line_note' => !empty($product['sell_line_note']) ? $product['sell_line_note'] : '',
            'sub_unit_id' => !empty($product['sub_unit_id']) ? $product['sub_unit_id'] : null,
            'res_service_staff_id' => !empty($product['res_service_staff_id']) ? $product['res_service_staff_id'] : null,
            'weight_loss' => !empty($product['weight_loss']) ? $this->num_uf($product['weight_loss']) : null,
            'weight_excess' => !empty($product['weight_excess']) ? $this->num_uf($product['weight_excess']) : null
        ]);
        $sell_line->save();
        //Set warranty
        if (!empty($product['warranty_id'])) {
            $warranty_ids = $sell_line->warranties->pluck('warranty_id')->toArray();
            if (!in_array($product['warranty_id'], $warranty_ids)) {
                $warranty_ids[] = $product['warranty_id'];
                $sell_line->warranties()->sync($warranty_ids);
            }
        } else {
            $sell_line->warranties()->sync([]);
        }
        //Adjust the sell line for combo items.
        if (isset($product['product_type']) && $product['product_type'] == 'combo') {
            //$this->editSellLineCombo($sell_line, $location_id, $sell_line->quantity, $new_qty);
            $adjust_stock = ($status_before != 'draft');
            $this->updateEditedSellLineCombo($product['combo'], $location_id, $adjust_stock);
        }
    }
    /**
     * Delete the products removed and increment product stock.
     *
     * @param array $transaction_line_ids
     * @param int $location_id
     *
     * @return boolean
     */

    public function deleteSellLines($transaction_line_ids, $location_id, $adjust_qty = true)
    {
        if (!empty($transaction_line_ids)) {
            $sell_lines = TransactionSellLine::with('transaction')->whereIn('id', $transaction_line_ids)
                ->get();
            //Adjust quanity
            if ($adjust_qty) {
                foreach ($sell_lines as $line) {
                    $store_id = optional($line->transaction)->store_id;
                    $this->adjustQuantity($location_id, $line->product_id, $line->variation_id, $line->quantity, $store_id);
                }
            }
            TransactionSellLine::whereIn('id', $transaction_line_ids)
                ->delete();
        }
    }
    /**
     * Delete the products removed and increment product stock.
     *
     * @param array $transaction_line_ids
     * @param int $location_id
     *
     * @return boolean
     */

    public function deleteSellLinesSettlement($transaction_line_ids, $location_id, $adjust_qty = true)
    {
        if (!empty($transaction_line_ids)) {
            $sell_lines = TransactionSellLine::with('transaction')->whereIn('id', $transaction_line_ids)
                ->get();
            //Adjust quanity
            if ($adjust_qty) {
                foreach ($sell_lines as $line) {
                    $store_id = optional($line->transaction)->store_id;
                    $this->adjustQuantity($location_id, $line->product_id, $line->variation_id, $line->quantity, $store_id);
                }
            }
            TransactionSellLine::whereIn('id', $transaction_line_ids)
                ->forceDelete();
        }
    }
    /**
     * Adjust the quantity of product and its variation
     *
     * @param int $location_id
     * @param int $product_id
     * @param int $variation_id
     * @param float $increment_qty
     *
     * @return boolean
     */

    public function createCreditPurchaseTransactions($transaction, $account_transaction_data)
    {
        $account_payable_id = $this->account_exist_return_id('Accounts Payable');
        $payable_transaction_exist = AccountTransaction::where('transaction_id', $transaction->id)->where('type', 'credit')->where('account_id', $account_payable_id)->first();
        $contact_ledger_exist = ContactLedger::where('transaction_id', $transaction->id)->where('type', 'credit')->first();
        if (!empty($payable_transaction_exist)) {
            AccountTransaction::where('transaction_id', $transaction->id)->where('type', 'credit')->where('account_id', $account_payable_id)->where('id', '!=', $payable_transaction_exist->id)->forcedelete(); // quick fix for more then one payable account entries
            ContactLedger::where('transaction_id', $transaction->id)->where('type', 'credit')->where('id', '!=', $contact_ledger_exist->id)->forcedelete();
        } else {
            if ($transaction->type == 'purchase') {
                $account_transaction_data['type'] = 'credit';
                $account_transaction_data['account_id'] = $account_payable_id;
                AccountTransaction::createAccountTransaction($account_transaction_data);
                ContactLedger::createContactLedger($account_transaction_data);
            }
        }
        return true;
    }

    public function createCreditSaleTransactions($transaction, $account_transaction_data)
    {
        $account_payable_id = $account_transaction_data['account_id'];
        $payable_transaction_exist = AccountTransaction::where('transaction_id', $transaction->id)->where('type', 'credit')->where('account_id', $account_payable_id)->first();
        $contact_ledger_exist = ContactLedger::where('transaction_id', $transaction->id)->where('type', 'credit')->first();
        if (!empty($payable_transaction_exist)) {
            // Update existing account transaction with new amount
            $payable_transaction_exist->amount = $account_transaction_data['amount'];
            $payable_transaction_exist->operation_date = $account_transaction_data['operation_date'];
            $payable_transaction_exist->save();
            
            // Update existing contact ledger with new amount
            if (!empty($contact_ledger_exist)) {
                $contact_ledger_exist->amount = $account_transaction_data['amount'];
                $contact_ledger_exist->operation_date = $account_transaction_data['operation_date'];
                $contact_ledger_exist->save();
            }
            
            // Delete any duplicate entries
            AccountTransaction::where('transaction_id', $transaction->id)->where('type', 'credit')->where('account_id', $account_payable_id)->where('id', '!=', $payable_transaction_exist->id)->forcedelete();
            ContactLedger::where('transaction_id', $transaction->id)->where('type', 'credit')->where('id', '!=', $contact_ledger_exist->id)->forcedelete();
        } else {
            $account_transaction_data['type'] = 'credit';
            $account_transaction_data['account_id'] = $account_payable_id;
            AccountTransaction::createAccountTransaction($account_transaction_data);
            ContactLedger::createContactLedger($account_transaction_data);
        }
        return true;
    }

    public function createTransitTransactions($transaction, $account_transaction_data)
    {
        $account_payable_id = $this->account_exist_return_id('Goods in Transit');
        $payable_transaction_exist = AccountTransaction::where('transaction_id', $transaction->id)->where('type', 'debit')->where('account_id', $account_payable_id)->first();
        if (!empty($payable_transaction_exist)) {
            AccountTransaction::where('transaction_id', $transaction->id)->where('type', 'debit')->where('account_id', $account_payable_id)->where('id', '!=', $payable_transaction_exist->id)->forcedelete(); // quick fix for more then one payable account entries
        } else {
            if ($transaction->type == 'purchase') {
                $account_transaction_data['amount'] = $transaction->final_total;
                $account_transaction_data['type'] = 'debit';
                $account_transaction_data['account_id'] = $account_payable_id;
                AccountTransaction::createAccountTransaction($account_transaction_data);
            }
        }
        return true;
    }

    /**
     * Reconcile payment account transactions for a sell after edit.
     * Ensures a debit AccountTransaction exists per TransactionPayment to the proper payment account.
     */

    public function updateCreditTransactions($transaction, $payment_array)
    {
        $transaction_id = $transaction->id;
        $payable_account_id = $this->account_exist_return_id('Accounts Payable');
        if ($transaction->type == 'purchase') {
            //delete the account transaction if exist, payments, and ledger transactions
            AccountTransaction::where('transaction_id', $transaction_id)->forcedelete();
            TransactionPayment::where('transaction_id', $transaction_id)->forcedelete();
            ContactLedger::where('transaction_id', $transaction_id)->forcedelete();
            //create account payable entry
            $contact_id = $transaction->contact_id;
            $account_transaction_data = [
                'amount' => $this->num_uf($payment_array['amount']),
                'contact_id' => $contact_id,
                'account_id' => $payable_account_id,
                'type' => 'credit',
                'operation_date' => $transaction->transaction_date,
                'created_by' => Auth::user()->id,
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => null,
                'note' => null
            ];
            AccountTransaction::createAccountTransaction($account_transaction_data);
            //create ledger entry
            ContactLedger::createContactLedger($account_transaction_data);
        }
    }
    //create account payable transaction if purchase order date and cheque date not matches

    public function deleteAccountPaybleTransactionReverse($transaction)
    {
        if ($transaction->type == 'purchase') {
            $payable_account_id = $this->account_exist_return_id('Accounts Payable');
            $contact_id = $transaction->contact_id;
            $account_transaction_data = [
                'amount' => $transaction->final_total,
                'contact_id' => $contact_id,
                'account_id' => $payable_account_id,
                'type' => 'debit',
                'operation_date' => \Carbon::now(),
                'created_by' => Auth::user()->id,
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => null,
                'note' => null
            ];
            $new_account_transaction = AccountTransaction::createAccountTransaction($account_transaction_data);
            $new_account_transaction->deleted_by = Auth::user()->id;
            $new_account_transaction->save();
        }
        if ($transaction->type == 'sell') {
            $payable_account_id = $this->account_exist_return_id('Accounts Payable');
            $contact_id = $transaction->contact_id;
            $account_transaction_data = [
                'amount' => $transaction->final_total,
                'contact_id' => $contact_id,
                'account_id' => $payable_account_id,
                'type' => 'debit',
                'operation_date' => \Carbon::now(),
                'created_by' => Auth::user()->id,
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => null,
                'note' => null
            ];
            $new_account_transaction = AccountTransaction::createAccountTransaction($account_transaction_data);
            $new_account_transaction->deleted_by = Auth::user()->id;
            $new_account_transaction->save();
        }
    }

    public function canBeEdited($transaction, $edit_duration)
    {
        if (!is_object($transaction)) {
            $transaction = Transaction::find($transaction);
        }
        if (empty($transaction)) {
            return false;
        }
        $date = \Carbon::parse($transaction->transaction_date)
            ->addDays($edit_duration);
        $today = today();
        if ($date->gte($today)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Calculates total stock on the given date
     *
     * @param int $business_id
     * @param string $date
     * @param int $location_id
     * @param boolean $is_opening = false
     *
     * @return float
     */

    public function createSellReturnTransaction($business_id, $input, $invoice_total, $user_id)
    {
        $transaction = Transaction::create([
            'business_id' => $business_id,
            'location_id' => $input['location_id'],
            'type' => 'sell_return',
            'status' => 'final',
            'contact_id' => $input['contact_id'],
            'customer_group_id' => $input['customer_group_id'],
            'ref_no' => $input['ref_no'],
            'total_before_tax' => $invoice_total['total_before_tax'],
            'transaction_date' => $input['transaction_date'],
            'tax_id' => null,
            'discount_type' => $input['discount_type'],
            'discount_amount' => $this->num_uf($input['discount_amount']),
            'tax_amount' => $invoice_total['tax'],
            'final_total' => $this->num_uf($input['final_total']),
            'additional_notes' => !empty($input['additional_notes']) ? $input['additional_notes'] : null,
            'created_by' => $user_id,
            'is_quotation' => isset($input['is_quotation']) ? $input['is_quotation'] : 0
        ]);
        return $transaction;
    }

    public function createOpeningBalanceTransactionForBakeryUser($business_id, $pump_operator_id, $amount, $type, $location_id, $transaction_date = null)
    {
        $final_amount = $this->num_uf($amount);
        $ob_data = [
            'business_id' => $business_id,
            'location_id' => $location_id,
            'type' => 'bakery_user_opening_balance',
            'sub_type' => $type,
            'status' => 'final',
            'payment_status' => 'due',
            'pump_operator_id' => $pump_operator_id,
            'transaction_date' => \Carbon::parse($transaction_date) ?: \Carbon::now(),
            'total_before_tax' => $final_amount,
            'final_total' => $final_amount,
            'created_by' => request()->session()->get('user.id')
        ];
        //Update reference count
        $ob_ref_count = $this->setAndGetReferenceCount('opening_balance');
        //Generate reference number
        $ob_data['ref_no'] = $this->generateReferenceNumber('opening_balance', $ob_ref_count);
        //Create opening balance transaction
        $transaction = Transaction::create($ob_data);
        if ($type == 'shortage') {
            $transaction_type = 'debit';
            $account_id = $this->account_exist_return_id('Accounts Receivable');
        }
        if ($type == 'excess') {
            $transaction_type = 'credit';
            $account_id = $this->account_exist_return_id('Accounts Payable');
        }
        $account_transaction_data = [
            'amount' => abs($transaction->final_total),
            'account_id' => $account_id,
            'type' => $transaction_type,
            'sub_type' => 'ledger_show',
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => null
        ];
        AccountTransaction::createAccountTransaction($account_transaction_data);

        if ($type == 'shortage') {
            $opening_balance_transaction_type = 'credit';
        }
        if ($type == 'excess') {
            $opening_balance_transaction_type = 'debit';
        }
        $opening_balance_equity_id = $this->account_exist_return_id('Opening Balance Equity Account');
        $this->createAccountTransaction($transaction, $opening_balance_transaction_type, $opening_balance_equity_id, abs($transaction->final_total));
    }

    public function updateOpeningBalanceTransactionForBakeryUser($business_id, $pump_operator_id, $amount, $type, $location_id, $transaction_date = null)
    {
        $final_amount = $this->num_uf($amount);

        $opening_bal = Transaction::where([
            'type' => 'bakery_user_opening_balance',
            'pump_operator_id' => $pump_operator_id,
            'business_id' => $business_id
        ])->first();

        if (!empty($opening_bal)) {
            AccountTransaction::where('transaction_id', $opening_bal->id)->forcedelete();
        }

        if (!empty($amount)) {

            $ob_data = [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'type' => 'bakery_user_opening_balance',
                'sub_type' => $type,
                'status' => 'final',
                'payment_status' => 'due',
                'pump_operator_id' => $pump_operator_id,
                'transaction_date' => \Carbon::parse($transaction_date) ?: \Carbon::now(),
                'total_before_tax' => $final_amount,
                'final_total' => $final_amount,
                'created_by' => request()->session()->get('user.id')
            ];

            if (!empty($opening_bal)) {
                Transaction::where([
                    'type' => 'bakery_user_opening_balance',
                    'pump_operator_id' => $pump_operator_id,
                    'business_id' => $business_id
                ])->update($ob_data);

                $transaction = $opening_bal;
            } else {

                //Update reference count
                $ob_ref_count = $this->setAndGetReferenceCount('opening_balance');
                //Generate reference number
                $ob_data['ref_no'] = $this->generateReferenceNumber('opening_balance', $ob_ref_count);
                //Create opening balance transaction
                $transaction = Transaction::create($ob_data);
            }


            if ($type == 'shortage') {
                $transaction_type = 'debit';
                $account_id = $this->account_exist_return_id('Accounts Receivable');
            }
            if ($type == 'excess') {
                $transaction_type = 'credit';
                $account_id = $this->account_exist_return_id('Accounts Payable');
            }
            $account_transaction_data = [
                'amount' => abs($transaction->final_total),
                'account_id' => $account_id,
                'type' => $transaction_type,
                'sub_type' => 'ledger_show',
                'operation_date' => $transaction->transaction_date,
                'created_by' => $transaction->created_by,
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => null,
                'note' => null
            ];
            AccountTransaction::createAccountTransaction($account_transaction_data);

            if ($type == 'shortage') {
                $opening_balance_transaction_type = 'credit';
            }
            if ($type == 'excess') {
                $opening_balance_transaction_type = 'debit';
            }
            $opening_balance_equity_id = $this->account_exist_return_id('Opening Balance Equity Account');
            $this->createAccountTransaction($transaction, $opening_balance_transaction_type, $opening_balance_equity_id, abs($transaction->final_total));
        } else {
            if (!empty($opening_bal)) {
                $opening_bal->forcedelete();
            }
        }
    }

    /**
     * Creates a new opening balance transaction for a contact
     *
     * @param  int $business_id
     * @param  int $contact_id
     * @param  int $amount
     *
     * @return void
     */

    public function createOpeningBalanceTransaction($business_id, $contact_id, $amount, $transaction_date = null, $invoice_no = null)
    {
        $business_location = BusinessLocation::where('business_id', $business_id)
            ->first();
        $contact = Contact::where('id', $contact_id)->first();
        $final_amount = $this->num_uf($amount);
        $ob_data = [
            'business_id' => $business_id,
            'location_id' => $business_location->id,
            'type' => 'opening_balance',
            'status' => 'final',
            'payment_status' => 'due',
            'invoice_no' => !empty($invoice_no) ? $invoice_no : null,
            'contact_id' => $contact_id,
            'transaction_date' => !empty($transaction_date) ? \Carbon::parse($transaction_date)->format('Y-m-d') : \Carbon::now(),
            'total_before_tax' => $final_amount,
            'final_total' => $final_amount,
            'created_by' => request()->session()->get('user.id')
        ];
        //Update reference count
        $ob_ref_count = $this->setAndGetReferenceCount('opening_balance');
        //Generate reference number
        $ob_data['ref_no'] = $this->generateReferenceNumber('opening_balance', $ob_ref_count);
        //Create opening balance transaction
        $transaction = Transaction::create($ob_data);
        if ($contact->type == 'supplier') {
            if ($final_amount > 0) {
                $type = 'credit';
            }
            if ($final_amount < 0) {
                $type = 'debit';
            }
            $account_id = $this->account_exist_return_id('Accounts Payable');
        }
        if ($contact->type == 'customer') {
            if ($final_amount > 0) {
                $type = 'debit';
            }
            if ($final_amount < 0) {
                $type = 'credit';
            }
            $account_id = $this->account_exist_return_id('Accounts Receivable');
        }
        $account_transaction_data = [
            'amount' => abs($transaction->final_total),
            'contact_id' => $contact_id,
            'account_id' => $account_id,
            'type' => $type,
            'sub_type' => 'ledger_show',
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => null
        ];
        AccountTransaction::createAccountTransaction($account_transaction_data);
        ContactLedger::createContactLedger($account_transaction_data);

        if ($contact->type == 'customer') {
            if ($final_amount > 0) {
                $type = 'credit';
            }
            if ($final_amount < 0) {
                $type = 'debit';
            }
        } else {
            if ($final_amount > 0) {
                $type = 'debit';
            }
            if ($final_amount < 0) {
                $type = 'credit';
            }
        }


        $opening_balance_equity_id = $this->account_exist_return_id('Opening Balance Equity Account');
        $this->createAccountTransaction($transaction, $type, $opening_balance_equity_id, abs($transaction->final_total));


        if ($final_amount < 0) {
            if ($contact->type == 'customer') {
                $type = 'credit';
                $customer_over_payment_id = $this->account_exist_return_id('Customer Over Payments');
                $this->createAccountTransaction($transaction, $type, $customer_over_payment_id, abs($transaction->final_total));
            } else {
                $type = 'debit';
                $supplier_over_payment_id = $this->account_exist_return_id('Supplier Over Payments');
                $this->createAccountTransaction($transaction, $type, $supplier_over_payment_id, abs($transaction->final_total));
            }
        }
    }

    public function isReturnExist($transacion_id)
    {
        return Transaction::where('return_parent_id', $transacion_id)
            ->whereIn('type', ['sell_return', 'purchase_return'])
            ->whereNull('deleted_at')
            ->exists();
    }
    /**
     * Recalculates sell line data according to subunit data
     *
     * @param integer $unit_id
     *
     * @return array
     */

    public function recalculateSellLineTotals($business_id, $sell_line)
    {
        $unit_details = $this->getSubUnits($business_id, $sell_line->product->unit->id);
        $sub_unit = null;
        $sub_unit_id = $sell_line->sub_unit_id;
        foreach ($unit_details as $key => $value) {
            if ($key == $sub_unit_id) {
                $sub_unit = $value;
            }
        }
        if (!empty($sub_unit)) {
            $multiplier = !empty($sub_unit['multiplier']) ? $sub_unit['multiplier'] : 1;
            $sell_line->quantity = $sell_line->quantity / $multiplier;
            $sell_line->unit_price_before_discount = $sell_line->unit_price_before_discount * $multiplier;
            $sell_line->unit_price = $sell_line->unit_price * $multiplier;
            $sell_line->unit_price_inc_tax = $sell_line->unit_price_inc_tax * $multiplier;
            $sell_line->item_tax = $sell_line->item_tax * $multiplier;
            $sell_line->quantity_returned = $sell_line->quantity_returned / $multiplier;
            $sell_line->unit_details = $unit_details;
        }
        return $sell_line;
    }
    /**
     * Retrieves sum of due amount of a contact
     * @param int $contact_id
     *
     * @return mixed
     */

    public function createRecurringInvoice($transaction, $is_draft = false)
    {
        $data = $transaction->toArray();
        unset($data['id']);
        unset($data['created_at']);
        unset($data['updated_at']);
        if ($is_draft) {
            $data['status'] = 'draft';
        }
        $data['payment_status'] = 'due';
        $data['recur_parent_id'] = $transaction->id;
        $data['is_recurring'] = 0;
        $data['recur_interval'] = null;
        $data['recur_interval_type'] = null;
        $data['recur_repetitions'] = 0;
        $data['recur_stopped_on'] = null;
        $data['transaction_date'] = \Carbon::now();
        if (isset($data['invoice_token'])) {
            $data['invoice_token'] = null;
        }
        if (isset($data['woocommerce_order_id'])) {
            $data['woocommerce_order_id'] = null;
        }
        if (isset($data['recurring_invoices'])) {
            unset($data['recurring_invoices']);
        }
        if (isset($data['sell_lines'])) {
            unset($data['sell_lines']);
        }
        if (isset($data['business'])) {
            unset($data['business']);
        }
        $data['invoice_no'] = $this->getInvoiceNumber($transaction->business_id, $data['status'], $data['location_id']);
        $recurring_invoice = Transaction::create($data);
        $recurring_sell_lines = [];
        foreach ($transaction->sell_lines as $sell_line) {
            $sell_line_data = $sell_line->toArray();
            unset($sell_line_data['id']);
            unset($sell_line_data['created_at']);
            unset($sell_line_data['updated_at']);
            unset($sell_line_data['product']);
            if (isset($sell_line_data['quantity_returned'])) {
                unset($sell_line_data['quantity_returned']);
            }
            if (isset($sell_line_data['lot_no_line_id'])) {
                unset($sell_line_data['lot_no_line_id']);
            }
            if (isset($sell_line_data['woocommerce_line_items_id'])) {
                unset($sell_line_data['woocommerce_line_items_id']);
            }
            $recurring_sell_lines[] = $sell_line_data;
        }
        $recurring_invoice->sell_lines()->createMany($recurring_sell_lines);
        return $recurring_invoice;
    }
    /**
     * Retrieves and sum total amount paid for a transaction
     * @param int $transaction_id
     *
     */

    public function getTransactionTotals(
        $business_id,
        $transaction_types,
        $start_date = null,
        $end_date = null,
        $location_id = null,
        $created_by = null
    ) {
        $query = Transaction::where('business_id', $business_id);
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween('transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        }
        if (empty($start_date) && !empty($end_date)) {
            $query->where('transaction_date', '<=', $end_date . ' 23:59:59');
        }
        //Filter by the location
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        //Filter by created_by
        if (!empty($created_by)) {
            $query->where('transactions.created_by', $created_by);
        }
        if (in_array('purchase_return', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='purchase_return', final_total, 0)) as total_purchase_return_inc_tax"),
                DB::raw("SUM(IF(transactions.type='purchase_return', total_before_tax, 0)) as total_purchase_return_exc_tax")
            );
        }
        if (in_array('sell_return', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='sell_return', final_total, 0)) as total_sell_return_inc_tax"),
                DB::raw("SUM(IF(transactions.type='sell_return', total_before_tax, 0)) as total_sell_return_exc_tax")
            );
        }
        if (in_array('sell_transfer', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='sell_transfer', shipping_charges, 0)) as total_transfer_shipping_charges")
            );
        }
        if (in_array('expense', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='expense', final_total, 0)) as total_expense")
            );
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='settlement' AND transactions.sub_type='expense', final_total, 0)) as settlement_expense")
            );
        }
        if (in_array('payroll', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='payroll', final_total, 0)) as total_payroll")
            );
        }
        if (in_array('stock_adjustment', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='stock_adjustment', final_total, 0)) as total_adjustment"),
                DB::raw("SUM(IF(transactions.type='stock_adjustment' AND transactions.stock_adjustment_type='decrease', final_total, 0)) as decrease_stock_adjustment"),
                DB::raw("SUM(IF(transactions.type='stock_adjustment' AND transactions.stock_adjustment_type='increase', final_total, 0)) as increase_stock_adjustment"),
                DB::raw("SUM(IF(transactions.type='stock_adjustment', total_amount_recovered, 0)) as total_recovered")
            );
        }
        if (in_array('purchase', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='purchase', IF(discount_type = 'percentage', COALESCE(discount_amount, 0)*total_before_tax/100, COALESCE(discount_amount, 0)), 0)) as total_purchase_discount")
            );
        }
        if (in_array('sell', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='sell' AND transactions.status='final', IF(discount_type = 'percentage', COALESCE(discount_amount, 0)*total_before_tax/100, COALESCE(discount_amount, 0)), 0)) as total_sell_discount"),
                DB::raw("SUM(IF(transactions.type='sell' AND transactions.status='final', rp_redeemed_amount, 0)) as total_reward_amount")
            );
        }
        if (in_array('credit_sales_details', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='sell' AND transactions.is_credit_sale='1', transactions.final_total, 0)) as total_credit_sales"),
                DB::raw("SUM(IF(transactions.type='sell' AND transactions.is_credit_sale='1' AND transactions.payment_status='paid', transactions.final_total, 0)) as total_credit_sales_paid"),
                DB::raw("SUM(IF(transactions.type='sell' AND transactions.is_credit_sale='1' AND transactions.payment_status='due', transactions.final_total, 0)) as total_credit_sales_due")
            );
        }
        $transaction_totals = $query->first();
        $output = [];
        if (in_array('purchase_return', $transaction_types)) {
            $output['total_purchase_return_inc_tax'] = !empty($transaction_totals->total_purchase_return_inc_tax) ?
                $transaction_totals->total_purchase_return_inc_tax : 0;
            $output['total_purchase_return_exc_tax'] =
                !empty($transaction_totals->total_purchase_return_exc_tax) ?
                $transaction_totals->total_purchase_return_exc_tax : 0;
        }
        if (in_array('sell_return', $transaction_types)) {
            $output['total_sell_return_inc_tax'] =
                !empty($transaction_totals->total_sell_return_inc_tax) ?
                $transaction_totals->total_sell_return_inc_tax : 0;
            $output['total_sell_return_exc_tax'] =
                !empty($transaction_totals->total_sell_return_exc_tax) ?
                $transaction_totals->total_sell_return_exc_tax : 0;
        }
        if (in_array('sell_transfer', $transaction_types)) {
            $output['total_transfer_shipping_charges'] =
                !empty($transaction_totals->total_transfer_shipping_charges) ?
                $transaction_totals->total_transfer_shipping_charges : 0;
        }
        if (in_array('expense', $transaction_types)) {
            $output['total_expense'] =
                !empty($transaction_totals->total_expense) ?
                $transaction_totals->total_expense : 0;
            $output['settlement_expense'] =
                !empty($transaction_totals->settlement_expense) ?
                $transaction_totals->settlement_expense : 0;
        }
        if (in_array('payroll', $transaction_types)) {
            $output['total_payroll'] =
                !empty($transaction_totals->total_payroll) ?
                $transaction_totals->total_payroll : 0;
        }
        if (in_array('stock_adjustment', $transaction_types)) {
            $output['total_adjustment'] =
                !empty($transaction_totals->total_adjustment) ?
                $transaction_totals->total_adjustment : 0;
            $output['decrease_stock_adjustment'] =
                !empty($transaction_totals->decrease_stock_adjustment) ?
                $transaction_totals->decrease_stock_adjustment : 0;
            $output['increase_stock_adjustment'] =
                !empty($transaction_totals->increase_stock_adjustment) ?
                $transaction_totals->increase_stock_adjustment : 0;
            $output['total_recovered'] =
                !empty($transaction_totals->total_recovered) ?
                $transaction_totals->total_recovered : 0;
        }
        if (in_array('purchase', $transaction_types)) {
            $output['total_purchase_discount'] =
                !empty($transaction_totals->total_purchase_discount) ?
                $transaction_totals->total_purchase_discount : 0;
        }
        if (in_array('sell', $transaction_types)) {
            $output['total_sell_discount'] =
                !empty($transaction_totals->total_sell_discount) ?
                $transaction_totals->total_sell_discount : 0;
            $output['total_reward_amount'] =
                !empty($transaction_totals->total_reward_amount) ?
                $transaction_totals->total_reward_amount : 0;
        }
        if (in_array('credit_sales_details', $transaction_types)) {
            $output['total_credit_sales'] =
                !empty($transaction_totals->total_credit_sales) ?
                $transaction_totals->total_credit_sales : 0;
            $output['total_credit_sales_paid'] =
                !empty($transaction_totals->total_credit_sales_paid) ?
                $transaction_totals->total_credit_sales_paid : 0;
            $output['total_credit_sales_due'] =
                !empty($transaction_totals->total_credit_sales_due) ?
                $transaction_totals->total_credit_sales_due : 0;
        }
        return $output;
    }

    public function updateCustomerRewardPoints(
        $customer_id,
        $earned,
        $earned_before = 0,
        $redeemed = 0,
        $redeemed_before = 0
    ) {
        $customer = Contact::find($customer_id);
        //Return if walk in customer
        if ($customer->is_default == 1) {
            return false;
        }
        $total_earned = $earned - $earned_before;
        $total_redeemed = $redeemed - $redeemed_before;
        $diff = $total_earned - $total_redeemed;
        $customer_points = empty($customer->total_rp) ? 0 : $customer->total_rp;
        $total_points = $customer_points + $diff;
        $customer->total_rp = $total_points;
        $customer->total_rp_used += $total_redeemed;
        $customer->save();
    }
    /**
     * Calculates reward points to be redeemed from an order
     *
     * @return array
     */

    public function createOrUpdateSellLinesSettlement($transaction, $product_id, $variation_id, $location_id, $meter_sale)
    {
        $price = $meter_sale->price ?? $meter_sale->unit_price ?? 0;
        // Modified by Engr. Alex -- task 7889
        // Settlement discounts are entered as a TOTAL amount (e.g. Rs.100 off the whole sale),
        // but createSaleIncomeTransaction applies line_discount_amount * quantity (per-unit logic).
        // To get the right income posting we convert total-fixed to per-unit here so the
        // arithmetic cancels correctly: (discount/qty) * qty = discount.
        $raw_discount   = floatval($meter_sale->discount ?? 0);
        // other_sales / pump_operator_other_sales often store the discount value in discount_amount;
        // meter_sales uses discount_amount for NET line total — never use that fallback for MeterSale.
        if ($raw_discount <= 0 && ! empty($meter_sale->discount_amount)) {
            if ($meter_sale instanceof OtherSale || $meter_sale instanceof PumpOperatorOtherSale) {
                $raw_discount = floatval($meter_sale->discount_amount);
            }
        }
        $discount_type  = $meter_sale->discount_type ?? 'fixed';
        $qty            = floatval($meter_sale->qty ?? $meter_sale->sold_qty ?? 1);
        if ($discount_type === 'fixed' && $qty > 0 && $raw_discount > 0) {
            $line_discount_amount = $raw_discount / $qty; // per-unit so * qty restores original
        } else {
            $line_discount_amount = $raw_discount; // percentage: stored as-is, calculation uses %
        }
        $sell_line_data = array(
            'transaction_id' => $transaction->id,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'quantity' => $meter_sale->qty ?? $meter_sale->sold_qty ?? 0,
            'unit_price_before_discount' => $price,
            'unit_price_inc_tax' => $price,
            'unit_price' => $price,
            'line_discount_type' => $discount_type,
            'line_discount_amount' => $line_discount_amount,
            'item_tax' => 0.00
        );
        TransactionSellLine::create($sell_line_data);
    }

    public function createOrUpdateSellLinesVatBill($transaction, $product_id, $variation_id, $location_id, $sale)
    {
        $sell_line_data = array(
            'transaction_id' => $transaction->id,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'quantity' => $sale->qty,
            'unit_price_before_discount' => $sale->unit_price,
            'unit_price_inc_tax' => $sale->unit_price,
            'unit_price' => $sale->unit_price,
            'line_discount_type' => 'fixed',
            'line_discount_amount' => !empty($sale->discount) ? $sale->discount : 0,
            'item_tax' => !empty($sale->tax) ? $sale->tax : 0,
        );
        TransactionSellLine::create($sell_line_data);
    }

    public function getTransactionProductDetail($transaction_id, $transaction_type)
    {
        if ($transaction_type == 'purchase' || $transaction_type == 'purchase_return' || $transaction_type == 'opening_stock') {
            $product = Transaction::leftjoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
                ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
                ->where('transactions.id', $transaction_id)
                ->select('products.id', 'purchase_lines.id as purchase_line_id', 'enable_stock', 'stock_type', DB::raw('SUM(purchase_lines.quantity * purchase_lines.purchase_price_inc_tax) as amount'))->groupBy('stock_type')->get();
        }
        if ($transaction_type == 'sell' || $transaction_type == 'sell_return') {
            $product = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->leftjoin('variations', 'transaction_sell_lines.variation_id', 'variations.id')
                ->where('transactions.id', $transaction_id)
                ->select('products.id', 'enable_stock', 'stock_type', DB::raw('SUM(transaction_sell_lines.quantity*variations.dpp_inc_tax) as amount'))->first();
        }
        return $product;
    }

    public function updatePropertyStatus($property_id)
    {
        $blocks = PropertyBlock::where('property_id', $property_id)->where('is_sold', 0)->first();
        if (empty($blocks)) {
            Property::where('id', $property_id)->update(['status' => 'close']);
        }
        return true;
    }
}
