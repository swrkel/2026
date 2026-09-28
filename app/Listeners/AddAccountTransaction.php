<?php

namespace App\Listeners;

use App\Account;
use App\AccountTransaction;
use App\AccountType;
use App\BusinessLocation;
use App\Category;
use App\ContactLedger;
use App\Events\TransactionPaymentAdded;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Fleet\Entities\Fleet;
use Modules\Property\Entities\PropertyAccountSetting;
use Modules\Property\Entities\PropertySellLine;

class AddAccountTransaction
{
    protected $moduleUtil;
    protected $transactionUtil;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil, TransactionUtil $transactionUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function getAccountTypeIdOfAccount($account_id, $business_id)
    {
        if (empty($account_id)) {
            return null;
        }

        $query = Account::join('account_types', 'accounts.account_type_id', 'account_types.id')
            ->where('accounts.id', $account_id)
            ->select('account_types.id as account_type_id');

        if (!empty($business_id)) {
            $query->where(function ($q) use ($business_id) {
                $q->where('accounts.business_id', $business_id)
                  ->orWhereNull('accounts.business_id');
            });
        }

        $account_type = $query->first();

        // Tenant databases copied between servers can have account records whose
        // business_id does not match the current session. Do not crash purchase
        // saving in that case; resolve by account id only as a safe fallback.
        if (empty($account_type)) {
            $account_type = Account::join('account_types', 'accounts.account_type_id', 'account_types.id')
                ->where('accounts.id', $account_id)
                ->select('account_types.id as account_type_id')
                ->first();
        }

        if (empty($account_type)) {
            Log::warning('Account type not found for account transaction.', [
                'account_id' => $account_id,
                'business_id' => $business_id,
            ]);
            return null;
        }

        return $account_type->account_type_id;
    }

    public function account_exist_return_id($account_name)
    {
        $business_id = request()->session()->get('business.id');
        $account = Account::where('name', 'like', '%' . $account_name . '%')->where('business_id', $business_id)->first();
        if (!empty($account)) {
            return $account->id;
        } else {
            return 0;
        }
    }


    public function getDefaultAccountId($account_name, $location_id)
    {
        $business_id = request()->session()->get('business.id');

        $account_id = null;
        $defualt_accounts = BusinessLocation::where('business_id', $business_id)->where('id', $location_id)->first();
        if (!empty($defualt_accounts)) {
            $default_payment_accounts = (array) json_decode($defualt_accounts->default_payment_accounts);
            $account_id = $default_payment_accounts[$account_name]->account;
        }

        return $account_id;
    }
    public function getTransactionProductDetail($transaction_id, $transaction_type)
    {
        if ($transaction_type == 'purchase' || $transaction_type == 'purchase_return' || $transaction_type == 'opening_stock') {
            $product = Transaction::leftjoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
                ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
                ->where('transactions.id', $transaction_id)
                ->select('products.id', 'enable_stock', 'stock_type', 'transactions.final_total as amount')->first();
        }

        if ($transaction_type == 'sell' || $transaction_type == 'sell_return') {
            $product = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->leftjoin('product_variations', 'products.id', 'product_variations.product_id')
                ->leftjoin('variations', 'product_variations.id', 'variations.product_variation_id')
                ->where('transactions.id', $transaction_id)
                ->select('products.id', 'enable_stock', 'stock_type', DB::raw('SUM(transaction_sell_lines.quantity*variations.default_purchase_price) as amount'))->first();
        }
        return $product;
    }

