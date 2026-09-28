<?php

namespace Modules\Chequer\Entities;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\Chequer\Entities\Chequer\ChequerBankAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Superadmin\Entities\AccountNumber;

class Account extends Model
{
    use SoftDeletes;

    use LogsActivity;

    public const FREE_PRODUCTS_OR_SAMPLES_ACCOUNT_NAME = 'Income - Free Products or Samples';

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;


    protected static $logName = 'Account';

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

    public static function forDropdown($business_id, $prepend_none, $closed = false, $account_id = null)
    {
        $query = Account::where('business_id', $business_id);

        if (!$closed) {
            $query->where('is_closed', 0);
        }
        if ($account_id) {
            $acc = Account::find($account_id);
            $query->where('asset_type', $acc->asset_type);
        }

        $dropdown = $query->pluck('name', 'id');
        if ($prepend_none) {
            $dropdown->prepend(__('lang_v1.none'), '');
        }

        return $dropdown;
    }

    public static function forDropdownStockType($business_id, $prepend_none, $closed = false)
    {
        $query = Account::where('business_id', $business_id)->whereIn('name', ['Stock Account', 'Raw Material Account', 'Finished Goods Account']);

        if (!$closed) {
            $query->where('is_closed', 0);
        }

        $dropdown = $query->pluck('name', 'id');
        if ($prepend_none) {
            $dropdown->prepend(__('lang_v1.none'), '');
        }

        return $dropdown;
    }

    /**
     * Scope a query to only include not closed accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotClosed($query)
    {
        return $query->where('is_closed', 0);
    }

    /**
     * Scope a query to only include non capital accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    // public function scopeNotCapital($query)
    // {
    //     return $query->where(function ($q) {
    //         $q->where('account_type', '!=', 'capital');
    //         $q->orWhereNull('account_type');
    //     });
    // }

    public static function accountTypes()
    {
        return [
            '' => __('account.not_applicable'),
            'saving_current' => __('account.saving_current'),
            'capital' => __('account.capital')
        ];
    }

    public function account_type()
    {
        return $this->belongsTo(\Modules\Finance\Entities\AccountType::class, 'account_type_id');
    }
    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
    public function account_type_data()
    {
        return $this->hasOne(\Modules\Finance\Entities\AccountType::class, 'id', 'account_type_id');
    }

    public function sub_accounts()
    {
        return $this->hasMany(\Modules\Finance\Entities\Account::class, 'parent_account_id');
    }

    public static function AssetTypeAccountGroupActive()
    {
        return  array(
            '1' => 'Raw material Account',
            '2' => 'Finished Goods Account',
            '3' => 'Other Stocks',
            '4' => 'Bank Account',
            '5' => 'Cash Account',
            '6' => 'Cheques in Hand (Customer���s)',
            '7' => 'Card',
            '8' => 'COGS',
            '9' => 'Sales Income',
        );
    }
    public static function AssetTypeAccountGroupNoneActive()
    {
        return  array(
            '4' => 'Bank Account',
            '5' => 'Cash Account',
            '6' => 'Cheques in Hand (Customer���s)',
            '7' => 'Card'
        );
    }

    public static function getAccountTypeIdByName($account_type_name)
    {
        $business_id = request()->session()->get('user.business_id');

        return AccountType::where('business_id', $business_id)->where('name', $account_type_name)->first()->id;
    }

    public static function getAccountByAccountGroupId($group_id, $is_main_account = 0)
    {
        $business_id = request()->session()->get('user.business_id');

        return Account::where('business_id', $business_id)->where('asset_type', $group_id)->where('is_main_account', $is_main_account)->pluck('name', 'id');
    }
    function AccountGroups()
    {
        return $this->belongsTo(AccountGroup::class, 'asset_type', 'id');
    }
    public static function getAccountByAccountTypeId($account_type_id)
    {
        $business_id = request()->session()->get('user.business_id');

        return Account::where('business_id', $business_id)->where('account_type_id', $account_type_id)->where('is_main_account', 0)->pluck('name', 'id');
    }

    public static function getAccountByAccountTypeName($account_type_name)
    {
        $business_id = request()->session()->get('user.business_id');

        return Account::leftjoin('account_types', 'accounts.account_type_id', 'account_types.id')
            ->where('accounts.business_id', $business_id)
            ->where('account_types.name', $account_type_name)
            ->where('is_main_account', 0)
            ->pluck('accounts.name', 'accounts.id');
    }

    public static function getAccountByAccountName($account_name)
    {
        $business_id = request()->session()->get('business.id');
        $account = Account::where(DB::raw("REPLACE(`name`, '  ', ' ')"), $account_name)->where('business_id', $business_id)->first();

        return $account;
    }

    /* return all sub accounts if exist */
    public static function getSubAccountOrParentAccountByName($account_name)
    {
        $business_id = request()->session()->get('business.id');
        $this_account = Account::where('name', $account_name)->where('business_id', $business_id)->first();
        if (!empty($this_account->is_main_account)) { //if main account then return sub accounts
            $sub_accounts = Account::where('business_id', $business_id)->where('parent_account_id', $this_account->id)->pluck('name', 'id');
            return  $sub_accounts;
        }
        return $this_account->pluck('name', 'id');
    }


