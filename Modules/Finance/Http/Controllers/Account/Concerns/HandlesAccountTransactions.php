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
 * Editing, deleting and de-duplicating individual account transactions.
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
 * Methods here: editAccountTransaction, updateAccountTransaction, deleteAccountTransaction, destroyAccountTransaction, duplicateTransactions, restorePayments, restoreSettlementPayments, is1497RemovePdSettlementDuplicateRows, is1497PaymentIdentityFromAccountRow, is1497SettlementNoFromAccountRow
 */
trait HandlesAccountTransactions
{
    public function editAccountTransaction($transaction_id)
    {
        $account_transaction = AccountTransaction::findOrFail($transaction_id);
        $business_id         = request()->session()->get('user.business_id');
        $account_access      = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        if ($this->userCan('superadmin') || $this->userCan('account.access')) {
            $account_access = 1;
        }
        if ($account_access == 0) {
            $accounts = Account::where('business_id', $business_id)->where(function ($query) {
                $query->whereIn('accounts.name', ['Accounts Receivable', 'Accounts Payable', 'Cards (Credit Debit) Account', 'Cash', 'Cheques in Hand', 'Customer Deposits', 'Petty Cash']);
                $query->orWhere('accounts.visible', 1);
            })->pluck('name', 'id');
        } else {
            $accounts = Account::where('business_id', $business_id)->pluck('name', 'id');
        }

        // modified by iftekhar
        return view('finance::account.edit_account_transaction')->with(compact('account_transaction', 'accounts'));
    }

    public function updateAccountTransaction($transaction_id)
    {
        try {
            $input               = request()->except('_token');
            $new_amount          = $this->transactionUtil->num_uf($input['amount']);
            $input['amount']     = $this->transactionUtil->num_uf($input['amount']);
            $account_transaction = AccountTransaction::findOrFail($transaction_id);

            $transaction = Transaction::find($account_transaction->transaction_id);

            DB::beginTransaction();
            AccountTransaction::where('id', $transaction_id)->update($input);
            $contact_ledger = ContactLedger::where('transaction_id', $account_transaction->transaction_id)->where('transaction_payment_id', $account_transaction->transaction_payment_id)->update(['amount' => $input['amount']]);

            $business_id  = request()->session()->get('user.business_id');
            $business     = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

            if (! empty($business->sms_settings)) {
                $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));

                switch ($transaction->type) {
                    case 'advance_payment':
                        $trans_type = 'Advance Payment';
                        break;

                    case 'expense':
                        $trans_type = 'Expense';
                        break;

                    case 'ledger':
                        $trans_type = 'Ledger';
                        break;

                    case 'opening_balance':
                        $trans_type = 'Opening Balance';
                        break;

                    case 'opening_stock':
                        $trans_type = 'Opening Stock';
                        break;

                    case 'purchase':
                        $trans_type = 'Purchase';
                        break;

                    case 'purchase_return':
                        $trans_type = 'Purchase Return';
                        break;

                    case 'route_operation':
                        $trans_type = 'Route Operation';
                        break;

                    case 'sell':
                        $trans_type = 'Sale';
                        break;

                    case 'settlement':
                        $trans_type = 'Settlement';
                        break;

                    case 'stock_adjustment':
                        $trans_type = 'Stock Adjustment';
                        break;
                }

                $accountName  = Account::find($account_transaction->account_id);
                $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'transaction_changed')->first();

