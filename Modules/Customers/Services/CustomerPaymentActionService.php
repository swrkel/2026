<?php

namespace Modules\Customers\Services;

use App\Account;
use App\AccountTransaction;
use App\TransactionPayment;
use App\BusinessLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Customers\Support\SchemaCache;
use Modules\Customers\Entities\Customer;

class CustomerPaymentActionService
{
    /** @var array<int, array> */
    protected array $paymentSettingsCache = [];

    /** @var array<int, array> */
    protected array $paymentMethodsCache = [];
    public function formData(Customer $customer, string $action): array
    {
        $businessId = (int) request()->session()->get('user.business_id');
        $ledgerService = app(CustomerLedgerService::class);
        $summary = $ledgerService->ledgerSummary($businessId, (int) $customer->id, $customer);

        return [
            'totalDue' => (float) ($summary['balance'] ?? 0),
            'paymentMethods' => $this->paymentMethodsForAction($businessId, $action),
            'accountOptions' => [],
            // Account options are loaded only after a method is selected.
            // Avoid preloading every account group while the modal is opening.
            'paymentAccountMap' => [],
            'defaultAccountId' => null,
            'today' => Carbon::now()->format('m/d/Y'),
            'todayIso' => Carbon::now()->format('Y-m-d'),
            'action' => $action,
            // S710 v2: show the configured next reference on Customer Pay Due.
            'paymentReferencePreview' => $action === 'pay_due'
                ? app(CustomerPaymentReferenceService::class)->preview('customer_pay_due', $businessId, Carbon::now())
                : null,
        ];
    }

    public function paymentMethods(?int $businessId = null): array
    {
        $businessId = $businessId ?: (int) request()->session()->get('user.business_id');

        if (array_key_exists($businessId, $this->paymentMethodsCache)) {
            return $this->paymentMethodsCache[$businessId];
        }

        // S410: show only methods enabled in Super Admin > All Businesses
        // > Manage > Payment Method. Do not expose inactive custom methods.
        $methods = [];

        foreach ($this->paymentAccountSettings($businessId) as $key => $value) {
            if ($key === 'location_id' || $key === '' || !is_array($value)) {
                continue;
            }

            // Customer payment dropdown should not show unused custom payment slots.
            if (strpos($key, 'custom_pay_') === 0) {
                continue;
            }

            if (!empty($value['is_enabled']) && (int) $value['is_enabled'] === 1) {
                $methods[$key] = $this->paymentMethodLabel($key);
            }
        }

        return $this->paymentMethodsCache[$businessId] = $methods;
    }

    /**
     * IS2297: Pay Due is a receipt against an existing customer balance. These
     * accounting-only/non-receipt methods must not be offered there even when
     * they are globally enabled for other sales/purchase workflows.
     *
     * Keep the filter action-specific so Advance Payment and any other existing
     * workflow preserve their previous payment-method behaviour.
     */
    protected function paymentMethodsForAction(int $businessId, string $action): array
    {
        $methods = $this->paymentMethods($businessId);

        if ($action !== 'pay_due') {
            return $methods;
        }

        $blocked = [
            'credit',
            'credit_sale',
            'credit_sales',
            'own_card',
            'own_cards',
            'pre_payment',
            'pre_payments',
            'prepayment',
            'prepayments',
        ];

        foreach ($methods as $method => $label) {
            $methodToken = $this->normalisePaymentMethodToken((string) $method);
            $labelToken = $this->normalisePaymentMethodToken((string) $label);

            if (in_array($methodToken, $blocked, true) || in_array($labelToken, $blocked, true)) {
                unset($methods[$method]);
            }
        }

        return $methods;
    }

    protected function normalisePaymentMethodToken(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: '';
        return trim($value, '_');
    }