    public static function getSubAccountBalanceByMainAccountId($prent_account_id, $start_date = null, $end_date = null)
    {
        $business_id = request()->session()->get('user.business_id');

        $accounts = Account::where('parent_account_id', $prent_account_id)->where('business_id', $business_id)
            ->select([
                'accounts.name',
                'accounts.id',
                'accounts.account_number'
            ])->get();

        $balance = 0;
        foreach ($accounts as  $account) {
            $balance += Account::getAccountBalance($account->id, null, $end_date);
        }
        return round($balance, 2);
    }

    public static function checkInsufficientBalance($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $account_group = AccountGroup::getAccountGroupByAccountId($id);
        $check_insufficient = false;
        if (!empty($account_group)) {
            if ($account_group->name == 'Cash Account' || $account_group->name == "Cheques in Hand (Customer's)" || $account_group->name == 'Card') {
                $check_insufficient = true;
            }
        }
        return $check_insufficient;
    }

    /**
     * Calculates account balance.
     * @param  int $id
     * @return float
     */
    static function getAccountBalance($id, $start_date = null, $end_date = null, $get_previous = false, $account_book = false, $is_daily_report = false, $location_id = null)
    {
        $start_date = self::normalizeReportDate($start_date);
        $end_date = self::normalizeReportDate($end_date);

        $account_type_id = Account::where('id', $id)->first() ? Account::where('id', $id)->first()->account_type_id : 0;
        $account_type = AccountType::where('id', $account_type_id)->first();
        $account_type_name = !empty($account_type) ? $account_type->name : "";
        $business_id = session()->get('user.business_id');
        
        $account_query = Account::leftjoin('account_transactions as AT','AT.account_id','=','accounts.id')
            ->whereNull('AT.deleted_at')
            ->where('accounts.business_id', $business_id)
            ->where('accounts.id', $id);

        if (!empty($start_date) && !$get_previous) {
            $account_query->whereDate('operation_date', '>=', $start_date);
        }

        if (!empty($end_date) && !$get_previous) {
            $account_query->whereDate('operation_date', '<=', $end_date);
        }

        if (!empty($location_id)) {
            $account_query->join('transactions', 'AT.transaction_id', '=', 'transactions.id')
                          ->where('transactions.location_id', $location_id);
        }

        if ($get_previous && !empty($start_date)) {
            if ($account_book) {
                $account_query->whereDate('operation_date', '<', $start_date);
            } elseif ($is_daily_report) {
                $account_query->whereDate('operation_date', '<=', $end_date);
            } else {
                $account_query->whereDate('operation_date', '<=', $start_date);
            }
        }

        $account_query->where('is_closed', 0);

        $account = $account_query->select(
                DB::raw("SUM(IF(AT.type='credit',amount, 0)) as creditSum"),
                DB::raw("SUM(IF(AT.type='debit', amount, 0)) as debitSum")
        )->first();

        if ($account_type_name == "Assets" || $account_type_name == "Expenses" || $account_type_name == "Current Assets" || $account_type_name == "Fixed Assets") {
            $balance = $account->debitSum - $account->creditSum;
        } else if ($account_type_name == "Liabilities" || $account_type_name == "Equity" || $account_type_name == "Income" || $account_type_name == "Long Term Liabilities" || $account_type_name == "Current Liabilities") {
            $balance = $account->creditSum - $account->debitSum;
        } else {
            $balance = $account->debitSum - $account->creditSum;
        }


        return $balance;
    }
    /**
     * Calculates account balance.
     * @param  int $id
     * @return float
     */
    static function getStockGroupAccountBalanceByTransactionType($type, $start_date = null, $end_date = null, $get_previous = false, $location_id = null)
    {
        $start_date = self::normalizeReportDate($start_date);
        $end_date = self::normalizeReportDate($end_date);

        $business_id = session()->get('user.business_id');
        $balance = 0;
        $FGA_group_id = AccountGroup::getGroupByName('Finished Goods Account')->id;
        $OS_group_id = AccountGroup::getGroupByName('Other Stocks')->id;
        $RMA_group_id = AccountGroup::getGroupByName('Raw Material Account')->id;

        $stock_group_id_array = [$FGA_group_id, $OS_group_id, $RMA_group_id];

        $account_query = Account::leftjoin(
            'account_transactions as AT',
            'AT.account_id',
            '=',
            'accounts.id'
        )->leftjoin(
            'transactions',
            'AT.transaction_id',
            '=',
            'transactions.id'
        )
            ->where('accounts.business_id', $business_id)
            ->where('transactions.type', $type)
            ->whereIn('accounts.asset_type', $stock_group_id_array)
            ->whereNull('AT.deleted_at')
            ->where('is_closed', 0)
            ->when(!empty($location_id), function ($query) use ($location_id) {
                return $query->where('transactions.location_id', $location_id);
            });
        if (!empty($start_date) && !empty($end_date) && !$get_previous && $type != 'opening_stock') {
            $account_query->whereDate('AT.operation_date', '>=', $start_date);
            $account_query->whereDate('AT.operation_date', '<=', $end_date);
        }
        if ($get_previous && $type != 'opening_stock') {
            $account_query->whereDate('AT.operation_date', '<', $start_date);
        }
        if ($type == 'opening_stock') {
            $account_query->whereDate('AT.operation_date', '<=', $end_date);
        }

        $balance = $account_query->select(
            DB::raw("SUM( IF(AT.type='credit', -1 * amount, amount) ) as balance")
        )
            ->first();


        return $balance ? $balance->balance : 0;
    }
    /**
     * Calculates account balance.
     * @param  int $id
     * @return float
     */
    static function getStockGroupAccountBalanceByTransactionTypeAndCategory($type, $sub_cat_id, $start_date = null, $end_date = null, $get_previous = false, $get_qty = false, $module = 'dailysummary_stocksummary_value', $location_id = null)
    {
        $business_id = session()->get('user.business_id');
        $balance = 0;
        $FGA_group_id = AccountGroup::getGroupByName('Finished Goods Account')->id;
        $OS_group_id = AccountGroup::getGroupByName('Other Stocks')->id;
        $RMA_group_id = AccountGroup::getGroupByName('Raw Material Account')->id;

        $stock_group_id_array = [$FGA_group_id, $OS_group_id, $RMA_group_id];

        $account_query = Account::leftjoin(
            'account_transactions as AT',
            'AT.account_id',
            '=',
            'accounts.id'
        )->leftjoin(
            'transactions',
            'AT.transaction_id',
            '=',
            'transactions.id'
        );
        if ($type == 'sell') {
            $account_query->leftjoin(
                'transaction_sell_lines',
                'transactions.id',
                'transaction_sell_lines.transaction_id'
            )
                ->leftjoin(
                    'products',
                    'transaction_sell_lines.product_id',
                    'products.id'
                );
        }
        if ($type == 'purchase' || $type == 'opening_stock') {
            $account_query->leftjoin(
                'purchase_lines',
                'transactions.id',
                'purchase_lines.transaction_id'
            )
                ->leftjoin(
                    'products',
                    'purchase_lines.product_id',
                    'products.id'
                );
        }
        if ($type == 'stock_adjustment') {
            $account_query->leftjoin(
                'stock_adjustment_lines',
                'transactions.id',
                'stock_adjustment_lines.transaction_id'
            )
                ->leftjoin(
                    'products',
                    'stock_adjustment_lines.product_id',
                    'products.id'
                );
        }
        $account_query->where('products.sub_category_id', $sub_cat_id)->groupBy('products.sub_category_id');

        $account_query->where(function ($q) use ($module) {
            $q->whereNull('products.disabled_in')->orwhereRaw("NOT FIND_IN_SET(?, products.disabled_in)", [$module]);
        });

        $account_query->where('accounts.business_id', $business_id)
            ->where('transactions.type', $type)
            ->whereIn('accounts.asset_type', $stock_group_id_array)
            ->whereNull('AT.deleted_at')
            ->where('is_closed', 0)
            ->when(!empty($location_id), function ($query) use ($location_id) {
                return $query->where('transactions.location_id', $location_id);
            });
        if (!empty($start_date) && !empty($end_date) && !$get_previous && $type != 'opening_stock') {
            $account_query->whereDate('AT.operation_date', '>=', $start_date);
            $account_query->whereDate('AT.operation_date', '<=', $end_date);
        }
        if ($get_previous && $type != 'opening_stock') {
            $account_query->whereDate('AT.operation_date', '<', $start_date);
        }
        if ($type == 'opening_stock') {
            $account_query->whereDate('AT.operation_date', '<', $end_date);
        }

        if (!$get_qty) {
            $balance = $account_query->select(
                DB::raw("SUM(AT.amount) as balance")
            )->first();
        }
        if ($get_qty) {
            if ($type == 'sell') {
                $balance = $account_query->groupBy('products.sub_category_id')->select(
                    DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax as balance ')
                )->first();
            }
            if ($type == 'purchase' || $type == 'opening_stock') {
                $balance = $account_query->select(
                    DB::raw("SUM(purchase_lines.quantity) as balance")
                )->first();
            }
            if ($type == 'stock_adjustment') {
                $balance = $account_query->select(
                    DB::raw("SUM(stock_adjustment_lines.quantity) as balance")
                )->first();
            }
        }



        return $balance ? $balance->balance : 0;
    }