    public function createCostofGoodsSoldTransaction($transaction, $sub_type = null, $type)
    {
        // Check if COGS entries already exist for this transaction to prevent duplicates
        $cogs_account_ids = Account::where('business_id', request()->session()->get('business.id'))
            ->where('name', 'like', '%Cost of Goods Sold%')
            ->pluck('id')
            ->toArray();
        
        $existing_cogs_transactions = AccountTransaction::where('transaction_id', $transaction->id)
            ->where('type', $type)
            ->whereNotNull('sell_line_id')
            ->whereIn('account_id', $cogs_account_ids)
            ->count();
        
        // Only create entries if they don't already exist
        if ($existing_cogs_transactions == 0) {
            $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->leftjoin('product_variations', 'products.id', 'product_variations.product_id')
                ->leftjoin('variations', 'product_variations.id', 'variations.product_variation_id')
                ->where('transaction_id', $transaction->id)
                ->select('transaction_sell_lines.*', 'products.category_id', 'products.sub_category_id', 'variations.default_purchase_price', 'variations.dpp_inc_tax')
                ->get();
            foreach ($sell_lines as $sale) {
                $account_id = $this->account_exist_return_id('Cost of Goods Sold');
                if ($sale->quantity >= 0) { //not include pos page return 
                    if (!empty($sale->sub_category_id)) {
                        $account_id = $this->getCategoryAccountId($sale->sub_category_id, 'cogs');
                        if (empty($account_id)) {
                            $account_id = $this->getCategoryAccountId($sale->category_id, 'cogs');
                        }
                        if (empty($account_id)) {
                            $account_id = $this->account_exist_return_id('Cost of Goods Sold');
                        }
                    } else {
                        $account_id = $this->getCategoryAccountId($sale->category_id, 'cogs');
                        if (empty($account_id)) {
                            $account_id = $this->account_exist_return_id('Cost of Goods Sold');
                        }
                    }
                    if (!empty($account_id)) {
                        $account_transaction_data = [
                            'amount' => abs($sale->quantity * $sale->dpp_inc_tax), // include tax
                            'account_id' => $account_id,
                            'type' => $type,
                            'sub_type' => $sub_type,
                            'operation_date' => $transaction->transaction_date,
                            'created_by' => $transaction->created_by,
                            'transaction_id' => $transaction->id,
                            'sell_line_id' => $sale->id,
                            'note' => null
                        ];

                        AccountTransaction::createAccountTransaction($account_transaction_data);
                    }
                }
            }
        }
    }
    public function createSaleIncomeTransaction($transaction, $sub_type = null, $type)
    {
        // Check if Sales Income entries already exist for this transaction to prevent duplicates
        $sales_income_account_ids = Account::where('business_id', $transaction->business_id)
            ->where('name', 'like', '%Sales Income%')
            ->pluck('id')
            ->toArray();
        
        $existing_sales_income_transactions = AccountTransaction::where('transaction_id', $transaction->id)
            ->where('type', $type)
            ->whereNotNull('sell_line_id')
            ->whereIn('account_id', $sales_income_account_ids)
            ->count();
        
        // Only create entries if they don't already exist
        if ($existing_sales_income_transactions == 0) {
            $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                ->where('transaction_id', $transaction->id)
                ->select('transaction_sell_lines.*', 'products.category_id', 'products.sub_category_id')
                ->get();

            $total_line_amounts = 0;
            foreach ($sell_lines as $sale) {
                if ($sale->quantity >= 0) {
                    $unit_price_inc_tax = round(floatval($sale->unit_price_inc_tax), 2);
                    $quantity = floatval($sale->quantity);
                    $line_total = $unit_price_inc_tax * $quantity;
                    $total_line_amounts += $line_total;
                }
            }
            
            $final_total = abs($transaction->final_total ?? 0);

            foreach ($sell_lines as $sale) {
                $account_id = $this->account_exist_return_id('Sales Income');
                if ($sale->quantity >= 0) {
                    if (!empty($sale->sub_category_id)) {
                        $account_id = $this->getCategoryAccountId($sale->sub_category_id, 'sale_income');
                        if (empty($account_id)) {
                            $account_id = $this->getCategoryAccountId($sale->category_id, 'sale_income');
                        }
                        if (empty($account_id)) {
                            $account_id = $this->account_exist_return_id('Sales Income');
                        }
                    } else {
                        $account_id = $this->getCategoryAccountId($sale->category_id, 'sale_income');
                        if (empty($account_id)) {
                            $category = Category::find($sale->category_id);
                            $account_id = $this->account_exist_return_id("Sales Income - {$category->category_name}");
                            if (empty($account_id)) {
                                $account_id = $this->account_exist_return_id('Sales Income');
                            }
                        }
                    }
                    if (!empty($account_id)) {
                        $unit_price_inc_tax = round(floatval($sale->unit_price_inc_tax), 2);
                        $quantity = floatval($sale->quantity);
                        $line_total = $unit_price_inc_tax * $quantity;
                        
                        $amount_after_line_discount = $line_total;
                        
                        if ($total_line_amounts > 0) {
                            // The true Sales Income for a line should be its original total 
                            // minus its proportional share of the BILL-WISE discount.
                            // Order tax, shipping, and rounding shouldn't affect Sales Income.
                            $bill_wise_discount = floatval($transaction->discount_amount);
                            
                            if ($transaction->discount_type == 'percentage') {
                                $bill_wise_discount = ($bill_wise_discount / 100) * $total_line_amounts;
                            }
                            
                            $share_of_bill_discount = ($amount_after_line_discount / $total_line_amounts) * $bill_wise_discount;
                            $amount = $amount_after_line_discount - $share_of_bill_discount;
                        } else {
                            $amount = $amount_after_line_discount;
                        }

                        $account_transaction_data = [
                            'amount' => abs($amount),
                            'account_id' => $account_id,
                            'type' => $type,
                            'sub_type' => $sub_type,
                            'operation_date' => $transaction->transaction_date,
                            'created_by' => $transaction->created_by,
                            'transaction_id' => $transaction->id,
                            'sell_line_id' => $sale->id,
                            'note' => null
                        ];
                        AccountTransaction::createAccountTransaction($account_transaction_data);
                    }
                }
            }
        }
    }

