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
 * Dropdowns, lookups and permission helpers.
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
 * Methods here: getParentAccountsByType, getAccountGroupByAccount, getBankAccountByGroupDP, getAccountByGroupId, getBankAccountDropDown, getPermittedLocations, userCan, isIncomeFreeProductsOrSamplesLockedAccount, lockedAccountResponse
 */
trait ProvidesAccountLookups
{
    /**
     * Finance-owned account-group options for the Add Account form.
     *
     * S724 #3: keep this lookup inside Finance so the modal never depends on
     * the legacy/root /get-account-groups route. Child account types inherit
     * the groups configured for their parent type; exact child groups are also
     * included when a tenant has created them explicitly.
     */
    public function getAccountGroupsByType($type_id)
    {
        $businessId = (int) (session()->get('user.business_id') ?: session()->get('business.id'));
        $typeId = (int) $type_id;

        if ($businessId <= 0 || $typeId <= 0) {
            return '<option value="">' . e(__('messages.please_select')) . '</option>';
        }

        $accountType = AccountType::query()
            ->where('business_id', $businessId)
            ->find($typeId);

        if (empty($accountType)) {
            return '<option value="">' . e(__('messages.please_select')) . '</option>';
        }

        $groupTypeIds = [$accountType->id];
        if (! empty($accountType->parent_account_type_id)) {
            $groupTypeIds[] = (int) $accountType->parent_account_type_id;
        }

        $groups = AccountGroup::query()
            ->where('business_id', $businessId)
            ->whereIn('account_type_id', array_values(array_unique($groupTypeIds)))
            ->orderBy('name')
            ->get();

        if ($groups->isEmpty()) {
            return '<option value="">No Account Groups for selected Account Type — optional</option>';
        }

        $html = '<option value="">' . e(__('messages.please_select')) . '</option>';
        foreach ($groups as $group) {
            $showCheque = isset($group->reg_cheque) ? (string) $group->reg_cheque : 'N';
            $html .= '<option value="' . (int) $group->id . '" data-show-cheque="' . e($showCheque) . '">'
                . e($group->name) . '</option>';
        }

        return $html;
    }

public function getParentAccountsByType($type_id)
{
    $business_id = session()->get('user.business_id');
    $location_id = request()->get('location_id');

    $parent_accounts = Account::leftjoin(
            'account_types',
            'accounts.account_type_id',
            'account_types.id'
        )
        ->where(function ($query) use ($type_id) {
            $query->where('account_types.id', $type_id)
                  ->orWhere('account_types.parent_account_type_id', $type_id);
        })
        ->where('accounts.business_id', $business_id)

        ->when(!empty($location_id) && $location_id != 'all', function ($query) use ($location_id) {
            $query->where(function ($q) use ($location_id) {
                $q->where('accounts.location_id', $location_id)
                  ->orWhereNull('accounts.location_id')
                  ->orWhere('accounts.location_id', 'all');
            });
        })

        ->select('accounts.id', 'accounts.name')
        ->orderBy('accounts.name', 'asc')
        ->get();

    $html = '<option selected="selected" value="">Please Select</option>';

    foreach ($parent_accounts as $account) {
        $html .= '<option value="' . $account->id . '">' . $account->name . '</option>';
    }

    return $html;
}

    public function getAccountGroupByAccount($account_id)
    {
        $business_id         = session()->get('user.business_id');
        $parent_accounts     = Account::where('id', $account_id)->where('business_id', $business_id)->select('asset_type')->first();
        $asset_type_accounts = Account::AssetTypeAccountGroupActive();
        $html                = '<option selected="selected" value="' . $parent_accounts->asset_type . '">' . $asset_type_accounts[$parent_accounts->asset_type] . '</option>';

        return $html;
    }

    /**
     * show image modal.
     *
     * @return \Illuminate\Http\Response
     */