    public static function getAccountGroupBalanceByType($account_group_id, $type, $start_date, $end_date, $is_previous = false)
    {

        $balance = AccountTransaction::leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')
            ->where('accounts.asset_type', $account_group_id)
            ->where('accounts.is_main_account', 0)
            ->where('type', $type)
            ->where(
                function ($query) {
                    $query->where('sub_type', '!=', 'opening_balance')
                        ->orWhereNull('sub_type');
                }
            );

        // $balance->where('sub_type', '!=', 'opening_balance');
        $amount = 0;
        if (!$is_previous) {
            $balance->whereDate('operation_date', '>=', $start_date);
            $balance->whereDate('operation_date', '<=', $end_date);
            $amount = $balance->sum('amount');
        } else {
            $balance->whereDate('operation_date', '<', $start_date);
            $amount = $balance->sum('amount');
        }


        if (!empty($amount)) {
            return $amount;
        }
        return 0;
    }

    public static function getAccountTotalByRange($account_id, $type, $start_date, $end_date)
    {
        $amount = 0;
        $start_date = date('Y-m-d', strtotime("-1 day", strtotime($start_date)));
        $end_date = date('Y-m-d', strtotime("-1 day", strtotime($end_date)));


        $balance = AccountTransaction::leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')
            ->where('accounts.id', $account_id)
            ->where('account_transactions.type', $type)
            ->whereDate('operation_date', '>=', $start_date)
            ->whereDate('operation_date', '<=', $end_date);


        $amount = $balance->sum('amount');
        if (!empty($amount)) {
            return $amount;
        }
        return 0;
    }

