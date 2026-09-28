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
 * Cheque deposits and realisation.
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
 * Methods here: getChequeDeposit, postChequeDeposit, getChequeList, getRealizeChequeList, getRealizeChequeDeposit, postRealizeChequeDeposit
 */
trait HandlesCheques
{
    public function getChequeDeposit()
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) session()->get('user.business_id');
        $cheque_list_service = app(ChequeDepositListService::class);
        $cheque_source_account_ids = $cheque_list_service->chequesInHandAccountIds($business_id);

        // Use the same Finance-owned source-account resolver for opening,
        // listing and posting.  Previous versions opened only when an account
        // was named exactly "Cheques in Hand", while the list itself also
        // accepted operational child accounts. That split caused valid saved
        // cheques to disappear or become non-depositable.
        $account = Account::where('business_id', $business_id)
            ->whereIn('id', $cheque_source_account_ids)
            ->where(function ($active): void {
                $active->where('is_closed', 0)->orWhereNull('is_closed');
            })
            ->orderByRaw("CASE WHEN LOWER(TRIM(name)) = 'cheques in hand' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if (empty($account)) {
            return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">Cheques in Hand account is not available for this business. Please create/enable the account or its Finance account group first.</div></div></div></div>', 200);
        }

        // Finance-owned, business-scoped bank account list.  This does not
        // depend on Customers, Purchase, or any other module controller.
        $to_accounts = app(BankDepositAccountResolver::class)
            ->optionsForBusiness($business_id);

        $subscription = Subscription::active_subscription($business_id);
        $package_details = ! empty($subscription) ? (array) $subscription->package_details : [];
        $mpcs_module = ! empty($package_details['mpcs_module']);
        // Cheques in Hand is a protected source group, so its available
        // balance is always enforced. Kept for view compatibility.
        $allow_over_deposit = false;

        return view('finance::account.cheque_deposit', compact('account', 'to_accounts', 'mpcs_module', 'allow_over_deposit'));
    }

    /**
     * Shows deposit form.// id will treate as type for list page deopsit cheque buttons
     *
     * @param  int  $id
     * @return Response
     */

    public function postChequeDeposit(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) session()->get('user.business_id');

        // S735: Cheque Deposit must be reconfirmed before any accounting entry
        // is created.
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

        $selected_cheque_ids = collect($request->input('select_cheques', []))
            ->filter(function ($id) {
                return is_numeric($id) && (int) $id > 0;
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        if ($selected_cheque_ids->isEmpty()) {
            /*
             * MA-002 (S-611): say WHICH check failed.
             *
             * postChequeDeposit has five ways to fail and they all showed a
             * vague message - "no item found" or "something went wrong" - so a
             * report of "the cheque deposit fails" could mean any of them and
             * there was no way to tell from the screen.
             *
             * Each now names itself. Nothing about when a deposit succeeds or
             * fails has changed; only the wording.
             */
            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => __('account.no_item_found') . ' (no cheques were ticked)',
            ]);
        }

        $has_reviewed = $this->transactionUtil->hasReviewed($request->input('operation_date'));
        if (! empty($has_reviewed)) {
            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => __('lang_v1.review_first'),
            ]);
        }

        $reviewed = $this->transactionUtil->get_review(
            $request->input('operation_date'),
            $request->input('operation_date')
        );
        if (! empty($reviewed)) {
            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => "You can't add a deposit for an already reviewed date",
            ]);
        }

        // S673: same resolver as the deposit paths - uf_date() returns null on a
        // format mismatch, which is what left the Date & Time column blank.
        $operation_date = $this->resolveOperationDate($request->input('operation_date'));
        if (empty($operation_date)) {
            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
                    . ' (the transaction date could not be read: "'
                    . e((string) $request->input('operation_date')) . '")',
            ]);
        }

        $encash = $request->boolean('encash');
        $destination_account_id = (int) $request->input('from_account');
        $note = $request->input('note');
        $is_manager_cash_deposit = $request->boolean('is_manager_cash_deposit') ? 1 : 0;
        if ($is_manager_cash_deposit) {
            $note = trim(($note ?? '') . ' ' . __('account.cash_deposited_by_manager'));
        }

        $required_accounts = Account::where('business_id', $business_id)
            ->whereIn('name', ['Cash', 'Post Dated Cheques'])
            ->get()
            ->keyBy('name');

        $cash_account = $required_accounts->get('Cash');
        $post_dated_account = $required_accounts->get('Post Dated Cheques');

        $cheque_list_service = app(ChequeDepositListService::class);
        $cheque_source_account_ids = $cheque_list_service->chequesInHandAccountIds($business_id);

        $destination_account = $encash
            ? $cash_account
            : Account::where('business_id', $business_id)->NotClosed()->find($destination_account_id);

        if (empty($destination_account) || empty($cheque_source_account_ids)) {
            /*
             * MA-002 (S-611): name the account that is missing.
             *
             * This handler needs three accounts to exist, matched on their
             * EXACT names: 'Cash', 'Cheques in Hand' and 'Post Dated Cheques'.
             * If a business has renamed one - or never had it - the deposit
             * fails with "something went wrong" and nothing indicates that an
             * ACCOUNT is the problem rather than the cheques or the date.
             *
             * On the two tenants I hold, all three exist on every business, so
             * I could not reproduce it here. This makes the screen say which
             * one is absent instead.
             */
            $missing = [];
            if (empty($cheque_source_account_ids)) {
                $missing[] = 'Cheques in Hand / Cheques in Hand account group';
            }
            if (empty($destination_account)) {
                $missing[] = $encash ? 'Cash' : 'the selected deposit account';
            }

            \Log::warning('MA-002: cheque deposit blocked - required account missing', [
                'business_id' => $business_id,
                'missing' => $missing,
                'encash' => $encash,
                'destination_account_id' => $destination_account_id,
            ]);

            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
                    . ' (account not found: ' . implode(', ', $missing)
                    . '. These are matched by exact name.)',
            ]);
        }

        // The selected cheque rows are the amounts leaving the protected
        // Cheques-in-Hand source account(s). One selection may span child
        // accounts, so validate each source independently.
        $required_by_source = DB::table('account_transactions as account_tx')
            ->join('accounts', 'accounts.id', '=', 'account_tx.account_id')
            ->where('accounts.business_id', $business_id)
            ->whereIn('account_tx.account_id', $cheque_source_account_ids)
            ->whereIn('account_tx.id', $selected_cheque_ids->all())
            ->whereNull('account_tx.deleted_at')
            ->groupBy('account_tx.account_id')
            ->select('account_tx.account_id', DB::raw('SUM(account_tx.amount) as required_amount'))
            ->pluck('required_amount', 'account_tx.account_id')
            ->map(static function ($amount) {
                return (float) $amount;
            })
            ->all();

        $balance_result = app(FinanceTransactionGuard::class)
            ->checkMultipleSourceBalances($business_id, $required_by_source, true);

        if (! $balance_result['allowed']) {
            $output = [
                'success' => false,
                'msg' => $balance_result['message'],
            ];

            Log::warning('Finance cheque deposit blocked - insufficient balance', [
                'business_id' => $business_id,
                'source_account_id' => $balance_result['account_id'] ?? null,
                'available' => $balance_result['available'] ?? null,
                'required' => $balance_result['required'] ?? null,
            ]);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json($output);
            }

            return Redirect::back()->with('status', $output);
        }

        $upload_file = null;
        try {
            $upload_directory = public_path('img/account_transaction/' . $business_id);
            if (! is_dir($upload_directory)) {
                mkdir($upload_directory, 0777, true);
            }

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $extension = strtolower($file->getClientOriginalExtension());
                $filename = uniqid('account_', true) . '.' . $extension;
                $relative_path = 'public/img/account_transaction/' . $business_id . '/' . $filename;

                if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                    $image_width = (int) System::getProperty('upload_image_width');
                    $image_height = (int) System::getProperty('upload_image_height');
                    Image::make($file->getRealPath())
                        ->resize($image_width, $image_height)
                        ->save(base_path($relative_path));
                } else {
                    $file->move($upload_directory, $filename);
                }
                $upload_file = $relative_path;
            }

            $total_amount = DB::transaction(function () use (
                $business_id,
                $selected_cheque_ids,
                $operation_date,
                $note,
                $upload_file,
                $is_manager_cash_deposit,
                $destination_account,
                $cheque_source_account_ids,
                $post_dated_account,
                $encash
            ) {
                $eligible_ids = DB::table('account_transactions as account_tx')
                    ->join('accounts', 'accounts.id', '=', 'account_tx.account_id')
                    ->where('accounts.business_id', $business_id)
                    ->whereIn('account_tx.account_id', $cheque_source_account_ids)
                    ->whereIn('account_tx.id', $selected_cheque_ids->all())
                    ->whereNull('account_tx.deleted_at')
                    ->pluck('account_tx.id');

                $account_transactions = AccountTransaction::whereIn('id', $eligible_ids)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $payment_ids = $account_transactions->pluck('transaction_payment_id')->filter()->unique();
                $transaction_payments = TransactionPayment::whereIn('id', $payment_ids)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $total = 0.0;
                foreach ($selected_cheque_ids as $selected_cheque_id) {
                    $account_transaction = $account_transactions->get($selected_cheque_id);
                    if (empty($account_transaction)) {
                        continue;
                    }

                    // S738 #2: Cheques in Hand is the source of truth. A valid
                    // ledger cheque must remain depositable even when an older
                    // Customer/Bulk Payment row is missing, deleted or linked in
                    // a different parent/child shape.
                    $transaction_payment = ! empty($account_transaction->transaction_payment_id)
                        ? $transaction_payments->get($account_transaction->transaction_payment_id)
                        : null;

                    $cheque_meta = $this->financeResolveChequeLedgerMetadata(
                        $account_transaction,
                        $transaction_payment
                    );
                    $cheque_number = $cheque_meta['cheque_number'];
                    $cheque_date = $cheque_meta['cheque_date'];
                    $bank_name = $cheque_meta['bank_name'];

                    // Ledger-authoritative consumed check. Current rows match by
                    // transaction_payment_id; legacy/manual rows match by cheque
                    // number. We deliberately do not trust is_deposited.
                    $already_deposited_query = AccountTransaction::query()
                        ->where('account_id', $account_transaction->account_id)
                        ->where('type', 'credit')
                        ->where('amount', $account_transaction->amount)
                        ->whereNull('deleted_at')
                        ->where(function ($scope) use ($business_id) {
                            $scope->where('business_id', $business_id)
                                ->orWhereNull('business_id');
                        });

                    if (! empty($account_transaction->transaction_payment_id)) {
                        $already_deposited_query->where(
                            'transaction_payment_id',
                            $account_transaction->transaction_payment_id
                        );
                    } elseif ($cheque_number !== '') {
                        $already_deposited_query->where('cheque_number', $cheque_number);
                    } else {
                        $already_deposited_query->where('id', $account_transaction->transfer_transaction_id ?: 0);
                    }

                    if ($already_deposited_query->exists()) {
                        continue;
                    }

                    $amount = (float) $this->commonUtil->num_uf($account_transaction->amount);
                    if ($amount <= 0) {
                        continue;
                    }

                    $transaction_payment_id = $transaction_payment->id ?? $account_transaction->transaction_payment_id;

                    // Credit the SAME Cheques-in-Hand source account that held
                    // this receipt. Copy the cheque identity to the ledger credit
                    // so even rows without transaction_payment_id can never reappear
                    // after they have actually been deposited.
                    $credit = AccountTransaction::createAccountTransaction([
                        'business_id' => $business_id,
                        'amount' => $amount,
                        'account_id' => $account_transaction->account_id,
                        'type' => 'credit',
                        'post_dated_cheque' => 1,
                        'sub_type' => 'deposit',
                        'operation_date' => $operation_date,
                        'created_by' => session()->get('user.id'),
                        'transaction_id' => $account_transaction->transaction_id,
                        'transaction_payment_id' => $transaction_payment_id,
                        'cheque_number' => $cheque_number !== '' ? $cheque_number : null,
                        'cheque_date' => $cheque_date,
                        'bank_name' => $bank_name,
                        'note' => $note,
                        'attachment' => $upload_file,
                        'cash_deposited_by_manager' => $is_manager_cash_deposit,
                    ]);

                    // If this is a legacy/manual Cheques-in-Hand row with no
                    // payment id and no cheque number, preserve a ledger-only
                    // identity so it disappears after this real outgoing credit.
                    if (empty($account_transaction->transaction_payment_id)
                        && $cheque_number === ''
                        && empty($account_transaction->transfer_transaction_id)
                        && ! empty($credit)) {
                        $account_transaction->transfer_transaction_id = $credit->id;
                        $account_transaction->save();
                    }

                    if ((int) $account_transaction->post_dated_cheque === 1) {
                        if (empty($post_dated_account)) {
                            throw new \RuntimeException('Post Dated Cheques account is not available.');
                        }

                        $debit = AccountTransaction::createAccountTransaction([
                            'amount' => $amount,
                            'account_id' => $post_dated_account->id,
                            'type' => 'debit',
                            'post_dated_cheque' => 1,
                            'sub_type' => 'deposit',
                            'operation_date' => $operation_date,
                            'created_by' => session()->get('user.id'),
                            'transaction_payment_id' => $transaction_payment_id,
                            'cheque_number' => $cheque_number !== '' ? $cheque_number : null,
                            'cheque_date' => $cheque_date,
                            'bank_name' => $bank_name,
                            'transfer_transaction_id' => $credit->id,
                            'note' => $note,
                            'attachment' => $upload_file,
                            'cash_deposited_by_manager' => $is_manager_cash_deposit,
                        ]);

                        $credit->transfer_transaction_id = $debit->id;
                        $credit->save();

                        if (! $encash) {
                            DB::table('cheque_deposit_bank')->updateOrInsert(
                                ['account_trans_id' => $debit->id],
                                [
                                    'bank_id' => $destination_account->id,
                                    'cheque_number' => $cheque_number,
                                ]
                            );
                        }
                    } else {
                        $debit = AccountTransaction::createAccountTransaction([
                            'amount' => $amount,
                            'account_id' => $destination_account->id,
                            'type' => 'debit',
                            'sub_type' => 'deposit',
                            'operation_date' => $operation_date,
                            'created_by' => session()->get('user.id'),
                            'transaction_payment_id' => $transaction_payment_id,
                            'cheque_number' => $cheque_number !== '' ? $cheque_number : null,
                            'cheque_date' => $cheque_date,
                            'bank_name' => $bank_name,
                            'transfer_transaction_id' => $credit->id,
                            'note' => $note,
                            'attachment' => $upload_file,
                            'cash_deposited_by_manager' => $is_manager_cash_deposit,
                        ]);

                        $credit->transfer_transaction_id = $debit->id;
                        $credit->save();

                        if (! $encash) {
                            DB::table('cheque_deposit_bank')->updateOrInsert(
                                ['account_trans_id' => $debit->id],
                                [
                                    'bank_id' => $destination_account->id,
                                    'cheque_number' => $cheque_number,
                                ]
                            );
                        }
                    }

                    if (! empty($transaction_payment)) {
                        $transaction_payment->is_deposited = 1;
                        $transaction_payment->save();
                    }
                    $total += $amount;
                }

                return $total;
            }, 3);

            if ($total_amount > 0) {
                $business = Business::find($business_id);
                $msg_template = NotificationTemplate::where('business_id', $business_id)
                    ->where('template_for', 'deposit')
                    ->first();

                if (! empty($business) && ! empty($msg_template)) {
                    $sms_settings = empty($business->sms_settings)
                        ? $this->businessUtil->defaultSmsSettings()
                        : $business->sms_settings;
                    $phones = ! empty($business->sms_settings['msg_phone_nos'])
                        ? explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']))
                        : [];

                    if (! empty($phones)) {
                        $msg = str_replace(
                            ['{account}', '{amount}', '{date}', '{staff}'],
                            [
                                $destination_account->name,
                                $this->productUtil->num_f($total_amount),
                                $request->input('operation_date'),
                                auth()->user()->username,
                            ],
                            $msg_template->sms_body
                        );

                        $this->businessUtil->sendSms([
                            'sms_settings' => $sms_settings,
                            'mobile_number' => implode(',', $phones),
                            'sms_body' => $msg,
                        ], 'deposit');
                    }
                }
            }

            if ($total_amount <= 0) {
                $output = [
                    'success' => false,
                    'msg' => 'No eligible outstanding cheque was found. Please reopen Cheque Deposit and refresh the list.',
                ];
            } else {
                $output = [
                    'success' => true,
                    'msg' => 'Successfully Saved',
                ];
            }
        } catch (\Throwable $e) {
            Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return Redirect::back()->with('status', $output);
    }

    /**
     * Resolve cheque metadata from the Cheques-in-Hand ledger first and use
     * transaction-payment parent/child rows only as enrichment. This keeps the
     * deposit workflow independent from which Customer payment screen created
     * the cheque.
     *
     * @return array{cheque_number:string, cheque_date:mixed, bank_name:string}
     */
    private function financeResolveChequeLedgerMetadata(
        AccountTransaction $accountTransaction,
        ?TransactionPayment $payment = null
    ): array {
        $number = trim((string) ($accountTransaction->cheque_number ?? ''));
        $date = $accountTransaction->cheque_date ?? null;
        $bank = trim((string) ($accountTransaction->bank_name ?? ''));

        $candidates = collect();
        if (! empty($payment)) {
            $candidates->push($payment);

            if (! empty($payment->parent_id)) {
                $parent = TransactionPayment::where('id', $payment->parent_id)->first();
                if (! empty($parent)) {
                    $candidates->push($parent);
                }
            }

            $parentIds = collect([$payment->id, $payment->parent_id ?? null])
                ->filter()
                ->unique()
                ->values();

            if ($parentIds->isNotEmpty()) {
                $children = TransactionPayment::whereIn('parent_id', $parentIds)
                    ->where(function ($query) {
                        $query->where('method', 'cheque')
                            ->orWhereNotNull('cheque_number')
                            ->orWhereNotNull('cheque_date')
                            ->orWhereNotNull('bank_name');
                    })
                    ->orderByDesc('id')
                    ->get();

                $candidates = $candidates->merge($children);
            }
        }

        foreach ($candidates as $candidate) {
            if ($number === '') {
                $number = trim((string) ($candidate->cheque_number ?? ''));
            }
            if (empty($date)) {
                $date = $candidate->cheque_date ?? $candidate->paid_on ?? null;
            }
            if ($bank === '') {
                $bank = trim((string) ($candidate->bank_name ?? ''));
            }

            if ($number !== '' && ! empty($date) && $bank !== '') {
                break;
            }
        }

        if (empty($date)) {
            $date = $accountTransaction->operation_date ?? null;
        }

        return [
            'cheque_number' => $number,
            'cheque_date' => $date,
            'bank_name' => $bank,
        ];
    }

    /**
     * Shows deposit form.// id will treate as type for list page deopsit cheque buttons
     *
     * @param  request  $id
     * @return Response
     */

    public function getChequeList(Request $request)
    {
        if (
            ! $this->userCan('account.access') &&
            ! $this->userCan('purchase.create') &&
            ! $this->userCan('purchase.payments') &&
            ! $this->userCan('sell.create') &&
            ! $this->userCan('sell.payments') &&
            ! $this->userCan('expense.add') &&
            ! $this->userCan('expense.add_payment') &&
            ! $this->userCan('add.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        // S723 #3: this is a Finance-owned HTML fragment endpoint.  Do not
        // depend on request()->ajax(); some server/proxy combinations strip the
        // X-Requested-With header even though the request genuinely comes from
        // the Cheque Deposit modal.  Returning the fragment is safe because the
        // route is already authenticated and permission-checked above.
        $business_id = (int) $request->session()->get('user.business_id');
        $payment_type = (string) $request->input('payment_type', 'cheque');

        if ($payment_type === 'pre_payments'
            && ! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_cheque_writing')) {
            return view('finance::account.partials.cheque_list', [
                'cheque_lists' => collect(),
                'payment_type' => $payment_type,
                'is_truncated' => false,
            ]);
        }

        $selected_cheque_numbers = collect(explode(',', (string) $request->input('selected_cheque_numbers', '')))
            ->map(static function ($chequeNumber): string {
                return trim((string) $chequeNumber);
            })
            ->filter()
            ->values()
            ->all();

        $result = app(ChequeDepositListService::class)->search($business_id, [
            'payment_type' => $payment_type,
            'contact_id' => (int) $request->input('contact_id', 0),
            'selected_cheque_numbers' => $selected_cheque_numbers,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'start_date_created' => $request->input('start_date_created'),
            'end_date_created' => $request->input('end_date_created'),
            'cheque_no' => $request->input('cheque_no'),
            'amount' => $request->input('amount'),
        ]);

        return view('finance::account.partials.cheque_list', [
            'cheque_lists' => $result['rows'],
            'payment_type' => $payment_type,
            'is_truncated' => $result['is_truncated'],
        ]);
    }

    /**
     * CH1 IS2124 #2: Finance-owned Cheque Deposit filter options.
     *
     * Cheque Deposit must work even when Contacts/Customers menu modules are
     * disabled.  Do not call /customer-payment-information from this form.
     */
    public function getChequeDepositFilterOptions(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        // S723 #3: same proxy-safe rule as getChequeList().  This route always
        // returns JSON and is already protected by auth/permission middleware.
        $business_id = (int) $request->session()->get('user.business_id');
        $field = (string) $request->input('field', '');

        $values = app(ChequeDepositListService::class)->filterOptions($business_id, $field, [
            'payment_type' => (string) $request->input('payment_type', 'cheque'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'start_date_created' => $request->input('start_date_created'),
            'end_date_created' => $request->input('end_date_created'),
        ]);

        return response()->json(['data' => $values]);
    }

    public function getRealizeChequeList(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {

            $business_id = session()->get('user.business_id');
            $start_date  = ! empty($request->start_date) ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
            $end_date    = ! empty($request->end_date) ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

            // $start_date_create = !empty($request->start_date_created) ? Carbon::parse($request->start_date_created)->format('Y-m-d') : date('Y-m-d');
            // $end_date_create = !empty($request->end_date_created) ? Carbon::parse($request->end_date_created)->format('Y-m-d') : date('Y-m-d');

            $amount    = $request->amount;
            $cheque_no = $request->cheque_no;

            $cheque_account = Account::where('business_id', $business_id)->where('name', 'Cheques in Hand')->first();

            // update this query and addedgiven bank for get data on cheque realize popup table by virtual it professional referance docs number 7338
            // Get Issued/Post Dated Cheques accounts (for current business)
            $issued_pd_cheque_account = Account::where('business_id', $business_id)->where('name', 'Issued Post Dated Cheques')->first();
            $post_dated_cheque_account = Account::where('business_id', $business_id)->where('name', 'Post Dated Cheques')->first();

            $deposit_bank_query = DB::table('cheque_deposit_bank')
                ->select('account_trans_id', DB::raw('MAX(bank_id) as bank_id'))
                ->groupBy('account_trans_id');

            $query = AccountTransaction::join('transaction_payments', 'account_transactions.transaction_payment_id', 'transaction_payments.id')
                ->leftJoinSub($deposit_bank_query, 'cheque_deposit_bank', function ($join) {
                    $join->on('cheque_deposit_bank.account_trans_id', '=', 'account_transactions.id');
                })
                ->leftJoin('accounts', 'account_transactions.account_id', 'accounts.id')
                ->leftJoin('accounts as related_accounts', function ($join) use ($business_id) {
                    $join->on('transaction_payments.related_account_id', '=', 'related_accounts.id')
                        ->where('related_accounts.business_id', '=', $business_id);
                })
                ->leftJoin('account_transactions as given_bank', 'cheque_deposit_bank.account_trans_id', '=', 'given_bank.id')
                ->leftJoin('contacts', 'transaction_payments.payment_for', 'contacts.id')
                ->leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->leftJoin('expense_categories as expense_categories', 'transactions.expense_category_id', '=', 'expense_categories.id')
                ->where('accounts.business_id', $business_id);
            if ($cheque_account) {
                $query->where('account_transactions.account_id', '!=', $cheque_account->id);
            }
            $query
                ->where(function ($q) use ($issued_pd_cheque_account, $post_dated_cheque_account) {
                    // CASE 1: Regular received cheques (debit, deposit) OR
                    // CASE 2: Issued PD cheques to suppliers (credit to Issued Post Dated Cheques account)
                    $q->where(function ($q2) {
                        // Regular cheques - received from customers
                        $q2->where('transaction_payments.method', 'cheque')
                           ->where('account_transactions.type', 'debit')
                           ->where('account_transactions.sub_type', 'deposit');
                    });
                    if ($issued_pd_cheque_account) {
                        $q->orWhere(function ($q2) use ($issued_pd_cheque_account, $post_dated_cheque_account) {
                            // Issued PD cheques to suppliers - support both correctly posted
                            // rows and legacy expense/purchase rows still sitting in Post Dated Cheques.
                            $q2->where('account_transactions.type', 'credit')
                               ->where(function ($q3) use ($issued_pd_cheque_account, $post_dated_cheque_account) {
                                   $q3->where(function ($q4) use ($issued_pd_cheque_account) {
                                       $q4->where('account_transactions.account_id', $issued_pd_cheque_account->id)
                                          ->where(function ($q5) {
                                              $q5->where('transaction_payments.post_dated_cheque', 1)
                                                 ->orWhere('transaction_payments.update_post_dated_cheque', 1);
                                          });
                                   });

                                   if ($post_dated_cheque_account) {
                                       $q3->orWhere(function ($q4) use ($post_dated_cheque_account) {
                                           $q4->where('account_transactions.account_id', $post_dated_cheque_account->id)
                                              ->whereIn('transactions.type', ['expense', 'purchase', 'property_purchase']);
                                       });
                                   }
                               });
                        });
                    }
                })
                ->where(function ($q) {
                    // For regular cheques, must be deposited; for PD cheques, don't require deposit
                    $q->where(function ($q2) {
                        $q2->where('transaction_payments.method', 'cheque')
                           ->where('transaction_payments.is_deposited', 1);
                    })->orWhere(function ($q2) {
                        $q2->where(function ($q3) {
                               $q3->where('transaction_payments.post_dated_cheque', 1)
                                  ->orWhere('transaction_payments.update_post_dated_cheque', 1);
                           })->orWhere(function ($q3) {
                               $q3->whereIn('transactions.type', ['expense', 'purchase', 'property_purchase'])
                                  ->where('account_transactions.type', 'credit')
                                  ->where('accounts.name', 'Post Dated Cheques');
                           });
                    });
                })
                ->whereNotNull('transaction_payments.cheque_date')
                ->whereNull('transaction_payments.deleted_at')
                ->where('transaction_payments.is_realized', 0);
            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereDate('transaction_payments.cheque_date', '>=', $start_date);
                $query->whereDate('transaction_payments.cheque_date', '<=', $end_date);
            }

            // if (!empty($start_date_create) && !empty($end_date_create)) {
            //     $query->whereDate('account_transactions.operation_date', '>=', $start_date_create);
            //     $query->whereDate('account_transactions.operation_date', '<=', $end_date_create);
            // }

            if (! empty($amount)) {
                $query->where('transaction_payments.amount', $amount);
            }

            if (! empty($cheque_no)) {
                $query->where('transaction_payments.cheque_number', $cheque_no);
            }

            if (! empty($request->realize_cheque_bank)) {
                if ($issued_pd_cheque_account && (int) $request->realize_cheque_bank === (int) $issued_pd_cheque_account->id) {
                    $query->where(function ($q) use ($issued_pd_cheque_account, $post_dated_cheque_account) {
                        $q->where('account_transactions.account_id', $issued_pd_cheque_account->id);

                        if ($post_dated_cheque_account) {
                            $q->orWhere(function ($q2) use ($post_dated_cheque_account) {
                                $q2->where('account_transactions.account_id', $post_dated_cheque_account->id)
                                   ->whereIn('transactions.type', ['expense', 'purchase', 'property_purchase']);
                            });
                        }
                    });
                } else {
                    $query->where('accounts.id', $request->realize_cheque_bank);
                }
            }

            // add given bank and receiving bank details from accounts table by virtual it professional referance docs number 7338
            // sending bank = which bank to issue the cheque
            // receiving bank = which bank to receive the fund
            $cheque_lists = $query->select(
                DB::raw('COALESCE(contacts.name, expense_categories.name, "") as customer_name'),
                'transaction_payments.cheque_number',
                'transaction_payments.cheque_date',
                'transaction_payments.paid_on',
                DB::raw('CASE
                    WHEN (
                        transactions.type IN ("expense", "purchase", "property_purchase")
                        AND (
                            transaction_payments.post_dated_cheque = 1
                            OR transaction_payments.update_post_dated_cheque = 1
                            OR accounts.name = "Post Dated Cheques"
                            OR accounts.name = "Issued Post Dated Cheques"
                        )
                    )
                    THEN ' . (int) optional($issued_pd_cheque_account)->id . '
                    ELSE accounts.id
                END as deposit_to_bank_id'),
                DB::raw('CASE
                    WHEN (
                        transactions.type IN ("expense", "purchase", "property_purchase")
                        AND (
                            transaction_payments.post_dated_cheque = 1
                            OR transaction_payments.update_post_dated_cheque = 1
                            OR accounts.name = "Post Dated Cheques"
                            OR accounts.name = "Issued Post Dated Cheques"
                        )
                    )
                    THEN "' . addslashes(optional($issued_pd_cheque_account)->name ?? 'Issued Post Dated Cheques') . '"
                    ELSE accounts.name
                END as deposit_to_bank_name'),
                'related_accounts.id as given_bank_id',
                DB::raw('COALESCE(related_accounts.name, transaction_payments.bank_name, accounts.name) as given_bank_name'),
                'given_bank.id as sending_bank',
                'given_bank.transfer_transaction_id as receiving_bank',
                'account_transactions.amount',
                'account_transactions.id as t_id',
                'transaction_payments.id as tp_id',
                'transactions.type as source_transaction_type',
            )->get();

            $business_details = Business::where('id', $business_id)->select('currency_precision')->first();

            return view('finance::account.partials.realized_cheque_list') // modified by iftekhar
                ->with(compact('cheque_lists', 'business_details'));
        }
    }

    public function getRealizeChequeDeposit()
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = session()->get('user.business_id');

        $to_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->where('accounts.business_id', $business_id)
            ->whereIn('account_groups.name', ['Bank Account'])
            ->pluck('accounts.name', 'accounts.id');

        return view('finance::account.realize_cheque_deposit')
            ->with(compact('to_accounts'));
    }

    public function postRealizeChequeDeposit(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) session()->get('user.business_id');
        $selected_cheque_ids = collect($request->input('select_cheques', []))
            ->filter(function ($id) {
                return is_numeric($id) && (int) $id > 0;
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        if ($selected_cheque_ids->isEmpty()) {
            return Redirect::back()->with('status', [
                'success' => false,
                'msg' => 'Please select at least one cheque to realize.',
            ]);
        }

        try {
            DB::transaction(function () use ($business_id, $selected_cheque_ids, $request) {
                $eligible_ids = DB::table('account_transactions as account_tx')
                    ->join('accounts', 'accounts.id', '=', 'account_tx.account_id')
                    ->where('accounts.business_id', $business_id)
                    ->whereIn('account_tx.id', $selected_cheque_ids->all())
                    ->whereNull('account_tx.deleted_at')
                    ->pluck('account_tx.id');

                $account_transactions = AccountTransaction::whereIn('id', $eligible_ids)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $payment_ids = $account_transactions->pluck('transaction_payment_id')->filter()->unique();
                $transaction_payments = TransactionPayment::whereIn('id', $payment_ids)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $transaction_ids = $transaction_payments->pluck('transaction_id')->filter()->unique();
                $transactions = Transaction::with('contact')
                    ->where('business_id', $business_id)
                    ->whereIn('id', $transaction_ids)
                    ->get()
                    ->keyBy('id');

                $user_ids = $transactions->pluck('created_by')->filter()->unique();
                $users = User::whereIn('id', $user_ids)->get()->keyBy('id');

                $expense_category_ids = $transactions->pluck('expense_category_id')->filter()->unique();
                $expense_categories = DB::table('expense_categories')
                    ->whereIn('id', $expense_category_ids)
                    ->pluck('name', 'id');

                $business_accounts = Account::where('business_id', $business_id)->get();
                $accounts_by_id = $business_accounts->keyBy('id');
                $issued_pd_cheque_account = $business_accounts->firstWhere('name', 'Issued Post Dated Cheques');
                $post_dated_cheque_account = $business_accounts->firstWhere('name', 'Post Dated Cheques');

                $deposit_bank_rows = DB::table('cheque_deposit_bank')
                    ->whereIn('account_trans_id', $eligible_ids)
                    ->get()
                    ->keyBy('account_trans_id');

                foreach ($selected_cheque_ids as $selected_cheque_id) {
                    $account_transaction = $account_transactions->get($selected_cheque_id);
                    if (empty($account_transaction) || empty($account_transaction->transaction_payment_id)) {
                        continue;
                    }

                    $transaction_payment = $transaction_payments->get($account_transaction->transaction_payment_id);
                    if (empty($transaction_payment) || (int) $transaction_payment->is_realized === 1) {
                        continue;
                    }

                    $realize_date = $request->input('paid_on_' . $selected_cheque_id);
                    if (empty($realize_date)) {
                        throw new \InvalidArgumentException('Realize date is required.');
                    }

                    $transaction = $transactions->get($transaction_payment->transaction_id);
                    $supplier_or_payee = '';
                    if (! empty($transaction)) {
                        if (! empty($transaction->contact)) {
                            $supplier_or_payee = $transaction->contact->name;
                        } elseif (! empty($transaction->expense_for)) {
                            $supplier_or_payee = $transaction->expense_for;
                        } elseif (! empty($transaction->created_by) && $users->has($transaction->created_by)) {
                            $created_by_user = $users->get($transaction->created_by);
                            $supplier_or_payee = trim(implode(' ', array_filter([
                                $created_by_user->surname ?? null,
                                $created_by_user->first_name ?? null,
                                $created_by_user->last_name ?? null,
                                $created_by_user->username ?? null,
                            ])));
                        } elseif (! empty($transaction->expense_category_id)) {
                            $supplier_or_payee = (string) ($expense_categories[$transaction->expense_category_id] ?? '');
                        }
                    }

                    $bank_account = $accounts_by_id->get($transaction_payment->related_account_id);
                    $bank_name = ! empty($bank_account)
                        ? $bank_account->name
                        : (string) ($transaction_payment->bank_name ?? '');

                    $is_legacy_issued_pd_cheque = ! empty($post_dated_cheque_account)
                        && (int) $account_transaction->account_id === (int) $post_dated_cheque_account->id
                        && $account_transaction->type === 'credit'
                        && ! empty($transaction)
                        && in_array($transaction->type, ['expense', 'purchase', 'property_purchase'], true);

                    $is_issued_pd_cheque = ! empty($issued_pd_cheque_account)
                        && (int) $account_transaction->account_id === (int) $issued_pd_cheque_account->id
                        && $account_transaction->type === 'credit';

                    if ($is_issued_pd_cheque || $is_legacy_issued_pd_cheque) {
                        if (empty($issued_pd_cheque_account)) {
                            throw new \RuntimeException('Issued Post Dated Cheques account is not available.');
                        }

                        $original_bank_id = (int) ($transaction_payment->related_account_id ?? 0);
                        $requested_bank_id = (int) $request->input('realize_cheque_bank_' . $selected_cheque_id);

                        if (
                            empty($original_bank_id)
                            && $requested_bank_id > 0
                            && $requested_bank_id !== (int) $issued_pd_cheque_account->id
                            && $requested_bank_id !== (int) optional($post_dated_cheque_account)->id
                            && $accounts_by_id->has($requested_bank_id)
                        ) {
                            $original_bank_id = $requested_bank_id;
                        }

                        if (empty($original_bank_id) && ! empty($transaction_payment->bank_name)) {
                            $normalized_bank_name = strtolower(trim($transaction_payment->bank_name));
                            $matched_bank = $business_accounts->first(function ($account) use ($normalized_bank_name) {
                                return strtolower(trim($account->name)) === $normalized_bank_name;
                            });
                            if (empty($matched_bank)) {
                                $matched_bank = $business_accounts->first(function ($account) use ($normalized_bank_name) {
                                    return $normalized_bank_name !== ''
                                        && str_contains(strtolower($account->name), $normalized_bank_name);
                                });
                            }
                            $original_bank_id = (int) optional($matched_bank)->id;
                        }

                        $debit_ipdc = AccountTransaction::createAccountTransaction([
                            'amount' => $account_transaction->amount,
                            'account_id' => $issued_pd_cheque_account->id,
                            'type' => 'debit',
                            'sub_type' => 'cheque_realize',
                            'cheque_number' => $transaction_payment->cheque_number,
                            'cheque_date' => $transaction_payment->cheque_date,
                            'operation_date' => $realize_date,
                            'created_by' => session()->get('user.id'),
                            'transaction_payment_id' => $transaction_payment->id,
                            'note' => $supplier_or_payee . "\nPost dated Cheque Issued from Bank " . $bank_name,
                        ]);

                        if ($original_bank_id > 0 && $accounts_by_id->has($original_bank_id)) {
                            $credit_bank = AccountTransaction::createAccountTransaction([
                                'amount' => $account_transaction->amount,
                                'account_id' => $original_bank_id,
                                'type' => 'credit',
                                'sub_type' => 'cheque_realize',
                                'cheque_number' => $transaction_payment->cheque_number,
                                'cheque_date' => $transaction_payment->cheque_date,
                                'operation_date' => $realize_date,
                                'created_by' => session()->get('user.id'),
                                'transaction_payment_id' => $transaction_payment->id,
                                'note' => $supplier_or_payee . "\nPost dated Cheque Issued",
                                'transfer_transaction_id' => $debit_ipdc->id,
                            ]);
                            $debit_ipdc->transfer_transaction_id = $credit_bank->id;
                            $debit_ipdc->save();
                        } else {
                            Log::warning('Legacy issued PD cheque realized without resolved original bank account', [
                                'account_transaction_id' => $account_transaction->id,
                                'transaction_payment_id' => $transaction_payment->id,
                            ]);
                        }
                    } elseif ((int) $account_transaction->post_dated_cheque === 1) {
                        if (empty($post_dated_cheque_account)) {
                            continue;
                        }

                        $deposit_bank = $deposit_bank_rows->get($account_transaction->id);
                        $bank_id = (int) optional($deposit_bank)->bank_id;
                        if ($bank_id <= 0 || ! $accounts_by_id->has($bank_id)) {
                            continue;
                        }

                        $credit = AccountTransaction::createAccountTransaction([
                            'amount' => $account_transaction->amount,
                            'account_id' => $post_dated_cheque_account->id,
                            'type' => 'credit',
                            'sub_type' => 'cheque_realize',
                            'cheque_number' => $transaction_payment->cheque_number,
                            'cheque_date' => $transaction_payment->cheque_date,
                            'operation_date' => $realize_date,
                            'created_by' => session()->get('user.id'),
                            'transaction_payment_id' => $transaction_payment->id,
                        ]);

                        $debit = AccountTransaction::createAccountTransaction([
                            'amount' => $account_transaction->amount,
                            'account_id' => $bank_id,
                            'type' => 'debit',
                            'sub_type' => 'cheque_realize',
                            'cheque_number' => $transaction_payment->cheque_number,
                            'cheque_date' => $transaction_payment->cheque_date,
                            'operation_date' => $realize_date,
                            'created_by' => session()->get('user.id'),
                            'transaction_payment_id' => $transaction_payment->id,
                            'transfer_transaction_id' => $credit->id,
                        ]);

                        $credit->transfer_transaction_id = $debit->id;
                        $credit->save();
                    }

                    $transaction_payment->is_realized = 1;
                    $transaction_payment->paid_on = $realize_date;
                    $transaction_payment->save();
                }
            }, 3);

            $output = [
                'success' => true,
                'msg' => 'Successfully Saved',
            ];
        } catch (\Throwable $e) {
            Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return Redirect::back()->with('status', $output);
    }

    /**
     * Shows deposit form.// id will treate as type for list page deopsit cash and card buttons
     *
     * @param  int  $id
     * @return Response
     */
}
