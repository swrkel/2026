<?php

namespace Modules\Finance\Http\Controllers\Account\Concerns;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountGroup;
use Modules\Finance\Entities\AccountSetting;
use Modules\Finance\Entities\AccountTransaction;
use Modules\Finance\Entities\AccountType;
use App\Business;
use Modules\Finance\Entities\BusinessLocation;
use App\Category;
use Modules\Finance\Entities\Contact;
use App\ContactLedger;
use App\Journal;
use App\NotificationTemplate;
use App\Product;
use App\PurchaseLine;
use Modules\Finance\Entities\System;
use Modules\Finance\Entities\Transaction;
use Modules\Finance\Entities\TransactionPayment;
use App\TransactionSellLine;
use Modules\Finance\Entities\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\StockAdjustmentLine;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;
use Modules\Essentials\Entities\EssentialsEmployee;
use Modules\Fleet\Entities\Driver;
use Modules\Fleet\Entities\Fleet;
use Modules\Fleet\Entities\Helper;
use Modules\Hms\Entities\HmsRoom;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\PetroDailyShift;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\PriceChanges\Entities\PriceChangesDetail;
use Modules\PriceChanges\Entities\PriceChangesHeader;
use Modules\Property\Entities\Property;
use Modules\Property\Entities\PropertySellLine;
use Modules\Shipping\Entities\ShippingAgent;
use Modules\Shipping\Entities\ShippingAgentCommission;
use Modules\Shipping\Entities\ShippingPartner;
use Modules\Superadmin\Entities\AccountNumber;
use Modules\Superadmin\Entities\ModulePermissionLocation;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatPayment;
use Modules\Finance\Services\Reports\FinanceIntegrationLedgerService;
use Modules\Finance\Services\Accounts\AccountBookDataService;
use Modules\Finance\Services\Accounts\AccountListQueryService;
use Modules\Finance\Services\Deposits\BankDepositAccountResolver;
use Modules\Finance\Services\Deposits\CardDepositAccountResolver;
use Modules\Finance\Services\Deposits\ChequeDepositListService;
use Yajra\DataTables\Facades\DataTables;

/**
 * One-off data-correction routines. These rewrite posted accounting rows, so they are grouped together and away from everyday screens.
 *
 * MA-002: split out of Finance's AccountController, which was 9,782 lines in
 * a single file.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. The 84 routes that point at
 *   AccountController still resolve, action() targets still resolve, and the
 *   $this-> calls between these 98 methods still work. Separate controller
 *   classes would mean rewriting all of those.
 *
 * Method bodies are byte-identical to the original.
 *
 * Methods here: corectStockAccounts, fixDecemberSalesAccounts, updateDecemberSalesAccounts, correctSaleIncomeAccountsTax, updateSaleIncomeAccountsTax, correctCOGSAccountsTax, updateCOGSAccountsTax, correctSellLinesTax, updateSellLinesTax, correctSellLinesDecimalDifference, updateSellLinesDecimalDifference, getAccountsReceivableSettlementCustomerPaymentToCredit, updateAccountsReceivableSettlementCustomerPaymentToCredit, getFinishedGoodsAccountPosSaleTax, updateFinishedGoodsAccountPosSaleTax, getCashAccountPosSaleTax, updateCashAccountPosSaleTax, correctAccountsProductWiseDiscount, updateAccountsProductWiseDiscount
 */
trait RunsAccountCorrections
{
    public function corectStockAccounts()
    {
        $this->transactionUtil->corectStockAccounts();
    }

    public function fixDecemberSalesAccounts()
    {
        $accounts = Account::whereNull('deleted_at')->get();
        foreach ($accounts as $account) {
            // $start_date = date('Y') . '-12-01';
            $account->dec_accounts_to_fix = AccountTransaction::whereNull('account_transactions.deleted_at')
                ->where('account_transactions.type', '')
                ->where('account_transactions.account_id', $account->id)
            // ->whereDate('account_transactions.updated_at', '>=', $start_date)
                ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
                ->whereNotNull('transactions.type')
                ->where('transactions.type', '!=', '')
                ->select('account_transactions.*', 'transactions.type as transaction_type')
                ->count();
        }

        return view('finance::account.fix_december_sales_accounts')->with(compact(
            'accounts'
        ));
    }

    public function updateDecemberSalesAccounts($id)
    {
        if ($id == 'All') {
            $accounts = Account::whereNull('deleted_at')->get();
        } else {
            $accounts = Account::whereNull('deleted_at')->where('id', $id)->get();
        }
        foreach ($accounts as $account) {
            // $start_date = date('Y') . '-12-01';
            $account->dec_accounts_to_fix = AccountTransaction::whereNull('account_transactions.deleted_at')
                ->where('account_transactions.type', '')
                ->where('account_transactions.account_id', $account->id)
            // ->whereDate('account_transactions.updated_at', '>=', $start_date)
                ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
                ->whereNotNull('transactions.type')
                ->where('transactions.type', '!=', '')
                ->select('account_transactions.*', 'transactions.type as transaction_type')
                ->get();
            foreach ($account->dec_accounts_to_fix as $dec_account_to_fix) {
                $dec_account_to_fix->type = AccountTransaction::getAccountTransactionType($dec_account_to_fix->transaction_type) ?? '';
                \Log::info("Updating AccountTransaction: {$dec_account_to_fix->id} type to {$dec_account_to_fix->type}");
                $dec_account_to_fix->update();
            }
        }

        return Redirect::route('accounts.fixDecemberSalesAccounts')->with('msg', 'Accounts updated');
    }

