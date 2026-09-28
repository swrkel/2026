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
 * VAT and tax calculation.
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
 * Methods here: calculateAndUpdateVAT, __calculateSaleVAT, __calculatePurchaseVAT, __calculateExpenseVAT, getInputTax, getOutputTax, getExpenseTax, groupTaxDetails, sumGroupTaxDetails
 */
trait CalculatesTax
{
public function calculateAndUpdateVAT($transaction)
    {
        switch ($transaction->type) {
            case 'expense':
                $this->__calculateExpenseVAT($transaction);
                break;
            case 'purchase':
                $this->__calculatePurchaseVAT($transaction);
                break;
            case 'sell':
                $this->__calculateSaleVAT($transaction);
                break;
            default:
        }
    }

    function __formatTime($start_time, $end_time)
    {
        $time_taken = $end_time - $start_time;

        if ($time_taken < 60) {
            return $time_taken . ' seconds';
        } elseif ($time_taken < 3600) {
            return ceil($time_taken / 60) . ' minutes';
        } else {
            return ceil($time_taken / 3600) . ' hours';
        }
    }

    private function __calculateSaleVAT($transaction)
    {
        if (!empty($transaction)) {

            $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->leftjoin('product_variations', 'products.id', 'product_variations.product_id')
                ->leftjoin('variations', 'product_variations.id', 'variations.product_variation_id')
                ->where('transaction_id', $transaction->id)
                ->select('transaction_sell_lines.*', 'products.category_id', 'products.sub_category_id', 'products.vat_claimed', 'variations.dpp_inc_tax', 'variations.default_sell_price')
                ->get();

            $tax_amt = 0;
            $vat_claimed = 0;
            $tax_id = 0;


            // delete any previous tax entries
            $tax_account_id = $this->account_exist_return_id('Taxes Payable');
            AccountTransaction::where('account_id', $tax_account_id)->where('transaction_id', $transaction->id)->forceDelete();

            foreach ($sell_lines as $sell) {
                if (!empty($sell->item_tax) && $sell->item_tax > 0) {
                    $tax_amt += ($sell->quantity - $sell->quantity_returned) * $sell->item_tax;
                    
                    if (!empty($sell->vat_claimed)) {
                        $vat_claimed += ($sell->quantity - $sell->quantity_returned) * $sell->item_tax;
                    }
                    
                    if (!empty($sell->tax_id)) {
                        $tax_id = $sell->tax_id;
                    }
                }
            }


            if ($this->moduleUtil->hasThePermissionInSubscription(request()->session()->get('user.business_id'), 'vat_module')) {
                // if product is set as vat claimed; and the VAT is existend; store in Account Transaction
                if (!empty($tax_account_id) && $vat_claimed > 0) {
                    $account_transaction_data = [
                        'amount' => $vat_claimed,
                        'account_id' => $tax_account_id,
                        'type' => "debit",
                        'sub_type' => null,
                        'operation_date' => $transaction->transaction_date,
                        'created_by' => $transaction->created_by,
                        'transaction_id' => $transaction->id,
                        'sell_line_id' => $sell->id,
                        'note' => null
                    ];
                    // AccountTransaction::createAccountTransaction($account_transaction_data);
                }

                // Tax calculation formula: [Total Amount with Taxes – Discounts] – [Total Amount with Taxes after discount / (1+ (Tax Rate / 100))]
                $rounded_tax_amount = $this->num_uf($this->num_f($tax_amt));
                
                // Tax Base calculation formula: Total Invoice Amount with VAT / (1+ (Tax rate / 100))
                // Get the tax rate to calculate tax base
                $tax_rate_obj = TaxRate::find($tax_id);
                if (!empty($tax_rate_obj)) {
                    $tax_base = $transaction->final_total / (1 + ($tax_rate_obj->amount / 100));
                    $rounded_tax_base = $this->num_uf($this->num_f($tax_base));
                    $transaction->total_before_tax = $rounded_tax_base;
                } else {
                    $transaction->total_before_tax = $transaction->final_total - $rounded_tax_amount;
                }
                
                $transaction->tax_id = $tax_id;
                $transaction->tax_amount = $rounded_tax_amount;
                $transaction->save();
            } else {
                $transaction->tax_id = null;
                $transaction->total_before_tax = $transaction->final_total;
                $transaction->tax_amount = 0;
                $transaction->save();
            }
        }
    }