    public static function getAccountBalanceByType($account_id, $type, $start_date, $end_date, $is_previous = false, $opening_balance_only = false, $sub_type = null)
    {
        $balance = AccountTransaction::leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')
            ->leftjoin('transactions', 'account_transactions.transaction_id', 'transactions.id')
            ->where('accounts.id', $account_id)
            ->where('accounts.is_main_account', 0)
            ->where('account_transactions.type', $type);
        if ($opening_balance_only) {
            $balance->whereIn('transactions.type', ['opening_balance'])->where('transactions.final_total', '>', 0);
        } else {
            $balance->whereNotIn('transactions.type', ['opening_balance']);
        }

        $amount = 0;
        if (!$opening_balance_only) {
            if (!$is_previous) {
                $balance->whereDate('operation_date', '>=', $start_date);
                $balance->whereDate('operation_date', '<=', $end_date);
            } else {
                $balance->whereDate('operation_date', '<', $start_date);
            }
        }
        if (strlen($sub_type) > 0) {
            $balance->where('account_transactions.sub_type', $sub_type);
        }

        $amount = $balance->sum('amount');
        if (!empty($amount)) {
            return $amount;
        }
        return 0;
    }

    public static function getAccountGroupOpeningBalanceByType($account_group_id, $type, $start_date, $end_date)
    {
        $balance = AccountTransaction::leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')
            ->where('accounts.asset_type', $account_group_id)
            ->where('accounts.is_main_account', 0)
            ->where('type', $type);

        $amount = 0;

        $balance->where('sub_type', '=', 'opening_balance');
        // $balance->whereDate('operation_date', '<', $start_date);
        $amount = $balance->sum('amount');


        if (!empty($amount)) {
            return $amount;
        }
        return 0;
    }