    public function correctSaleIncomeAccountsTax()
    {
        $products     = Product::get();
        $category_ids = [];
        foreach ($products as $product) {
            if (! empty($product->sub_category_id)) {
                $category_ids[] = $product->sub_category_id;
            } elseif (! empty($product->category_id)) {
                $category_ids[] = $product->category_id;
            }
        }
        // Remove duplicates
        $category_ids = array_unique($category_ids);

        $sales_income_account_ids = [];
        foreach ($category_ids as $category_id) {
            $category = Category::where('id', $category_id)->select('sales_income_account_id')->first();
            if (! empty($category) && ! empty($category->sales_income_account_id)) {
                $sales_income_account_ids[] = $category->sales_income_account_id;
            }
        }
        $sales_income_account_ids = array_unique($sales_income_account_ids);

        $accounts = Account::whereNull('deleted_at')->whereIn('id', $sales_income_account_ids)->get();
        foreach ($accounts as $account) {
            $account->account_transactions_to_correct = AccountTransaction::whereNull('account_transactions.deleted_at')
                ->whereNotNull('account_transactions.sell_line_id')
                ->where('account_transactions.account_id', $account->id)
                ->where('account_transactions.type', 'credit')
                ->join('transaction_sell_lines', 'account_transactions.sell_line_id', '=', 'transaction_sell_lines.id')
                ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
                ->whereRaw('ROUND(transaction_sell_lines.unit_price_before_discount, 2) != ROUND(variations.sell_price_inc_tax, 2)')
                ->whereRaw('ROUND(transaction_sell_lines.unit_price_inc_tax * transaction_sell_lines.quantity, 2) != ROUND(account_transactions.amount, 2)')
                ->count();
        }

        return view('finance::account.correct_sale_income_accounts_tax')->with(compact(
            'accounts'
        ));
    }

    public function updateSaleIncomeAccountsTax($id)
    {
        if ($id == 'All') {
            $products     = Product::get();
            $category_ids = [];
            foreach ($products as $product) {
                if (! empty($product->sub_category_id)) {
                    $category_ids[] = $product->sub_category_id;
                } elseif (! empty($product->category_id)) {
                    $category_ids[] = $product->category_id;
                }
            }
            // Remove duplicates
            $category_ids = array_unique($category_ids);

            $sales_income_account_ids = [];
            foreach ($category_ids as $category_id) {
                $category = Category::where('id', $category_id)->select('sales_income_account_id')->first();
                if (! empty($category) && ! empty($category->sales_income_account_id)) {
                    $sales_income_account_ids[] = $category->sales_income_account_id;
                }
            }
            $sales_income_account_ids = array_unique($sales_income_account_ids);
            $accounts                 = Account::whereNull('deleted_at')->whereIn('id', $sales_income_account_ids)->get();
        } else {
            $accounts = Account::whereNull('deleted_at')->where('id', $id)->get();
        }

        foreach ($accounts as $account) {
            $account->account_transactions_to_correct = AccountTransaction::whereNull('account_transactions.deleted_at')
                ->whereNotNull('account_transactions.sell_line_id')
                ->where('account_transactions.account_id', $account->id)
                ->where('account_transactions.type', 'credit')
                ->join('transaction_sell_lines', 'account_transactions.sell_line_id', '=', 'transaction_sell_lines.id')
                ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
                ->whereRaw('ROUND(transaction_sell_lines.unit_price_before_discount, 2) != ROUND(variations.sell_price_inc_tax, 2)')
                ->whereRaw('ROUND(transaction_sell_lines.unit_price_inc_tax * transaction_sell_lines.quantity, 2) != ROUND(account_transactions.amount, 2)')
                ->select('account_transactions.*', 'transaction_sell_lines.unit_price_inc_tax as unit_price_inc_tax_tsl', 'transaction_sell_lines.quantity as quantity_tsl')
                ->get();
            foreach ($account->account_transactions_to_correct as $account_transaction_to_correct) {
                $newAmount = $account_transaction_to_correct->unit_price_inc_tax_tsl * $account_transaction_to_correct->quantity_tsl;
                \Log::info("Updating AccountTransaction Tax - id: {$account_transaction_to_correct->id} prevAmount: {$account_transaction_to_correct->amount} newAmount: {$newAmount}");
                $account_transaction_to_correct->amount = $newAmount;
                $account_transaction_to_correct->update();
            }
        }

        return Redirect::route('accounts.correctSaleIncomeAccountsTax')->with('msg', 'Account transactions tax updated');
    }

