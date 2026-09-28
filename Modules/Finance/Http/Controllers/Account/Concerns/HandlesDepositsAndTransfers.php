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
use Modules\Finance\Services\Transactions\FinanceTransactionGuard;
use Yajra\DataTables\Facades\DataTables;

/**
 * Deposits, fund transfers and cheque opening balances.
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
 * Methods here: getDeposit, postDeposit, getFundTransfer, postFundTransfer, listDepositTransfer, editDepositTransfer, updateDepositTransfer, transferPostDatedCheques, chequeObTransfer, editChequeOb, updateChequeOb, deleteChequeOb
 */
trait HandlesDepositsAndTransfers
{
    public function getDeposit($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        $type = strtolower(trim((string) $id));

        // IS2254: this endpoint is a GET form endpoint. Some production
        // proxies/WAF layers do not preserve X-Requested-With consistently, so
        // gating the form behind request()->ajax() can make a perfectly valid
        // modal GET return an empty 200 response. Always render the Finance
        // form for GET requests; the modal loader still owns how it is displayed.
        if (request()->isMethod('get')) {
            $business_id       = (int) session()->get('user.business_id');
            $sub_card_accounts = null;
            $account           = null;

            /*
             * IS2248 #1
             * Cash/Card toolbar deposits pass the symbolic values "cash" and
             * "card".  Do not run those strings through Account::find() first:
             * depending on the SQL mode/driver that can produce an invalid-id
             * query before the intended branch is reached.
             */
            if ($type === 'cash') {
                $account = Account::where('business_id', $business_id)
                    ->NotClosed()
                    ->whereRaw('LOWER(name) = ?', ['cash'])
                    ->first();

                if (empty($account)) {
                    return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">Cash account is not available for this business. Please create/enable the Cash account first.</div></div></div></div>', 200);
                }

                $id = (int) $account->id;
            } elseif ($type === 'card') {
                $sub_card_accounts = app(CardDepositAccountResolver::class)
                    ->optionsForBusiness($business_id);

                if ($sub_card_accounts->isEmpty()) {
                    return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">No active Card account is available for this business. Please create or enable an account under the Card account group.</div></div></div></div>', 200);
                }

                $id = null;
            } else {
                if (! ctype_digit((string) $id)) {
                    abort(404);
                }

                $id = (int) $id;
                $account = Account::where('business_id', $business_id)
                    ->NotClosed()
                    ->find($id);

                if (empty($account)) {
                    return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">Selected account is not available or closed.</div></div></div></div>', 200);
                }
            }

            $from_accounts = Account::where('business_id', $business_id)
                ->when(! empty($id), function ($query) use ($id) {
                    $query->where('id', '!=', $id);
                })
                ->NotClosed()
                ->pluck('name', 'id');

            $group_name = null;
            if ($type !== 'card' && ! empty($account)) {
                $from_account_group = AccountGroup::where('business_id', $business_id)
                    ->where('id', $account->asset_type)
                    ->first();
                $group_name = ! empty($from_account_group) ? $from_account_group->name : null;
            }

            // 17 Sep 2026 business rule: only Cash/Card/Cheques in Hand
            // linked source accounts are restricted by available balance.
            $check_insufficient = $type === 'card'
                ? true
                : (! empty($id) && app(FinanceTransactionGuard::class)
                    ->requiresAvailableBalance((int) $business_id, (int) $id));

            $account_groups = AccountGroup::where('business_id', $business_id)
                ->pluck('name', 'id');

            /*
             * MPCS and Petro Shift are optional integrations.  A missing/disabled
             * integration must not prevent the Finance deposit modal from opening.
             */
            $mpcs_module = false;
            try {
                $subscription = Subscription::active_subscription($business_id);
                $package_details = ! empty($subscription) ? $subscription->package_details : [];
                if (is_string($package_details)) {
                    $package_details = json_decode($package_details, true) ?: [];
                } elseif (is_object($package_details)) {
                    $package_details = (array) $package_details;
                }
                $mpcs_module = ! empty($package_details['mpcs_module']);
            } catch (\Throwable $e) {
                Log::warning('Finance deposit: optional MPCS subscription lookup failed', [
                    'business_id' => $business_id,
                    'message' => $e->getMessage(),
                ]);
            }

            $account_balance = $type !== 'card'
                ? $this->getAccountBalance($id)
                : (object) ['balance' => 0.00];

            $petroDailyShifts = [];
            try {
                if (class_exists(PetroDailyShift::class)) {
                    $petroDailyShifts = PetroDailyShift::where('business_id', $business_id)
                        ->where('status', 0)
                        ->orderByDesc('id')
                        ->pluck('shift_no')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                }
            } catch (\Throwable $e) {
                Log::warning('Finance deposit: optional Petro shift lookup failed', [
                    'business_id' => $business_id,
                    'message' => $e->getMessage(),
                ]);
            }

            // Kept for view compatibility. Overpayment permission is now
            // decided per source account through $check_insufficient.
            $allow_over_deposit = ! $check_insufficient;

            return view('finance::account.deposit')
                ->with(compact(
                    'account',
                    'from_accounts',
                    'account_balance',
                    'check_insufficient',
                    'sub_card_accounts',
                    'group_name',
                    'account_groups',
                    'mpcs_module',
                    'petroDailyShifts',
                    'allow_over_deposit'
                ));
        }
    }

    /**
     * Deposits amount.
     *
     * @return \App\Http\Controllers\json
     */