    public function getBankAccountByGroupDP()
    {
        $group_name  = request()->group_name;
        $location_id = request()->location_id;
        $business_id = session()->get('user.business_id');

        if (! empty($group_name)) {
            if ($group_name == 'credit_sale') {
                $acc_id = $this->moduleUtil->account_exist_return_id('Accounts Receivable');

                $accounts = Account::where('id', $acc_id)->select('name', 'id')->pluck('name', 'id');
            } elseif ($group_name == 'credit_purchase' || $group_name == 'credit_expense') {
                $acc_id   = $this->moduleUtil->account_exist_return_id('Accounts Payable');
                $accounts = Account::where('id', $acc_id)->select('name', 'id')->pluck('name', 'id');
            } elseif ($group_name === 'cheque') {
                $cheque_group_id = AccountGroup::where('business_id', $business_id)
                    ->where('name', "Cheques in Hand (Customer's)")
                    ->value('id');

                $accounts = Account::where('business_id', $business_id)
                    ->where('is_main_account', 0)
                    ->where(function ($query) use ($cheque_group_id) {
                        $query->where('name', 'Cheques in Hand')
                            ->orWhere('name', 'Post Dated Cheques')
                            ->orWhere('name', 'Issued Post Dated Cheques');

                        if (! empty($cheque_group_id)) {
                            $query->orWhere('asset_type', $cheque_group_id);
                        }
                    })
                    ->orderBy('name')
                    ->pluck('name', 'id');

                if ($accounts->isEmpty()) {
                    $accounts = Account::where('business_id', $business_id)
                        ->where('is_main_account', 0)
                        ->where('name', 'LIKE', '%Cheque%')
                        ->orderBy('name')
                        ->pluck('name', 'id');
                }
            } elseif ($group_name === 'pre_payments') {
                $accounts = Account::where('business_id', $business_id)
                    ->whereIn(DB::raw('LOWER(name)'), ['pre_payments', 'pre payments', 'prepayments'])
                    ->orderBy('name')
                    ->pluck('name', 'id');
            } else {
                $group_id = $this->moduleUtil->one_payment_type($group_name, $location_id);
                // $group_id = (AccountGroup::where('business_id', $business_id)->where('name', 'Like','%'.$group_name.'%')->first())->id;

                if (! empty($group_id)) {
                    $accounts = Account::getAccountByAccountGroupId($group_id);
                } else {
                    $accounts = [];
                }
            }
        } else {
            $accounts = [];
        }

        $dropdown = '<option value="">Please Select</option>';
        foreach ($accounts as $key => $account) {
            if (strcasecmp(trim($account), 'Issued Post Dated Cheques') === 0) {
                continue;
            }
            $dropdown .= '<option value="' . $key . '">' . $account . '</option>';
        }

        return $dropdown;
    }

    // S673: nullable to match the now-optional {group_id?} route parameter.
    // The body already returns just the "Please Select" option when empty.
    public function getAccountByGroupId($group_id = null)
    {
        if (! empty($group_id)) {
            $accounts = Account::getAccountByAccountGroupId($group_id);

            // S723 #1: a transfer destination must never offer the exact same
            // account selected as Transfer From.  Keep this protection on the
            // server as well as in the modal JS because changing Account Group
            // rebuilds the destination options from this endpoint.
            $excludeAccountId = (int) request()->input('exclude_account_id', 0);
            if ($excludeAccountId > 0 && $accounts instanceof \Illuminate\Support\Collection) {
                $accounts = $accounts->except([$excludeAccountId]);
            }
        } else {
            $accounts = collect();
        }
        /*
         | IS2104: say WHY the list is empty.
         |
         | A group with no accounts returned only "Please Select", so the field
         | looked broken. It is usually not: several account groups ship with no
         | default accounts at all - Direct Expense, Indirect Expense, IOC, Loans
         | Taken, Own Cards, Owners Drawings - and a business is expected to add
         | its own under List Accounts. Two group names are also duplicated in the
         | template, so one copy of each is always empty and there is no way to
         | tell them apart on screen.
         |
         | A disabled option explains it instead. It cannot be selected, so no
         | form can post it, and the message names the remedy.
        */
        if (count($accounts) === 0) {
            if (empty($group_id)) {
                return '<option value="">Please Select</option>';
            }

            return '<option value="">Please Select</option>'
                 . '<option value="" disabled>'
                 . 'No accounts in this group — add one under List Accounts'
                 . '</option>';
        }

        $dropdown = '<option value="">Please Select</option>';
        foreach ($accounts as $key => $account) {
            $dropdown .= '<option value="' . $key . '">' . $account . '</option>';
        }

        return $dropdown;
    }