    // function for creating 'Post Dated Cheques' account 
    public static function crearePostdatedChequesAccount($business_id, $user_id)
    {
        Account::where('business_id', $business_id)->where('name', 'Company Post dated Cheques')->update(['name' => 'Post Dated Cheques']);

        $account_type = AccountType::getAccountTypeIdByName('Current Assets', $business_id, true);
        $account_group = AccountGroup::getGroupByName('Bank Account', true);

        // if 'Post Dated Cheques' account is not created, then create
        $account_post_dated_cheque = Account::where('business_id', $business_id)->where('name', 'Post Dated Cheques')->first();
        if (empty($account_post_dated_cheque)) {
            $account = new Account;
            $account->business_id = $business_id;
            $account->name = "Post Dated Cheques";
            $account->account_number = rand(111111, 999999);
            $account->account_type_id = $account_type;
            $account->asset_type = $account_group;
            $account->is_need_cheque = "N";
            $account->created_by = $user_id;
            $account->is_main_account = 0;
            $account->is_closed = 0;
            $account->visible = 1;
            $account->disabled = 0;
            $account->save();
        }

        // if issued post dated cheques not available, then create
        $issued_post_dated_cheque = Account::where('business_id', $business_id)->where('name', 'Issued Post Dated Cheques')->first();
        if (empty($issued_post_dated_cheque)) {
            $account = new Account;
            $account->business_id = $business_id;
            $account->name = "Issued Post Dated Cheques";
            $account->account_number = rand(111111, 999999);
            $account->account_type_id = AccountType::getAccountTypeIdByName('Current Liabilities', $business_id, true);;
            $account->asset_type = AccountGroup::getGroupByName('Bank Account', true);
            $account->is_need_cheque = "N";
            $account->created_by = $user_id;
            $account->is_main_account = 0;
            $account->is_closed = 0;
            $account->visible = 1;
            $account->disabled = 0;
            $account->save();
        }

        return "Ok";
    }

    /**
     * Get all of the chequeBank for the Account
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chequeBankAccount()
    {
        return $this->hasMany(ChequerBankAccount::class, 'account_id');
    }

    /**
     * Validate that a cash account exists for the business
     *
     * @param int $business_id
     * @return bool
     */
    public static function validateCashAccountExists($business_id)
    {
        $cash_account = self::getAccountByAccountName('Cash');
        return $cash_account && $cash_account->business_id == $business_id && $cash_account->is_closed == 0;
    }