    private function __calculatePurchaseVAT($transaction)
    {
        if (!empty($transaction)) {

            $sell_lines = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')
                ->leftjoin('product_variations', 'products.id', 'product_variations.product_id')
                ->leftjoin('variations', 'product_variations.id', 'variations.product_variation_id')
                ->where('transaction_id', $transaction->id)
                ->select('purchase_lines.*', 'products.category_id', 'products.sub_category_id', 'products.vat_claimed')
                ->get();

            $tax_amt = 0;
            $vat_claimed = 0;
            $tax_id = 0;

            // delete any previous tax entries
            $tax_account_id = $this->account_exist_return_id('Taxes Receivable');
            AccountTransaction::where('account_id', $tax_account_id)->where('transaction_id', $transaction->id)->forceDelete();

            foreach ($sell_lines as $sell) {
                $cat = Category::findOrFail($sell->category_id);

                $tax_rate = null;
                if (!empty($sell->tax_id)) {
                    $tax_rate = TaxRate::find($sell->tax_id);
                    // if the tax id does not retun any results; then get the first tax in the list
                    if (empty($tax_rate)) {
                        $tax_rate = TaxRate::where('business_id', $transaction->business_id)->first();
                    }
                }

                if (!empty($tax_rate)) {
                    $sub_cat_vat = null;
                    if (!empty($sell->sub_category_id)) {
                        $sub_cat = Category::findOrFail($sell->sub_category_id);
                        $sub_cat_vat = $sub_cat->vat_based_on;
                    }

                    if (!empty($sub_cat) && $sub_cat->vat_exempted == 'Yes') {
                        $multiplier = 0;
                    } else {
                        if (!empty($sub_cat_vat)) {
                            if ($sub_cat_vat == 'sale_price') {
                                $multiplier = $sell->purchase_price_inc_tax - $sell->purchase_price;
                            } else {
                                $multiplier = 0;
                            }
                        } else {
                            if ($cat->vat_based_on == 'sale_price') {
                                $multiplier = $sell->purchase_price_inc_tax - $sell->purchase_price;
                            } else {
                                $multiplier = 0;
                            }
                        }
                    }
                    $tax_amt += $sell->quantity * $multiplier;
                    if (!empty($sell->vat_claimed)) {
                        $vat_claimed += $sell->quantity * $multiplier;
                    }

                    $tax_id = $tax_rate->id;
                }
            }

            if ($this->moduleUtil->hasThePermissionInSubscription(request()->session()->get('user.business_id'), 'vat_module')) {
                // if product is set as vat claimed; and the VAT is existend; store in Account Transaction
                if (!empty($tax_account_id) && $vat_claimed > 0) {
                    $account_transaction_data = [
                        'amount' => $vat_claimed,
                        'account_id' => $tax_account_id,
                        'type' => "debit",
                        'sub_type' => null,
                        'operation_date' => $transaction->transaction_date,
                        'created_by' => $transaction->created_by,
                        'transaction_id' => $transaction->id,
                        'purchase_line_id' => $sell->id,
                        'note' => null
                    ];
                    // AccountTransaction::createAccountTransaction($account_transaction_data);
                }

                // Tax calculation formula: [Total Amount with Taxes – Discounts] – [Total Amount with Taxes after discount / (1+ (Tax Rate / 100))]
                $rounded_tax_amount = $this->num_uf($this->num_f($tax_amt));
                
                // Tax Base calculation formula: Total Invoice Amount with VAT / (1+ (Tax rate / 100))
                // Get the tax rate to calculate tax base
                $tax_rate_obj = TaxRate::find($tax_id);
                if (!empty($tax_rate_obj)) {
                    $tax_base = $transaction->final_total / (1 + ($tax_rate_obj->amount / 100));
                    $rounded_tax_base = $this->num_uf($this->num_f($tax_base));
                    $transaction->total_before_tax = $rounded_tax_base;
                } else {
                    $transaction->total_before_tax = $transaction->final_total - $rounded_tax_amount;
                }
                
                $transaction->tax_id = $tax_id;
                $transaction->tax_amount = $rounded_tax_amount;
                $transaction->save();
            } else {
                $transaction->tax_id = null;
                $transaction->total_before_tax = $transaction->final_total;
                $transaction->tax_amount = 0;
                $transaction->save();
            }
        }
    }

