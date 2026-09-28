<?php

namespace Modules\Vat\Http\Controllers;

use App\Business;
// Separation step 3 (document 5-18): the shared `contacts` table is now
// reached through a VAT-owned model, so this file no longer depends on the
// core App\Contact class when the Contact module is retired for Customers.
// NOTE: SharedContact maps to `contacts`; the existing VatContact entity
// maps to `vat_contacts` and is a different data set.
use Modules\Vat\Entities\SharedContact as Contact;
use App\Product;
use App\Transaction;
use App\Variation;
use App\AccountTransaction;
use App\ContactLedger;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Modules\Vat\Entities\VatInvoiceToTransactionSetting;
use Modules\Vat\Entities\VatInvoiceToTransactionHistory;
use Modules\Vat\Entities\VatInvoice2;
use Modules\Vat\Entities\VatInvoiceDetail2;
use Modules\Vat\Entities\VatInvoicePayment2;

class VatInvoiceToTransactionController extends Controller
{
    protected $productUtil;
    protected $transactionUtil;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('business.id');
        $settings = VatInvoiceToTransactionSetting::where('business_id', $business_id)
            ->with(['created_by_user'])
            ->get();
        
        return view('vat::vat_invoice2.vat_invoice_to_transactions', compact('settings'));
    }

    public function store(Request $request)
    {
        try {
            $business_id = request()->session()->get('business.id');
            $user_id = auth()->user()->id;

            DB::beginTransaction();
            
            VatInvoiceToTransactionSetting::create([
                'business_id' => $business_id,
                'auto_update' => $request->auto_update ? 1 : 0,
                'status' => 'Active',
                'created_by' => $user_id,
                'note' => $request->note // Rule: Note box
            ]);

            DB::commit();
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return Redirect::back()->with('status', $output);
    }

    public function toggleStatus($id)
    {
        try {
            $business_id = request()->session()->get('business.id');
            $user_id = auth()->user()->id;

            DB::beginTransaction();
            
            $setting = VatInvoiceToTransactionSetting::where('business_id', $business_id)->findOrFail($id);
            $original_status = $setting->status;
            $new_status = $original_status == 'Active' ? 'Inactive' : 'Active';
            
            $setting->status = $new_status;
            $setting->save();

            VatInvoiceToTransactionHistory::create([
                'business_id' => $business_id,
                'setting_id' => $id,
                'original_status' => $original_status,
                'changed_status' => $new_status,
                'changed_by' => $user_id
            ]);

            DB::commit();
            $output = ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return Redirect::back()->with('status', $output);
    }

    public function history()
    {
        $business_id = request()->session()->get('business.id');
        $history = VatInvoiceToTransactionHistory::where('business_id', $business_id)
            ->with(['changed_by_user'])
            ->orderBy('id', 'desc')
            ->get();
        
        return view('vat::vat_invoice2.partials.history_table', compact('history'))->render();
    }

    public static function formatTransactionNote($customer_name, $invoice_no)
    {
        return "Customer " . $customer_name . "\n" . "VAT Invoice " . $invoice_no;
    }

    public static function autoPostVatInvoiceTransactions($issue_customer_bill)
    {
        $business_id = $issue_customer_bill->business_id;
        $setting = VatInvoiceToTransactionSetting::where('business_id', $business_id)
            ->where('status', 'Active')
            ->where('auto_update', 1)
            ->first();

        if (empty($setting)) {
            return;
        }

        $customer = Contact::find($issue_customer_bill->customer_id);
        $invoice_no = $issue_customer_bill->customer_bill_no;
        $total_amount = $issue_customer_bill->total_amount;
        $date = $issue_customer_bill->date;

        self::deleteVatInvoiceTransactions($business_id, $invoice_no);

        $transactionUtil = app(\App\Utils\TransactionUtil::class);

        // 1. Customer Ledger - DEBIT
        $accounts_receivable_id = $transactionUtil->account_exist_return_id('Accounts Receivable');
        if ($accounts_receivable_id) {
            $at_data = [
                'amount' => $total_amount,
                'account_id' => $accounts_receivable_id,
                'type' => 'debit',
                'sub_type' => 'vat_invoice',
                'operation_date' => $date,
                'created_by' => $issue_customer_bill->created_by,
                'note' => 'VAT Invoice No ' . $invoice_no,
                'business_id' => $business_id
            ];
            AccountTransaction::createAccountTransaction($at_data);
            
            $cl_data = $at_data;
            $cl_data['contact_id'] = $issue_customer_bill->customer_id;
            $cl_data['sub_type'] = 'sell';
            ContactLedger::createContactLedger($cl_data);
        }

        // 2. Payment Account - DEBIT
        $payments = VatInvoicePayment2::where('invoice_id', $issue_customer_bill->id)->get();
        foreach ($payments as $payment) {
            if ($payment->account_id) {
                $at_data = [
                    'amount' => $payment->amount,
                    'account_id' => $payment->account_id,
                    'type' => 'debit',
                    'operation_date' => $date,
                    'created_by' => $issue_customer_bill->created_by,
                    'note' => 'Customer ' . $customer->name . ', VAT Invoice No ' . $invoice_no,
                    'business_id' => $business_id
                ];
                AccountTransaction::createAccountTransaction($at_data);
            }
        }

        // Product level entries
        $details = VatInvoiceDetail2::where('issue_bill_id', $issue_customer_bill->id)->get();
        $fg_account_id = \App\Account::where('business_id', $business_id)
            ->whereRaw("REPLACE(`name`, '  ', ' ') = ?", ['Finished Goods Account'])
            ->value('id');
        $cogs_account_id = \App\Account::where('business_id', $business_id)
            ->whereRaw("REPLACE(`name`, '  ', ' ') = ?", ['Cost of Goods Sold'])
            ->value('id');
        $sales_income_account_id = \App\Account::where('business_id', $business_id)
            ->whereRaw("REPLACE(`name`, '  ', ' ') = ?", ['Sales Income'])
            ->value('id');

        foreach ($details as $detail) {
            $product = Product::find($detail->product_id);
            if (empty($product)) continue;

            $category = \App\Category::find($product->category_id);
            $product_cogs_account_id = (!empty($category) && !empty($category->cogs_account_id))
                ? $category->cogs_account_id
                : $cogs_account_id;

            $product_sales_income_account_id = (!empty($category) && !empty($category->sales_income_account_id))
                ? $category->sales_income_account_id
                : $sales_income_account_id;

            // Manage Stock check
            if ($product->enable_stock && !$product->is_service) {
                $variation = Variation::where('product_id', $product->id)->first();
                $purchase_price = $variation ? $variation->dpp_inc_tax : 0;
                $line_purchase_total = $detail->qty * $purchase_price;

                $note = self::formatTransactionNote($customer->name, $invoice_no);

                // 3. Finished Goods Account - CREDIT
                if ($fg_account_id) {
                    AccountTransaction::createAccountTransaction([
                        'amount' => $line_purchase_total,
                        'account_id' => $fg_account_id,
                        'type' => 'credit',
                        'operation_date' => $date,
                        'created_by' => $issue_customer_bill->created_by,
                        'note' => $note,
                        'business_id' => $business_id,
                        'skip_duplicate_check' => true
                    ]);
                }

                // 4. COGS Account - DEBIT
                if ($product_cogs_account_id) {
                    AccountTransaction::createAccountTransaction([
                        'amount' => $line_purchase_total,
                        'account_id' => $product_cogs_account_id,
                        'type' => 'debit',
                        'operation_date' => $date,
                        'created_by' => $issue_customer_bill->created_by,
                        'note' => $note,
                        'business_id' => $business_id,
                        'skip_duplicate_check' => true
                    ]);
                }
            }

            // 5. Sales Income Account - CREDIT
            $line_sale_total = $detail->qty * $detail->unit_price;
            if ($product_sales_income_account_id) {
                AccountTransaction::createAccountTransaction([
                    'amount' => $line_sale_total,
                    'account_id' => $product_sales_income_account_id,
                    'type' => 'credit',
                    'operation_date' => $date,
                    'created_by' => $issue_customer_bill->created_by,
                    'note' => self::formatTransactionNote($customer->name, $invoice_no),
                    'business_id' => $business_id,
                    'skip_duplicate_check' => true
                ]);
            }
        }
    }

    public static function deleteVatInvoiceTransactions($business_id, $invoice_no)
    {
        AccountTransaction::where('business_id', $business_id)
            ->where('note', 'like', '%' . $invoice_no . '%')
            ->forceDelete();

        ContactLedger::where('note', 'like', '%' . $invoice_no . '%')
            ->forceDelete();
    }
}