    public function correctCOGSAccountsTax()
    {
        $products     = Product::get();
        $category_ids = [];
        foreach ($products as $product) {
            if (! empty($product->sub_category_id)) {
                $category_ids[] = $product->sub_category_id;
            } elseif (! empty($product->category_id)) {
                $category_ids[] = $product->category_id;
            }
        }
        // Remove duplicates
        $category_ids = array_unique($category_ids);

        $cogs_account_ids = [];
        foreach ($category_ids as $category_id) {
            $category = Category::where('id', $category_id)->select('cogs_account_id')->first();
            if (! empty($category) && ! empty($category->cogs_account_id)) {
                $cogs_account_ids[] = $category->cogs_account_id;
            }
        }
        $cogs_account_ids = array_unique($cogs_account_ids);

        $accounts = Account::whereNull('deleted_at')->whereIn('id', $cogs_account_ids)->get();
        foreach ($accounts as $account) {
            $account->account_transactions_to_correct = AccountTransaction::whereNull('account_transactions.deleted_at')
                ->whereNotNull('account_transactions.sell_line_id')
                ->where('account_transactions.account_id', $account->id)
                ->where('account_transactions.type', 'debit')
                ->join('transaction_sell_lines', 'account_transactions.sell_line_id', '=', 'transaction_sell_lines.id')
                ->whereRaw('ROUND(transaction_sell_lines.last_purchased_price * transaction_sell_lines.quantity, 2) != ROUND(account_transactions.amount, 2)')
                ->count();
        }

        return view('finance::account.correct_cogs_accounts_tax')->with(compact(
            'accounts'
        ));
    }

    public function updateCOGSAccountsTax($id)
    {
        if ($id == 'All') {
            $products     = Product::get();
            $category_ids = [];
            foreach ($products as $product) {
                if (! empty($product->sub_category_id)) {
                    $category_ids[] = $product->sub_category_id;
                } elseif (! empty($product->category_id)) {
                    $category_ids[] = $product->category_id;
                }
            }
            // Remove duplicates
            $category_ids = array_unique($category_ids);

            $cogs_account_ids = [];
            foreach ($category_ids as $category_id) {
                $category = Category::where('id', $category_id)->select('cogs_account_id')->first();
                if (! empty($category) && ! empty($category->cogs_account_id)) {
                    $cogs_account_ids[] = $category->cogs_account_id;
                }
            }
            $cogs_account_ids = array_unique($cogs_account_ids);
            $accounts         = Account::whereNull('deleted_at')->whereIn('id', $cogs_account_ids)->get();
        } else {
            $accounts = Account::whereNull('deleted_at')->where('id', $id)->get();
        }

        foreach ($accounts as $account) {
            $account->account_transactions_to_correct = AccountTransaction::whereNull('account_transactions.deleted_at')
                ->whereNotNull('account_transactions.sell_line_id')
                ->where('account_transactions.account_id', $account->id)
                ->where('account_transactions.type', 'debit')
                ->join('transaction_sell_lines', 'account_transactions.sell_line_id', '=', 'transaction_sell_lines.id')
                ->whereRaw('ROUND(transaction_sell_lines.last_purchased_price * transaction_sell_lines.quantity, 2) != ROUND(account_transactions.amount, 2)')
                ->select('account_transactions.*', 'transaction_sell_lines.last_purchased_price as last_purchased_price_tsl', 'transaction_sell_lines.quantity as quantity_tsl')
                ->get();
            foreach ($account->account_transactions_to_correct as $account_transaction_to_correct) {
                $newAmount = $account_transaction_to_correct->last_purchased_price_tsl * $account_transaction_to_correct->quantity_tsl;
                \Log::info("Updating COGS AccountTransaction Tax - id: {$account_transaction_to_correct->id} prevAmount: {$account_transaction_to_correct->amount} newAmount: {$newAmount}");
                $account_transaction_to_correct->amount = $newAmount;
                $account_transaction_to_correct->update();
            }
        }

        return Redirect::route('accounts.correctCOGSAccountsTax')->with('msg', 'Account transactions tax updated');
    }