    /**
     * Get the default cash account for a business and location
     *
     * @param int $business_id
     * @param int|null $location_id
     * @return Account|null
     */
    public static function getDefaultCashAccount($business_id, $location_id = null)
    {
        $cash_account = self::where('business_id', $business_id)
            ->where(DB::raw("REPLACE(`name`, '  ', ' ')"), 'Cash')
            ->where('is_closed', 0)
            ->first();

        if (!$cash_account) {
            Log::warning("Cash account not found for business ID: {$business_id}");
        }

        return $cash_account;
    }

    /**
     * Ensure cash account exists, create if missing
     *
     * @param int $business_id
     * @return Account
     * @throws \Exception
     */
    public static function ensureCashAccountExists($business_id)
    {
        $cash_account = self::getDefaultCashAccount($business_id);
        
        if (!$cash_account) {
            // Try to create a basic cash account
            try {
                $cash_account = self::create([
                    'business_id' => $business_id,
                    'name' => 'Cash',
                    'account_number' => 'CASH-001',
                    'account_type_id' => 1, // Assuming 1 is for current assets
                    'is_closed' => 0,
                    'created_by' => auth()->id() ?? 1
                ]);
                
                Log::info("Created missing cash account for business ID: {$business_id}");
            } catch (\Exception $e) {
                Log::error("Failed to create cash account for business ID: {$business_id}. Error: " . $e->getMessage());
                throw new \Exception('Cash account is required but could not be created. Please contact administrator.');
            }
        }

        return $cash_account;
    }

    /**
     * Get cash account ID safely with null check
     *
     * @param int $business_id
     * @return int|null
     */
    public static function getCashAccountId($business_id)
    {
        $cash_account = self::getDefaultCashAccount($business_id);
        return $cash_account ? $cash_account->id : null;
    }

    public static function isIncomeFreeProductsOrSamplesAccountName(?string $name): bool
    {
        $normalized_name = trim((string) $name);

        return strcasecmp($normalized_name, self::FREE_PRODUCTS_OR_SAMPLES_ACCOUNT_NAME) === 0;
    }

    public static function isIncomeFreeProductsOrSamplesAccount(?self $account): bool
    {
        return $account instanceof self
            && self::isIncomeFreeProductsOrSamplesAccountName($account->name);
    }

    public static function ensureIncomeFreeProductsOrSamplesAccountsByBusiness(int $business_id, ?int $user_id = null): int
    {
        $income_account_type = AccountType::where('business_id', $business_id)
            ->whereRaw('LOWER(name) = ?', ['income'])
            ->orderBy('parent_account_type_id')
            ->first();

        if (empty($income_account_type)) {
            $income_account_type = AccountType::where('business_id', $business_id)
                ->where('name', 'like', '%Income%')
                ->orderBy('parent_account_type_id')
                ->first();
        }

        if (empty($income_account_type)) {
            Log::warning('Income account type not found for free products account creation', [
                'business_id' => $business_id,
            ]);

            return 0;
        }

        $sales_income_group = AccountGroup::where('business_id', $business_id)
            ->where('name', 'Sales Income')
            ->first();

        $locations = BusinessLocation::where('business_id', $business_id)->pluck('id');
        $created_count = 0;
        $resolved_user_id = !empty($user_id) ? $user_id : 1;

        foreach ($locations as $location_id) {
            $location_key = (string) $location_id;

            $existing_account = self::where('business_id', $business_id)
                ->where('location_id', $location_key)
                ->whereRaw("REPLACE(`name`, '  ', ' ') = ?", [self::FREE_PRODUCTS_OR_SAMPLES_ACCOUNT_NAME])
                ->first();

            if (!empty($existing_account)) {
                continue;
            }

            $account_number = self::getNextIncomeAccountNumber($business_id);

            self::create([
                'business_id' => $business_id,
                'location_id' => $location_key,
                'name' => self::FREE_PRODUCTS_OR_SAMPLES_ACCOUNT_NAME,
                'account_number' => $account_number,
                'account_type_id' => $income_account_type->id,
                'asset_type' => !empty($sales_income_group) ? $sales_income_group->id : null,
                'is_need_cheque' => 'N',
                'created_by' => $resolved_user_id,
                'is_main_account' => 0,
                'parent_account_id' => null,
                'show_in_balance_sheet' => 0,
                'is_closed' => 0,
                'visible' => 1,
                'disabled' => 0,
            ]);

            $created_count++;
        }

        return $created_count;
    }