                if (! empty($msg_template)) {
                    $msg = $msg_template->sms_body;
                    $msg = str_replace('{transaction_type}', $trans_type, $msg);
                    $msg = str_replace('{account_name}', $accountName->name, $msg);
                    $msg = str_replace('{amount}', $this->productUtil->num_f($new_amount), $msg);
                    $msg = str_replace('{transaction_date}', $this->commonUtil->format_date($transaction->transaction_date), $msg);
                    $msg = str_replace('{invoice_no}', $transaction->invoice_no, $msg);
                    $msg = str_replace('{staff}', auth()->user()->username, $msg);

                    if (! empty($phones)) {
                        $data = [
                            'sms_settings'  => $sms_settings,
                            'mobile_number' => implode(',', $phones),
                            'sms_body'      => $msg,
                        ];

                        $response = $this->businessUtil->sendSms($data, 'transaction_changed');
                    }
                }
            }

            DB::commit();
            $output = [
                'success' => true,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return Redirect::back()->with('status', $output);
    }

    public function deleteAccountTransaction($id)
    {
        try {
            $account_transaction = AccountTransaction::leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')->where('account_transactions.id', $id)->select('account_transactions.*', 'accounts.name', 'accounts.asset_type')->first();
            $cash_group_id       = AccountGroup::getGroupByName('Cash Account', true);
            $card_group_id       = AccountGroup::getGroupByName('Card', true);
            $cheque_group_id     = AccountGroup::getGroupByName("Cheques in Hand (Customer's)", true);
            $bank_group_id       = AccountGroup::getGroupByName('Bank Account', true);
            $transaction_id      = $account_transaction->transaction_id;
            $transaction         = Transaction::find($transaction_id);
            if ($transaction && in_array($transaction->type, ['sell', 'purchase', 'expense'])) {
                $output = [
                    'success' => false,
                    'msg'     => __('lang_v1.transaction_exist_msg_account_transaction'),
                ];

                return $output;
            }
            // if related transaction account one of the above account group
            if (! empty($account_transaction) && in_array($account_transaction->asset_type, [$cash_group_id, $card_group_id, $cheque_group_id, $bank_group_id])) {
                // delete only if account more balance then the transaction account
                if ($this->getAccountBalance($account_transaction->account_id)->balance >= $account_transaction->amount) {
                    ContactLedger::where('transaction_id', $account_transaction->transaction_id)->where('transaction_payment_id', $account_transaction->transaction_payment_id)->forcedelete();
                    TransactionPayment::where('id', $account_transaction->transaction_payment_id)->delete();
                    AccountTransaction::where('id', $id)->forcedelete();
                    $this->transactionUtil->updatePaymentStatus($transaction_id);

                    $business_id  = request()->session()->get('user.business_id');
                    $business     = Business::where('id', $business_id)->first();
                    $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

                    if (! empty($business->sms_settings)) {
                        $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));

                        switch ($transaction->type) {
                            case 'advance_payment':
                                $trans_type = 'Advance Payment';
                                break;

                            case 'expense':
                                $trans_type = 'Expense';
                                break;

                            case 'ledger':
                                $trans_type = 'Ledger';
                                break;

                            case 'opening_balance':
                                $trans_type = 'Opening Balance';
                                break;

                            case 'opening_stock':
                                $trans_type = 'Opening Stock';
                                break;

                            case 'purchase':
                                $trans_type = 'Purchase';
                                break;

                            case 'purchase_return':
                                $trans_type = 'Purchase Return';
                                break;

                            case 'route_operation':
                                $trans_type = 'Route Operation';
                                break;

                            case 'sell':
                                $trans_type = 'Sale';
                                break;

                            case 'settlement':
                                $trans_type = 'Settlement';
                                break;

                            case 'stock_adjustment':
                                $trans_type = 'Stock Adjustment';
                                break;
                        }

                        $accountName = Account::find($account_transaction->account_id);

                        $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'transaction_deleted')->first();
                        if (! empty($msg_template)) {
                            $msg = $msg_template->sms_body;
                            $msg = str_replace('{transaction_type}', $trans_type, $msg);
                            $msg = str_replace('{account_name}', $accountName->name, $msg);
                            $msg = str_replace('{amount}', $this->productUtil->num_f($account_transaction->amount), $msg);
                            $msg = str_replace('{transaction_date}', $this->commonUtil->format_date($transaction->transaction_date), $msg);
                            $msg = str_replace('{invoice_no}', $transaction->invoice_no, $msg);
                            $msg = str_replace('{staff}', auth()->user()->username, $msg);

                            if (! empty($phones)) {
                                $data = [
                                    'sms_settings'  => $sms_settings,
                                    'mobile_number' => implode(',', $phones),
                                    'sms_body'      => $msg,
                                ];

                                $response = $this->businessUtil->sendSms($data, 'transaction_deleted');
                            }
                        }
                    }
                }
            } else {
                $output = [
                    'success' => false,
                    'msg'     => __('lang_v1.transaction_exist_msg_account_transaction'),
                ];

                return $output;
            }
            $output = [
                'success' => true,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            echo 'File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage();
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */

    public function destroyAccountTransaction($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (! request()->ajax()) {
            abort(404);
        }

        try {
            $business_id = (int) request()->session()->get('user.business_id');

            DB::transaction(function () use ($id, $business_id) {
                $account_transaction = AccountTransaction::query()
                    ->join('accounts', 'accounts.id', '=', 'account_transactions.account_id')
                    ->where('account_transactions.id', $id)
                    ->where('accounts.business_id', $business_id)
                    ->select('account_transactions.*')
                    ->lockForUpdate()
                    ->firstOrFail();

                $operation_type = $account_transaction->sub_type ?: $account_transaction->type;
                if (! in_array($operation_type, ['fund_transfer', 'deposit'], true)) {
                    abort(422, 'Only deposits and fund transfers can be deleted here.');
                }

                $transaction_ids = [$account_transaction->id];
                if (! empty($account_transaction->transfer_transaction_id)) {
                    $paired_id = AccountTransaction::query()
                        ->join('accounts', 'accounts.id', '=', 'account_transactions.account_id')
                        ->where('account_transactions.id', $account_transaction->transfer_transaction_id)
                        ->where('accounts.business_id', $business_id)
                        ->value('account_transactions.id');
                    if (! empty($paired_id)) {
                        $transaction_ids[] = (int) $paired_id;
                    }
                }

                AccountTransaction::whereIn('id', array_unique($transaction_ids))->delete();
            });

            $output = [
                'success' => true,
                'msg' => __('lang_v1.deleted_success'),
            ];
        } catch (\Throwable $e) {
            Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Closes the specified account.
     *
     * @return Response
     */

    public function duplicateTransactions($ats)
    {
        $originalRecords = AccountTransaction::whereIn('id', $ats)->get();

        $newRecords = [];

        foreach ($originalRecords as $record) {
            $newRecord = $record->replicate();

            if ($newRecord->type == 'credit') {
                $newRecord->type = 'debit';
            } elseif ($newRecord->type == 'debit') {
                $newRecord->type = 'credit';
            }

            $newRecords[] = $newRecord;
        }

        DB::transaction(function () use ($newRecords) {
            foreach ($newRecords as $newRecord) {
                $newRecord->save();
            }
        });

        return $newRecords;
    }

    public function restorePayments()
    {
        echo 'Updating security Deposits<br>';
        $this->transactionUtil->__correctSecurityDeposits();
        echo '<br>------------------------------<br>';
        echo 'Updating Customer Payments<br>';
        $this->transactionUtil->__correctCustomerPayments();
        echo '<br>------------------------------<br>';
        echo 'Updating Purchase Payments<br>';
        $this->transactionUtil->__correctPurchasePayments();
        echo '<br>------------------------------<br>';
        echo 'Updating Expenses<br>';
        $this->transactionUtil->__correctExpenses();
    }

    public function restoreSettlementPayments()
    {
        echo 'Updating Sell Payments <br>';
        $this->transactionUtil->__correctSellPayments();
        echo '<br>------------------------------<br>';
        echo 'Updating Settlement Payments<br>';
        $this->transactionUtil->__correctSettlement();
        echo '<br>------------------------------<br>';
    }

    /**
     * Extract settlement number from account transaction note field
     */

    private function is1497RemovePdSettlementDuplicateRows($rows)
    {
        if (! $rows instanceof \Illuminate\Support\Collection) {
            $rows = collect($rows);
        }

        $seen = [];

        return $rows->filter(function ($row) use (&$seen) {
            $row = is_array($row) ? (object) $row : $row;
            $settlementNo = $this->is1497SettlementNoFromAccountRow($row);

            if ($settlementNo === '' || stripos($settlementNo, 'PDST') === false) {
                return true;
            }

            // IS1497-FIX-003:
            // Do not include transaction_id / transaction_payment_id / account_transaction id in the duplicate key.
            // In the duplicate Card Account issue, the same PD settlement card posting was written twice with
            // different row IDs, so the old key treated both rows as different and displayed both.
            $paymentIdentity = $this->is1497PaymentIdentityFromAccountRow($row);

            $keyParts = [
                strtoupper($settlementNo),
                (string) ($row->account_id ?? ''),
                (string) ($row->type ?? ''),
                (string) ($row->at_sub_type ?? $row->sub_type ?? ''),
                number_format((float) ($row->amount ?? 0), 6, '.', ''),
                date('Y-m-d', strtotime((string) ($row->operation_date ?? $row->transaction_date ?? '1970-01-01'))),
                $paymentIdentity,
            ];

            $key = md5(implode('|', $keyParts));

            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;
            return true;
        })->values();
    }

    private function is1497PaymentIdentityFromAccountRow($row): string
    {
        $parts = [];

        foreach (['slip_no', 'card_type', 'card_number', 'cheque_number', 'payment_ref_no', 'method'] as $field) {
            if (! empty($row->{$field})) {
                $parts[] = $field . ':' . strtoupper(trim((string) $row->{$field}));
            }
        }

        if (! empty($row->transaction_payment) && is_object($row->transaction_payment)) {
            foreach (['slip_no', 'card_type', 'card_number', 'cheque_number', 'payment_ref_no', 'method'] as $field) {
                if (! empty($row->transaction_payment->{$field})) {
                    $parts[] = $field . ':' . strtoupper(trim((string) $row->transaction_payment->{$field}));
                }
            }
        }

        $note = trim(preg_replace('/\s+/', ' ', (string) ($row->note ?? '')));
        if ($note !== '') {
            // Keep only stable card/payment references from note, not the whole text.
            if (preg_match('/Slip\s*(No\.?|#)?\s*[:\-]?\s*([A-Za-z0-9\-\/]+)/i', $note, $m)) {
                $parts[] = 'SLIP:' . strtoupper($m[2]);
            }
            if (preg_match('/Card\s*(No\.?|#)?\s*[:\-]?\s*([A-Za-z0-9\-\/]+)/i', $note, $m)) {
                $parts[] = 'CARD:' . strtoupper($m[2]);
            }
        }

        $parts = array_values(array_unique(array_filter($parts)));

        return empty($parts) ? 'NO_PAYMENT_REF' : implode('|', $parts);
    }

    private function is1497SettlementNoFromAccountRow($row): string
    {
        foreach (['settlement_no', 'ref_no', 'invoice_no'] as $field) {
            if (! empty($row->{$field})) {
                return (string) $row->{$field};
            }
        }

        if (! empty($row->transaction)) {
            foreach (['invoice_no', 'ref_no'] as $field) {
                if (! empty($row->transaction->{$field})) {
                    return (string) $row->transaction->{$field};
                }
            }
        }

        foreach (['note', 'description'] as $field) {
            $text = (string) ($row->{$field} ?? '');
            if (preg_match('/PDST\d+/i', $text, $m)) {
                return strtoupper($m[0]);
            }
        }

        return '';
    }
}