    public function manageStockAccount($transaction, $account_transaction_data, $trans_type, $amount, $sub_type = null)
    {
        $product_details = $this->getTransactionProductDetail($transaction->id, $transaction->type);

        if ($transaction->type == 'sell') {
            // Check if Finished Goods Account entries already exist for this transaction to prevent duplicates
            $existing_stock_transactions = AccountTransaction::where('transaction_id', $transaction->id)
                ->where('type', $trans_type)
                ->whereNotNull('sell_line_id')
                ->count();
            
            // Only create entries if they don't already exist
            if ($existing_stock_transactions == 0) {
                $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                    ->leftjoin('product_variations', 'products.id', 'product_variations.product_id')
                    ->leftjoin('variations', 'product_variations.id', 'variations.product_variation_id')
                    ->where('transaction_id', $transaction->id)
                    ->select('transaction_sell_lines.*', 'products.category_id', 'products.enable_stock', 'products.stock_type', 'products.sub_category_id', 'variations.default_purchase_price', 'variations.dpp_inc_tax')
                    ->get();

                foreach ($sell_lines as $sale) {
                    if ($sale->quantity >= 0 && $sale->enable_stock) { //not include pos page return and non-stock items
                        $account_transaction_data['type'] = $trans_type;
                        $account_transaction_data['sub_type'] = $sub_type;
                        
                        // For Finished Goods Account, always use purchase price (cost), never sale price
                        $quantity = floatval($sale->quantity);
                        $unit_cost = 0.0;
                        
                        // Prefer last_purchased_price (line-specific cost) then fall back to variation cost fields
                        if (!empty($sale->last_purchased_price) && $sale->last_purchased_price > 0) {
                            $unit_cost = floatval($sale->last_purchased_price);
                        } elseif (!empty($sale->dpp_inc_tax) && $sale->dpp_inc_tax > 0) {
                            $unit_cost = floatval($sale->dpp_inc_tax);
                        } elseif (!empty($sale->default_purchase_price) && $sale->default_purchase_price > 0) {
                            $unit_cost = floatval($sale->default_purchase_price);
                        }
                        
                        if ($unit_cost > 0) {
                            // Use purchase price only - no discounts applied as this is cost basis
                            $line_amount = abs($quantity * $unit_cost);
                            $account_transaction_data['amount'] = $line_amount;
                            $account_transaction_data['operation_date'] = $transaction->transaction_date;
                            
                            if (!empty($sale->stock_type)) {
                                $account_transaction_data['account_id'] = $sale->stock_type;
                                $account_transaction_data['sell_line_id'] = $sale->id;
                                AccountTransaction::createAccountTransaction($account_transaction_data);
                            }
                        }
                    }
                }
            }
        } else {
            // Check if entries already exist for non-sell transactions
            $existing_transactions = AccountTransaction::where('transaction_id', $transaction->id)
                ->where('account_id', $account_transaction_data['account_id'] ?? null)
                ->where('type', $trans_type)
                ->count();
            
            if ($existing_transactions == 0) {
                $account_transaction_data['type'] = $trans_type;
                $account_transaction_data['amount'] = $amount;
                if ($product_details->enable_stock) {
                    $account_transaction_data['type'] = $trans_type;
                    $account_transaction_data['sub_type'] = $sub_type;
                    $account_transaction_data['amount'] = $product_details->amount;
                    if (!empty($product_details->stock_type)) {
                        $account_transaction_data['account_id'] = $product_details->stock_type;
                        AccountTransaction::createAccountTransaction($account_transaction_data);
                    }
                }
            }
        }

        return true;
    }


