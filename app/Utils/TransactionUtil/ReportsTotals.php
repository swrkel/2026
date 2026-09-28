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
 * Totals, dues and reporting queries.
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
 * Methods here: getOutstandingInvoices, getUserTotalSales, getListSells, getListPurchases, getPurchaseProducts, getSaleCost, getPurchaseTotals, getPurchaseTotalsAll, getSellTotals, getSellTotalsAll, getPurchaseTotalsForCustomer, getProfitLossDetails, getSellsLast30Days, getCreditSells, getPurchaseLast30DaysForCustomer, getSellsCurrentFy, getExpenseReport, getContactDue, getGeneralCustomerDue, getGrossProfit
 */
trait ReportsTotals
{
public function getOutstandingInvoices($start_date, $end_date)
    {
        $outstanding_types = ($this->outstanding_payment_types);

        $business_id = request()->session()->get('user.business_id');
        [$paid_on_start, $paid_on_end] = $this->normaliseDateRange($start_date, $end_date);

        $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')

            ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')

            ->whereNull('tp.deleted_at')

            ->where('transactions.business_id', $business_id)

            ->where('contacts.type', 'customer')

            ->whereIn('transactions.payment_status', ['paid', 'partial'])

            ->whereIn('transactions.type', $outstanding_types)

            ->whereBetween('tp.paid_on', [$paid_on_start, $paid_on_end])

            ->whereNotNull('transactions.invoice_no')

            ->select('transactions.invoice_no')

            ->pluck('transactions.invoice_no', 'transactions.invoice_no');


        return $sells;
    }

    public function getUserTotalSales($business_id, $user_id, $start_date, $end_date)
    {
        $totals = Transaction::where('business_id', $business_id)
            ->where('commission_agent', $user_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereBetween(DB::raw('transaction_date'), [$start_date, $end_date])
            ->select(
                DB::raw('SUM(final_total) as total_sales'),
                DB::raw('SUM(total_before_tax - shipping_charges - (SELECT SUM(item_tax*quantity) FROM transaction_sell_lines as tsl WHERE tsl.transaction_id=transactions.id) ) as total_sales_without_tax')
            )
            ->first();

        return [
            'total_sales' => $totals->total_sales ?? 0,
            'total_sales_without_tax' => $totals->total_sales_without_tax ?? 0,
        ];
    }

    public function getListSells($business_id, $sale_type = 'sell')
    {
        $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            // ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
            ->leftJoin('transaction_sell_lines as tsl', function ($join) {
                $join->on('transactions.id', '=', 'tsl.transaction_id')
                    ->whereNull('tsl.parent_sell_line_id');
            })
            ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
            ->leftJoin('users as ss', 'transactions.res_waiter_id', '=', 'ss.id')
            ->leftJoin('res_tables as tables', 'transactions.res_table_id', '=', 'tables.id')
            ->join(
                'business_locations AS bl',
                'transactions.location_id',
                '=',
                'bl.id'
            )
            ->leftJoin(
                'transactions AS SR',
                'transactions.id',
                '=',
                'SR.return_parent_id'
            )
            ->leftJoin(
                'types_of_services AS tos',
                'transactions.types_of_service_id',
                '=',
                'tos.id'
            )
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', $sale_type)
            ->select(
                'transactions.id',
                'transactions.transaction_date',
                'transactions.type',
                'transactions.is_direct_sale',
                'transactions.invoice_no',
                'transactions.invoice_no as invoice_no_text',
                'contacts.name',
                'contacts.mobile',
                'contacts.contact_id',
                'contacts.supplier_business_name',
                'transactions.status',
                'transactions.payment_status',
                'transactions.final_total',
                'transactions.tax_amount',
                'transactions.discount_amount',
                'transactions.discount_type',
                'transactions.total_before_tax',
                'transactions.rp_redeemed',
                'transactions.rp_redeemed_amount',
                'transactions.rp_earned',
                'transactions.types_of_service_id',
                'transactions.shipping_status',
                'transactions.pay_term_number',
                'transactions.pay_term_type',
                'transactions.additional_notes',
                'transactions.staff_note',
                'transactions.shipping_details',
                'transactions.document',

                /*'transactions.shipping_custom_field_1',
                    'transactions.shipping_custom_field_2',
                    'transactions.shipping_custom_field_3',
                    'transactions.shipping_custom_field_4',
                    'transactions.shipping_custom_field_5',
                    'transactions.custom_field_1',
                    'transactions.custom_field_2',
                    'transactions.custom_field_3',
                    'transactions.custom_field_4',*/

                DB::raw('DATE_FORMAT(transactions.transaction_date, "%Y/%m/%d") as sale_date'),
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                DB::raw('(SELECT SUM(IF(TP.is_return = 1,-1*TP.amount,TP.amount)) FROM transaction_payments AS TP WHERE
                        TP.transaction_id=transactions.id) as total_paid'),
                'bl.name as business_location',
                DB::raw('COUNT(SR.id) as return_exists'),
                DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                        TP2.transaction_id=SR.id ) as return_paid'),
                DB::raw('COALESCE(SR.final_total, 0) as amount_return'),
                'SR.id as return_transaction_id',
                'tos.name as types_of_service_name',
                'transactions.service_custom_field_1',
                DB::raw('COUNT( DISTINCT tsl.id) as total_items'),
                DB::raw("CONCAT(COALESCE(ss.surname, ''),' ',COALESCE(ss.first_name, ''),' ',COALESCE(ss.last_name,'')) as waiter"),
                'tables.name as table_name',
                DB::raw('SUM(tsl.quantity - tsl.so_quantity_invoiced) as so_qty_remaining'),
                'transactions.is_export'
            );

        if ($sale_type == 'sell') {
            $sells->where('transactions.status', 'final');
        }

        return $sells;
    }