    private function __calculateExpenseVAT($transaction)
    {
        // conditions for expense VAT:
        // ii) VAT rate must be defined

        if (!empty($transaction)) {
            // delete any previous tax entries
            $tax_account_id = $this->account_exist_return_id('Taxes Receivable');
            AccountTransaction::where('account_id', $tax_account_id)->where('transaction_id', $transaction->id)->forceDelete();

            $tax_rate = null;
            if (!empty($transaction->tax_id)) {
                $tax_rate = TaxRate::find($transaction->tax_id);

                // if the tax id does not retun any results; then get the first tax in the list
                if (empty($tax_rate)) {
                    $tax_rate = TaxRate::where('business_id', $transaction->business_id)->first();
                }
            } else {
                if (!empty($transaction->is_vat)) {
                    $tax_rate = TaxRate::where('business_id', $transaction->business_id)->first();
                }
            }

            if (!empty($tax_rate)) {
                // Tax calculation formula: [Total Amount with Taxes – Discounts] – [Total Amount with Taxes after discount / (1+ (Tax Rate / 100))]
                $tax_amount = $transaction->final_total - ($transaction->final_total / (1 + ($tax_rate->amount / 100)));
                
                // Tax Base calculation formula: Total Invoice Amount with VAT / (1+ (Tax rate / 100))
                $tax_base = $transaction->final_total / (1 + ($tax_rate->amount / 100));

                $rounded_tax_amount = $this->num_uf($this->num_f($tax_amount));
                $rounded_tax_base = $this->num_uf($this->num_f($tax_base));
                
                $transaction->tax_id = $tax_rate->id;
                $transaction->total_before_tax = $rounded_tax_base;
                $transaction->tax_amount = $rounded_tax_amount;
                $transaction->save();

                $tax_account_id = $this->account_exist_return_id('Taxes Receivable');
                $expense_cat = ExpenseCategory::find($transaction->expense_category_id);


                if ($this->moduleUtil->hasThePermissionInSubscription(request()->session()->get('user.business_id'), 'vat_module') && $transaction->tax_amount > 0) {

                    // first delete previous tax account transactions
                    AccountTransaction::where('account_id', $tax_account_id)->where('transaction_id', $transaction->id)->forceDelete();

                    if (!empty($tax_account_id) && !empty($expense_cat) && !empty($expense_cat->vat_claimed)) {

                        $account_transaction_data = [
                            'amount' => abs($transaction->tax_amount),
                            'account_id' => $tax_account_id,
                            'type' => "debit",
                            'sub_type' => null,
                            'operation_date' => $transaction->transaction_date,
                            'created_by' => $transaction->created_by,
                            'transaction_id' => $transaction->id,
                            'note' => null
                        ];
                        // AccountTransaction::createAccountTransaction($account_transaction_data);
                    }
                } else {
                    // vat module is disabled remove any VAT components saved
                    $transaction->tax_id = null;
                    $transaction->total_before_tax = $transaction->final_total;
                    $transaction->tax_amount = 0;
                    $transaction->save();

                    // delete any tax account transactions
                    AccountTransaction::where('account_id', $tax_account_id)->where('transaction_id', $transaction->id)->forceDelete();
                }
            }
        }
    }