    public function getCategoryAccountId($category_id, $group)
    {
        $business_id = request()->session()->get('business.id');
        if ($group == 'cogs') {
            return Category::where('business_id', $business_id)->where('id', $category_id)->select('cogs_account_id')->first()->cogs_account_id;
        }
        if ($group == 'sale_income') {
            return Category::where('business_id', $business_id)->where('id', $category_id)->select('sales_income_account_id')->first()->sales_income_account_id;
        }
    }


    /**
     * manage stock (Raw material, Finish good) account transaction
     * $trans_type could be debit or credit 
     * 
     * @return void
     */

    public function updateAccountonPurchase($transaction, $account_transaction_data, $trans_type)
    {
        $product_details = $this->getTransactionProductDetail($transaction->id, $transaction->type);

        if ($product_details->enable_stock) {
            $account_transaction_data['type'] = $trans_type;
            $account_transaction_data['amount'] = $transaction->final_total;
            if ($product_details->stock_type) {
                $raw_material_account_id = $this->getDefaultAccountId('raw_material_account', $transaction->location_id);
                $account_transaction_data['account_id'] = $raw_material_account_id;
            } else {
                $finish_good_account_id = $this->getDefaultAccountId('finished_goods_account', $transaction->location_id);
                $account_transaction_data['account_id'] = $finish_good_account_id;
            }
        }
        AccountTransaction::createAccountTransaction($account_transaction_data);

        return true;
    }

