<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Modules\Customers\Support\SchemaCache;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Customers\Entities\Customer;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    protected CustomerReceivableService $receivableService;

    public function __construct(?CustomerReceivableService $receivableService = null)
    {
        $this->receivableService = $receivableService ?: new CustomerReceivableService();
    }

    public function query($businessId)
    {
        return Customer::forBusiness($businessId)
            ->customersOnly()
            ->whereNull('deleted_at');
    }

    public function activeQuery($businessId)
    {
        return $this->query($businessId)->activeOnly();
    }

    public function registerRows($businessId)
    {
        $query = $this->query($businessId);

        if (SchemaCache::hasTable('contact_ledgers')) {
            // S330: Some tenant databases have contact_ledgers.type while older/newer
            // schemas use acc_transaction_type only. Do not reference a column that
            // does not exist, otherwise the Customers list DataTable throws the
            // "Unknown column" warning seen in testing.
            if (SchemaCache::hasColumn('contact_ledgers', 'type') && SchemaCache::hasColumn('contact_ledgers', 'acc_transaction_type')) {
                $ledgerTypeExpr = "COALESCE(type, acc_transaction_type)";
            } elseif (SchemaCache::hasColumn('contact_ledgers', 'type')) {
                $ledgerTypeExpr = "type";
            } elseif (SchemaCache::hasColumn('contact_ledgers', 'acc_transaction_type')) {
                $ledgerTypeExpr = "acc_transaction_type";
            } else {
                $ledgerTypeExpr = "'debit'";
            }

            $ledgerBalanceSub = DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->selectRaw("contact_id, SUM(CASE WHEN {$ledgerTypeExpr} = 'credit' THEN -amount ELSE amount END) as ledger_balance")
                ->groupBy('contact_id');

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $ledgerBalanceSub->whereNull('deleted_at');
            }

            $query->leftJoinSub($ledgerBalanceSub, 'customer_ledger_balance', function ($join) {
                $join->on('contacts.id', '=', 'customer_ledger_balance.contact_id');
            });
        }

        $openingExpr = SchemaCache::hasColumn('contacts', 'opening_balance')
            ? 'COALESCE(contacts.opening_balance, 0)'
            : '0';

        $ledgerExpr = SchemaCache::hasTable('contact_ledgers')
            ? 'COALESCE(customer_ledger_balance.ledger_balance, 0)'
            : '0';

        return $query->select([
                'contacts.id',
                'contacts.contact_id',
                'contacts.name',
                'contacts.mobile',
                'contacts.email',
                'contacts.credit_limit',
                'contacts.active',
                'contacts.customer_passcode',
                'contacts.created_at',
                /*
                 * S635: the Walk-In Customer reads zero here too.
                 *
                 * This expression is built in SQL and does NOT go through
                 * CustomerReceivableService::balancesForCustomers(), so the
                 * zeroing applied there would have missed any screen using this
                 * builder directly - leaving two ways to ask the same question
                 * and get different answers.
                 *
                 * is_default is the flag app/Contact.php already uses for the
                 * walk-in; the guard falls back to the normal calculation if the
                 * column is absent.
                 */
                DB::raw(
                    SchemaCache::hasColumn('contacts', 'is_default')
                        ? "(CASE WHEN contacts.is_default = 1 THEN 0 ELSE ({$openingExpr} + {$ledgerExpr}) END) as total_due"
                        : "({$openingExpr} + {$ledgerExpr}) as total_due"
                ),
            ]);
    }


    /**
     * Fast Customer Register server-side page.
     *
     * The customer page is fetched first, then ledger totals are calculated only
     * for customer ids visible on that page. This avoids grouping the complete
     * contact_ledgers table for every DataTables request.
     */
    public function registerPage(int $businessId, array $input): array
    {
        $draw = max(0, (int) ($input['draw'] ?? 0));
        $start = max(0, (int) ($input['start'] ?? 0));
        $length = (int) ($input['length'] ?? 25);
        $showAll = $length === -1;
        if (!$showAll) {
            $length = min(max($length, 10), 500);
        }
        $search = trim((string) data_get($input, 'search.value', ''));

        $base = DB::table('contacts')
            ->where('contacts.business_id', $businessId);

        // Older tenant databases do not all have the same Contacts columns.
        // In particular, `active` was added after some early databases were
        // created and a few legacy copies also pre-date soft deletes.  Never
        // reference an optional column unless it actually exists: otherwise the
        // Customer Register DataTable returns HTTP 500 and DataTables can only
        // show its generic "Ajax error" warning.
        if (SchemaCache::hasColumn('contacts', 'type')) {
            $base->whereIn('contacts.type', ['customer', 'both']);
        }
        if (SchemaCache::hasColumn('contacts', 'deleted_at')) {
            $base->whereNull('contacts.deleted_at');
        }

        // Cache only the unfiltered count. Customer creates/deletes clear this key.
        $countKey = 'customers_register_count_' . $businessId;
        $recordsTotal = (int) cache()->remember($countKey, 60, function () use ($base) {
            return (clone $base)->count('contacts.id');
        });

        $filtered = clone $base;
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search) . '%';
            $searchColumns = array_values(array_filter([
                SchemaCache::hasColumn('contacts', 'name') ? 'contacts.name' : null,
                SchemaCache::hasColumn('contacts', 'contact_id') ? 'contacts.contact_id' : null,
                SchemaCache::hasColumn('contacts', 'mobile') ? 'contacts.mobile' : null,
                SchemaCache::hasColumn('contacts', 'email') ? 'contacts.email' : null,
            ]));

            if (!empty($searchColumns)) {
                $filtered->where(function ($query) use ($like, $searchColumns) {
                    foreach ($searchColumns as $index => $column) {
                        if ($index === 0) {
                            $query->where($column, 'like', $like);
                        } else {
                            $query->orWhere($column, 'like', $like);
                        }
                    }
                });
            }
        }

        $recordsFiltered = $search === ''
            ? $recordsTotal
            : (int) (clone $filtered)->count('contacts.id');

        $orderMap = [];
        foreach ([
            1 => 'contact_id',
            2 => 'name',
            3 => 'mobile',
            4 => 'email',
            5 => 'credit_limit',
        ] as $index => $column) {
            if (SchemaCache::hasColumn('contacts', $column)) {
                $orderMap[$index] = 'contacts.' . $column;
            }
        }
        $orderColumnIndex = (int) data_get($input, 'order.0.column', 2);
        $defaultOrderColumn = SchemaCache::hasColumn('contacts', 'name') ? 'contacts.name' : 'contacts.id';
        $orderColumn = $orderMap[$orderColumnIndex] ?? $defaultOrderColumn;
        $orderDirection = strtolower((string) data_get($input, 'order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Build the result shape expected by CustomerController even when an
        // older database is missing one of the later optional Contacts fields.
        $columns = ['contacts.id'];
        $columns[] = SchemaCache::hasColumn('contacts', 'contact_id')
            ? 'contacts.contact_id'
            : DB::raw("'' as contact_id");
        $columns[] = SchemaCache::hasColumn('contacts', 'name')
            ? 'contacts.name'
            : DB::raw("'' as name");
        $columns[] = SchemaCache::hasColumn('contacts', 'mobile')
            ? 'contacts.mobile'
            : DB::raw("'' as mobile");
        $columns[] = SchemaCache::hasColumn('contacts', 'email')
            ? 'contacts.email'
            : DB::raw("'' as email");
        $columns[] = SchemaCache::hasColumn('contacts', 'credit_limit')
            ? 'contacts.credit_limit'
            : DB::raw('0 as credit_limit');
        $columns[] = SchemaCache::hasColumn('contacts', 'active')
            ? 'contacts.active'
            : DB::raw('1 as active');
        if (SchemaCache::hasColumn('contacts', 'opening_balance')) {
            $columns[] = 'contacts.opening_balance';
        }

        $rowsQuery = $filtered
            ->select($columns)
            ->orderBy($orderColumn, $orderDirection)
            ->orderBy('contacts.id', 'asc');

        if (!$showAll) {
            $rowsQuery->offset($start)->limit($length);
        }

        $rows = $rowsQuery->get();

        $ids = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $openingBalances = $rows->mapWithKeys(function ($row) {
            return [(int) $row->id => property_exists($row, 'opening_balance')
                ? (float) $row->opening_balance
                : 0.0];
        })->all();
        $balances = $this->ledgerBalancesForCustomers($businessId, $ids, $openingBalances);

        foreach ($rows as $row) {
            $row->total_due = (float) ($balances[(int) $row->id] ?? 0.0);
        }

        return compact('draw', 'recordsTotal', 'recordsFiltered', 'rows');
    }

    /**
     * Return the Total Due for every customer in the business, not merely the
     * currently visible DataTables page. The calculation uses the same hybrid
     * receivable source as the per-customer Total Due column.
     */
    public function overallTotalDue(int $businessId): float
    {
        /*
         * IS2307 performance follow-up:
         * The overall card needs one scalar, not a balance array for every
         * customer.  Use the scalar set-based path so Customer Register does not
         * repeat the expensive grouped reconciliation used by row-level totals.
         */
        return $this->receivableService->overallBalanceForBusiness($businessId);
    }

    /**
     * S526: Build Total Due from the existing contact ledger plus only those
     * credit-sale/payment rows that have not been posted to contact_ledgers.
     * This keeps the fast page-sized query and prevents duplicate amounts.
     *
     * @param array<int, int> $customerIds
     * @param array<int|string, float|int|string|null> $openingBalances
     * @return array<int, float>
     */
    protected function ledgerBalancesForCustomers(
        int $businessId,
        array $customerIds,
        array $openingBalances = []
    ): array {
        return $this->receivableService->balancesForCustomers(
            $businessId,
            $customerIds,
            $openingBalances
        );
    }

    public function clearRegisterCountCache(int $businessId): void
    {
        cache()->forget('customers_register_count_' . $businessId);
    }

    public function findCustomer($businessId, $id)
    {
        return $this->query($businessId)->findOrFail($id);
    }

    /**
     * Load the customer record for the View action and supplement fields that
     * older tenant schemas store only in the opening-balance transaction.
     */
    public function findCustomerForView(int $businessId, int $id): Customer
    {
        $customer = $this->findCustomer($businessId, $id);

        if (
            !SchemaCache::hasTable('transactions')
            || !SchemaCache::hasColumn('transactions', 'business_id')
            || !SchemaCache::hasColumn('transactions', 'contact_id')
            || !SchemaCache::hasColumn('transactions', 'type')
        ) {
            return $customer;
        }

        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customer->id)
            ->where('type', 'opening_balance');

        if (SchemaCache::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $openingBalanceTransaction = $query->orderBy('id')->first();

        if (!$openingBalanceTransaction) {
            return $customer;
        }

        if (
            !SchemaCache::hasColumn('contacts', 'opening_balance')
            || $customer->getAttribute('opening_balance') === null
            || $customer->getAttribute('opening_balance') === ''
        ) {
            $openingBalanceAmount = 0.0;

            if (property_exists($openingBalanceTransaction, 'final_total')) {
                $openingBalanceAmount = (float) $openingBalanceTransaction->final_total;
            } elseif (property_exists($openingBalanceTransaction, 'total_before_tax')) {
                $openingBalanceAmount = (float) $openingBalanceTransaction->total_before_tax;
            }

            $customer->setAttribute('opening_balance', $openingBalanceAmount);
        }

        if (
            empty($customer->getAttribute('transaction_date'))
            && property_exists($openingBalanceTransaction, 'transaction_date')
        ) {
            $customer->setAttribute('transaction_date', $openingBalanceTransaction->transaction_date);
        }

        return $customer;
    }

    public function createCustomer(array $data, int $businessId, int $userId = null)
    {
        return DB::transaction(function () use ($data, $businessId, $userId) {
            $customer = new Customer();
            $customer->business_id = $businessId;
            $customer->type = 'customer';
            if (empty($data['customer_passcode'])) {
                $data['customer_passcode'] = $this->generateUniquePasscode($businessId);
            }

            $this->fillCustomer($customer, $data);

            if (!empty($userId)) {
                $customer->created_by = $userId;
            }

            $customer->save();
            $this->clearRegisterCountCache($businessId);

            if (empty($customer->contact_id)) {
                $customer->contact_id = 'CUS-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT);
                $customer->save();
            }

            return $customer;
        });
    }

    public function updateCustomer(Customer $customer, array $data, ?int $businessId = null)
    {
        return DB::transaction(function () use ($customer, $data, $businessId) {
            $originalOpeningBalance = $this->numberFromInput($customer->getAttribute('opening_balance'));
            $originalTransactionDate = $customer->getAttribute('transaction_date');

            $this->fillCustomer($customer, $data);
            $customer->save();

            // Only touch the legacy opening-balance transaction when its amount
            // or date was actually changed. This keeps ordinary customer edits
            // independent from optional Accounting/Contacts schema variations.
            $newOpeningBalance = $this->numberFromInput($data['opening_balance'] ?? $originalOpeningBalance);
            $newTransactionDate = $data['transaction_date'] ?? $originalTransactionDate;
            $openingBalanceChanged = abs($newOpeningBalance - $originalOpeningBalance) > 0.00001;
            $openingDateChanged = $this->normaliseDate($newTransactionDate) !== $this->normaliseDate($originalTransactionDate);

            if ($openingBalanceChanged || $openingDateChanged) {
                $this->syncOpeningBalanceTransaction($customer, $data, $businessId);
            }

            $this->clearRegisterCountCache((int) ($businessId ?: $customer->business_id));

            return $customer->fresh();
        });
    }


    protected function syncOpeningBalanceTransaction(Customer $customer, array $data, ?int $businessId = null): void
    {
        if (!array_key_exists('opening_balance', $data) || !SchemaCache::hasTable('transactions')) {
            return;
        }

        // Tenant databases do not all have identical optional transaction columns.
        // Do not let a missing integration column prevent the Edit Customer form
        // from saving its own contacts record.
        foreach (['business_id', 'contact_id', 'type', 'id'] as $requiredColumn) {
            if (!SchemaCache::hasColumn('transactions', $requiredColumn)) {
                Log::warning('Customers: opening balance sync skipped because transactions column is missing.', [
                    'column' => $requiredColumn,
                    'customer_id' => (int) $customer->id,
                ]);
                return;
            }
        }

        $businessId = $businessId ?: (int) $customer->business_id;
        $amount = $this->numberFromInput($data['opening_balance'] ?? 0);
        $transactionDate = $this->normaliseDate($data['transaction_date'] ?? null);

        $obQuery = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('contact_id', $customer->id)
            ->where('type', 'opening_balance');

        if (SchemaCache::hasColumn('transactions', 'deleted_at')) {
            $obQuery->whereNull('deleted_at');
        }

        $obTransaction = $obQuery->orderBy('id')->first();

        if ($obTransaction) {
            $transactionUpdate = [];

            if (SchemaCache::hasColumn('transactions', 'total_before_tax')) {
                $transactionUpdate['total_before_tax'] = $amount;
            }
            if (SchemaCache::hasColumn('transactions', 'final_total')) {
                $transactionUpdate['final_total'] = $amount;
            }
            if ($transactionDate && SchemaCache::hasColumn('transactions', 'transaction_date')) {
                $transactionUpdate['transaction_date'] = $transactionDate;
            }
            if (SchemaCache::hasColumn('transactions', 'updated_at')) {
                $transactionUpdate['updated_at'] = now();
            }

            if (!empty($transactionUpdate)) {
                DB::table('transactions')->where('id', $obTransaction->id)->update($transactionUpdate);
            }

            $absAmount = abs($amount);
            $ledgerType = $amount >= 0 ? 'debit' : 'credit';

            if (
                SchemaCache::hasTable('contact_ledgers')
                && SchemaCache::hasColumn('contact_ledgers', 'transaction_id')
            ) {
                $ledgerUpdate = [];

                if (SchemaCache::hasColumn('contact_ledgers', 'amount')) {
                    $ledgerUpdate['amount'] = $absAmount;
                }
                if (SchemaCache::hasColumn('contact_ledgers', 'type')) {
                    $ledgerUpdate['type'] = $ledgerType;
                }
                if (SchemaCache::hasColumn('contact_ledgers', 'acc_transaction_type')) {
                    $ledgerUpdate['acc_transaction_type'] = $ledgerType;
                }
                if ($transactionDate && SchemaCache::hasColumn('contact_ledgers', 'amount_date')) {
                    $ledgerUpdate['amount_date'] = $transactionDate;
                }
                if (SchemaCache::hasColumn('contact_ledgers', 'updated_at')) {
                    $ledgerUpdate['updated_at'] = now();
                }

                if (!empty($ledgerUpdate)) {
                    DB::table('contact_ledgers')
                        ->where('transaction_id', $obTransaction->id)
                        ->update($ledgerUpdate);
                }
            }

            if (
                SchemaCache::hasTable('account_transactions')
                && SchemaCache::hasColumn('account_transactions', 'transaction_id')
            ) {
                $accountUpdate = [];

                if (SchemaCache::hasColumn('account_transactions', 'amount')) {
                    $accountUpdate['amount'] = $absAmount;
                }
                if (SchemaCache::hasColumn('account_transactions', 'type')) {
                    $accountUpdate['type'] = $ledgerType;
                }
                if ($transactionDate && SchemaCache::hasColumn('account_transactions', 'operation_date')) {
                    $accountUpdate['operation_date'] = $transactionDate;
                }
                if (SchemaCache::hasColumn('account_transactions', 'updated_at')) {
                    $accountUpdate['updated_at'] = now();
                }

                if (!empty($accountUpdate)) {
                    DB::table('account_transactions')
                        ->where('transaction_id', $obTransaction->id)
                        ->update($accountUpdate);
                }
            }

            return;
        }

        if ($amount != 0 && class_exists(\App\Utils\TransactionUtil::class)) {
            try {
                app(\App\Utils\TransactionUtil::class)
                    ->createOpeningBalanceTransaction($businessId, $customer->id, $amount, $transactionDate);
            } catch (\Throwable $e) {
                // The contact edit itself is valid and must not be rolled back
                // merely because an optional legacy integration is unavailable.
                Log::error('Customers: opening balance integration failed after customer update.', [
                    'business_id' => $businessId,
                    'customer_id' => (int) $customer->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function normaliseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function numberFromInput($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }

    public function generateUniquePasscode(int $businessId, ?int $excludeCustomerId = null): string
    {
        if (!SchemaCache::hasColumn('contacts', 'customer_passcode')) {
            return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        for ($i = 0; $i < 80; $i++) {
            $passcode = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $query = Customer::where('business_id', $businessId)
                ->where('customer_passcode', $passcode);

            if (!empty($excludeCustomerId)) {
                $query->where('id', '!=', $excludeCustomerId);
            }

            if (!$query->exists()) {
                return $passcode;
            }
        }

        throw new \RuntimeException('Unable to generate a unique customer passcode.');
    }

    protected function fillCustomer(Customer $customer, array $data): void
    {
        $table = $customer->getTable();

        $assign = function (string $column, $value) use ($customer, $table) {
            if (SchemaCache::hasColumn($table, $column)) {
                $customer->{$column} = $value;
            }
        };

        $assign('type', $data['type'] ?? 'customer');
        $assign('name', $data['name'] ?? $customer->name);
        $assign('supplier_business_name', $data['supplier_business_name'] ?? null);

        if (!empty($data['contact_id'])) {
            $assign('contact_id', $data['contact_id']);
        }

        $assign('mobile', $data['mobile'] ?? null);
        $assign('alternate_number', $data['alternate_number'] ?? null);
        $assign('landline', $data['landline'] ?? null);
        $assign('email', $data['email'] ?? null);
        $assign('tax_number', $data['tax_number'] ?? null);
        $assign('vat_number', $data['vat_number'] ?? null);
        $assign('customer_group_id', $data['customer_group_id'] ?? null);
        $assign('business_location_id', $data['business_location_id'] ?? null);
        $assign('pay_term_number', $data['pay_term_number'] ?? null);
        $assign('pay_term_type', $data['pay_term_type'] ?? null);
        $assign('whatsapp_number', $data['whatsapp_number'] ?? null);
        $assign('assigned_to', $data['assigned_to'] ?? null);
        $assign('vehicle_no', $data['vehicle_no'] ?? null);
        $assign('landmark', $data['landmark'] ?? null);
        $assign('credit_limit', !empty($data['credit_limit']) ? str_replace(',', '', $data['credit_limit']) : 0);
        $assign('opening_balance', !empty($data['opening_balance']) ? str_replace(',', '', $data['opening_balance']) : 0);
        $assign('address_line_1', $data['address_line_1'] ?? ($data['address'] ?? null));
        $assign('address_line_2', $data['address_line_2'] ?? ($data['address_2'] ?? null));
        $assign('address_line_3', $data['address_3'] ?? null);
        $assign('address', $data['address'] ?? ($data['address_line_1'] ?? null));
        $assign('address_2', $data['address_2'] ?? ($data['address_line_2'] ?? null));
        $assign('address_3', $data['address_3'] ?? null);
        $assign('city', $data['city'] ?? null);
        $assign('state', $data['state'] ?? null);
        $assign('country', $data['country'] ?? null);
        $assign('zip_code', $data['zip_code'] ?? null);
        $assign('active', isset($data['active']) ? (int) $data['active'] : 1);
        $assign('should_notify', isset($data['should_notify']) ? (int) $data['should_notify'] : 0);
        $assign('credit_notification', $data['credit_notification'] ?? null);
        /*
         * MA-002 (S-622): "Need to Send SMS".
         *
         * Defaults to 1 when not supplied, matching the column default. That
         * matters on the EDIT form before the migration has run, and on any
         * save that posts a partial payload - a missing value must not be read
         * as "no", or a customer would silently stop receiving notifications.
         *
         * $assign already skips columns that do not exist, so this is safe
         * whether or not the migration has been applied.
         */
        $assign('need_to_send_sms', isset($data['need_to_send_sms']) ? (int) $data['need_to_send_sms'] : 1);
        $assign('manual_bill_settlement', isset($data['manual_bill_settlement']) ? (int) $data['manual_bill_settlement'] : 0);
        $assign('sub_customer', isset($data['sub_customer']) ? (int) $data['sub_customer'] : 0);
        if (array_key_exists('sub_customers', $data)) {
            $subCustomers = is_array($data['sub_customers']) ? array_values(array_filter($data['sub_customers'])) : [];
            $assign('sub_customers', !empty($subCustomers) ? json_encode($subCustomers) : '[]');
        }

        if (!empty($data['transaction_date']) && SchemaCache::hasColumn($table, 'transaction_date')) {
            try {
                $customer->transaction_date = Carbon::parse($data['transaction_date'])->format('Y-m-d');
            } catch (\Throwable $e) {
                $customer->transaction_date = date('Y-m-d');
            }
        }

        if (SchemaCache::hasColumn($table, 'notification_contacts')) {
            if (array_key_exists('notification_contacts', $data)) {
                $numbers = array_values(array_filter(array_map('trim', explode(',', (string) $data['notification_contacts']))));

                // IS1808: notification_contacts is NOT NULL in several tenant
                // databases. Keep the optional field JSON-valid without sending
                // NULL when the form is submitted empty.
                $customer->notification_contacts = !empty($numbers)
                    ? json_encode($numbers)
                    : '[]';
            } elseif (!$customer->exists && empty($customer->notification_contacts)) {
                // Protect API/import creates where the optional field is absent.
                $customer->notification_contacts = '[]';
            }
        }

        if (array_key_exists('customer_passcode', $data)) {
            $assign('customer_passcode', $data['customer_passcode']);
        }
    }
}