    private static function getNextIncomeAccountNumber(int $business_id): string
    {
        $account_number_setup = AccountNumber::leftJoin(
            'default_account_types as type',
            'account_numbers.account_type',
            '=',
            'type.id'
        )
            ->whereRaw('LOWER(type.name) = ?', ['income'])
            ->where('account_numbers.business_id', $business_id)
            ->select(['account_numbers.prefix', 'account_numbers.account_number'])
            ->first();

        if (!empty($account_number_setup)) {
            $prefix = trim((string) $account_number_setup->prefix);
            $configured_start = (int) $account_number_setup->account_number;
            $latest_number = null;

            $latest_account = self::where('business_id', $business_id)
                ->where('account_number', 'like', $prefix . '-%')
                ->orderByDesc('id')
                ->first();

            if (!empty($latest_account) && preg_match('/(\d+)$/', (string) $latest_account->account_number, $matches)) {
                $latest_number = (int) $matches[1];
            }

            $next_number = is_null($latest_number)
                ? max($configured_start, 1)
                : max($latest_number + 1, $configured_start);

            return $prefix . '-' . $next_number;
        }

        $fallback_latest = self::where('business_id', $business_id)
            ->where('account_number', 'like', 'INC-%')
            ->orderByDesc('id')
            ->first();

        $next_number = 1;
        if (!empty($fallback_latest) && preg_match('/(\d+)$/', (string) $fallback_latest->account_number, $matches)) {
            $next_number = ((int) $matches[1]) + 1;
        }

        return 'INC-' . $next_number;
    }

    private static function normalizeReportDate($value)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $matches)) {
                return Carbon::createFromFormat('Y-m-d', $matches[0])->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            Log::warning('Invalid account report date received', [
                'value' => $value,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function ensureSalesDiscountAccount($business_id, $user_id = null)
    {
        $user_id = $user_id ?? 1;

        // 1. Get Income Account Type
        $income_type = AccountType::where('business_id', $business_id)
            ->where('name', 'Income')
            ->first();

        if (empty($income_type)) {
            Log::warning("Income account type not found for business ID: {$business_id}");
            return;
        }

        // 2. Ensure "Contra Revenue Account" Group exists under Income
        $contra_group = AccountGroup::where('business_id', $business_id)
            ->where('name', 'Contra Revenue Account')
            ->first();

        if (empty($contra_group)) {
            $contra_group = AccountGroup::create([
                'business_id' => $business_id,
                'name' => 'Contra Revenue Account',
                'account_type_id' => $income_type->id,
                'note' => 'Default Contra Revenue Account Group'
            ]);
        } else {
            // Update type if it's different
            if ($contra_group->account_type_id != $income_type->id) {
                $contra_group->account_type_id = $income_type->id;
                $contra_group->save();
            }
        }

        // 3. Ensure "Sales Discount" Account exists under this group and type
        $sales_discount = Account::where('business_id', $business_id)
            ->where('name', 'Sales Discount')
            ->first();

        if (empty($sales_discount)) {
            Account::create([
                'business_id' => $business_id,
                'name' => 'Sales Discount',
                'account_number' => 'SD100',
                'account_type_id' => $income_type->id,
                'asset_type' => $contra_group->id,
                'created_by' => $user_id,
                'is_main_account' => 0,
                'is_closed' => 0,
                'visible' => 1,
                'show_in_balance_sheet' => 1
            ]);
        } else {
            // Update group and type if they are different
            if ($sales_discount->account_type_id != $income_type->id || $sales_discount->asset_type != $contra_group->id) {
                $sales_discount->account_type_id = $income_type->id;
                $sales_discount->asset_type = $contra_group->id;
                $sales_discount->save();
            }
        }
    }
}