    public function getAmountofTransactionWithoutPosReturn($transaction)
    {
        $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('product_variations', 'products.id', 'product_variations.product_id')
            ->leftjoin('variations', 'product_variations.id', 'variations.product_variation_id')
            ->where('transaction_id', $transaction->id)
            ->select('transaction_sell_lines.*')
            ->get();
        $amount = 0;
        foreach ($sell_lines as $sale) {
            if ($sale->quantity >= 0) { //not include pos page return 
                // $amount += ($sale->quantity * $sale->unit_price_inc_tax)  - $sale->line_discount_amount; // changed to include tax
                $amount += ($sale->quantity * $sale->unit_price_inc_tax); // line_discount_amount is already included in unit_price_inc_tax
            }
        }

        // remove bill discount from total products amounts
        if ($transaction->discount_amount > 0) {
            if ($transaction->discount_type == 'fixed') {
                $amount -= $transaction->discount_amount;
            } else {
                $amount -= ($amount * $transaction->discount_amount / 100);
            }
        }

        return $amount;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TransactionPaymentAdded $event)
    {
        // For PD cheque payments, prefer the payment's account_id (Issued/Post Dated Cheques) so the account book shows correctly
        $usePdCheque = !empty($event->transactionPayment->update_post_dated_cheque) || !empty($event->transactionPayment->post_dated_cheque);
        if ($usePdCheque && !empty($event->transactionPayment->account_id)) {
            $account_id = $event->transactionPayment->account_id;
        } else {
            $account_id = !empty($event->formInput['account_id']) ? $event->formInput['account_id'] : null;
        }

        // If not in form input, try to get from the transaction payment record itself
        if (empty($account_id) && !empty($event->transactionPayment)) {
            $account_id = $event->transactionPayment->account_id ?? null;
        }

        // If still null, try to get from the existing transaction payment in database (for edits)
        if (empty($account_id) && !empty($event->transactionPayment->id)) {
            $existing_payment = TransactionPayment::find($event->transactionPayment->id);
            $account_id = $existing_payment->account_id ?? null;
        }

        // If account_id is still null, try to get default Cash account
        if (empty($account_id)) {
            $business_id = request()->session()->get('business.id');
            $default_cash_account = Account::where('business_id', $business_id)
                ->where('name', 'Cash')
                ->where('is_closed', 0)
                ->first();
            $account_id = !empty($default_cash_account) ? $default_cash_account->id : null;
        }

        // If we still don't have an account_id, log and return
        if (empty($account_id)) {
            Log::error('Cannot create account transaction: account_id is null', [
                'transaction_payment_id' => $event->transactionPayment->id ?? null,
                'transactionPayment_account_id' => $event->transactionPayment->account_id ?? 'not set',
                'formInput' => $event->formInput
            ]);
            return;
        }

        $business_id = request()->session()->get('business.id');
        $asset_type_ids = AccountType::getAccountTypeIdOfType('Assets', $business_id);
        $account_type_id = $this->getAccountTypeIdOfAccount($account_id, $business_id);

        // If the selected/default payment account cannot be mapped to an account
        // type, skip account ledger posting instead of failing the purchase save.
        // The purchase/payment records are still saved, and accounts can be fixed
        // from settings without blocking users.
        if (empty($account_type_id)) {
            Log::warning('Skipped account transaction because account_type_id could not be resolved.', [
                'transaction_payment_id' => $event->transactionPayment->id ?? null,
                'account_id' => $account_id,
                'business_id' => $business_id,
                'formInput' => $event->formInput,
            ]);
            return;
        }

        $transaction_payment_details = TransactionPayment::where('id', $event->transactionPayment->id)->first();
        $transaction = Transaction::where('id', $transaction_payment_details->transaction_id)->first();
        $account_payable = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->where('is_closed', 0)->first();
        $account_payable_id = !empty($account_payable) ? $account_payable->id : 0;
        $account_receivable = Account::where('business_id', $business_id)->where('name', 'Accounts Receivable')->where('is_closed', 0)->first();
        $account_receivable_id = !empty($account_receivable) ? $account_receivable->id : 0;

        //Create new account transaction

        $account_transaction_data = [
            'contact_id' => !empty($transaction) ? $transaction->contact_id : null,
            'amount' => $event->formInput['amount'],
            'account_id' => $account_id,
            'type' => AccountTransaction::getAccountTransactionType($event->formInput['transaction_type']),
            'operation_date' => !empty($transaction->transaction_date) ? $transaction->transaction_date : date('Y-m-d H:i:s'),
            'created_by' => $event->transactionPayment->created_by,

            'related_account_id' => $event->transactionPayment->related_account_id,
            'post_dated_cheque' => $event->transactionPayment->post_dated_cheque,
            'update_post_dated_cheque' => $event->transactionPayment->update_post_dated_cheque,


            'transaction_id' => !empty($transaction) ? $transaction->id : null,
            'transaction_payment_id' => !empty($event->transactionPayment->id) ? $event->transactionPayment->id : null
        ];

        if (
            $event->transactionPayment->method == 'bank_transfer'
            || $event->transactionPayment->method == 'direct_bank_deposits'
            || ($event->transactionPayment->method == 'cheque' && ! $usePdCheque)
        ) {
            $account_transaction_data['operation_date'] = $event->transactionPayment->cheque_date;
        }

        if ($usePdCheque) {
            $account_transaction_data['operation_date'] = !empty($event->transactionPayment->paid_on)
                ? $event->transactionPayment->paid_on
                : (!empty($transaction->transaction_date) ? $transaction->transaction_date : date('Y-m-d H:i:s'));
        }

        if ($event->transactionPayment->pay_supplier_due && $event->formInput['transaction_type'] == 'purchase') {
            $account_transaction_data['amount'] = $event->formInput['amount'];
            $account_transaction_data['type'] = 'credit';
            $account_transaction_data['account_id'] = $account_id;
            AccountTransaction::createAccountTransaction($account_transaction_data);

            $account_transaction_data['type'] = 'debit';
            $account_transaction_data['account_id'] = $account_payable_id;

            AccountTransaction::createAccountTransaction($account_transaction_data);
            return true;
        }


        // if purhcase then change type to credit
        if ($event->formInput['transaction_type'] == 'purchase' || $event->formInput['transaction_type'] == 'property_purchase') {
            $amount_paid = (float) $event->formInput['amount'];
            if ($amount_paid > 0.0) {
                $account_transaction_data['amount'] = $amount_paid;
                $account_transaction_data['type'] = 'credit';
                $account_transaction_data['account_id'] = $account_id;
                
                // Add description for Issued Post Dated Cheques
                if ($usePdCheque) {
                    $supplier_name = '';
                    if (!empty($transaction->contact)) {
                        $supplier_name = $transaction->contact->name;
                    }
                    
                    $bank_name = $event->transactionPayment->bank_name;
                    if (!empty($event->transactionPayment->related_account_id)) {
                        $bank_account = Account::find($event->transactionPayment->related_account_id);
                        if (!empty($bank_account)) {
                            $bank_name = $bank_account->name;
                        }
                    }
                    
                    $account_transaction_data['note'] = $supplier_name . "\n" . 
                                                       'Post dated Cheque Issued from Bank ' . $bank_name;
                    $account_transaction_data['cheque_number'] = $event->transactionPayment->cheque_number;
                }

                AccountTransaction::createAccountTransaction($account_transaction_data);
                ContactLedger::createContactLedger($account_transaction_data);
                $account_transaction_data['type'] = 'debit';
                ContactLedger::createContactLedger($account_transaction_data);
            } else {
                $account_transaction_data['amount'] = $transaction->final_total;
                $account_transaction_data['type'] = 'credit';
                $account_transaction_data['account_id'] = $account_payable_id;

                AccountTransaction::createAccountTransaction($account_transaction_data);
                $account_transaction_data['sub_type'] = 'payment';
                ContactLedger::createContactLedger($account_transaction_data);
            }
        }

        // if expense then change type to credit
        if ($event->formInput['transaction_type'] == 'expense') {
            if (in_array($account_type_id, $asset_type_ids)) {  //if account type is asset
                $account_transaction_data['type'] = 'credit';
                
                // Add description for Issued Post Dated Cheques
                if ($usePdCheque) {
                    $supplier_name = '';
                    if (!empty($transaction->contact)) {
                        $supplier_name = $transaction->contact->name;
                    } elseif (!empty($transaction->expense_for)) {
                        $supplier_name = $transaction->expense_for;
                    }
                    
                    // Get expense category name if no supplier
                    if (empty($supplier_name) && !empty($transaction->expense_category_id)) {
                        $expense_category = \App\ExpenseCategory::find($transaction->expense_category_id);
                        if (!empty($expense_category)) {
                            $supplier_name = $expense_category->name;
                        }
                    }
                    
                    $bank_name = $event->transactionPayment->bank_name;
                    if (!empty($event->transactionPayment->related_account_id)) {
                        $bank_account = Account::find($event->transactionPayment->related_account_id);
                        if (!empty($bank_account)) {
                            $bank_name = $bank_account->name;
                        }
                    }
                    
                    $account_transaction_data['note'] = $supplier_name . "\n" . 
                                                       'Post dated Cheque Issued from Bank ' . $bank_name;
                    $account_transaction_data['cheque_number'] = $event->transactionPayment->cheque_number;
                }
                
                AccountTransaction::createAccountTransaction($account_transaction_data);

                if (!empty($transaction->controller_account)) {
                    $account_payable_id = $transaction->controller_account;
                }
                $account_transaction_data['type'] = 'debit';
                $account_transaction_data['account_id'] = $account_payable_id;

                AccountTransaction::createAccountTransaction($account_transaction_data);
            }
        }

        // if sell_return then change type to credit
        if ($event->formInput['transaction_type'] == 'sell_return') {
            $this->createCostofGoodsSoldTransaction($transaction, 'ledger_show', 'credit');
            $this->createSaleIncomeTransaction($transaction, null, 'debit');
            $this->manageStockAccount($transaction, $account_transaction_data, 'debit', $event->formInput['amount']);
            ContactLedger::createContactLedger($account_transaction_data);
        }

        // if sell then change type to debit
        if ($event->formInput['transaction_type'] == 'sell') {
            if ((in_array($account_type_id, $asset_type_ids) || $event->formInput['method'] == 'card')) {  //if account type is asset
                // Check if this entry already exists to prevent duplication
                $existing_account_transaction = AccountTransaction::where('transaction_id', $transaction->id)
                    ->where('account_id', $account_id)
                    ->where('type', 'debit')
                    ->where('transaction_payment_id', $event->transactionPayment->id)
                    ->first();

                $existing_contact_ledger = ContactLedger::where('transaction_id', $transaction->id)
                    ->where('contact_id', $transaction->contact_id)
                    ->where('type', 'debit')
                    ->where('transaction_payment_id', $event->transactionPayment->id)
                    ->first();

                if (!$existing_account_transaction) {
                    $account_transaction_data['type'] = 'debit';
                    $account_transaction_data['sub_type'] = 'ledger_show';
                    
                    // Add customer name to description
                    $customer_name = '';
                    if (!empty($transaction->contact)) {
                        $customer_name = $transaction->contact->name;
                    }
                    
                    if (!empty($customer_name)) {
                        $account_transaction_data['note'] = 'Customer: ' . $customer_name . 
                                                            "\nPayment Ref: " . $event->transactionPayment->payment_ref_no;
                    }
                    
                    AccountTransaction::createAccountTransaction($account_transaction_data);
                }

                if (!$existing_contact_ledger && $transaction->is_credit_sale != '1') {
                    $account_transaction_data['type'] = 'credit';  // Credit for payment receive
                    $account_transaction_data['sub_type'] = 'payment';
                    ContactLedger::createContactLedger($account_transaction_data);

                    if ($transaction->contact && $transaction->contact->is_default == 1) {
                        $existing_debit_ledger = ContactLedger::where('transaction_id', $transaction->id)
                            ->where('contact_id', $transaction->contact_id)
                            ->where('type', 'debit')
                            ->where('transaction_payment_id', $event->transactionPayment->id)
                            ->first();
                        if (!$existing_debit_ledger) {
                            $debit_ledger_data = $account_transaction_data;
                            $debit_ledger_data['type'] = 'debit';
                            $debit_ledger_data['sub_type'] = 'sell';
                            ContactLedger::createContactLedger($debit_ledger_data);
                        }
                    }
                }
            }

            // Prevent duplicate Finished Goods Account entries when multiple payments are added
            // These functions should only be called once per transaction, not once per payment
            // Check if Finished Goods Account entries already exist for this transaction
            // Entries created by manageStockAccount have sell_line_id set
            // We check for entries with sell_line_id regardless of transaction_payment_id to handle both:
            // - New entries (without transaction_payment_id) created after this fix
            // - Old entries (with transaction_payment_id) created before this fix
            $finished_goods_account_id = $this->transactionUtil->account_exist_return_id('Finished Goods Account');
            $existing_fga_transaction = null;
            if (!empty($finished_goods_account_id)) {
                $existing_fga_transaction = AccountTransaction::where('transaction_id', $transaction->id)
                    ->where('account_id', $finished_goods_account_id)
                    ->where('type', 'credit')
                    ->whereNotNull('sell_line_id') // Entries created by manageStockAccount have sell_line_id
                    ->first(); // Check regardless of transaction_payment_id to catch both old and new entries
            }
            
            // Only call stock-related functions if Finished Goods Account entries don't already exist.
            // This prevents duplicates when multiple payments are added.
            if (empty($existing_fga_transaction)) {
                $this->manageStockAccount($transaction, $account_transaction_data, 'credit', $transaction->final_total);
                $this->createCostofGoodsSoldTransaction($transaction, null, 'debit');
            }

            // createSaleIncomeTransaction has its OWN duplicate-guard inside.
            // It must be called OUTSIDE the FGA guard so that non-stock products
            // (fuel, services, etc.) whose Finished Goods Account entries are never
            // created still get their Sales Income bookings recorded.
            if ($transaction->is_credit_sale == '1') {
                $this->createSaleIncomeTransaction($transaction, null, 'credit');
            } else {
                $this->createSaleIncomeTransaction($transaction, 'ledger_show', 'credit');
            }
        }
        // if property sell then change type to debit
        if ($event->formInput['transaction_type'] == 'property_sell') {
            $transaction_sell_line = PropertySellLine::where('transaction_id', $transaction->id)->first();
            $account_transaction_data['type'] = 'debit';
            $account_transaction_data['amount'] = $event->formInput['amount'];
            $account_transaction_data['account_id'] = $account_id;

            AccountTransaction::createAccountTransaction($account_transaction_data);

            $account_transaction_data['type'] = 'credit';
            ContactLedger::createContactLedger($account_transaction_data);

            $property_accounts = PropertyAccountSetting::where('property_id', $transaction_sell_line->property_id)->first();

            if (!empty($property_accounts->account_receivable_account_id)) {
                $account_transaction_data['type'] = 'credit';
                $account_transaction_data['account_id'] = $property_accounts->account_receivable_account_id;
                AccountTransaction::createAccountTransaction($account_transaction_data);
            }
        }

        // if purhcase_return then change type to debit
        if ($event->formInput['transaction_type'] == 'purchase_return') {
            if (in_array($account_type_id, $asset_type_ids)) {  //if account type is asset
                $account_transaction_data['type'] = 'debit';
                AccountTransaction::createAccountTransaction($account_transaction_data);
            }
            $this->manageStockAccount($transaction, $account_transaction_data, 'credit', $event->formInput['amount']);
        }

        //if route operation
        if ($event->formInput['transaction_type'] == 'route_operation') {
            $fleet = Fleet::find($transaction->fleet_id);
            $account_transaction_data['type'] = 'debit';
            AccountTransaction::createAccountTransaction($account_transaction_data);

            $account_transaction_data['type'] = 'credit';
            $account_transaction_data['account_id'] = $fleet->income_account_id;
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }

        //if shipment
        if ($event->formInput['transaction_type'] == 'shipment') {
            // $fleet = Fleet::find($transaction->fleet_id);
            $account_transaction_data['type'] = 'debit';
            AccountTransaction::createAccountTransaction($account_transaction_data);

            // $account_transaction_data['type'] = 'credit';
            // $account_transaction_data['account_id'] = $fleet->income_account_id;
            // AccountTransaction::createAccountTransaction($account_transaction_data);
        }

        //if airline_ticket
        if ($event->formInput['transaction_type'] == 'airline_ticket') {
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }

        if ($event->formInput['transaction_type'] == 'fpos_sale') {
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }
    }
}