    public function postDeposit(Request $request)
    { 
        $db_transaction_started = false;

    try {
        $business_id = (int) session()->get('user.business_id');

        // S735: these Finance transactions must be explicitly reconfirmed in
        // the modal before the server is allowed to process them.
        if ((string) $request->input('finance_confirmation', '0') !== '1') {
            $output = [
                'success' => false,
                'msg' => 'Please confirm the transaction before it is processed.',
            ];

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json($output);
            }

            return Redirect::back()->with(['status' => $output]);
        }

        $has_reviewed = $this->transactionUtil->hasReviewed($request->input('operation_date'));
    
        if(!empty($has_reviewed)){
            $output = [
                'success' => 0,
                'msg'     => __('lang_v1.review_first'),
            ];
            
            return Redirect::back()->with(['status' => $output]);
        }
        
        $reviewed = $this->transactionUtil->get_review($request->input('operation_date'), $request->input('operation_date'));
    
        if(!empty($reviewed)){
            $output = [
                'success' => 0,
                'msg'     => "You can't add a deposit for an already reviewed date",
            ];
            
            return Redirect::back()->with('status', $output);
        }
        
        $amount_input = $request->input('amount');
        // Handle amount[] array input
        if (is_array($amount_input)) {
            $amount = $this->commonUtil->num_uf(reset($amount_input));
        } else {
            $amount = $this->commonUtil->num_uf($amount_input);
        }
        
        // Validate amount
        if (empty($amount) || $amount <= 0) {
            $output = [
                'success' => false,
                'msg' => "Invalid amount specified. Amount must be greater than 0."
            ];
            
            Log::warning('Cash deposit: Invalid amount', ['amount' => $amount]);
            
            if ($request->ajax()) {
                return response()->json($output);
            } else {
                return Redirect::back()->with(['status' => $output]);
            }
        }

        // S673: was uf_date(), which returns null on a format mismatch.
        $operation_date = $this->resolveOperationDate($request->input('operation_date'));
        if (empty($operation_date)) {
            $output = [
                'success' => false,
                'msg' => "Invalid deposit date. Please select the date again.",
            ];

            if ($request->ajax()) {
                return response()->json($output);
            }

            return redirect()->back()->with(['status' => $output]);
        }
        
        $account_id = $request->input('account_id');
        $note = $request->input('note');
        $cheque_number = $request->input('cheque_number');
        $from_account = $request->input('from_account');
        
        // Get account details
        $account = Account::where('business_id', $business_id)
            ->findOrFail($account_id);

        $cardAccountResolver = app(CardDepositAccountResolver::class);
        $is_card_deposit = $request->input('deposit_type') === 'card'
            || $cardAccountResolver->isCardAccount((int) $business_id, (int) $account->id);

        if ($request->input('deposit_type') === 'card'
            && ! $cardAccountResolver->isCardAccount((int) $business_id, (int) $account->id)) {
            $output = [
                'success' => false,
                'msg' => 'The selected Card account is not active or does not belong to this business.',
            ];

            if ($request->ajax()) {
                return response()->json($output);
            }

            return Redirect::back()->with(['status' => $output]);
        }

        if (empty($from_account)) {
            $output = [
                'success' => false,
                'msg' => __('account.deposit_to') . ' is required.',
            ];

            if ($request->ajax()) {
                return response()->json($output);
            }

            return Redirect::back()->with(['status' => $output]);
        }

        $fromAccountModel = Account::where('business_id', $business_id)
            ->NotClosed()
            ->findOrFail($from_account);
        if ((int) $fromAccountModel->id === (int) $account->id) {
            $output = [
                'success' => false,
                'msg' => __('lang_v1.from_account') . ' and ' . __('lang_v1.to_account') . ' must be different.',
            ];

            if ($request->ajax()) {
                return response()->json($output);
            }

            return Redirect::back()->with(['status' => $output]);
        }
        
        // ============ SOURCE-BALANCE VALIDATION ============
        // Money leaves account_id (the row-selected source) and is deposited
        // into from_account. Only Cash/Card/Cheques in Hand linked accounts
        // must have enough available balance; all other account groups may
        // overpay / go beyond their current balance.
        $balance_guard = app(FinanceTransactionGuard::class);
        $balance_result = $balance_guard->checkSourceBalance(
            (int) $business_id,
            (int) $account_id,
            (float) $amount,
            true
        );

        if (! $balance_result['allowed']) {
            $output = [
                'success' => false,
                'msg' => $balance_result['message'],
            ];

            Log::warning('Finance deposit blocked - insufficient balance', [
                'business_id' => (int) $business_id,
                'source_account_id' => (int) $account_id,
                'available' => $balance_result['available'] ?? null,
                'required' => $balance_result['required'] ?? $amount,
            ]);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json($output);
            }

            return Redirect::back()->with(['status' => $output]);
        }
        // ============ END SOURCE-BALANCE VALIDATION ============