    public function getBankAccountDropDown()
    {
        $type = request()->type;
        if ($type == 'cash') {
            $group_name = 'Cash Account';
        }
        if ($type == 'cheque') {
            $group_name = "Cheques in Hand (Customer's)";
        }
        if ($type == 'card') {
            $group_name = 'Card';
        }
        $default_account_id = BusinessLocation::getDefaultAccountIdForMethod($type, request()->location_id);
        $business_id        = session()->get('user.business_id');
        $group_id           = AccountGroup::where('business_id', $business_id)->where('name', $group_name)->select('id')->first();
        if (! empty($group_id)) {
            $accounts = Account::getAccountByAccountGroupId($group_id->id);
        } else {
            $accounts = Account::where('business_id', $business_id)->where('is_main_account', 0)->pluck('name', 'id');
        }
        $selected = 'selected';
        $dropdown = '<option value="">Please Select</option>';
        foreach ($accounts as $key => $account) {
            if ($key == $default_account_id) {
                $selected = 'selected';
            }
            $check_insufficient_balance = false;
            $check_insufficient_balance = Account::checkInsufficientBalance($key);
            $account_balance            = 0;
            $account_balance            = $this->getAccountBalance($key);
            $dropdown .= '<option  ' . $selected . ' value="' . $key . '" data-check_insufficient_balance="' . $check_insufficient_balance . '" data-account_balance="' . $account_balance->balance . '">' . $account . '</option>';
            $selected = null;
        }

        return $dropdown;
    }

    private function getPermittedLocations()
    {
        $business_id = (int) (session()->get('user.business_id') ?: session()->get('business.id'));

        /*
         |------------------------------------------------------------------
         | Finance List Accounts - resolve the CURRENT authenticated user.
         |------------------------------------------------------------------
         |
         | Do not re-load a user only by the numeric session user id here. In
         | a tenant/central multi-database installation the same numeric id can
         | legitimately exist in more than one database/context. The guard has
         | already resolved the signed-in tenant user, so Auth::user() is the
         | authoritative identity for location access.
         |
         | The Business Admin (Admin#{business_id}) and the business owner must
         | always see all locations for their own business. They should not lose
         | Finance accounts merely because no explicit location.* permissions
         | were stored on the Admin role.
         */
        $user = Auth::user();

        if (! is_object($user) || $business_id <= 0) {
            return 'all';
        }

        $user_business_id = (int) ($user->business_id ?? 0);
        if ($user_business_id > 0 && $user_business_id !== $business_id) {
            Log::warning('Finance location scope blocked due to authenticated user/business mismatch.', [
                'auth_user_id' => (int) ($user->id ?? 0),
                'auth_user_business_id' => $user_business_id,
                'session_business_id' => $business_id,
            ]);

            return [];
        }

        $is_business_admin = false;

        try {
            $is_business_admin = (bool) $this->moduleUtil->is_admin($user, $business_id);
        } catch (\Throwable $e) {
            // Keep the owner check below as the database-backed fallback.
        }

        if (! $is_business_admin) {
            $owner_id = (int) (Business::where('id', $business_id)->value('owner_id') ?: 0);
            $is_business_admin = $owner_id > 0 && (int) ($user->id ?? 0) === $owner_id;
        }

        if ($is_business_admin) {
            return 'all';
        }

        if (method_exists($user, 'permitted_locations')) {
            return $user->permitted_locations();
        }

        return 'all';
    }

    /**
     * Build the exact, business-scoped data required by the Add Account form.
     *
     * Keeping this in one method lets the List Accounts page preload the modal
     * and also keeps the conventional /account/create endpoint functional.
     */

    private function userCan(string $ability): bool
    {
        $user = Auth::user();

        return is_object($user) && method_exists($user, 'can')
            ? (bool) $user->can($ability)
            : false;
    }

    /**
     * Resolve permitted locations from a concrete User model for static analysis safety.
     *
     * @return array|string
     */

    private function isIncomeFreeProductsOrSamplesLockedAccount($account): bool
    {
        return Account::isIncomeFreeProductsOrSamplesAccount($account);
    }

    private function lockedAccountResponse(): array
    {
        return [
            'success' => false,
            'msg' => 'This account is system-generated and cannot be edited.',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
}