    public function correctSellLinesTax()
    {
        // get sell lines whose tax was calculated twice
        $transaction_sell_lines = TransactionSellLine::whereNull('transaction_sell_lines.deleted_at')
            ->whereNotNull('transaction_sell_lines.tax_id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->join('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            ->whereRaw('ROUND(transaction_sell_lines.unit_price_inc_tax, 2) != ROUND(variations.sell_price_inc_tax, 2)')
            ->whereRaw('ROUND(transaction_sell_lines.unit_price_inc_tax / ((tax_rates.amount/100)+1), 2) = ROUND(variations.sell_price_inc_tax, 2)')
            ->count();

        return view('finance::account.correct_transaction_sell_lines_tax')->with(compact(
            'transaction_sell_lines'
        ));
    }

    public function updateSellLinesTax()
    {
        // Get sell lines whose tax was calculated twice
        $transaction_sell_lines = TransactionSellLine::whereNull('transaction_sell_lines.deleted_at')
            ->whereNotNull('transaction_sell_lines.tax_id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->join('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
            ->whereRaw('ROUND(transaction_sell_lines.unit_price_inc_tax, 2) != ROUND(variations.sell_price_inc_tax, 2)')
            ->whereRaw('ROUND(transaction_sell_lines.unit_price_inc_tax / ((tax_rates.amount/100)+1), 2) = ROUND(variations.sell_price_inc_tax, 2)')
            ->select('transaction_sell_lines.id', 'transaction_sell_lines.unit_price_inc_tax', 'transaction_sell_lines.line_discount_type', 'transaction_sell_lines.line_discount_amount', 'variations.default_sell_price', 'variations.sell_price_inc_tax')
            ->get();

        foreach ($transaction_sell_lines as $line) {
            $new_item_tax             = $line->sell_price_inc_tax - $line->default_sell_price;
            $new_unit_price_inc_tax   = $line->sell_price_inc_tax;
            $unit_price_with_discount = $line->default_sell_price;
            if ($line->line_discount_amount > 0) {
                if ($line->line_discount_type == 'fixed') {
                    $unit_price_with_discount = $unit_price_with_discount + $line->line_discount_amount;
                }
                if ($line->line_discount_type == 'percentage') {
                    $unit_price_with_discount = $unit_price_with_discount * (1 + ($line->line_discount_amount / 100));
                }
            }
            \Log::info("Updating TransactionSellLine Tax - id: {$line->id} prev unit_price_inc_tax: {$line->unit_price_inc_tax} | new unit_price_inc_tax: {$new_unit_price_inc_tax}");
            TransactionSellLine::where('id', $line->id)->update([
                'item_tax'                   => $new_item_tax,
                'unit_price_inc_tax'         => $new_unit_price_inc_tax,
                'unit_price_before_discount' => $line->default_sell_price,
                'unit_price'                 => $unit_price_with_discount,
            ]);
        }

        return Redirect::route('accounts.correctSellLinesTax')->with('msg', 'Transaction lines tax updated');
    }

    public function correctSellLinesDecimalDifference()
    {
        $products     = Product::get();
        $category_ids = [];
        foreach ($products as $product) {
            if (! empty($product->sub_category_id)) {
                $category_ids[] = $product->sub_category_id;
            } elseif (! empty($product->category_id)) {
                $category_ids[] = $product->category_id;
            }
        }
        // Remove duplicates
        $category_ids = array_unique($category_ids);

        $sales_income_account_ids = [];
        foreach ($category_ids as $category_id) {
            $category = Category::where('id', $category_id)->select('sales_income_account_id')->first();
            if (! empty($category) && ! empty($category->sales_income_account_id)) {
                $sales_income_account_ids[] = $category->sales_income_account_id;
            }
        }
        $sales_income_account_ids = array_unique($sales_income_account_ids);

        // Get sell lines whose price has a small decimal difference
        $transaction_sell_lines = TransactionSellLine::whereNull('transaction_sell_lines.deleted_at')
            ->whereNotNull('transaction_sell_lines.tax_id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->whereRaw('ROUND(transaction_sell_lines.unit_price_inc_tax, 2) != ROUND(variations.sell_price_inc_tax, 2)')
            ->whereRaw('ROUND((transaction_sell_lines.unit_price_inc_tax), 0) = ROUND(variations.sell_price_inc_tax, 0)')
            ->select('transaction_sell_lines.id', 'transaction_sell_lines.unit_price_inc_tax', 'transaction_sell_lines.line_discount_type', 'transaction_sell_lines.line_discount_amount', 'variations.default_sell_price', 'variations.sell_price_inc_tax')
            ->get();

        $account_transactions_to_correct_arr = [];
        // \Log::debug("correctSellLinesDecimalDifference",['transaction_sell_lines'=>$transaction_sell_lines,'sales_income_account_ids'=>$sales_income_account_ids]);
        foreach ($transaction_sell_lines as $line) {
            $account_transactions_to_correct = AccountTransaction::where('account_transactions.sell_line_id', $line->id)
                ->whereIn('account_transactions.account_id', $sales_income_account_ids)
                ->join('transaction_sell_lines', 'account_transactions.sell_line_id', '=', 'transaction_sell_lines.id')
                ->select('account_transactions.*', 'transaction_sell_lines.unit_price_inc_tax as unit_price_inc_tax_tsl', 'transaction_sell_lines.quantity as quantity_tsl')
                ->get();
            foreach ($account_transactions_to_correct as $account_transaction_to_correct) {
                $account_transactions_to_correct_arr[] = $account_transaction_to_correct->account_id;
            }
        }
        $account_transactions_to_correct_arr   = array_unique($account_transactions_to_correct_arr);
        $account_transactions_to_correct_count = count($account_transactions_to_correct_arr);

        // get sell lines whose price has a small decimal difference
        $transaction_sell_lines = TransactionSellLine::whereNull('transaction_sell_lines.deleted_at')
            ->whereNotNull('transaction_sell_lines.tax_id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->whereRaw('ROUND(transaction_sell_lines.unit_price_inc_tax, 2) != ROUND(variations.sell_price_inc_tax, 2)')
            ->whereRaw('ROUND((transaction_sell_lines.unit_price_inc_tax), 0) = ROUND(variations.sell_price_inc_tax, 0)')
            ->count();

        return view('finance::account.correct_transaction_sell_lines_decimal_difference')->with(compact(
            'transaction_sell_lines',
            'account_transactions_to_correct_count'
        ));
    }

    public function updateSellLinesDecimalDifference()
    {
        $products     = Product::get();
        $category_ids = [];
        foreach ($products as $product) {
            if (! empty($product->sub_category_id)) {
                $category_ids[] = $product->sub_category_id;
            } elseif (! empty($product->category_id)) {
                $category_ids[] = $product->category_id;
            }
        }
        // Remove duplicates
        $category_ids = array_unique($category_ids);

        $sales_income_account_ids = [];
        foreach ($category_ids as $category_id) {
            $category = Category::where('id', $category_id)->select('sales_income_account_id')->first();
            if (! empty($category) && ! empty($category->sales_income_account_id)) {
                $sales_income_account_ids[] = $category->sales_income_account_id;
            }
        }
        $sales_income_account_ids = array_unique($sales_income_account_ids);

        // Get sell lines whose price has a small decimal difference
        $transaction_sell_lines = TransactionSellLine::whereNull('transaction_sell_lines.deleted_at')
            ->whereNotNull('transaction_sell_lines.tax_id')
            ->join('variations', 'transaction_sell_lines.variation_id', '=', 'variations.id')
            ->whereRaw('ROUND(transaction_sell_lines.unit_price_inc_tax, 2) != ROUND(variations.sell_price_inc_tax, 2)')
            ->whereRaw('ROUND((transaction_sell_lines.unit_price_inc_tax), 0) = ROUND(variations.sell_price_inc_tax, 0)')
            ->select('transaction_sell_lines.id', 'transaction_sell_lines.unit_price_inc_tax', 'transaction_sell_lines.line_discount_type', 'transaction_sell_lines.line_discount_amount', 'variations.default_sell_price', 'variations.sell_price_inc_tax')
            ->get();

        foreach ($transaction_sell_lines as $line) {
            $new_item_tax             = $line->sell_price_inc_tax - $line->default_sell_price;
            $new_unit_price_inc_tax   = $line->sell_price_inc_tax;
            $unit_price_with_discount = $line->default_sell_price;
            if ($line->line_discount_amount > 0) {
                if ($line->line_discount_type == 'fixed') {
                    $unit_price_with_discount = $unit_price_with_discount + $line->line_discount_amount;
                }
                if ($line->line_discount_type == 'percentage') {
                    $unit_price_with_discount = $unit_price_with_discount * (1 + ($line->line_discount_amount / 100));
                }
            }
            \Log::info("Updating TransactionSellLine Decimal Difference - id: {$line->id} prev unit_price_inc_tax: {$line->unit_price_inc_tax} | new unit_price_inc_tax: {$new_unit_price_inc_tax}");
            TransactionSellLine::where('id', $line->id)->update([
                'item_tax'                   => $new_item_tax,
                'unit_price_inc_tax'         => $new_unit_price_inc_tax,
                'unit_price_before_discount' => $line->default_sell_price,
                'unit_price'                 => $unit_price_with_discount,
            ]);
            $account_transactions_to_correct = AccountTransaction::where('account_transactions.sell_line_id', $line->id)
                ->whereIn('account_transactions.account_id', $sales_income_account_ids)
                ->join('transaction_sell_lines', 'account_transactions.sell_line_id', '=', 'transaction_sell_lines.id')
                ->select('account_transactions.*', 'transaction_sell_lines.unit_price_inc_tax as unit_price_inc_tax_tsl', 'transaction_sell_lines.quantity as quantity_tsl')
                ->get();
            foreach ($account_transactions_to_correct as $account_transaction_to_correct) {
                $newAmount = $account_transaction_to_correct->unit_price_inc_tax_tsl * $account_transaction_to_correct->quantity_tsl;
                \Log::info("Updating AccountTransaction Decimal Difference - id: {$account_transaction_to_correct->id} prevAmount: {$account_transaction_to_correct->amount} newAmount: {$newAmount}");
                $account_transaction_to_correct->amount = $newAmount;
                $account_transaction_to_correct->update();
            }
        }

        return Redirect::route('accounts.correctSellLinesDecimalDifference')->with('msg', 'Transaction lines Decimal Difference updated');
    }

    public function getAccountsReceivableSettlementCustomerPaymentToCredit()
    {
        $business_id          = session()->get('user.business_id');
        $account_id           = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
        $account_transactions = AccountTransaction::where('account_transactions.account_id', $account_id)
            ->where('account_transactions.type', 'debit')
            ->where('account_transactions.sub_type', 'deposit')
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'sell')
            ->where('transactions.sub_type', 'settlement')
            ->join('settlements', 'transactions.invoice_no', '=', 'settlements.settlement_no')
            ->join('customer_payments', 'settlements.id', '=', 'customer_payments.settlement_no')
            ->whereNotNull('customer_payments.settlement_no')
            ->distinct('customer_payments.id')
            ->count();

        return view('finance::account.update_accounts_receivable_settlement_customer_payment_to_credit')->with(compact(
            'account_transactions'
        ));
    }

    public function updateAccountsReceivableSettlementCustomerPaymentToCredit($id)
    {
        $business_id          = session()->get('user.business_id');
        $account_id           = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
        $account_transactions = AccountTransaction::where('account_transactions.account_id', $account_id)
            ->where('account_transactions.type', 'debit')
            ->where('account_transactions.sub_type', 'deposit')
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'sell')
            ->where('transactions.sub_type', 'settlement')
            ->join('settlements', 'transactions.invoice_no', '=', 'settlements.settlement_no')
            ->join('customer_payments', 'settlements.id', '=', 'customer_payments.settlement_no')
            ->whereNotNull('customer_payments.settlement_no')
            ->distinct('customer_payments.id');

        \Log::info('updateAccountsReceivableSettlementCustomerPaymentToCredit', ['updated AccountTransaction ids' => $account_transactions->pluck('account_transactions.id')->toArray()]);

        $updated_count = $account_transactions->update(['account_transactions.type' => 'credit']);

        return Redirect::route('accounts.getAccountsReceivableSettlementCustomerPaymentToCredit')->with('msg', 'Accounts Receivable, settlement customer payment, updated to credit');
    }

    public function getFinishedGoodsAccountPosSaleTax()
    {
        $account_id           = $this->transactionUtil->account_exist_return_id('Finished Goods Account');
        $account_transactions = AccountTransaction::where('account_transactions.account_id', $account_id)
            ->where('account_transactions.type', 'credit')
            ->whereNull('account_transactions.sub_type')
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'sell')
            ->join('transaction_sell_lines', 'account_transactions.sell_line_id', '=', 'transaction_sell_lines.id')
            ->whereNotNull('transaction_sell_lines.last_purchased_price')
            ->whereNotNull('transaction_sell_lines.quantity')
            ->whereRaw('ROUND(transaction_sell_lines.quantity * transaction_sell_lines.last_purchased_price, 2) != ROUND(account_transactions.amount, 2)')
            ->distinct('transaction_sell_lines.id')
            ->count();

        return view('finance::account.updateFinishedGoodsAccountPosSaleTax')->with(compact(
            'account_transactions'
        ));
    }

    public function updateFinishedGoodsAccountPosSaleTax($id)
    {
        $account_id           = $this->transactionUtil->account_exist_return_id('Finished Goods Account');
        $account_transactions = AccountTransaction::where('account_transactions.account_id', $account_id)
            ->where('account_transactions.type', 'credit')
            ->whereNull('account_transactions.sub_type')
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'sell')
            ->join('transaction_sell_lines', 'account_transactions.sell_line_id', '=', 'transaction_sell_lines.id')
            ->whereNotNull('transaction_sell_lines.last_purchased_price')
            ->whereNotNull('transaction_sell_lines.quantity')
            ->whereRaw('ROUND(transaction_sell_lines.quantity * transaction_sell_lines.last_purchased_price, 2) != ROUND(account_transactions.amount, 2)')
            ->distinct('transaction_sell_lines.id')
            ->select('account_transactions.*', 'transaction_sell_lines.last_purchased_price', 'transaction_sell_lines.quantity')
            ->get();

        foreach ($account_transactions as $transaction) {
            \Log::info('updateFinishedGoodsAccountPosSaleTax', ['id' => $transaction->id, 'calc' => "$transaction->quantity * $transaction->last_purchased_price", 'prevAmount' => $transaction->amount, 'newAmount' => ($transaction->quantity * $transaction->last_purchased_price)]);
            $transaction->amount = $transaction->quantity * $transaction->last_purchased_price;
            $transaction->update();
        }

        return Redirect::route('accounts.getFinishedGoodsAccountPosSaleTax')->with('msg', 'Finished Goods Account Pos Sales Tax updated');
    }