    public function getInputTax($business_id, $start_date = null, $end_date = null, $location_id = null)
    {
        //Calculate purchase taxes
        $query1 = Transaction::where('transactions.business_id', $business_id)

            ->where('transactions.tax_amount', '>', 0)

            // ->where('transactions.is_vat' ,1)

            ->whereIn('transactions.type', ['purchase'])

            ->whereIn('transactions.status', array('received', 'final'))

            ->select(
                'transactions.*'
            );

        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query1->whereIn('transactions.location_id', $permitted_locations);
        }
        if (!empty($start_date) && !empty($end_date)) {
            $query1->whereBetween('transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        }
        if (!empty($location_id)) {
            $query1->where('transactions.location_id', $location_id);
        }
        $output['total_tax'] = $query1->sum('tax_amount');
        return $output;
    }
    /**
     * Gives the total output tax for a business within the date range passed
     *
     * @param int $business_id
     * @param string $start_date default null
     * @param string $end_date default null
     *
     * @return float
     */

    public function getOutputTax($business_id, $start_date = null, $end_date = null, $location_id = null)
    {
        //Calculate sell taxes
        $query1 = Transaction::where('transactions.business_id', $business_id)

            ->where('transactions.tax_amount', '>', 0)

            ->whereIn('transactions.type', ['sell'])

            ->whereIn('transactions.status', array('received', 'final'))

            ->select(
                'transactions.*'
            );

        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query1->whereIn('transactions.location_id', $permitted_locations);
        }
        if (!empty($start_date) && !empty($end_date)) {
            $query1->whereBetween('transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        }
        if (!empty($location_id)) {
            $query1->where('transactions.location_id', $location_id);
        }
        $output['total_tax'] = $query1->sum('tax_amount');
        return $output;
    }
    /**
     * Gives the total expense tax for a business within the date range passed
     *
     * @param int $business_id
     * @param string $start_date default null
     * @param string $end_date default null
     *
     * @return float
     */

    public function getExpenseTax($business_id, $start_date = null, $end_date = null, $location_id = null)
    {
        $query1 = Transaction::where('transactions.business_id', $business_id)

            ->where('transactions.tax_amount', '>', 0)

            // ->where('transactions.is_vat' ,1)

            ->whereIn('transactions.status', array('received', 'final'))

            ->whereIn('transactions.type', ['expense'])

            ->select(
                'transactions.*'
            );

        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query1->whereIn('transactions.location_id', $permitted_locations);
        }
        if (!empty($start_date) && !empty($end_date)) {
            $query1->whereBetween('transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        }
        if (!empty($location_id)) {
            $query1->where('transactions.location_id', $location_id);
        }
        $output['total_tax'] = $query1->sum('tax_amount');
        return $output;
    }
    /**
     * Gives total sells of last 30 days day-wise
     *
     * @param int $business_id
     * @param array $filters
     *
     * @return Obj
     */

    public function groupTaxDetails($tax, $amount)
    {
        if (!is_object($tax)) {
            $tax = TaxRate::find($tax);
        }
        if (!empty($tax)) {
            $sub_taxes = $tax->sub_taxes;
            $sum = $tax->sub_taxes->sum('amount');
            $details = [];
            foreach ($sub_taxes as $sub_tax) {
                $details[] = [
                    'id' => $sub_tax->id,
                    'name' => $sub_tax->name,
                    'amount' => $sub_tax->amount,
                    'calculated_tax' => ($amount / $sum) * $sub_tax->amount,
                ];
            }
            return $details;
        } else {
            return [];
        }
    }

    public function sumGroupTaxDetails($group_tax_details)
    {
        $output = [];
        foreach ($group_tax_details as $group_tax_detail) {
            if (!isset($output[$group_tax_detail['name']])) {
                $output[$group_tax_detail['name']] = 0;
            }
            $output[$group_tax_detail['name']] += $group_tax_detail['calculated_tax'];
        }
        return $output;
    }
    /**
     * Retrieves all available lot numbers of a product from variation id
     *
     * @param  int $variation_id
     * @param  int $business_id
     * @param  int $location_id
     *
     * @return boolean
     */
}