    protected function standardPaymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'card' => 'Card',
            'bank' => 'Bank',
            'bank_deposit' => 'Bank Deposit',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'custom_pay_1' => 'Custom Payment 1',
            'custom_pay_2' => 'Custom Payment 2',
            'custom_pay_3' => 'Custom Payment 3',
            'custom_pay_4' => 'Custom Payment 4',
            'custom_pay_5' => 'Custom Payment 5',
        ];
    }

    protected function paymentMethodLabel(string $method): string
    {
        $labels = $this->standardPaymentMethods();
        return $labels[$method] ?? ucwords(str_replace('_', ' ', $method));
    }

    public function paymentAccountOptions(int $businessId, ?string $method = null): array
    {
        if (!class_exists(Account::class) || !SchemaCache::hasTable('accounts')) {
            return [];
        }

        if (empty($method)) {
            return [];
        }

        /*
         * S410 FIX 006
         * Super Admin > All Business > Manage > Payment Method stores the
         * selected value under default_payment_accounts[method][account]. In
         * this ERP screen that field is labelled "Default Account Groups" and
         * contains an account group id, not always one single account id.
         *
         * Therefore, after selecting a payment method, the Customer Pay Due
         * popup must show every active account that belongs to the linked
         * account group. We also keep a safe fallback for older tenants where
         * the same field may contain a direct accounts.id value.
         */
        $linkedId = $this->mappedAccountIdForMethod($businessId, $method);
        if (empty($linkedId)) {
            return [];
        }

        $query = Account::where('business_id', $businessId);
        if (SchemaCache::hasColumn('accounts', 'is_closed')) {
            $query->where(function ($q) {
                $q->where('is_closed', 0)->orWhereNull('is_closed');
            });
        }

        $query->where(function ($q) use ($linkedId) {
            // Current Super Admin mapping: account group id saved into
            // accounts.asset_type. This returns all accounts linked to the
            // selected payment method's account group.
            if (SchemaCache::hasColumn('accounts', 'asset_type')) {
                $q->where('asset_type', $linkedId);
            }

            // Backward-compatible fallback: older configurations may have
            // saved a direct accounts.id instead of an account group id.
            $q->orWhere('id', $linkedId);
        });

        return $query->orderBy('name')->pluck('name', 'id')->toArray();
    }

    // IS2079 2026-08-21: keep exactly one paymentAccountMap() implementation.
    public function paymentAccountMap(int $businessId): array
    {
        $map = [];
        foreach ($this->paymentMethods($businessId) as $method => $label) {
            $map[$method] = $this->paymentAccountOptions($businessId, $method);
        }

        return $map;
    }

    public function defaultPaymentAccountId(int $businessId, ?string $method = null): ?int
    {
        if (!class_exists(Account::class) || !SchemaCache::hasTable('accounts')) {
            return null;
        }

        if (!empty($method)) {
            $mapped = $this->mappedAccountIdForMethod($businessId, $method);
            if (!empty($mapped)) {
                return (int) $mapped;
            }
        }

        $account = Account::where('business_id', $businessId)
            ->whereIn('name', ['Cash', 'Cash Account'])
            ->first();

        if (!$account) {
            $account = Account::where('business_id', $businessId)->orderBy('id')->first();
        }

        return $account ? (int) $account->id : null;
    }

    public function mappedAccountIdForMethod(int $businessId, string $method): ?int
    {
        $settings = $this->paymentAccountSettings($businessId);
        $accountId = $settings[$method]['account'] ?? null;

        return !empty($accountId) ? (int) $accountId : null;
    }

    protected function paymentAccountSettings(int $businessId): array
    {
        if (array_key_exists($businessId, $this->paymentSettingsCache)) {
            return $this->paymentSettingsCache[$businessId];
        }

        $settings = [];

        if (!class_exists(BusinessLocation::class) || !SchemaCache::hasTable('business_locations')) {
            return $this->paymentSettingsCache[$businessId] = $settings;
        }

        $query = BusinessLocation::where('business_id', $businessId);

        if (SchemaCache::hasColumn('business_locations', 'is_active')) {
            $query->where(function ($q) {
                $q->where('is_active', 1)->orWhereNull('is_active');
            });
        }

        // Merge all business locations, instead of depending on only the first
        // location. This prevents Card/Bank/Transfer accounts being missed when
        // they are configured on another location.
        foreach ($query->orderBy('id')->get() as $location) {
            if (empty($location->default_payment_accounts)) {
                continue;
            }

            $decoded = json_decode($location->default_payment_accounts, true) ?: [];
            foreach ($decoded as $key => $value) {
                if ($key === 'location_id' || !is_array($value)) {
                    continue;
                }

                if (!isset($settings[$key])) {
                    $settings[$key] = $value;
                    continue;
                }

                // Prefer the first non-empty account mapping found.
                if (empty($settings[$key]['account']) && !empty($value['account'])) {
                    $settings[$key]['account'] = $value['account'];
                }

                if (!empty($value['is_enabled'])) {
                    $settings[$key]['is_enabled'] = $value['is_enabled'];
                }
            }
        }

        return $this->paymentSettingsCache[$businessId] = $settings;
    }

    protected function defaultLocation(int $businessId)
    {
        if (!class_exists(BusinessLocation::class) || !SchemaCache::hasTable('business_locations')) {
            return null;
        }

        $query = BusinessLocation::where('business_id', $businessId);

        if (SchemaCache::hasColumn('business_locations', 'is_active')) {
            $query->where(function ($q) {
                $q->where('is_active', 1)->orWhereNull('is_active');
            });
        }

        return $query->orderBy('id')->first();
    }

    public function acknowledge(Request $request, Customer $customer, string $action): array
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');
        $amount = $this->cleanAmount($request->input('amount'));

        if ($amount <= 0) {
            return ['success' => 0, 'msg' => 'Please enter a valid amount.'];
        }

        $paymentDate = $this->parseDate($request->input('payment_date') ?: $request->input('date'));
        $method = trim((string) $request->input('method'));
        $accountId = (int) $request->input('account_id');
        $note = trim((string) $request->input('note'));
        $chequeNumber = trim((string) $request->input('cheque_number'));
        $bankName = trim((string) $request->input('bank_name'));
        $chequeDate = $request->input('cheque_date');

        if (empty($method)) {
            return ['success' => 0, 'msg' => 'Please select Payment Method.'];
        }

        if (!array_key_exists($method, $this->paymentMethodsForAction($businessId, $action))) {
            return ['success' => 0, 'msg' => 'Selected payment method is not available for this customer action.'];
        }

        if (empty($accountId)) {
            return ['success' => 0, 'msg' => 'Please select Payment Account.'];
        }

        if (!array_key_exists($accountId, $this->paymentAccountOptions($businessId, $method))) {
            return ['success' => 0, 'msg' => 'Selected payment account is not linked with the selected payment method.'];
        }

        if ($this->requiresBankChequeDetails($method)) {
            if ($chequeNumber === '' || $bankName === '' || empty($chequeDate)) {
                return ['success' => 0, 'msg' => 'Cheque No, Bank and Cheque Date are required for this payment method.'];
            }
        } else {
            $chequeNumber = '';
            $bankName = '';
            $chequeDate = null;
        }

        $label = $this->label($action);

        /*
         | IS2264: Pay Due is saved through an AJAX popup. The button is disabled
         | client-side, but a fast double-click, Enter key retry or browser/network
         | resubmission can still reach PHP twice. Use the form's one-time token as
         | an idempotency guard so only the first request may write payment/ledger
         | rows. No database schema change is required.
         */
        $submissionToken = $this->normaliseSubmissionToken($request->input('submission_token'));
        if (!$this->reserveSubmissionToken($businessId, (int) $customer->id, $action, $submissionToken)) {
            return [
                'success' => 0,
                'msg' => 'This payment is already being processed or has already been saved. Please refresh before trying again.',
            ];
        }

        $paymentId = null;

        try {
            DB::transaction(function () use ($businessId, $userId, $customer, $amount, $paymentDate, $method, $accountId, $note, $action, $label, $chequeNumber, $bankName, $chequeDate, &$paymentId) {
                $paymentId = $this->createTransactionPayment($businessId, $userId, $customer, $amount, $paymentDate, $method, $accountId, $note, $action, $chequeNumber, $bankName, $chequeDate);

                if ($action === 'loan_to_customer') {
                    $this->createContactLedger($businessId, $userId, $customer->id, $amount, 'debit', $paymentDate, $note ?: $label, null, $paymentId);
                    $this->postAccounts($businessId, $userId, $amount, $paymentDate, $accountId, 'loan_to_customer', null, $paymentId, $note ?: $label);
                } else {
                    $this->createContactLedger($businessId, $userId, $customer->id, $amount, 'credit', $paymentDate, $note ?: $label, null, $paymentId);
                    $this->postAccounts($businessId, $userId, $amount, $paymentDate, $accountId, $action, null, $paymentId, $note ?: $label);
                }
            });
        } catch (\Throwable $e) {
            // A failed first attempt must remain retryable with the same open form.
            $this->releaseSubmissionToken($businessId, (int) $customer->id, $action, $submissionToken);
            throw $e;
        }

        /*
         | S773: Pay Due and Advance Payment were saving correctly but never
         | reached the existing payment_received notification pipeline.  Keep
         | notification delivery strictly after the DB commit: an SMS/WhatsApp
         | gateway failure must never make a valid payment look unsaved.
         */
        if (in_array($action, ['pay_due', 'advance_payment'], true)) {
            try {
                $paymentReference = null;
                if (!empty($paymentId) && class_exists(TransactionPayment::class)) {
                    $savedPayment = TransactionPayment::find($paymentId);
                    $paymentReference = $savedPayment->payment_ref_no ?? null;
                }

                $this->sendCustomerPaymentReceivedNotification(
                    $businessId,
                    $customer,
                    $amount,
                    $paymentDate,
                    $paymentReference,
                    $label
                );
            } catch (\Throwable $notificationException) {
                \Illuminate\Support\Facades\Log::error('S773: Customer payment notification failed after a saved payment.', [
                    'business_id' => $businessId,
                    'contact_id' => $customer->id ?? null,
                    'payment_id' => $paymentId,
                    'action' => $action,
                    'message' => $notificationException->getMessage(),
                ]);
            }
        }

        return [
            'success' => 1,
            'msg' => $label . ' saved successfully.',
            'customer_id' => $customer->id,
            // IS2297: Pay Due is launched from Customer Register. A full page
            // refresh after save guarantees the register totals, row actions
            // and any cached customer balance all reflect the new payment.
            'refresh_register' => $action === 'pay_due',
        ];
    }

    protected function createTransactionPayment(int $businessId, int $userId, Customer $customer, float $amount, string $paymentDate, string $method, int $accountId, string $note, string $action, string $chequeNumber = '', string $bankName = '', $chequeDate = null): ?int
    {
        if (!class_exists(TransactionPayment::class) || !SchemaCache::hasTable('transaction_payments')) {
            return null;
        }

        $payment = new TransactionPayment();
        $payment->business_id = $businessId;
        $payment->amount = $amount;
        $payment->method = $method;
        $payment->account_id = $accountId ?: null;
        $payment->paid_on = $paymentDate;
        $payment->payment_for = (int) $customer->id;
        $payment->created_by = $userId ?: null;
        $payment->payment_ref_no = $action === 'pay_due'
            ? app(CustomerPaymentReferenceService::class)->next('customer_pay_due', $businessId, $paymentDate)
            : 'CUS-' . strtoupper(str_replace('_', '-', $action)) . '-' . date('YmdHis');
        $payment->note = $note;
        if ($this->requiresBankChequeDetails($method)) {
            if (SchemaCache::hasColumn('transaction_payments', 'cheque_number')) {
                $payment->cheque_number = $chequeNumber;
            }
            if (SchemaCache::hasColumn('transaction_payments', 'bank_name')) {
                $payment->bank_name = $bankName;
            }
            if (SchemaCache::hasColumn('transaction_payments', 'cheque_date')) {
                $payment->cheque_date = $this->parseDateOnly($chequeDate);
            }
        }
        $payment->is_advance = $action === 'advance_payment' ? 1 : 0;

        /*
         * IS2307: make new standalone Security Deposit rows explicitly
         * identifiable.  The dedicated Security Deposit history, Customer
         * Ledger exclusion and Total Due exclusion already understand this
         * marker.  Older tenants without paid_in_type keep working because the
         * column is checked before assignment.
         */
        if ($action === 'security_deposit' && SchemaCache::hasColumn('transaction_payments', 'paid_in_type')) {
            $payment->paid_in_type = 'security_deposit';
        }

        /*
         | SW Shift No.
         |
         | Cash taken from a customer during a shift is part of that shift's
         | Balance In Hand, so the entry has to say which shift it belongs to.
         |
         | Read from the request rather than passed in: this method already
         | takes twelve positional arguments, and a thirteenth would have to be
         | threaded correctly through every path that reaches it - pay due,
         | advance payment, security deposit and the bulk screen.
         |
         | The column is checked first. A tenant that has not had the SW schema
         | applied would otherwise fail on every customer payment, which is far
         | worse than a missing shift number.
        */
        if (SchemaCache::hasColumn('transaction_payments', 'sw_shift_no')) {
            $swShiftNo = request()->input('sw_shift_no');
            $payment->sw_shift_no = ! empty($swShiftNo) ? $swShiftNo : null;
        }

        $payment->save();

        return (int) $payment->id;
    }

    protected function createContactLedger(int $businessId, int $userId, int $customerId, float $amount, string $type, string $operationDate, string $description, ?int $transactionId = null, ?int $paymentId = null): void
    {
        if (!SchemaCache::hasTable('contact_ledgers')) {
            return;
        }

        $data = [
            'business_id' => $businessId,
            'contact_id' => $customerId,
            'amount' => $amount,
            'type' => $type,
            'operation_date' => $operationDate,
            'description' => $description,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => $paymentId,
            'created_by' => $userId ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $insert = [];
        foreach ($data as $column => $value) {
            if (SchemaCache::hasColumn('contact_ledgers', $column)) {
                $insert[$column] = $value;
            }
        }

        /*
         |------------------------------------------------------------------
         | IS2276: one transaction payment may post to Customer Ledger once.
         |------------------------------------------------------------------
         |
         | Historical tenants contain cases where the same persisted payment id
         | was posted to contact_ledgers more than once by overlapping payment
         | flows.  The read-side reconciliation fixes those old rows; this guard
         | prevents the Customers Pay Due / payment actions from creating another
         | physical duplicate in future.
         */
        if ($paymentId
            && SchemaCache::hasColumn('contact_ledgers', 'business_id')
            && SchemaCache::hasColumn('contact_ledgers', 'contact_id')
            && SchemaCache::hasColumn('contact_ledgers', 'transaction_payment_id')) {
            $existing = DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->where('transaction_payment_id', $paymentId);

            if (SchemaCache::hasColumn('contact_ledgers', 'amount')) {
                $existing->whereRaw('ABS(COALESCE(amount, 0) - ?) < 0.0001', [abs($amount)]);
            }

            $hasLedgerType = SchemaCache::hasColumn('contact_ledgers', 'type');
            $hasLedgerAccType = SchemaCache::hasColumn('contact_ledgers', 'acc_transaction_type');
            if ($hasLedgerType && $hasLedgerAccType) {
                $existing->whereRaw(
                    "LOWER(COALESCE(NULLIF(type, ''), NULLIF(acc_transaction_type, ''), 'debit')) = ?",
                    [strtolower($type)]
                );
            } elseif ($hasLedgerType) {
                $existing->whereRaw("LOWER(COALESCE(NULLIF(type, ''), 'debit')) = ?", [strtolower($type)]);
            } elseif ($hasLedgerAccType) {
                $existing->whereRaw("LOWER(COALESCE(NULLIF(acc_transaction_type, ''), 'debit')) = ?", [strtolower($type)]);
            }

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $existing->whereNull('deleted_at');
            }

            if ($existing->exists()) {
                return;
            }
        }

        if (!empty($insert)) {
            DB::table('contact_ledgers')->insert($insert);
        }
    }

    protected function postAccounts(int $businessId, int $userId, float $amount, string $operationDate, int $paymentAccountId, string $action, ?int $transactionId = null, ?int $paymentId = null, string $note = ''): void
    {
        if (!class_exists(AccountTransaction::class) || !SchemaCache::hasTable('account_transactions')) {
            return;
        }

        $accountsReceivableId = $this->accountIdByName($businessId, 'Accounts Receivable');

        if ($action === 'loan_to_customer') {
            if ($accountsReceivableId) {
                $this->createAccountTransaction($businessId, $userId, $accountsReceivableId, 'debit', $amount, $operationDate, $transactionId, $paymentId, $note);
            }
            if ($paymentAccountId) {
                $this->createAccountTransaction($businessId, $userId, $paymentAccountId, 'credit', $amount, $operationDate, $transactionId, $paymentId, $note);
            }
            return;
        }

        if ($paymentAccountId) {
            $this->createAccountTransaction($businessId, $userId, $paymentAccountId, 'debit', $amount, $operationDate, $transactionId, $paymentId, $note);
        }
        if ($accountsReceivableId) {
            $this->createAccountTransaction($businessId, $userId, $accountsReceivableId, 'credit', $amount, $operationDate, $transactionId, $paymentId, $note);
        }
    }

    protected function createAccountTransaction(int $businessId, int $userId, int $accountId, string $type, float $amount, string $operationDate, ?int $transactionId = null, ?int $paymentId = null, string $note = ''): void
    {
        AccountTransaction::createAccountTransaction([
            'business_id' => $businessId,
            'account_id' => $accountId,
            'type' => $type,
            'amount' => $amount,
            'operation_date' => $operationDate,
            'created_by' => $userId ?: null,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => $paymentId,
            'note' => $note,
        ]);
    }

    protected function accountIdByName(int $businessId, string $name): ?int
    {
        if (!class_exists(Account::class) || !SchemaCache::hasTable('accounts')) {
            return null;
        }

        $account = Account::where('business_id', $businessId)->where('name', $name)->first();
        return $account ? (int) $account->id : null;
    }

    protected function requiresBankChequeDetails(string $method): bool
    {
        return in_array(strtolower($method), ['bank', 'cheque', 'bank_transfer', 'direct_bank_deposit', 'bank_deposit'], true);
    }

    /**
     * Keep submission tokens cache-key safe. An empty token is accepted for
     * backward compatibility with an already-open page from before this parcel.
     */
    protected function normaliseSubmissionToken($token): string
    {
        $token = substr(trim((string) $token), 0, 100);
        return preg_replace('/[^A-Za-z0-9\-]/', '', $token) ?: '';
    }

    protected function reserveSubmissionToken(int $businessId, int $customerId, string $action, string $token): bool
    {
        if ($token === '') {
            return true;
        }

        try {
            return \Illuminate\Support\Facades\Cache::add(
                $this->submissionTokenCacheKey($businessId, $customerId, $action, $token),
                1,
                now()->addMinutes(20)
            );
        } catch (\Throwable $e) {
            // Cache availability must never make Customer Payments unusable.
            \Illuminate\Support\Facades\Log::warning('Customers payment idempotency cache unavailable', [
                'business_id' => $businessId,
                'customer_id' => $customerId,
                'action' => $action,
                'message' => $e->getMessage(),
            ]);
            return true;
        }
    }

    protected function releaseSubmissionToken(int $businessId, int $customerId, string $action, string $token): void
    {
        if ($token === '') {
            return;
        }

        try {
            \Illuminate\Support\Facades\Cache::forget(
                $this->submissionTokenCacheKey($businessId, $customerId, $action, $token)
            );
        } catch (\Throwable $e) {
            // Nothing to do: the database transaction has already failed.
        }
    }

    protected function submissionTokenCacheKey(int $businessId, int $customerId, string $action, string $token): string
    {
        return 'customers:payment-submit:' . $businessId . ':' . $customerId . ':' . $action . ':' . $token;
    }

    protected function cleanAmount($amount): float
    {
        return (float) str_replace(',', '', (string) $amount);
    }

    protected function parseDate($date): string
    {
        try {
            return Carbon::parse($date ?: now())->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return Carbon::now()->format('Y-m-d H:i:s');
        }
    }

    protected function parseDateOnly($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Send the standard Customer "Payment Received" notification.
     *
     * This deliberately delegates to the application's existing NotificationUtil
     * so the business's Notification Templates > SMS & WhatsApp settings, gateway
     * setup and template tags remain the single source of truth.  It does not send
     * directly through a second SMS gateway.
     */
    public function sendCustomerPaymentReceivedNotification(
        int $businessId,
        Customer $customer,
        float $amount,
        $paymentDate,
        ?string $reference = null,
        string $source = 'Customer Payment',
        ?float $ledgerBalance = null
    ): bool {
        if ($businessId <= 0 || empty($customer->id)) {
            return false;
        }

        if (!class_exists(\App\Utils\NotificationUtil::class) || !class_exists(\App\Transaction::class)) {
            return false;
        }

        // NotificationUtil is built around App\Contact.  Customers uses the same
        // contacts table, so load the core model when available instead of passing
        // a module copy and risking template/contact relationship differences.
        $contact = null;
        if (class_exists(\App\Contact::class)) {
            try {
                $contactQuery = \App\Contact::where('business_id', $businessId);
                if (method_exists($contactQuery->getModel(), 'getQualifiedDeletedAtColumn') && SchemaCache::hasColumn('contacts', 'deleted_at')) {
                    $contactQuery->whereNull('deleted_at');
                }
                $contact = $contactQuery->find((int) $customer->id);
            } catch (\Throwable $e) {
                $contact = null;
            }
        }
        $contact = $contact ?: $customer;

        if ($ledgerBalance === null) {
            try {
                $summary = app(CustomerLedgerService::class)->ledgerSummary(
                    $businessId,
                    (int) $customer->id,
                    $customer->fresh() ?: $customer
                );
                $ledgerBalance = (float) ($summary['balance'] ?? 0);
            } catch (\Throwable $e) {
                $ledgerBalance = 0.0;
            }
        }

        $transaction = new \App\Transaction();
        $transaction->contact = $contact;
        $transaction->transaction_date = (string) ($paymentDate ?: now()->format('Y-m-d H:i:s'));
        $transaction->single_payment_amount = $amount;
        $transaction->total_payment_amount = $amount;
        $transaction->payment_ref_number = (string) ($reference ?: '');
        $transaction->payment_ref_no = (string) ($reference ?: '');
        $transaction->cumulative_due_amount = (float) $ledgerBalance;
        $transaction->type = 'sell';
        $transaction->status = 'final';
        $transaction->notification_source = $source;

        app(\App\Utils\NotificationUtil::class)
            ->autoSendNotification($businessId, 'payment_received', $transaction, $contact, true);

        return true;
    }

    protected function label(string $action): string
    {
        $labels = [
            'pay_due' => 'Pay Due Amount',
            'advance_payment' => 'Advance Payment',
            'loan_to_customer' => 'Loan to Customer',
            'security_deposit' => 'Security Deposit',
            'refund_deposit' => 'Refund Deposit',
            'refund_payment' => 'Refund Payment',
            'cheque_return' => 'Cheque Return',
        ];

        return $labels[$action] ?? 'Customer Payment Action';
    }
}