        // Handle file upload
        $uploadFile = null;
        $upload_dir = public_path('img/account_transaction/' . $business_id);
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if ($request->hasfile('attachment')) {
            $image_width = (int) System::getProperty('upload_image_width');
            $image_hieght = (int) System::getProperty('upload_image_height');
            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                Image::make($file->getRealPath())->resize($image_width, $image_hieght)
                    ->save($upload_dir . '/' . $filename);
            } else {
                $file->move($upload_dir, $filename);
            }
            $uploadFile = 'public/img/account_transaction/' . $business_id . '/' . $filename;
        }
        
        $operation_date_input = $request->input('operation_date')
            ?? $request->input('transaction_date')
            ?? $request->input('date');
        // S673: was uf_date(), which returns null on a format mismatch.
        $operation_date = $this->resolveOperationDate($operation_date_input);


        // ============ TRANSACTION CREATION ============
        if (!empty($amount)) {
            DB::beginTransaction();
            $db_transaction_started = true;

            // For cash deposits, always create entries
            $credit_data = [
                'amount' => $amount,
                'account_id' => $account_id,
                'type' => 'credit',
                'sub_type' => 'deposit',
                'operation_date' => $operation_date,
                'created_by' => session()->get('user.id'),
                'note' => $note,
                'cheque_number' => $cheque_number,
                'attachment' => $uploadFile
            ];
            
            // Create credit transaction
            /*
             | SW Shift No, on the CREDIT leg - which is CASH.
             |
             | Money leaving an asset account credits it. The data confirms it:
             |
             |     credit | Cash          <- the till paying out
             |     debit  | BOC-83422433  <- the bank receiving
             |
             | Balance In Hand is about the till, so the shift belongs here. It
             | was on the debit until now, which would have subtracted deposits
             | from the bank rather than the cash - plausible-looking and wrong.
             |
             | Set BEFORE the debit copy is taken, then cleared from the copy
             | below, so only this leg carries it. Tagging both would
             | double-count every deposit.
            */
            $credit_data['sw_shift_no'] = $request->input('sw_shift_no') ?: null;

            /*
             | The description, from each account's own point of view.
             |
             | The user's note is kept and appended - they typed it for a
             | reason, and losing it to make room for generated text would be a
             | poor trade.
            */
            /*
             | The description, from each account's own point of view.
             |
             | On its own LINE, with whatever the user typed beneath it. Joining
             | the two with a dash made the note read as part of the generated
             | text - which is what made it look as though their words had been
             | swallowed.
             |
             | Both go in `note`: account_transactions has no description
             | column. Checking that before writing saved losing the text
             | entirely.
            */
            $__sw_shift_suffix = $request->input('sw_shift_no')
                ? ' SW Shift No: ' . $request->input('sw_shift_no')
                : '';

            $__sw_user_note = trim((string) ($credit_data['note'] ?? ''));

            // The account being deposited INTO.
            $__sw_to_name = optional(Account::find($from_account))->name ?: 'account';

            $credit_data['note'] = trim(
                'Deposited To ' . $__sw_to_name . '.' . $__sw_shift_suffix
                . ($__sw_user_note !== '' ? "\n" . $__sw_user_note : '')
            );

            $credit = AccountTransaction::createAccountTransaction($credit_data);
            
            // Handle from_account for debit entry
            if (empty($from_account)) {
                // Auto-find cash account if not specified
                $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
                if (!empty($cash_account_id)) {
                    $from_account = $cash_account_id;
                }
            }
            
            // Create debit transaction if from_account exists
            if (!empty($from_account)) {
                $debit_data = $credit_data;
                $debit_data['type'] = 'debit';
                $debit_data['account_id'] = $from_account;
                $debit_data['transfer_transaction_id'] = $credit->id;

                /*
                 | The shift is NOT carried onto this leg.
                 |
                 | $debit_data is a copy of $credit_data, which now has it - so
                 | it must be cleared, or every deposit would appear twice in
                 | any sum by shift.
                 |
                 | The shift still shows in this leg's description, so it is
                 | visible from either account when reading the book.
                */
                unset($debit_data['sw_shift_no']);

                /*
                 | This leg belongs to the account RECEIVING, so it must name
                 | where the money came FROM - the Cash account on the credit
                 | leg.
                 |
                 | It said "Received from BOC-83422433" on the BOC row, because
                 | I used $from_account here, which is this same account.
                */
                $__sw_source_name = optional(Account::find($account_id))->name
                    ?: 'Cash Account';

                $debit_data['note'] = trim(
                    'Received from ' . $__sw_source_name . '.' . $__sw_shift_suffix
                    . ($__sw_user_note !== '' ? "\n" . $__sw_user_note : '')
                );

                $debit_data['attachment'] = $uploadFile;
                $debit_data['cheque_number'] = $cheque_number;
                
                $debit = AccountTransaction::createAccountTransaction($debit_data);
                
                $credit->transfer_transaction_id = $debit->id;
                $credit->save();
            }
            // SMS notification (existing code)
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            $accountName = $account;
            $msg_template = NotificationTemplate::where('business_id', $business_id)
                ->where('template_for', 'deposit')
                ->first();
                
            if(!empty($msg_template)){
                $msg = $msg_template->sms_body;
                $msg = str_replace('{account}', $accountName->name, $msg);
                $msg = str_replace('{amount}', $this->productUtil->num_f($amount), $msg);
                $msg = str_replace('{date}', $request->input('operation_date'), $msg);
                $msg = str_replace('{staff}', auth()->user()->username, $msg);
                
                $phones = [];
                if(!empty($business->sms_settings)){
                    $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));
                }
                
                if(!empty($phones)){
                    $data = [
                        'sms_settings' => $sms_settings,
                        'mobile_number' => implode(',', $phones),
                        'sms_body' => $msg
                    ];
                    
                    $response = $this->businessUtil->sendSms($data, 'deposit');
                }
            }
            
            $output = [
                'success' => true,
                'msg' => 'Successfully Saved'
            ];
        }
        
        DB::commit();
        $db_transaction_started = false;
        
    } catch (\Exception $e) {
        if ($db_transaction_started) {
            DB::rollBack();
        }
        Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
        
        $output = [
            'success' => false,
            'msg' => __("messages.something_went_wrong")
        ];
    }
    
    return Redirect::back()->with('status', $output);
}

    /**
     * Shows the fund transfer form.
     *
     * S-627 #4: $id is now optional.
     *
     * Every existing caller passes one - the Action menu on each row of the
     * account list, and the Disabled Accounts list - and those are unchanged:
     * with an id the form opens against that account exactly as before.
     *
     * The new Transfer button in the List Accounts toolbar has no row to take
     * an account from, so it calls this without one and the form renders a
     * From Account picker instead. That mirrors what Card Deposit already
     * does, where the account is chosen inside the form rather than before it.
     *
     * @param  int|null  $id
     * @return Response
     */
    public function getFundTransfer($id = null)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        // IS2254: do not depend on X-Requested-With to render this GET
        // form. The toolbar Transfer action has no row id and must still receive
        // the Finance transfer fragment even if a proxy strips AJAX headers.
        if (request()->isMethod('get')) {
            $business_id  = session()->get('user.business_id');

            $from_account = null;
            if (! empty($id)) {
                $from_account = Account::where('business_id', $business_id)
                    ->NotClosed()
                    ->findOrFail($id);
            }

            // Only populated in picker mode, so the view can tell the two
            // cases apart without a second flag.
            $from_accounts = collect();

            if (empty($from_account)) {
                $from_accounts = Account::where('business_id', $business_id)
                    ->NotClosed()
                    ->orderBy('name')
                    ->pluck('name', 'id');

                if ($from_accounts->isEmpty()) {
                    return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">No active account is available for this business. Please create or enable an account first.</div></div></div></div>', 200);
                }
            }

            $to_accounts = Account::where('business_id', $business_id)
                ->when(! empty($from_account), function ($query) use ($from_account) {
                    $query->where('id', '!=', $from_account->id);
                })
                ->NotClosed()
                ->pluck('name', 'id');

            $account_balance    = ! empty($from_account) ? $this->getAccountBalance($from_account->id) : null;
            $group_name         = null;

            if (! empty($from_account)) {
                $from_account_group = AccountGroup::where('business_id', $business_id)
                    ->where('id', $from_account->asset_type)
                    ->first();
                $group_name         = ! empty($from_account_group) ? $from_account_group->name : null;
            }

            $check_insufficient = ! empty($from_account)
                ? app(FinanceTransactionGuard::class)->requiresAvailableBalance(
                    (int) $business_id,
                    (int) $from_account->id
                )
                : false;

            $account_groups     = AccountGroup::where('business_id', $business_id)->pluck('name', 'id');
            $subscription       = Subscription::active_subscription($business_id);
            $package_details    = ! empty($subscription) ? (array) $subscription->package_details : [];

            // Overpayment permission is source-account specific. Non-protected
            // groups may go over balance; Cash/Card/Cheques in Hand may not.
            $allow_over_deposit = ! $check_insufficient;

            return view('finance::account.transfer')
                ->with(compact('from_account', 'from_accounts', 'to_accounts', 'account_balance', 'check_insufficient', 'group_name', 'account_groups', 'package_details', 'allow_over_deposit'));
        }
    }

    /**
     * Transfers fund from one account to another.
     *
     * @return Response
     */

    public function postFundTransfer(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) session()->get('user.business_id');

        // S735: toolbar Transfer and row Action -> Transfer use the same
        // mandatory confirmation gate.
        if ((string) $request->input('finance_confirmation', '0') !== '1') {
            $output = [
                'success' => false,
                'msg' => 'Please confirm the transaction before it is processed.',
            ];

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json($output);
            }

            return Redirect::back()->with('status', $output);
        }

        $operation_date_input = $request->input('operation_date');

        $has_reviewed = $this->transactionUtil->hasReviewed($operation_date_input);
        if (! empty($has_reviewed)) {
            return Redirect::back()->with('status', [
                'success' => 0,
                'msg' => __('lang_v1.review_first'),
            ]);
        }

        $reviewed = $this->transactionUtil->get_review($operation_date_input, $operation_date_input);
        if (! empty($reviewed)) {
            return Redirect::back()->with('status', [
                'success' => 0,
                'msg' => "You can't add a transfer for an already reviewed date",
            ]);
        }

        $amount = (float) $this->commonUtil->num_uf($request->input('amount'));
        $from = (int) $request->input('from_account');
        $to = (int) $request->input('to_account');
        $mode = strtolower(trim((string) $request->input('transfer_or_cheque', 'transfer')));
        $mode = in_array($mode, ['transfer', 'cheque'], true) ? $mode : 'transfer';
        $cheque_number = trim((string) $request->input('cheque_number'));
        $note = $request->input('note');

        if ($amount <= 0) {
            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => __('sale.amount') . ' must be greater than zero.',
            ]);
        }

        if ($from <= 0 || $to <= 0) {
            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => 'Both Transfer From and Transfer To accounts are required.',
            ]);
        }

        if ($from === $to) {
            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => __('lang_v1.from_account') . ' and ' . __('lang_v1.to_account') . ' must be different.',
            ]);
        }

        if ($mode === 'cheque' && $cheque_number === '') {
            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => __('lang_v1.cheque_number') . ' is required.',
            ]);
        }

        // IS2067 #2 / S673: use the same hardened date resolver as deposits.
        // commonUtil->uf_date() can return null without throwing when the picker
        // and business formats differ, which previously produced incomplete
        // transfer rows and made the save appear to fail.
        $operation_date = $this->resolveOperationDate($operation_date_input);

        try {
            $fromAcc = Account::where('business_id', $business_id)->NotClosed()->findOrFail($from);
            $toAcc = Account::where('business_id', $business_id)->NotClosed()->findOrFail($to);

            $balance_result = app(FinanceTransactionGuard::class)
                ->checkSourceBalance($business_id, $from, $amount, false);

            if (! $balance_result['allowed']) {
                $output = [
                    'success' => false,
                    'msg' => $balance_result['message'],
                ];

                Log::warning('Finance transfer blocked - insufficient balance', [
                    'business_id' => $business_id,
                    'source_account_id' => $from,
                    'available' => $balance_result['available'] ?? null,
                    'required' => $balance_result['required'] ?? $amount,
                ]);

                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json($output);
                }

                return Redirect::back()->with('status', $output);
            }

            $uploadFile = null;
            $uploadDirectory = public_path('img/account_transaction/' . $business_id);
            if (! is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true);
            }

            if ($request->hasFile('attachment')) {
                $image_width = (int) System::getProperty('upload_image_width');
                $image_height = (int) System::getProperty('upload_image_height');
                $file = $request->file('attachment');
                $extension = strtolower($file->getClientOriginalExtension());
                $filename = uniqid('transfer_', true) . '.' . $extension;

                if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                    Image::make($file->getRealPath())
                        ->resize($image_width, $image_height)
                        ->save($uploadDirectory . '/' . $filename);
                } else {
                    $file->move($uploadDirectory, $filename);
                }

                $uploadFile = 'public/img/account_transaction/' . $business_id . '/' . $filename;
            }

            DB::transaction(function () use (
                $request,
                $business_id,
                $amount,
                $from,
                $to,
                $mode,
                $cheque_number,
                $note,
                $operation_date,
                $uploadFile,
                $fromAcc
            ) {
                /*
                 * IS2067 #2: a normal Account Transfer is NOT a cheque payment.
                 *
                 * The previous save path created a transaction_payments row with
                 * method=cheque for every transfer, even when the user selected
                 * "Transfer". It then wrote cheque_deposit_bank rows with an empty
                 * cheque number. On stricter tenant schemas that insert fails, and
                 * on permissive schemas the transfer is misclassified as a cheque.
                 *
                 * A true transfer now writes the authoritative paired debit/credit
                 * account_transactions only. This is the same accounting shape the
                 * original Finance transfer used and the List Deposits & Transfers
                 * report already reads.
                 */
                if ($mode === 'transfer') {
                    $credit = AccountTransaction::createAccountTransaction([
                        'business_id' => $business_id,
                        'amount' => $amount,
                        'account_id' => $from,
                        'type' => 'credit',
                        'sub_type' => 'fund_transfer',
                        'created_by' => session()->get('user.id'),
                        'note' => $note,
                        'transfer_account_id' => $to,
                        'operation_date' => $operation_date,
                        'attachment' => $uploadFile,
                        // Each manual transfer is a new accounting event even if
                        // amount/date/accounts happen to match an earlier one.
                        'skip_duplicate_check' => true,
                    ]);

                    if (empty($credit)) {
                        throw new \RuntimeException('Unable to create the transfer credit entry.');
                    }

                    $debit = AccountTransaction::createAccountTransaction([
                        'business_id' => $business_id,
                        'amount' => $amount,
                        'account_id' => $to,
                        'type' => 'debit',
                        'sub_type' => 'fund_transfer',
                        'created_by' => session()->get('user.id'),
                        'note' => $note,
                        'transfer_account_id' => $from,
                        'transfer_transaction_id' => $credit->id,
                        'operation_date' => $operation_date,
                        'attachment' => $uploadFile,
                        'skip_duplicate_check' => true,
                    ]);

                    if (empty($debit)) {
                        throw new \RuntimeException('Unable to create the transfer debit entry.');
                    }

                    $credit->transfer_transaction_id = $debit->id;
                    $credit->save();

                    $debit->transfer_transaction_id = $credit->id;
                    $debit->save();

                    return;
                }

                // Cheque transfer / post-dated-cheque mode keeps its existing
                // payment linkage, but uses the already validated operation date.
                $prefix_type = 'security_deposit';
                $ref_count = $this->transactionUtil->onlyGetReferenceCount($prefix_type, $business_id, false);
                $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                $parent_array = [
                    'business_id' => $business_id,
                    'method' => 'cheque',
                    'bank_name' => $fromAcc->name,
                    'cheque_number' => $cheque_number,
                    'paid_on' => $operation_date,
                    'created_by' => Auth::user()->id,
                    'amount' => $amount,
                    'cheque_date' => $operation_date,
                    'is_deposited' => 1,
                    'note' => $note,
                    'payment_ref_no' => $payment_ref_no,
                    'post_dated_cheque' => $request->post_dated_cheque ?? 0,
                    'update_post_dated_cheque' => $request->update_post_dated_cheque ?? 0,
                ];

                if (! empty($request->update_post_dated_cheque)) {
                    $postDatedAccountId = (int) $this->transactionUtil->account_exist_return_id('Post Dated Cheques');
                    if ($postDatedAccountId <= 0) {
                        throw new \RuntimeException('Post Dated Cheques account is not available.');
                    }
                    $parent_array['account_id'] = $postDatedAccountId;
                    $parent_array['related_account_id'] = $to;
                    $use_to = $postDatedAccountId;
                } else {
                    $parent_array['account_id'] = $to;
                    $use_to = $to;
                }

                $parent_payment = TransactionPayment::create($parent_array);
                $tp_id = $parent_payment->id;
                $credit = null;

                if (empty($request->update_post_dated_cheque)) {
                    $credit = AccountTransaction::createAccountTransaction([
                        'business_id' => $business_id,
                        'amount' => $amount,
                        'account_id' => $from,
                        'type' => 'credit',
                        'sub_type' => 'fund_transfer',
                        'created_by' => session()->get('user.id'),
                        'note' => $note,
                        'cheque_number' => $cheque_number,
                        'transfer_account_id' => $to,
                        'operation_date' => $operation_date,
                        'cheque_date' => $operation_date,
                        'attachment' => $uploadFile,
                        'transaction_payment_id' => $tp_id,
                        'post_dated_cheque' => $request->post_dated_cheque ?? 0,
                        'update_post_dated_cheque' => $request->update_post_dated_cheque ?? 0,
                    ]);
                }

                $debit_data = [
                    'business_id' => $business_id,
                    'amount' => $amount,
                    'account_id' => $use_to,
                    'type' => 'debit',
                    'sub_type' => 'fund_transfer',
                    'created_by' => session()->get('user.id'),
                    'note' => $note,
                    'cheque_number' => $cheque_number,
                    'transfer_account_id' => $from,
                    'operation_date' => $operation_date,
                    'cheque_date' => $operation_date,
                    'attachment' => $uploadFile,
                    'post_dated_cheque' => $request->post_dated_cheque ?? 0,
                    'update_post_dated_cheque' => $request->update_post_dated_cheque ?? 0,
                    'transaction_payment_id' => $tp_id,
                ];

                if (! empty($request->update_post_dated_cheque)) {
                    $debit_data['related_account_id'] = $to;
                    $debit_data['credit_related_account'] = $from;
                }

                $debit = AccountTransaction::createAccountTransaction($debit_data);
                if (empty($debit)) {
                    throw new \RuntimeException('Unable to create the cheque-transfer debit entry.');
                }

                if (! empty($credit)) {
                    $credit->transfer_transaction_id = $debit->id;
                    $credit->save();
                    $debit->transfer_transaction_id = $credit->id;
                    $debit->save();
                }

                // Keep cheque-bank metadata only for real cheque transfers.
                DB::table('cheque_deposit_bank')->updateOrInsert(
                    ['account_trans_id' => $debit->id],
                    [
                        'bank_id' => $debit->account_id,
                        'cheque_number' => $cheque_number,
                    ]
                );

                if (! empty($credit)) {
                    DB::table('cheque_deposit_bank')->updateOrInsert(
                        ['account_trans_id' => $credit->id],
                        [
                            'bank_id' => $credit->account_id,
                            'cheque_number' => $cheque_number,
                        ]
                    );
                }
            }, 3);

            // Notification failure must never make a committed transfer look
            // like a failed save. Log it separately and keep the transaction.
            try {
                $business = Business::where('id', $business_id)->first();
                if (! empty($business)) {
                    $sms_settings = empty($business->sms_settings)
                        ? $this->businessUtil->defaultSmsSettings()
                        : $business->sms_settings;

                    $msg_template = NotificationTemplate::where('business_id', $business_id)
                        ->where('template_for', 'transfer')
                        ->first();

                    if (! empty($msg_template)) {
                        $msg = str_replace(
                            ['{account}', '{amount}', '{date}', '{staff}'],
                            [
                                'from : ' . $fromAcc->name . ' to : ' . $toAcc->name,
                                $this->productUtil->num_f($amount),
                                $request->input('operation_date'),
                                auth()->user()->username,
                            ],
                            $msg_template->sms_body
                        );

                        $phones = ! empty($business->sms_settings['msg_phone_nos'])
                            ? explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']))
                            : [];

                        if (! empty($phones)) {
                            $this->businessUtil->sendSms([
                                'sms_settings' => $sms_settings,
                                'mobile_number' => implode(',', $phones),
                                'sms_body' => $msg,
                            ], 'transfer');
                        }
                    }
                }
            } catch (\Throwable $notificationException) {
                Log::warning('Finance fund transfer saved but notification failed', [
                    'business_id' => $business_id,
                    'from_account' => $from,
                    'to_account' => $to,
                    'message' => $notificationException->getMessage(),
                ]);
            }

            $output = [
                'success' => true,
                'msg' => 'Successfully Saved',
                'tab' => 'list_deposit_transfer',
            ];

            // IS2067 #2: after a successful save, take the user directly to
            // the report that must contain the new transfer.
            return redirect('/finance/account?ldt_tab=1')->with('status', $output);
        } catch (\Throwable $e) {
            Log::emergency('Finance fund transfer save failed', [
                'business_id' => $business_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    /**
     * Shows deposit form.// id will treate as type for list page deopsit cheque buttons
     *
     * @param  int  $id
     * @return Response
     */

    /**
     * S673: turn a submitted date into a storable datetime, or fall back to now().
     *
     * Deposits made from the List Account page were saving with an EMPTY
     * operation_date, so the Date & Time column on List Deposits & Transfers was
     * blank for them.
     *
     * The cause is that commonUtil->uf_date() parses against the BUSINESS date
     * format and returns NULL when the input does not match it - it does not
     * throw. The null went straight into the insert, and nothing downstream
     * noticed. The same defect was found on the Finance journals (IS1992) and
     * the VAT invoice date.
     *
     * uf_date() is still tried first: the business format is the only thing that
     * can read an ambiguous 08/11/2026 correctly. The fallbacks below exist so a
     * picker whose format differs cannot silently lose the timestamp, and the
     * round-trip check stops Carbon quietly rolling 31/02 into March.
     *
     * A deposit is never rejected over its date - now() is used as a last
     * resort, because a transaction dated today is far better than one with no
     * date at all, which is what the ticket reports.
     */
    protected function resolveOperationDate($value): string
    {
        $value = trim((string) $value);

        if ($value !== '') {
            try {
                $converted = $this->commonUtil->uf_date($value, true);

                if (! empty($converted)) {
                    return $converted;
                }
            } catch (\Throwable $e) {
                // Wrong format for this business - fall through.
            }

            $formats = [
                'd/m/Y H:i', 'm/d/Y H:i', 'd-m-Y H:i', 'm-d-Y H:i',
                'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i',
                'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'Y-m-d', 'Y/m/d',
            ];

            foreach ($formats as $format) {
                try {
                    $parsed = \Carbon\Carbon::createFromFormat($format, $value);

                    // Round-trip check: without it 31/02/2026 is accepted and
                    // rolled forward into March.
                    if ($parsed && $parsed->format($format) === $value) {
                        return $parsed->format('Y-m-d H:i:s');
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }

            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // fall through to now()
            }
        }

        return \Carbon\Carbon::now()->format('Y-m-d H:i:s');
    }

    public function listDepositTransfer()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            /*
             * S391 root fix 23:
             * The route/AJAX/JSON layer is now working. The remaining failure was
             * zero rows because the list was too strict about which side of the
             * deposit/transfer pair to read and because it relied on joined account
             * business scope. In this multi-tenant ERP the primary scope must be the
             * current tenant DB + account_transactions.business_id, with account
             * joins used only for display.
             *
             * Creation flow:
             * - Deposits create account_transactions rows with sub_type = deposit.
             * - Transfers create account_transactions rows with sub_type = fund_transfer.
             * - Normal pairs have debit/credit rows linked by transfer_transaction_id.
             *
             * Listing rule:
             * - Show one row per deposit/transfer, preferring the debit row.
             * - If the debit row is missing/legacy, show the available row.
             * - Read all valid current-business rows from account_transactions.
             */
            $invalid_filter_values = ['', null, 'null', 'NULL', 'None', 'none', 'undefined', '0', 0];

            $accounts = AccountTransaction::query()
                ->from('account_transactions')
                ->leftJoin('account_transactions as pair_at', function ($join) {
                    $join->on('account_transactions.transfer_transaction_id', '=', 'pair_at.id')
                         ->whereNull('pair_at.deleted_at');
                })
                ->leftJoin('accounts as current_account', 'current_account.id', '=', 'account_transactions.account_id')
                ->leftJoin('accounts as pair_account', 'pair_account.id', '=', 'pair_at.account_id')
                ->leftJoin('transaction_payments', 'account_transactions.transaction_payment_id', '=', 'transaction_payments.id')
                ->leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('users', 'account_transactions.created_by', '=', 'users.id')
                ->whereNull('account_transactions.deleted_at')
                ->where('current_account.business_id', $business_id)
                ->where(function ($q) use ($business_id) {
                    $q->whereNull('pair_account.id')
                      ->orWhere('pair_account.business_id', $business_id);
                })
                ->where(function ($q) {
                    $q->whereIn('account_transactions.sub_type', ['deposit', 'fund_transfer'])
                      ->orWhereIn('account_transactions.type', ['deposit', 'fund_transfer']);
                })
                ->where(function ($q) {
                    // Prefer debit rows. If this row is credit and its paired debit row exists,
                    // skip it to avoid duplicate display. If the pair is missing or not debit,
                    // include the current row so legacy data is still visible.
                    $q->where('account_transactions.type', 'debit')
                      ->orWhereNull('account_transactions.transfer_transaction_id')
                      ->orWhereNull('pair_at.id')
                      ->orWhere('pair_at.type', '!=', 'debit');
                })
                ->select([
                    'account_transactions.id',
                    'account_transactions.operation_date',
                    'account_transactions.created_at',
                    DB::raw("COALESCE(account_transactions.sub_type, account_transactions.type) as sub_type"),
                    'account_transactions.amount',
                    'account_transactions.cheque_number',
                    'account_transactions.note',
                    'account_transactions.transaction_payment_id',
                    'account_transactions.transfer_transaction_id',
                    DB::raw("CASE WHEN account_transactions.type = 'debit' THEN COALESCE(pair_account.name, '-') ELSE COALESCE(current_account.name, '-') END as from_account"),
                    DB::raw("CASE WHEN account_transactions.type = 'debit' THEN COALESCE(current_account.name, '-') ELSE COALESCE(pair_account.name, '-') END as to_account"),
                    'users.username',
                    DB::raw("COALESCE(contacts.name, '') as customer_name"),
                ]);

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $startDate = request()->start_date;
                $endDate = request()->end_date;
                // S724 #1: legacy/older deposit rows can have a null/zero
                // operation_date. Filter those rows by created_at, matching the
                // timestamp used for display, so they do not disappear.
                $accounts->where(function ($dateQuery) use ($startDate, $endDate) {
                    $dateQuery->where(function ($validDate) use ($startDate, $endDate) {
                        $validDate->whereDate('account_transactions.operation_date', '>=', $startDate)
                            ->whereDate('account_transactions.operation_date', '<=', $endDate);
                    })->orWhere(function ($fallbackDate) use ($startDate, $endDate) {
                        $fallbackDate->where(function ($missingOperationDate) {
                            $missingOperationDate->whereNull('account_transactions.operation_date')
                                ->orWhere('account_transactions.operation_date', 'like', '0000-00-00%');
                        })
                        ->whereDate('account_transactions.created_at', '>=', $startDate)
                        ->whereDate('account_transactions.created_at', '<=', $endDate);
                    });
                });
            }

            $user_id = request()->user_id;
            if (! in_array($user_id, $invalid_filter_values, true)) {
                $accounts->where('account_transactions.created_by', $user_id);
            }

            $sub_type = request()->sub_type;
            if (! in_array($sub_type, $invalid_filter_values, true)) {
                $accounts->where(function ($q) use ($sub_type) {
                    $q->where('account_transactions.sub_type', $sub_type)
                      ->orWhere('account_transactions.type', $sub_type);
                });
            }

            $from_account_id = request()->from_account_id;
            if (! in_array($from_account_id, $invalid_filter_values, true)) {
                $accounts->where(function ($q) use ($from_account_id) {
                    $q->where(function ($qq) use ($from_account_id) {
                        $qq->where('account_transactions.type', 'debit')
                           ->where('pair_at.account_id', $from_account_id);
                    })->orWhere(function ($qq) use ($from_account_id) {
                        $qq->where('account_transactions.type', '!=', 'debit')
                           ->where('account_transactions.account_id', $from_account_id);
                    });
                });
            }

            $to_account_id = request()->to_account_id;
            if (! in_array($to_account_id, $invalid_filter_values, true)) {
                $accounts->where(function ($q) use ($to_account_id) {
                    $q->where(function ($qq) use ($to_account_id) {
                        $qq->where('account_transactions.type', 'debit')
                           ->where('account_transactions.account_id', $to_account_id);
                    })->orWhere(function ($qq) use ($to_account_id) {
                        $qq->where('account_transactions.type', '!=', 'debit')
                           ->where('pair_at.account_id', $to_account_id);
                    });
                });
            }

            $accounts->orderBy('account_transactions.operation_date', 'desc')
                ->orderBy('account_transactions.id', 'desc');

            return DataTables::of($accounts)
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (auth()->user()->can('account.deposit_transfer.edit')) {
                        $html .= '<button data-href="' . url('/finance/edit-deposit-transfer/' . $row->id) . '" data-container=".account_model" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</button>';
                    }
                    return $html;
                })
                ->editColumn('operation_date', function ($row) {
                    $raw = (string) ($row->operation_date ?? '');
                    if ($raw === '' || \Illuminate\Support\Str::startsWith($raw, '0000-00-00')) {
                        $raw = (string) ($row->created_at ?? '');
                    }

                    if ($raw === '' || \Illuminate\Support\Str::startsWith($raw, '0000-00-00')) {
                        return '-';
                    }

                    try {
                        $date = Carbon::parse($raw);
                        $dateFormat = session('business.date_format', 'd/m/Y');
                        $timeFormat = (int) session('business.time_format', 24) === 24 ? 'H:i' : 'h:i A';
                        return $date->format($dateFormat . ' ' . $timeFormat);
                    } catch (\Throwable $e) {
                        return '-';
                    }
                })
                ->editColumn('sub_type', function ($row) {
                    return $row->sub_type == 'fund_transfer' ? __('lang_v1.transfer') : __('account.deposit');
                })
                ->addColumn('amount', function ($row) {
                    $amount = (float) $row->amount;
                    return '<span class="display_currency finance-deposit-transfer-amount" data-currency_symbol="false" data-orig-value="' . $amount . '">' . $this->productUtil->num_f($amount) . '</span>';
                })
                ->editColumn('from_account', function ($row) {
                    return $row->from_account ?: '-';
                })
                ->editColumn('to_account', function ($row) {
                    return $row->to_account ?: '-';
                })
                ->editColumn('customer_name', function ($row) {
                    return $row->customer_name ?: '-';
                })
                ->editColumn('username', function ($row) {
                    return $row->username ?: '-';
                })
                ->rawColumns(['action', 'amount'])
                ->make(true);
        }
    }

    public function editDepositTransfer($id)
    {
        $business_id = (int) session()->get('user.business_id');
        $account_transaction = AccountTransaction::leftJoin('account_transactions as account_transactions_from', function ($join) {
            $join->on('account_transactions.transfer_transaction_id', '=', 'account_transactions_from.id');
        })
            ->join('accounts as to_account_record', 'to_account_record.id', '=', 'account_transactions.account_id')
            ->leftJoin('accounts as from_account_record', 'from_account_record.id', '=', 'account_transactions_from.account_id')
            ->where('account_transactions.type', 'debit')
            ->where('account_transactions.id', $id)
            ->where('to_account_record.business_id', $business_id)
            ->where(function ($query) use ($business_id) {
                $query->whereNull('from_account_record.id')
                    ->orWhere('from_account_record.business_id', $business_id);
            })
            ->select([
                'account_transactions.*',
                'account_transactions.account_id as to_account',
                'account_transactions_from.account_id as from_account',
            ])->firstOrFail();
        $accounts    = Account::where('business_id', $business_id)->pluck('name', 'id');

        // modified by iftekhar
        return view('finance::account.edit_deposit_transfer')->with(compact(
            'account_transaction',
            'accounts'
        ));
    }

    public function updateDepositTransfer($id)
    {
        try {
            $business_id = (int) session()->get('user.business_id');
            $operation_date_input = request()->operation_date;

            if (! empty($this->transactionUtil->hasReviewed($operation_date_input))) {
                return Redirect::back()->with(['status' => [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ]]);
            }

            if (! empty($this->transactionUtil->get_review($operation_date_input, $operation_date_input))) {
                return Redirect::back()->with('status', [
                    'success' => 0,
                    'msg' => "You can't add a transfer for an already reviewed date",
                ]);
            }

            $amount = $this->transactionUtil->num_uf(request()->amount);
            $to_account_id = (int) request()->to_account;
            $from_account_id = (int) request()->from_account;
            if ($amount <= 0 || $to_account_id <= 0 || $from_account_id <= 0 || $to_account_id === $from_account_id) {
                return Redirect::back()->with('status', [
                    'success' => false,
                    'tab' => 'list_deposit_transfer',
                    'msg' => 'Please enter a valid amount and select two different accounts.',
                ]);
            }

            Account::where('business_id', $business_id)->NotClosed()->findOrFail($to_account_id);
            Account::where('business_id', $business_id)->NotClosed()->findOrFail($from_account_id);
            $operation_date = $this->transactionUtil->uf_date($operation_date_input);
            $cheque_number = request()->cheque_number;

            DB::transaction(function () use ($id, $business_id, $operation_date, $amount, $to_account_id, $from_account_id, $cheque_number) {
                $account_transaction_to = AccountTransaction::query()
                    ->join('accounts', 'accounts.id', '=', 'account_transactions.account_id')
                    ->where('account_transactions.id', $id)
                    ->where('account_transactions.type', 'debit')
                    ->where(function ($query) {
                        $query->whereIn('account_transactions.sub_type', ['deposit', 'fund_transfer'])
                            ->orWhereIn('account_transactions.type', ['deposit', 'fund_transfer']);
                    })
                    ->where('accounts.business_id', $business_id)
                    ->select('account_transactions.*')
                    ->lockForUpdate()
                    ->firstOrFail();

                $account_transaction_from = AccountTransaction::query()
                    ->join('accounts', 'accounts.id', '=', 'account_transactions.account_id')
                    ->where('account_transactions.id', $account_transaction_to->transfer_transaction_id)
                    ->where('accounts.business_id', $business_id)
                    ->select('account_transactions.*')
                    ->lockForUpdate()
                    ->firstOrFail();

                $account_transaction_to->operation_date = $operation_date;
                $account_transaction_to->amount = $amount;
                $account_transaction_to->account_id = $to_account_id;
                $account_transaction_to->cheque_number = $cheque_number;
                $account_transaction_to->save();

                $account_transaction_from->operation_date = $operation_date;
                $account_transaction_from->amount = $amount;
                $account_transaction_from->account_id = $from_account_id;
                $account_transaction_from->cheque_number = $cheque_number;
                $account_transaction_from->save();
            });

            $output = [
                'success' => true,
                'tab' => 'list_deposit_transfer',
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'list_deposit_transfer',
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return Redirect::back()->with('status', $output);
    }

    public function transferPostDatedCheques()
    {
        $this->transactionUtil->transferPostDatedCheques();
    }

    public function chequeObTransfer()
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $request = request();
        $businessId = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));
        if ($businessId <= 0) {
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'message' => 'Business session is not available. Please refresh the page and sign in again.',
            ], 403);
        }

        /*
         * Do not depend on request()->ajax() here.
         * Several production proxies do not preserve X-Requested-With even though
         * this is a genuine DataTables request. The old AJAX-only guard therefore
         * returned 404 and DataTables displayed tn/7. The draw parameter plus the
         * Finance-owned live route is sufficient for this read-only JSON endpoint.
         *
         * Also keep the query compatible with old master databases. Older schemas
         * can lack business_id/deleted_at on transaction_payments (and deleted_at
         * on transactions). Add those predicates only when the columns exist.
         */
        try {
            $schema = DB::getSchemaBuilder();

            if (! $schema->hasTable('transactions') || ! $schema->hasTable('transaction_payments')) {
                return response()->json([
                    'draw' => (int) $request->input('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'finance_message' => 'Cheque opening-balance tables are not available in this database.',
                ]);
            }

            $latestPayment = DB::table('transaction_payments');
            if ($schema->hasColumn('transaction_payments', 'business_id')) {
                $latestPayment->where('business_id', $businessId);
            }
            if ($schema->hasColumn('transaction_payments', 'deleted_at')) {
                $latestPayment->whereNull('deleted_at');
            }
            $latestPayment->select('transaction_id', DB::raw('MAX(id) as payment_id'))
                ->groupBy('transaction_id');

            $accounts = Transaction::joinSub($latestPayment, 'latest_payment', function ($join) {
                    $join->on('latest_payment.transaction_id', '=', 'transactions.id');
                })
                ->join('transaction_payments', 'transaction_payments.id', '=', 'latest_payment.payment_id');

            if ($schema->hasTable('contacts')) {
                $accounts->leftJoin('contacts', function ($join) use ($businessId, $schema) {
                    $join->on('transactions.contact_id', '=', 'contacts.id');
                    if ($schema->hasColumn('contacts', 'business_id')) {
                        $join->where('contacts.business_id', '=', $businessId);
                    }
                });
            }

            $accounts->where('transactions.type', 'cheque_opening_balance')
                ->where('transactions.business_id', $businessId);

            if ($schema->hasColumn('transactions', 'deleted_at')) {
                $accounts->whereNull('transactions.deleted_at');
            }

            $customerSelect = $schema->hasTable('contacts')
                ? 'contacts.name as customer'
                : DB::raw("'-' as customer");

            $accounts->select([
                    $customerSelect,
                    'transaction_payments.cheque_number',
                    'transaction_payments.cheque_date',
                    'transaction_payments.amount',
                    'transaction_payments.bank_name',
                    'transactions.transaction_date',
                    'transactions.id',
                ])
                ->orderByDesc('transactions.transaction_date')
                ->orderByDesc('transactions.id');

            if (! empty($request->start_date) && ! empty($request->end_date)) {
                $accounts->whereBetween('transactions.transaction_date', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            }
            if (! empty($request->user_id)) {
                $accounts->where('transactions.contact_id', $request->user_id);
            }
            if (! empty($request->cheque_number)) {
                $accounts->where('transaction_payments.cheque_number', $request->cheque_number);
            }
            if (! empty($request->bank_name)) {
                $accounts->where('transaction_payments.bank_name', $request->bank_name);
            }
            if ($request->filled('amount')) {
                $accounts->where('transaction_payments.amount', $this->commonUtil->num_uf($request->amount));
            }

            return DataTables::of($accounts)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">'
                        . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                        . __('messages.actions')
                        . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>'
                        . '<ul class="dropdown-menu dropdown-menu-left" role="menu">';
                    $html .= '<li><a data-href="' . route('finance.list-accounts.live.cheque-opening.edit', [$row->id]) . '" data-container="#account_type_modal" class="btn-modal edit_at_button"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                    $html .= '<li><a data-href="' . route('finance.list-accounts.live.cheque-opening.delete', [$row->id]) . '" class="cheque_ob_delete"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>';
                    $html .= '</ul></div>';

                    return $html;
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('cheque_date', '{{@format_date($cheque_date)}}')
                ->addColumn('amount', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="false">' . $this->productUtil->num_f($row->amount) . '</span>';
                })
                ->removeColumn('id')
                ->rawColumns(['amount', 'action'])
                ->make(true);
        } catch (\Throwable $e) {
            Log::error('Finance Cheques Opening Balance DataTable failed', [
                'business_id' => $businessId,
                'database' => DB::connection()->getDatabaseName(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Return valid DataTables JSON instead of HTTP 500/404. This prevents
            // the generic tn/7 alert while leaving a precise server log entry.
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'finance_message' => 'Unable to read Cheques in Hand opening details. Please contact the system administrator with the Finance log entry.',
            ]);
        }
    }

    public function editChequeOb($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) session()->get('user.business_id');
        $latest_payment = DB::table('transaction_payments')
            ->where('business_id', $business_id)
            ->whereNull('deleted_at')
            ->select('transaction_id', DB::raw('MAX(id) as payment_id'))
            ->groupBy('transaction_id');

        $account = Transaction::joinSub($latest_payment, 'latest_payment', function ($join) {
                $join->on('latest_payment.transaction_id', '=', 'transactions.id');
            })
            ->join('transaction_payments', 'transaction_payments.id', '=', 'latest_payment.payment_id')
            ->leftJoin('contacts', function ($join) use ($business_id) {
                $join->on('transactions.contact_id', '=', 'contacts.id')
                    ->where('contacts.business_id', '=', $business_id);
            })
            ->where('transactions.type', 'cheque_opening_balance')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.id', (int) $id)
            ->whereNull('transactions.deleted_at')
            ->select([
                'contacts.name as customer',
                'transaction_payments.cheque_number',
                'transaction_payments.cheque_date',
                'transaction_payments.amount',
                'transaction_payments.bank_name',
                'transactions.transaction_date',
                'transactions.id',
                'transactions.contact_id',
            ])
            ->firstOrFail();

        $customers = Contact::customersDropdown($business_id, false);

        return view('finance::account.partials.editchequeob', compact('account', 'customers'));
    }

    public function updateChequeOb(Request $request, $id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) session()->get('user.business_id');
        $amount = (float) $this->transactionUtil->num_uf($request->input('amount'));

        if ($amount <= 0 || empty($request->input('customer')) || empty($request->input('cheque_number'))) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $id, $business_id, $amount) {
                $contact_id = Contact::where('business_id', $business_id)
                    ->where('id', (int) $request->input('customer'))
                    ->value('id');
                if (empty($contact_id)) {
                    abort(422, 'Invalid customer.');
                }

                $transaction = Transaction::where('business_id', $business_id)
                    ->where('type', 'cheque_opening_balance')
                    ->where('id', (int) $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $payment = TransactionPayment::where('business_id', $business_id)
                    ->where('transaction_id', $transaction->id)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->firstOrFail();

                $transaction->final_total = $amount;
                $transaction->contact_id = $contact_id;
                $transaction->save();

                $payment->amount = $amount;
                $payment->bank_name = $request->input('bank_name');
                $payment->cheque_number = $request->input('cheque_number');
                $payment->cheque_date = $request->input('cheque_date');
                $payment->save();

                $account_transaction_ids = DB::table('account_transactions as AT')
                    ->join('accounts as A', 'A.id', '=', 'AT.account_id')
                    ->where('A.business_id', $business_id)
                    ->where('AT.transaction_id', $transaction->id)
                    ->whereNull('AT.deleted_at')
                    ->pluck('AT.id')
                    ->all();

                if (! empty($account_transaction_ids)) {
                    AccountTransaction::whereIn('id', $account_transaction_ids)->update(['amount' => $amount]);
                    AccountSetting::where(function ($query) use ($account_transaction_ids) {
                        $query->whereIn('at_asset_id', $account_transaction_ids)
                            ->orWhereIn('at_obe_id', $account_transaction_ids);
                    })->update(['amount' => $amount]);
                }
            }, 3);

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return response()->json($output);
    }

    public function deleteChequeOb($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) session()->get('user.business_id');

        try {
            DB::transaction(function () use ($id, $business_id) {
                $transaction = Transaction::where('business_id', $business_id)
                    ->where('type', 'cheque_opening_balance')
                    ->where('id', (int) $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $account_transaction_ids = DB::table('account_transactions as AT')
                    ->join('accounts as A', 'A.id', '=', 'AT.account_id')
                    ->where('A.business_id', $business_id)
                    ->where('AT.transaction_id', $transaction->id)
                    ->whereNull('AT.deleted_at')
                    ->pluck('AT.id')
                    ->all();

                if (! empty($account_transaction_ids)) {
                    // Preserve the existing reversal/audit behaviour, but only for this
                    // business and only once while the source rows are locked.
                    AccountTransaction::whereIn('id', $account_transaction_ids)->lockForUpdate()->get();
                    $this->duplicateTransactions($account_transaction_ids);

                    AccountSetting::where(function ($query) use ($account_transaction_ids) {
                        $query->whereIn('at_asset_id', $account_transaction_ids)
                            ->orWhereIn('at_obe_id', $account_transaction_ids);
                    })->delete();

                    AccountTransaction::whereIn('id', $account_transaction_ids)->update([
                        'deleted_by' => auth()->id(),
                    ]);
                    AccountTransaction::whereIn('id', $account_transaction_ids)->delete();
                }

                TransactionPayment::where('business_id', $business_id)
                    ->where('transaction_id', $transaction->id)
                    ->delete();
                $transaction->delete();
            }, 3);

            $output = [
                'success' => true,
                'msg' => __('lang_v1.deleted_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