    public function getCashAccountPosSaleTax()
    {
        $account_id           = $this->transactionUtil->account_exist_return_id('Cash');
        $account_transactions = AccountTransaction::where('account_transactions.account_id', $account_id)
            ->where('account_transactions.type', 'debit')
            ->where('account_transactions.sub_type', 'ledger_show')
            ->whereNotNull('account_transactions.transaction_payment_id')
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'sell')
            ->whereNull('transactions.sub_type')
            ->join('transaction_payments', 'account_transactions.transaction_payment_id', '=', 'transaction_payments.id')
            ->where('transaction_payments.method', 'cash')
            ->where('transaction_payments.account_id', $account_id)
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->whereNotNull('transaction_sell_lines.unit_price_inc_tax')
            ->whereNotNull('transaction_sell_lines.quantity')
            ->groupBy('account_transactions.id')
            ->havingRaw('ROUND(SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax), 2) != ROUND(account_transactions.amount, 2)')
            ->select('account_transactions.id as account_transaction_id', 'account_transactions.amount', DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as calculated_amount'))
            ->count();

        return view('finance::account.updateCashAccountPosSaleTax')->with(compact(
            'account_transactions'
        ));
    }

    public function updateCashAccountPosSaleTax($id)
    {
        $account_id           = $this->transactionUtil->account_exist_return_id('Cash');
        $account_transactions = AccountTransaction::where('account_transactions.account_id', $account_id)
            ->where('account_transactions.type', 'debit')
            ->where('account_transactions.sub_type', 'ledger_show')
            ->whereNotNull('account_transactions.transaction_payment_id')
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->where('transactions.type', 'sell')
            ->whereNull('transactions.sub_type')
            ->join('transaction_payments', 'account_transactions.transaction_payment_id', '=', 'transaction_payments.id')
            ->where('transaction_payments.method', 'cash')
            ->where('transaction_payments.account_id', $account_id)
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->whereNotNull('transaction_sell_lines.unit_price_inc_tax')
            ->whereNotNull('transaction_sell_lines.quantity')
            ->groupBy('account_transactions.id')
            ->havingRaw('ROUND(SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax), 2) != ROUND(account_transactions.amount, 2)')
            ->select('account_transactions.id as account_transaction_id', 'account_transactions.amount', DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as calculated_amount'))
            ->get();

        foreach ($account_transactions as $transaction) {
            \Log::info('updateCashAccountPosSaleTax', [
                'id'         => $transaction->account_transaction_id,
                'prevAmount' => $transaction->amount,
                'newAmount'  => $transaction->calculated_amount,
            ]);

            $transactionToUpdate         = AccountTransaction::find($transaction->account_transaction_id);
            $transactionToUpdate->amount = $transaction->calculated_amount;
            $transactionToUpdate->save();
        }

        return Redirect::route('accounts.getCashAccountPosSaleTax')->with('msg', 'Cash Account Pos Sales Tax updated');
    }

    public function correctAccountsProductWiseDiscount()
    {
        $products     = Product::get();
        $category_ids = [];
        foreach ($products as $product) {
            if (! empty($product->sub_category_id)) {
                $category_ids[] = $product->sub_category_id;
            } elseif (! empty($product->category_id)) {
                $category_ids[] = $product->category_id;
            }
        }
        // Remove duplicates
        $category_ids = array_unique($category_ids);

        $sales_income_account_ids = [];
        foreach ($category_ids as $category_id) {
            $category = Category::where('id', $category_id)->select('sales_income_account_id')->first();
            if (! empty($category) && ! empty($category->sales_income_account_id)) {
                $sales_income_account_ids[] = $category->sales_income_account_id;
            }
        }
        $sales_income_account_ids = array_unique($sales_income_account_ids);
        $payments_account_ids     = [];
        $payments_account_ids[]   = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
        $payments_account_ids[]   = Account::getAccountByAccountName('Cash')->id ?? 0;
        $payments_account_ids[]   = Account::getAccountByAccountName('Cheques in Hand')->id ?? 0;

        $card_group_id      = AccountGroup::getGroupByName('Card', true);
        $card_type_accounts = Account::where('asset_type', $card_group_id)
            ->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')
            ->pluck('id')
            ->toArray();

        $payments_account_ids = array_merge($payments_account_ids, $card_type_accounts);

        // get account transactions whose amount is not equal to the sell line unit_price_inc_tax (which includes the deducted discount) for products
        $account_transactions_to_correct = AccountTransaction::whereIn('account_transactions.account_id', $sales_income_account_ids)
            ->whereNotNull('account_transactions.account_id')
            ->where('account_transactions.account_id', '!=', 0)
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('accounts', 'account_transactions.account_id', '=', 'accounts.id')
            ->where('transactions.type', 'sell')
            ->whereNull('transactions.sub_type')
            ->where('transaction_sell_lines.line_discount_amount', '>', 0)
            ->whereRaw('ROUND((transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax), 2) != ROUND(account_transactions.amount, 2)')
            ->select('account_transactions.*', DB::raw('(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as correct_amount'), 'accounts.name as account_name')
            ->get();

        // get account transactions whose amount is not equal to the total sell line unit_price_inc_tax and bill discount
        $payments_account_transactions_to_correct = AccountTransaction::whereIn('account_transactions.account_id', $payments_account_ids)
            ->whereNotNull('account_transactions.account_id')
            ->where('account_transactions.account_id', '!=', 0)
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('accounts', 'account_transactions.account_id', '=', 'accounts.id')
            ->where('transactions.type', 'sell')
            ->whereNull('transactions.sub_type')
            ->where(function ($query) {
                $query->where('transaction_sell_lines.line_discount_amount', '>', 0)
                    ->orWhere('transactions.discount_amount', '>', 0);
            })
            ->groupBy(
                'account_transactions.id',
                'transactions.id',
                'accounts.id',
                'transactions.discount_type',
                'transactions.discount_amount'
            )
            ->havingRaw('ROUND(
            CASE
                WHEN transactions.discount_type = "fixed" THEN
                    SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) - transactions.discount_amount
                WHEN transactions.discount_type = "percentage" THEN
                    SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) - ((transactions.discount_amount / 100) * SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax))
                ELSE
                    SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax)
            END, 2) != ROUND(account_transactions.amount, 2)')
            ->select(
                'account_transactions.*',
                DB::raw('
                CASE
                    WHEN transactions.discount_type = "fixed" THEN
                        SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) - transactions.discount_amount
                    WHEN transactions.discount_type = "percentage" THEN
                        SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) - ((transactions.discount_amount / 100) * SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax))
                    ELSE
                        SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax)
                END as correct_amount
            '),
                'accounts.name as account_name'
            )
            ->get();

        $account_transactions_to_correct = $account_transactions_to_correct->merge($payments_account_transactions_to_correct);

        // \Log::debug("correctAccountsProductWiseDiscount",["account_transactions_to_correct"=>count($account_transactions_to_correct),"account_ids"=>$account_ids]);

        $account_ids = array_merge($payments_account_ids, $sales_income_account_ids);

        return view('finance::account.correctAccountsProductWiseDiscount')->with(compact(
            'account_ids',
            'account_transactions_to_correct'
        ));
    }

    public function updateAccountsProductWiseDiscount($id)
    {
        $products     = Product::get();
        $category_ids = [];
        foreach ($products as $product) {
            if (! empty($product->sub_category_id)) {
                $category_ids[] = $product->sub_category_id;
            } elseif (! empty($product->category_id)) {
                $category_ids[] = $product->category_id;
            }
        }
        // Remove duplicates
        $category_ids = array_unique($category_ids);

        $sales_income_account_ids = [];
        foreach ($category_ids as $category_id) {
            $category = Category::where('id', $category_id)->select('sales_income_account_id')->first();
            if (! empty($category) && ! empty($category->sales_income_account_id)) {
                $sales_income_account_ids[] = $category->sales_income_account_id;
            }
        }
        $sales_income_account_ids = array_unique($sales_income_account_ids);
        $payments_account_ids     = [];
        $payments_account_ids[]   = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
        $payments_account_ids[]   = Account::getAccountByAccountName('Cash')->id ?? 0;
        $payments_account_ids[]   = Account::getAccountByAccountName('Cheques in Hand')->id ?? 0;

        $card_group_id      = AccountGroup::getGroupByName('Card', true);
        $card_type_accounts = Account::where('asset_type', $card_group_id)
            ->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')
            ->pluck('id')
            ->toArray();

        $payments_account_ids = array_merge($payments_account_ids, $card_type_accounts);

        // get account transactions whose amount is not equal to the sell line unit_price_inc_tax (which includes the deducted discount) for products
        $account_transactions_to_correct = AccountTransaction::whereIn('account_transactions.account_id', $sales_income_account_ids)
            ->whereNotNull('account_transactions.account_id')
            ->where('account_transactions.account_id', '!=', 0)
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('accounts', 'account_transactions.account_id', '=', 'accounts.id')
            ->where('transactions.type', 'sell')
            ->whereNull('transactions.sub_type')
            ->where('transaction_sell_lines.line_discount_amount', '>', 0)
            ->whereRaw('ROUND((transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax), 2) != ROUND(account_transactions.amount, 2)')
            ->select('account_transactions.*', DB::raw('(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) as correct_amount'), 'accounts.name as account_name')
            ->get();

        // get account transactions whose amount is not equal to the total sell line unit_price_inc_tax and bill discount
        $payments_account_transactions_to_correct = AccountTransaction::whereIn('account_transactions.account_id', $payments_account_ids)
            ->whereNotNull('account_transactions.account_id')
            ->where('account_transactions.account_id', '!=', 0)
            ->join('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
            ->join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('accounts', 'account_transactions.account_id', '=', 'accounts.id')
            ->where('transactions.type', 'sell')
            ->whereNull('transactions.sub_type')
            ->where(function ($query) {
                $query->where('transaction_sell_lines.line_discount_amount', '>', 0)
                    ->orWhere('transactions.discount_amount', '>', 0);
            })
            ->groupBy(
                'account_transactions.id',
                'transactions.id',
                'accounts.id',
                'transactions.discount_type',
                'transactions.discount_amount'
            )
            ->havingRaw('ROUND(
            CASE
                WHEN transactions.discount_type = "fixed" THEN
                    SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) - transactions.discount_amount
                WHEN transactions.discount_type = "percentage" THEN
                    SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) - ((transactions.discount_amount / 100) * SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax))
                ELSE
                    SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax)
            END, 2) != ROUND(account_transactions.amount, 2)')
            ->select(
                'account_transactions.*',
                DB::raw('
                CASE
                    WHEN transactions.discount_type = "fixed" THEN
                        SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) - transactions.discount_amount
                    WHEN transactions.discount_type = "percentage" THEN
                        SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax) - ((transactions.discount_amount / 100) * SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax))
                    ELSE
                        SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price_inc_tax)
                END as correct_amount
            '),
                'accounts.name as account_name'
            )
            ->get();

        $account_transactions_to_correct = $account_transactions_to_correct->merge($payments_account_transactions_to_correct);

        foreach ($account_transactions_to_correct as $account_transaction_to_correct) {
            if ($id != 'All') {
                if ($account_transaction_to_correct->account_id != $id) {
                    continue;
                }
            }
            \Log::info("Updating AccountTransaction Discount - id: {$account_transaction_to_correct->id} prevAmount: {$account_transaction_to_correct->amount} newAmount: {$account_transaction_to_correct->correct_amount}");
            $account_transaction_to_correct->amount = $account_transaction_to_correct->correct_amount;
            $account_transaction_to_correct->update();
        }

        return Redirect::route('accounts.correctAccountsProductWiseDiscount')->with('msg', 'Account Transactions Product Wise Discount updated');
    }

    /**
     * IS1497: Remove duplicate PD Settlement rows from account books.
     *
     * Root rule: duplicate rows should be prevented at posting time. This method is
     * still required for already-posted duplicated rows and for mixed old/new module
     * paths while Finance/Accounting/Contacts are being tested in parallel.
     */
}