    public function getListPurchases($business_id)
    {
        $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->join(
                'business_locations AS BS',
                'transactions.location_id',
                '=',
                'BS.id'
            )
            ->leftJoin(
                'transaction_payments AS TP',
                'transactions.id',
                '=',
                'TP.transaction_id'
            )
            ->leftJoin(
                'transactions AS PR',
                'transactions.id',
                '=',
                'PR.return_parent_id'
            )
            ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase')
            ->select(
                'transactions.id',
                'transactions.document',
                'transactions.transaction_date',
                'transactions.ref_no',
                'contacts.name',
                'contacts.supplier_business_name',
                'transactions.status',
                'transactions.payment_status',
                'transactions.final_total',
                'BS.name as location_name',
                'transactions.pay_term_number',
                'transactions.pay_term_type',
                'PR.id as return_transaction_id',
                DB::raw('SUM(TP.amount) as amount_paid'),
                DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                        TP2.transaction_id=PR.id ) as return_paid'),
                DB::raw('COUNT(PR.id) as return_exists'),
                DB::raw('COALESCE(PR.final_total, 0) as amount_return'),
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
            )
            ->groupBy('transactions.id');

        return $purchases;
    }

    public function getPurchaseProducts($business_id, $transaction_id)
    {
        $products = Transaction::join('purchase_lines as pl', 'transactions.id', '=', 'pl.transaction_id')
            ->leftjoin('products as p', 'pl.product_id', '=', 'p.id')
            ->leftjoin('variations as v', 'pl.variation_id', '=', 'v.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.id', $transaction_id)
            ->where('transactions.type', 'purchase')
            ->select('p.id as product_id', 'p.name as product_name', 'v.id as variation_id', 'v.name as variation_name', 'pl.quantity as quantity')
            ->get();
        return $products;
    }
    /**
     * Gives the total cost value for sales
     *
     * @param int $business_id
     * @param int $transaction_id
     *
     * @return object
     */

    public function getSaleCost($business_id, $start_date, $end_date)
    {
        $query = TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', 'sale.id')
            ->join('variations as v', 'transaction_sell_lines.variation_id', 'v.id')
            ->where('sale.business_id', $business_id)
            ->where('sale.type', 'sell')
            ->where(function ($q) {
                $q->whereNull('sale.sub_type')->orWhere('sale.sub_type', 'settlement');
            })
            ->whereDate('sale.transaction_date', '>=', $start_date)
            ->whereDate('sale.transaction_date', '<=', $end_date)
            ->select(DB::raw('SUM(transaction_sell_lines.quantity * v.dpp_inc_tax) AS total_sale_cost'))->first();
        return $query;
    }
    /**
     * Gives the total purchase amount for a business within the date range passed
     *
     * @param int $business_id
     * @param int $transaction_id
     *
     * @return array
     */

    public function getPurchaseTotals($business_id, $start_date = null, $end_date = null, $location_id = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->select(
                'final_total',
                DB::raw("(final_total - tax_amount) as total_exc_tax"),
                DB::raw("SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id)) as total_paid"),
                DB::raw('SUM(total_before_tax) as total_before_tax'),
                'shipping_charges'
            )
            ->groupBy('transactions.id');
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        if (!empty($start_date)) {
            $query->whereDate('transaction_date', '>=', $start_date);
        }

        if (!empty($end_date)) {
            $query->where('transaction_date', '<=', $end_date . ' 23:59:59');
        }

        //Filter by the location
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $purchase_details = $query->get();
        $output['total_purchase_inc_tax'] = $purchase_details->sum('final_total');
        $output['total_purchase_exc_tax'] = $purchase_details->sum('total_before_tax');
        $output['purchase_due'] = $purchase_details->sum('final_total') - $purchase_details->sum('total_paid');
        $output['total_shipping_charges'] = $purchase_details->sum('shipping_charges');


        return $output;
    }

    public function getPurchaseTotalsAll($business_id, $location_id = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->select(
                'final_total',
                DB::raw("(final_total - tax_amount) as total_exc_tax"),
                DB::raw("SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id)) as total_paid"),
                DB::raw('SUM(total_before_tax) as total_before_tax'),
                'shipping_charges'
            )
            ->groupBy('transactions.id');
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        //Filter by the location
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $purchase_details = $query->get();
        $output['total_purchase_due'] = $purchase_details->sum(function ($purchase) {
            return (float) $purchase->final_total - (float) $purchase->total_paid;
        });
        return $output;
    }
    /**
     * Gives the total sell amount for a business within the date range passed
     *
     * @param int $business_id
     * @param int $transaction_id
     *
     * @return array
     */

    public function getSellTotals($business_id, $start_date = null, $end_date = null, $location_id = null, $created_by = null)
    {
        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->where(function ($q) {
                $q->whereNull('transactions.sub_type')->orWhere('transactions.sub_type', 'settlement');
            })
            ->select(
                'transactions.id',
                'final_total',
                DB::raw("(final_total - tax_amount) as total_exc_tax"),
                DB::raw('(SELECT SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) as total_paid'),
                DB::raw('SUM(total_before_tax) as total_before_tax'),
                'shipping_charges'
            )
            ->groupBy('transactions.id');
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
        if (!empty($created_by)) {
            $query->where('transactions.created_by', $created_by);
        }
        $sell_details = $query->get();
        $output['total_sell_inc_tax'] = $sell_details->sum('final_total');
        //$output['total_sell_exc_tax'] = $sell_details->sum('total_exc_tax');
        $output['total_sell_exc_tax'] = $sell_details->sum('total_before_tax');
        $output['invoice_due'] = $sell_details->sum('final_total') - $sell_details->sum('total_paid');
        $output['total_shipping_charges'] = $sell_details->sum('shipping_charges');
        return $output;
    }

    public function getSellTotalsAll($business_id, $location_id = null, $created_by = null)
    {
        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->where(function ($q) {
                $q->whereNull('transactions.sub_type')->orWhere('transactions.sub_type', 'settlement');
            })
            ->select(
                'transactions.id',
                'final_total',
                DB::raw("(final_total - tax_amount) as total_exc_tax"),
                DB::raw('(SELECT SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) as total_paid'),
                DB::raw('SUM(total_before_tax) as total_before_tax'),
                'shipping_charges'
            )
            ->groupBy('transactions.id');
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        //Filter by the location
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        if (!empty($created_by)) {
            $query->where('transactions.created_by', $created_by);
        }
        $sell_details = $query->get();
        $output['total_sell_due'] = $sell_details->sum('total_paid');
        return $output;
    }
    /**
     * Gives the total sell amount for a business within the date range passed for customer
     *
     * @param int $business_id
     * @param int $transaction_id
     *
     * @return array
     */

    public function getPurchaseTotalsForCustomer($contact_id, $start_date = null, $end_date = null, $location_id = null, $created_by = null)
    {
        $query = Transaction::leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->where('contacts.contact_id', $contact_id)
            ->where('transactions.type', 'sell') //sell by company is purchase by customer
            ->where('transactions.status', 'final')
            ->select(
                'transactions.id',
                'final_total',
                DB::raw("(final_total - tax_amount) as total_exc_tax"),
                DB::raw('(SELECT SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) as total_paid'),
                DB::raw('SUM(total_before_tax) as total_before_tax'),
                'shipping_charges'
            )
            ->groupBy('transactions.id');
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween('transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        }
        if (empty($start_date) && !empty($end_date)) {
            $query->where('transaction_date', '<=', $end_date . ' 23:59:59');
        }
        $purchase_details = $query->get();
        $output['total_purchase_inc_tax'] = $purchase_details->sum('final_total');
        //$output['total_sell_exc_tax'] = $purchase_details->sum('total_exc_tax');
        // $output['total_sell_exc_tax'] = $purchase_details->sum('total_before_tax');
        $output['purchase_dues'] = $purchase_details->sum('final_total') - $purchase_details->sum('total_paid');
        $output['total_paids'] = $purchase_details->sum('total_paid');
        $output['total_shipping_charges'] = $purchase_details->sum('shipping_charges');
        return $output;
    }

    public function getProfitLossDetails($business_id, $location_id, $start_date, $end_date, $user_id = null)
    {
        //For Opening stock date should be 1 day before
        $day_before_start_date = \Carbon::createFromFormat('Y-m-d', $start_date)->subDay()->format('Y-m-d');
        $filters = ['user_id' => $user_id];
        //Get Opening stock
        $opening_stock = $this->getOpeningClosingStock($business_id, $day_before_start_date, $location_id, true, false, $filters);
        $opening_stock_by_sp = $this->getOpeningClosingStock($business_id, $day_before_start_date, $location_id, true, true, $filters);
        //Get Closing stock
        $closing_stock = $this->getOpeningClosingStock(
            $business_id,
            $end_date,
            $location_id,
            false,
            false,
            $filters
        );
        $closing_stock_by_sp = $this->getOpeningClosingStock(
            $business_id,
            $end_date,
            $location_id,
            false,
            true,
            $filters
        );
        //Get Purchase details
        $purchase_details = $this->getPurchaseTotals(
            $business_id,
            $start_date,
            $end_date,
            $location_id,
            $user_id
        );
        //Get Sell details
        $sell_details = $this->getSellTotals(
            $business_id,
            $start_date,
            $end_date,
            $location_id,
            $user_id
        );
        $transaction_types = [
            'purchase_return',
            'sell_return',
            'opening_balance',
            'expense',
            'stock_adjustment',
            'sell_transfer',
            'purchase',
            'sell'
        ];
        $transaction_totals = $this->getTransactionTotals(
            $business_id,
            $transaction_types,
            $start_date,
            $end_date,
            $location_id,
            $user_id
        );
        $gross_profit = $this->getGrossProfit(
            $business_id,
            $start_date,
            $end_date,
            $location_id,
            $user_id
        );
        // dd($transaction_totals);
        $data['total_purchase_shipping_charge'] = !empty($purchase_details['total_shipping_charges']) ? $purchase_details['total_shipping_charges'] : 0;
        $data['total_sell_shipping_charge'] = !empty($sell_details['total_shipping_charges']) ? $sell_details['total_shipping_charges'] : 0;
        //Shipping
        $data['total_transfer_shipping_charges'] = !empty($transaction_totals['total_transfer_shipping_charges']) ? $transaction_totals['total_transfer_shipping_charges'] : 0;
        //Discounts
        $total_purchase_discount = $transaction_totals['total_purchase_discount'];
        $total_sell_discount = $transaction_totals['total_sell_discount'];
        $total_reward_amount = $transaction_totals['total_reward_amount'];
        $total_sell_round_off = $transaction_totals['settlement_expense'];
        //Stocks
        $data['opening_stock'] = !empty($opening_stock) ? $opening_stock : 0;
        $data['closing_stock'] = !empty($closing_stock) ? $closing_stock : 0;
        $data['opening_stock_by_sp'] = !empty($opening_stock_by_sp) ? $opening_stock_by_sp : 0;
        $data['closing_stock_by_sp'] = !empty($closing_stock_by_sp) ? $closing_stock_by_sp : 0;
        //Purchase
        $data['total_purchase'] = !empty($purchase_details['total_purchase_exc_tax']) ? $purchase_details['total_purchase_exc_tax'] : 0;
        $data['total_purchase_discount'] = !empty($total_purchase_discount) ? $total_purchase_discount : 0;
        $data['total_purchase_return'] = $transaction_totals['total_purchase_return_exc_tax'];
        //Sales
        $data['total_sell'] = !empty($sell_details['total_sell_exc_tax']) ? $sell_details['total_sell_exc_tax'] : 0;
        $data['total_sell_discount'] = !empty($total_sell_discount) ? $total_sell_discount : 0;
        $data['total_sell_return'] = $transaction_totals['total_sell_return_exc_tax'];
        $data['total_sell_round_off'] = !empty($total_sell_round_off) ? $total_sell_round_off : 0;
        //Expense
        $data['total_expense'] = $transaction_totals['total_expense'];
        //Stock adjustments
        $data['total_adjustment'] = $transaction_totals['total_adjustment'];
        $data['total_recovered'] = $transaction_totals['total_recovered'];
        // $data['closing_stock'] = $data['closing_stock'] - $data['total_adjustment'];
        $data['total_reward_amount'] = !empty($total_reward_amount) ? $total_reward_amount : 0;
        $moduleUtil = new ModuleUtil();
        $module_parameters = [
            'business_id' => $business_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'location_id' => $location_id,
            'user_id' => $user_id
        ];
        $modules_data = $moduleUtil->getModuleData('profitLossReportData', $module_parameters);
        $data['left_side_module_data'] = [];
        $data['right_side_module_data'] = [];
        $module_total = 0;
        if (!empty($modules_data)) {
            foreach ($modules_data as $module_data) {
                if (!empty($module_data[0])) {
                    foreach ($module_data[0] as $array) {
                        $data['left_side_module_data'][] = $array;
                        if (!empty($array['add_to_net_profit'])) {
                            $module_total -= $array['value'];
                        }
                    }
                }
                if (!empty($module_data[1])) {
                    foreach ($module_data[1] as $array) {
                        $data['right_side_module_data'][] = $array;
                        if (!empty($array['add_to_net_profit'])) {
                            $module_total += $array['value'];
                        }
                    }
                }
            }
        }
        // $data['net_profit'] = $module_total + $data['total_sell']
        //                         + $data['closing_stock']
        //                         - $data['total_purchase']
        //                         - $data['total_sell_discount']
        //                         + $data['total_sell_round_off']
        //                         - $data['total_reward_amount']
        //                         - $data['opening_stock']
        //                         - $data['total_expense']
        //                         + $data['total_recovered']
        //                         - $data['total_transfer_shipping_charges']
        //                         - $data['total_purchase_shipping_charge']
        //                         + $data['total_sell_shipping_charge']
        //                         + $data['total_purchase_discount']
        //                         + $data['total_purchase_return']
        //                         - $data['total_sell_return'];
        $data['net_profit'] = $module_total + $gross_profit
            + ($data['total_sell_round_off'] + $data['total_recovered'] + $data['total_sell_shipping_charge'] + $data['total_purchase_discount']
            ) - ($data['total_reward_amount'] + $data['total_expense'] + $data['total_adjustment'] + $data['total_transfer_shipping_charges'] + $data['total_purchase_shipping_charge']
            );
        //get gross profit from Project Module
        $module_parameters = [
            'business_id' => $business_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'location_id' => $location_id
        ];
        $project_module_data = $moduleUtil->getModuleData('grossProfit', $module_parameters);
        if (!empty($project_module_data['Project']['gross_profit'])) {
            $gross_profit = $gross_profit + $project_module_data['Project']['gross_profit'];
            $data['gross_profit_label'] = __('project::lang.project_invoice');
        }
        $data['gross_profit'] = $gross_profit;
        //get sub type for total sales
        $sales_by_subtype = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final');
        if (!empty($start_date) && !empty($end_date)) {
            if ($start_date == $end_date) {
                $sales_by_subtype->whereDate('transaction_date', $end_date);
            } else {
                $sales_by_subtype->whereBetween(DB::raw('transaction_date'), [$start_date, $end_date]);
            }
        }
        $sales_by_subtype = $sales_by_subtype->select(DB::raw('SUM(total_before_tax) as total_before_tax'), 'sub_type')
            ->whereNotNull('sub_type')
            ->groupBy('transactions.sub_type')
            ->get();
        $data['total_sell_by_subtype'] = $sales_by_subtype;
        return $data;
    }
    /**
     * Gives the total input tax for a business within the date range passed
     *
     * @param int $business_id
     * @param string $start_date default null
     * @param string $end_date default null
     *
     * @return float
     */

    public function getSellsLast30Days($business_id, $group_by_location = false)
    {
        $query = Transaction::leftjoin('transactions as SR', function ($join) {
            $join->on('SR.return_parent_id', '=', 'transactions.id')
                ->where('SR.type', 'sell_return');
        })
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereBetween('transactions.transaction_date', [\Carbon::now()->subDays(30)->startOfDay(), \Carbon::now()->endOfDay()]);
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        $query->select(
            DB::raw("DATE_FORMAT(transactions.transaction_date, '%Y-%m-%d') as date"),
            DB::raw("SUM( transactions.final_total - COALESCE(SR.final_total, 0) ) as total_sells")
        )
            ->groupBy(DB::raw('Date(transactions.transaction_date)'));
        if ($group_by_location) {
            $query->addSelect('transactions.location_id');
            $query->groupBy('transactions.location_id');
        }
        $sells = $query->get();
        if (!$group_by_location) {
            $sells = $sells->pluck('total_sells', 'date');
        }
        return $sells;
    }
    /**
     * Gives total sells of last 30 days day-wise
     *
     * @param int $business_id
     * @param array $filters
     *
     * @return Obj
     */

    public function getCreditSells($business_id, $group_by_location = false, $start_date, $end_date, $location_id = null)
    {
        $query = Transaction::leftjoin('transactions as SR', function ($join) {
            $join->on('SR.return_parent_id', '=', 'transactions.id')
                ->where('SR.type', 'sell_return');
        })
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.is_credit_sale', '1')
            ->where('transactions.status', 'final');
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date);
            $query->whereDate('transactions.transaction_date', '<', $end_date);
        }
        $query->select(
            DB::raw("DATE_FORMAT(transactions.transaction_date, '%Y-%m-%d') as date"),
            DB::raw("SUM( transactions.final_total - COALESCE(SR.final_total, 0) ) as total_sells"),
            DB::raw('SUM(IF( transactions.payment_status = "paid", transactions.final_total, 0))as total_paid'),
            DB::raw('SUM(IF( transactions.payment_status = "due", transactions.final_total, 0))as total_due')
        )
            ->groupBy(DB::raw('Date(transactions.transaction_date)'));
        if ($group_by_location) {
            $query->addSelect('transactions.location_id');
            $query->groupBy('transactions.location_id');
        }
        $sells = $query->get();
        if (!$group_by_location) {
            $sells = $sells->pluck('total_sells', 'date');
        }
        return $sells;
    }
    /**
     * Gives total purchase of last 30 days day-wise for customer
     *
     * @param int $contact_id
     * @param array $filters
     *
     * @return Obj
     */

    public function getPurchaseLast30DaysForCustomer($contact_id, $group_by_location = false)
    {
        $query = Transaction::leftjoin('transactions as SR', function ($join) {
            $join->on('SR.return_parent_id', '=', 'transactions.id')
                ->where('SR.type', 'sell_return');
        })->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->where('contacts.contact_id', $contact_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereBetween('transactions.transaction_date', [\Carbon::now()->subDays(30)->startOfDay(), \Carbon::now()->endOfDay()]);
        //Check for permitted locations of a user
        $query->select(
            DB::raw("DATE_FORMAT(transactions.transaction_date, '%Y-%m-%d') as date"),
            DB::raw("SUM( transactions.final_total - COALESCE(SR.final_total, 0) ) as total_purchase")
        )
            ->groupBy(DB::raw('Date(transactions.transaction_date)'));
        if ($group_by_location) {
            $query->addSelect('transactions.location_id');
            $query->groupBy('transactions.location_id');
        }
        $purchase = $query->get();
        if (!$group_by_location) {
            $purchase = $purchase->pluck('total_purchase', 'date');
        }
        return $purchase;
    }
    /**
     * Gives total sells of current FY month-wise
     *
     * @param int $business_id
     * @param string $start
     * @param string $end
     *
     * @return Obj
     */

    public function getSellsCurrentFy($business_id, $start, $end, $group_by_location = false)
    {
        $query = Transaction::leftjoin('transactions as SR', function ($join) {
            $join->on('SR.return_parent_id', '=', 'transactions.id')
                ->where('SR.type', 'sell_return');
        })
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->whereBetween('transactions.transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59']);
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        $query->groupBy(DB::raw("DATE_FORMAT(transactions.transaction_date, '%Y-%m')"))
            ->select(
                DB::raw("DATE_FORMAT(transactions.transaction_date, '%m-%Y') as yearmonth"),
                DB::raw("SUM( transactions.final_total - COALESCE(SR.final_total, 0)) as total_sells")
            );
        if ($group_by_location) {
            $query->addSelect('transactions.location_id');
            $query->groupBy('transactions.location_id');
        }
        $sells = $query->get();
        if (!$group_by_location) {
            $sells = $sells->pluck('total_sells', 'yearmonth');
        }
        return $sells;
    }
    /**
     * Retrives expense report
     *
     * @param int $business_id
     * @param array $filters
     * @param string $type = by_category (by_category or total)
     *
     * @return Obj
     */

    public function getExpenseReport(
        $business_id,
        $filters = [],
        $type = 'by_category'
    ) {
        $query = Transaction::leftjoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
            ->where('transactions.business_id', $business_id)
            ->where('type', 'expense');
        // ->where('payment_status', 'paid');
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        if (!empty($filters['location_id'])) {
            $query->where('transactions.location_id', $filters['location_id']);
        }
        if (!empty($filters['expense_for'])) {
            $query->where('transactions.expense_for', $filters['expense_for']);
        }
        if (!empty($filters['category'])) {
            $query->where('ec.id', $filters['category']);
        }
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('transaction_date', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59'
            ]);
        }
        //Check tht type of report and return data accordingly
        if ($type == 'by_category') {
            $expenses = $query->select(
                DB::raw("SUM( final_total ) as total_expense"),
                'ec.name as category'
            )
                ->groupBy('expense_category_id')
                ->get();
        } elseif ($type == 'total') {
            $expenses = $query->select(
                DB::raw("SUM( final_total ) as total_expense")
            )
                ->first();
        }
        return $expenses;
    }
    /**
     * Get total paid amount for a transaction
     *
     * @param int $transaction_id
     *
     * @return int
     */

    public function getContactDue($contact_id)
    {
        $contact_payments = Contact::where('contacts.id', $contact_id)
            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->whereIn('t.type', ['sell', 'opening_balance'])
            ->where('is_customer_order', 0)
            ->select(
                DB::raw("SUM(IF(t.status = 'final' AND t.type = 'sell', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF(t.status = 'final' AND t.type = 'sell', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as total_paid"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid")
            )->first();
        $due = $contact_payments->total_invoice - $contact_payments->total_paid + $contact_payments->opening_balance - $contact_payments->opening_balance_paid;
        return $due;
    }
    /**
     * Retrieves sum of due amount of a contact
     * @param int $customer_id
     *
     *
     * @return mixed
     */

    public function getGeneralCustomerDue($contact_id)
    {
        $contact_payments = Contact::where('contacts.id', $contact_id)
            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->whereIn('t.type', ['sell', 'opening_balance'])
            ->where('is_customer_order', 1)
            ->select(
                DB::raw("SUM(IF(t.status = 'final' AND t.type = 'sell', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF(t.status = 'final' AND t.type = 'sell', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as total_paid"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid")
            )->first();
        $due = $contact_payments->total_invoice - $contact_payments->total_paid + $contact_payments->opening_balance - $contact_payments->opening_balance_paid;
        return $due;
    }
    /**
     * Check if lot number is used in any sell
     * @param obj $transaction
     *
     * @return boolean
     */

    public function getGrossProfit($business_id, $start_date = null, $end_date = null, $location_id = null)
    {
        $query = TransactionSellLinesPurchaseLines::join('transaction_sell_lines
                        as SL', 'SL.id', '=', 'transaction_sell_lines_purchase_lines.sell_line_id')
            ->join('transactions as sale', 'SL.transaction_id', '=', 'sale.id')
            ->join('purchase_lines as PL', 'PL.id', '=', 'transaction_sell_lines_purchase_lines.purchase_line_id')
            ->where('sale.business_id', $business_id);
        if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
            $query->whereBetween(DB::raw('sale.transaction_date'), [$start_date, $end_date]);
        }
        if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
            $query->whereDate('sale.transaction_date', $end_date);
        }
        //Filter by the location
        if (!empty($location_id)) {
            $query->where('sale.location_id', $location_id);
        }
        $gross_profit_obj = $query->select(DB::raw('SUM(
                        (transaction_sell_lines_purchase_lines.quantity - transaction_sell_lines_purchase_lines.qty_returned) * (SL.unit_price_inc_tax - PL.purchase_price_inc_tax) ) as gross_profit'))
            ->first();
        $gross_profit = !empty($gross_profit_obj->gross_profit) ? $gross_profit_obj->gross_profit : 0;
        //Deduct the sell transaction discounts.
        $transaction_totals = $this->getTransactionTotals($business_id, ['sell'], $start_date, $end_date, $location_id);
        $sell_discount = !empty($transaction_totals['total_sell_discount']) ? $transaction_totals['total_sell_discount'] : 0;
        //KNOWS ISSUE: If products are returned then also the discount gets applied for it.
        return $gross_profit - $sell_discount;
    }
    /**
     * Calculates reward points to be earned from an order
     *
     * @return integer
     */
}
