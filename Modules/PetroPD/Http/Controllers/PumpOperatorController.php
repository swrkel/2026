<?php
namespace Modules\PetroPD\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\Business;
use App\BusinessCategory;
use App\BusinessLocation;
use App\Contact;
use App\Notifications\CustomerNotification;
use App\Product;
use App\PumperLoginAttempt;
use App\PumperLoginAttemptHistory;
use App\Services\PumperLoginAttemptAuditService;
use App\System;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\UserStorePermission;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Litespeed\LSCache\LSCache;
use Maatwebsite\Excel\Facades\Excel;
use Modules\PetroPD\Entities\FuelTank;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorCommission;
use Modules\Superadmin\Entities\DefaultBusinessType;
use Modules\Superadmin\Entities\GiveAwayGift;
use Modules\Superadmin\Entities\Package;
use Modules\Superadmin\Entities\PayOnline;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class PumpOperatorController extends Controller
{

    /**
     * Urgent PetroPD / PumperDashboard login fix.
     *
     * This method is used by the keypad login page instead of the legacy
     * Auth\PumpOperatorLoginController. It supports PetroPD-created pump
     * operators, leading-zero passcodes, older records where the passcode was
     * saved only as the password hash, and screens where business_id is not
     * posted but company_number is posted.
     */
    public function pumperLogin(Request $request)
    {
        /*
         * SEQ077 Multi-Tenant Pumper Login Fix
         *
         * Root cause found:
         * - The pumper login route reaches this controller.
         * - It was checking users in the base DB.
         * - In this system, real client users are stored in tenant DBs.
         *
         * This fix:
         * 1. Resolves the tenant DB from the central tenants table.
         * 2. Switches the current DB connection to that tenant DB.
         * 3. Checks users.pump_operator_passcode inside the tenant DB.
         */
        $raw_passcode = (string) $request->input('passcode');
        $passcode = trim($raw_passcode);
        $login_display = $request->input('login_display', 'pumper_dashboard');
        $posted_business_id = (int) $request->input('business_id');
        $company_number = trim((string) $request->input('company_number'));

        if ($passcode === '') {
            return redirect()->back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Please enter the operator passcode.',
            ]);
        }

        try {
            $central_database = DB::connection()->getDatabaseName();
            $tenant_database = null;
            $tenant_id = null;
            $business_id = $posted_business_id;

            /*
             * Build passcode variants.
             * This supports normal 4 digits, leading zeros, and accidental non-digit input.
             */
            $digits_only = preg_replace('/\D+/', '', $passcode);
            $without_leading_zero = ltrim($digits_only, '0');
            if ($without_leading_zero === '') {
                $without_leading_zero = '0';
            }

            $passcode_values = array_values(array_unique(array_filter([
                $passcode,
                $digits_only,
                $without_leading_zero,
                str_pad($without_leading_zero, 4, '0', STR_PAD_LEFT),
            ], function ($value) {
                return $value !== null && $value !== '';
            })));

            /*
             * SEQ077 Resolve tenant DB by checking company number AND passcode.
             *
             * Company/business IDs can repeat in each tenant database. Therefore
             * company number alone can select the wrong tenant. We first look for
             * the tenant where the submitted passcode exists for that company.
             */
            if (Schema::hasTable('tenants')) {
                $tenants = DB::table('tenants')->select('id', 'data')->get();

                foreach ($tenants as $tenant) {
                    $tenant_data = json_decode($tenant->data, true);
                    $candidate_database = $tenant_data['tenancy_db_name'] ?? null;

                    if (empty($candidate_database)) {
                        continue;
                    }

                    try {
                        config(['database.connections.mysql.database' => $candidate_database]);
                        DB::purge('mysql');
                        DB::reconnect('mysql');

                        if (! Schema::hasTable('business') || ! Schema::hasTable('users')) {
                            continue;
                        }

                        $business_query = DB::table('business');

                        if ($company_number !== '') {
                            $business_query->where(function ($q) use ($company_number) {
                                $q->where('company_number', $company_number);

                                if (Schema::hasColumn('business', 'company_no')) {
                                    $q->orWhere('company_no', $company_number);
                                }
                            });
                        } elseif ($business_id > 0) {
                            $business_query->where('id', $business_id);
                        }

                        $business = $business_query->first();

                        if (empty($business)) {
                            continue;
                        }

                        $candidate_business_id = (int) $business->id;

                        $passcode_match_count = DB::table('users')
                            ->whereNotNull('pump_operator_id')
                            ->where('pump_operator_id', '!=', 0)
                            ->where(function ($query) use ($passcode_values) {
                                foreach ($passcode_values as $value) {
                                    $query->orWhereRaw('TRIM(CAST(pump_operator_passcode AS CHAR)) = ?', [$value]);
                                }
                            })
                            ->where('business_id', $candidate_business_id)
                            ->count();

                        if ($passcode_match_count > 0) {
                            $tenant_database = $candidate_database;
                            $tenant_id = $tenant->id;
                            $business_id = $candidate_business_id;
                            break;
                        }

                        Log::warning('SEQ077 tenant candidate skipped - company matched but passcode not found', [
                            'tenant_id' => $tenant->id,
                            'candidate_database' => $candidate_database,
                            'company_number' => $company_number,
                            'candidate_business_id' => $candidate_business_id,
                        ]);
                    } catch (\Exception $tenantException) {
                        Log::warning('SEQ077 tenant scan skipped database', [
                            'tenant_id' => $tenant->id,
                            'candidate_database' => $candidate_database,
                            'error' => $tenantException->getMessage(),
                        ]);
                    }
                }

                /*
                 * Fallback only when no passcode match was found:
                 * choose the tenant by company number, so the user gets a normal
                 * incorrect-passcode message instead of tenant-not-found.
                 */
                if (empty($tenant_database)) {
                    config(['database.connections.mysql.database' => $central_database]);
                    DB::purge('mysql');
                    DB::reconnect('mysql');

                    $tenants = DB::table('tenants')->select('id', 'data')->get();

                    foreach ($tenants as $tenant) {
                        $tenant_data = json_decode($tenant->data, true);
                        $candidate_database = $tenant_data['tenancy_db_name'] ?? null;

                        if (empty($candidate_database)) {
                            continue;
                        }

                        try {
                            config(['database.connections.mysql.database' => $candidate_database]);
                            DB::purge('mysql');
                            DB::reconnect('mysql');

                            if (! Schema::hasTable('business')) {
                                continue;
                            }

                            $business_query = DB::table('business');

                            if ($company_number !== '') {
                                $business_query->where(function ($q) use ($company_number) {
                                    $q->where('company_number', $company_number);

                                    if (Schema::hasColumn('business', 'company_no')) {
                                        $q->orWhere('company_no', $company_number);
                                    }
                                });
                            } elseif ($business_id > 0) {
                                $business_query->where('id', $business_id);
                            }

                            $business = $business_query->first();

                            if (! empty($business)) {
                                $tenant_database = $candidate_database;
                                $tenant_id = $tenant->id;
                                $business_id = (int) $business->id;
                                break;
                            }
                        } catch (\Exception $tenantException) {
                            Log::warning('SEQ077 fallback tenant scan skipped database', [
                                'tenant_id' => $tenant->id,
                                'candidate_database' => $candidate_database,
                                'error' => $tenantException->getMessage(),
                            ]);
                        }
                    }
                }
            }

            /*
             * SEQ139 fallback:
             * If the current connection is already the tenant database (common on
             * localhost/test copies or after replacing the database), there may be
             * no usable central tenants mapping. In that case, validate the current
             * database directly instead of blocking pumper login with tenant-not-found.
             */
            if (empty($tenant_database)) {
                config(['database.connections.mysql.database' => $central_database]);
                DB::purge('mysql');
                DB::reconnect('mysql');

                try {
                    if (Schema::hasTable('business') && Schema::hasTable('users')) {
                        $current_business_query = DB::table('business');

                        if ($company_number !== '') {
                            $current_business_query->where(function ($q) use ($company_number) {
                                $q->where('company_number', $company_number);

                                if (Schema::hasColumn('business', 'company_no')) {
                                    $q->orWhere('company_no', $company_number);
                                }
                            });
                        } elseif ($business_id > 0) {
                            $current_business_query->where('id', $business_id);
                        }

                        $current_business = $current_business_query->first();

                        if (! empty($current_business)) {
                            $tenant_database = $central_database;
                            $tenant_id = null;
                            $business_id = (int) $current_business->id;

                            Log::warning('SEQ139 Pumper Dashboard using current database as tenant fallback', [
                                'tenant_database' => $tenant_database,
                                'resolved_business_id' => $business_id,
                                'posted_business_id' => $posted_business_id,
                                'company_number' => $company_number,
                            ]);
                        }
                    }
                } catch (\Exception $currentDbException) {
                    Log::warning('SEQ139 current database tenant fallback failed', [
                        'central_database' => $central_database,
                        'posted_business_id' => $posted_business_id,
                        'company_number' => $company_number,
                        'error' => $currentDbException->getMessage(),
                    ]);
                }
            }

            if (empty($tenant_database)) {
                config(['database.connections.mysql.database' => $central_database]);
                DB::purge('mysql');
                DB::reconnect('mysql');

                Log::warning('SEQ139 tenant database not resolved for pumper login', [
                    'central_database' => $central_database,
                    'posted_business_id' => $posted_business_id,
                    'company_number' => $company_number,
                    'passcode_length' => strlen($passcode),
                ]);

                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => 'Tenant database was not found for this company number. Please contact administrator.',
                ]);
            }

            /*
             * Ensure we are now connected to the tenant DB before reading users.
             */
            config(['database.connections.mysql.database' => $tenant_database]);
            DB::purge('mysql');
            DB::reconnect('mysql');

            Log::warning('SEQ077 Pumper Dashboard tenant login debug', [
                'central_database' => $central_database,
                'tenant_database' => DB::connection()->getDatabaseName(),
                'tenant_id' => $tenant_id,
                'posted_business_id' => $posted_business_id,
                'resolved_business_id' => $business_id,
                'company_number' => $company_number,
                'raw_passcode_length' => strlen($raw_passcode),
                'trimmed_passcode_length' => strlen($passcode),
                'passcode_values' => $passcode_values,
            ]);

            /*
             * Raw users table lookup inside the resolved tenant DB.
             */
            $raw_user = DB::table('users')
                ->whereNotNull('pump_operator_id')
                ->where('pump_operator_id', '!=', 0)
                ->where(function ($query) use ($passcode_values) {
                    foreach ($passcode_values as $value) {
                        $query->orWhereRaw('TRIM(CAST(pump_operator_passcode AS CHAR)) = ?', [$value]);
                    }
                })
                ->when($business_id > 0, function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                })
                ->orderBy('id', 'desc')
                ->first();

            /*
             * Emergency all-business fallback inside the same tenant database.
             */
            if (empty($raw_user)) {
                $raw_user = DB::table('users')
                    ->whereNotNull('pump_operator_id')
                    ->where('pump_operator_id', '!=', 0)
                    ->where(function ($query) use ($passcode_values) {
                        foreach ($passcode_values as $value) {
                            $query->orWhereRaw('TRIM(CAST(pump_operator_passcode AS CHAR)) = ?', [$value]);
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->first();
            }

            $user = null;
            if (! empty($raw_user)) {
                $user = User::find($raw_user->id);
            }

            /*
             * Compatibility fallback for hashed-only passcode records.
             */
            if (empty($user)) {
                $candidate_query = User::query()
                    ->whereNotNull('pump_operator_id')
                    ->where('pump_operator_id', '!=', 0);

                if ($business_id > 0) {
                    $candidate_query->where('business_id', $business_id);
                }

                $candidate_users = $candidate_query->get();

                foreach ($candidate_users as $candidate_user) {
                    if (! empty($candidate_user->password) && Hash::check($passcode, (string) $candidate_user->password)) {
                        $user = $candidate_user;
                        $user->pump_operator_passcode = $passcode;
                        $user->is_pump_operator = 1;
                        $user->save();
                        break;
                    }
                }
            }

            if (empty($user) || empty($user->pump_operator_id)) {
                Log::warning('SEQ077 Pumper Dashboard passcode rejected in tenant database', [
                    'central_database' => $central_database,
                    'tenant_database' => DB::connection()->getDatabaseName(),
                    'tenant_id' => $tenant_id,
                    'posted_business_id' => $posted_business_id,
                    'resolved_business_id' => $business_id,
                    'company_number' => $company_number,
                    'passcode_length' => strlen($passcode),
                    'business_exact_matches_raw' => DB::table('users')
                        ->where(function ($query) use ($passcode_values) {
                            foreach ($passcode_values as $value) {
                                $query->orWhereRaw('TRIM(CAST(pump_operator_passcode AS CHAR)) = ?', [$value]);
                            }
                        })
                        ->when($business_id > 0, function ($query) use ($business_id) {
                            $query->where('business_id', $business_id);
                        })
                        ->count(),
                    'all_exact_matches_raw' => DB::table('users')
                        ->where(function ($query) use ($passcode_values) {
                            foreach ($passcode_values as $value) {
                                $query->orWhereRaw('TRIM(CAST(pump_operator_passcode AS CHAR)) = ?', [$value]);
                            }
                        })
                        ->count(),
                    'business_pumper_users' => DB::table('users')
                        ->whereNotNull('pump_operator_id')
                        ->where('pump_operator_id', '!=', 0)
                        ->when($business_id > 0, function ($query) use ($business_id) {
                            $query->where('business_id', $business_id);
                        })
                        ->count(),
                    'all_pumper_users' => DB::table('users')
                        ->whereNotNull('pump_operator_id')
                        ->where('pump_operator_id', '!=', 0)
                        ->count(),
                ]);

                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => 'SEQ077: Your Passcode is incorrect. Please recheck and try again.',
                ]);
            }

            if ($business_id <= 0) {
                $business_id = (int) $user->business_id;
            }

            /*
             * Normalize user row after successful match.
             */
            $needs_save = false;

            if ((string) $user->pump_operator_passcode !== $passcode) {
                $user->pump_operator_passcode = $passcode;
                $needs_save = true;
            }

            if ((int) $user->is_pump_operator !== 1) {
                $user->is_pump_operator = 1;
                $needs_save = true;
            }

            if (empty($user->password) || ! Hash::check($passcode, (string) $user->password)) {
                $user->password = Hash::make($passcode);
                $needs_save = true;
            }

            if ($needs_save) {
                $user->save();
            }

            $pump_operator = PumpOperator::withoutGlobalScope('active')
                ->where('business_id', $business_id)
                ->where('id', $user->pump_operator_id)
                ->first();

            if (empty($pump_operator)) {
                Log::warning('SEQ077 Pumper Dashboard user found but pump operator missing', [
                    'tenant_database' => DB::connection()->getDatabaseName(),
                    'user_id' => $user->id,
                    'business_id' => $business_id,
                    'pump_operator_id' => $user->pump_operator_id,
                ]);

                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => 'Pump operator record was not found for this business.',
                ]);
            }

            if (isset($pump_operator->active) && (int) $pump_operator->active !== 1) {
                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => 'This pump operator is inactive. Please contact the administrator.',
                ]);
            }

            Log::warning('SEQ077 Pumper Dashboard login accepted', [
                'tenant_database' => DB::connection()->getDatabaseName(),
                'tenant_id' => $tenant_id,
                'user_id' => $user->id,
                'business_id' => $business_id,
                'pump_operator_id' => $user->pump_operator_id,
                'company_number' => $company_number,
            ]);

            Auth::loginUsingId($user->id);

            /*
             * SEQ026: Store exact pumper identity matched by passcode.
             * Dashboard must use these values, not any stale Auth/session data.
             */
            $request->session()->forget([
                'pumper_user_id',
                'pump_operator_id',
                'pumper_operator_id',
                'pumper_operator_name',
            ]);

            $request->session()->put('tenant_id', $tenant_id);
            $request->session()->put('tenancy_db_name', $tenant_database);
            $request->session()->put('user.business_id', $business_id);
            $request->session()->put('business.id', $business_id);
            $request->session()->put('user.id', $user->id);
            $request->session()->put('user.is_pump_operator', 1);
            $request->session()->put('pumper_user_id', $user->id);
            $request->session()->put('pump_operator_id', $user->pump_operator_id);
            $request->session()->put('pumper_operator_id', $user->pump_operator_id);
            $request->session()->put('pumper_operator_name', optional($pump_operator)->name);
            $request->session()->put('business.company_number', $company_number);
            $request->session()->put('login_display', $login_display);

            if ($login_display === 'my_auto_agent') {
                return redirect()->route('petropd.pd-operators.my-auto-dashboard');
            }

            return redirect()->route('petropd.pd-operators.dashboard');
        } catch (\Exception $e) {
            Log::emergency('SEQ077 Pumper Dashboard passcode login failed. File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    private function standaloneModuleEnabled(int $business_id): bool
    {
        return $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');
    }

    private function canAccessDashboard(?string $permission = null): bool
    {
        $user = Auth::user();

        if (empty($user)) {
            return false;
        }

        if ($user->can('pump_operator.dashboard')) {
            return true;
        }

        if (! empty($permission) && $user->can($permission)) {
            return true;
        }

        return ! empty($user->is_pump_operator) && ! empty($user->pump_operator_id);
    }

    private function myAutoBusinessesForDashboard(): Collection
    {
        return Business::where(function ($query) {
            $query->where('common_settings->is_my_auto', 1)
                ->orWhere('common_settings', 'like', '%"is_my_auto":1%')
                ->orWhere('common_settings', 'like', '%"is_my_auto":"1"%');
        })
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;

    protected $commonUtil;
    protected $businessUtil;
    protected $notificationUtil;

    private $barcode_types;
    private $periodBalanceCache = [];

    /**
     * Constructor
     *
     * @param ProductUtil $product
     * @return void
     */
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, NotificationUtil $notificationUtil)
    {
        $this->commonUtil      = $commonUtil;
        $this->productUtil     = $productUtil;
        $this->moduleUtil      = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil    = $businessUtil;
        $this->notificationUtil = $notificationUtil;

        //barcode types
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */

    public function index()
    {

        $business_id = Auth::user()->business_id;
        if (! $this->standaloneModuleEnabled($business_id)) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {

            $business_id = Auth::user()->business_id;
            if (request()->ajax()) {
                $query = PumpOperator::withoutGlobalScope('active')
                ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                    ->leftjoin('settlements', 'pump_operators.id', 'settlements.pump_operator_id')
                    ->where('pump_operators.business_id', $business_id)
                    ->select([
                        'pump_operators.*',
                        'settlements.settlement_no as st_no',
                        'pump_operators.id as pump_operator_id',
                        'business_locations.name as location_name',
                    ])->groupBy('pump_operators.id');

                if (! empty(request()->location_id)) {
                    $query->where('pump_operators.location_id', request()->location_id);
                }
                if (! empty(request()->pump_operator)) {
                    $query->where('pump_operators.id', request()->pump_operator);
                }
                if (! empty(request()->settlement_no)) {
                    $query->where('settlements.settlement_no', request()->settlement_no);
                }
                if (! empty(request()->status)) {
                    if (request()->status == 'active') {
                        $query->where('pump_operators.active', 1);
                    } else {
                        $query->where('pump_operators.active', 0);
                    }
                }
                if (! empty(request()->type)) {
                }

                $start_date       = request()->start_date;
                $end_date         = request()->end_date;
                
                // FIX: Provide default date range if not provided (today)
                if (empty($start_date)) {
                    $start_date = now()->format('Y-m-d');
                }
                if (empty($end_date)) {
                    $end_date = now()->format('Y-m-d');
                }
                
                $business_details = Business::find($business_id);
                $period_balances  = $this->getPeriodBalancesForRange($business_id, $start_date, $end_date, [
                    'location_id' => request()->location_id,
                ]);
                
                // DEBUG: Log period balances to verify data
                Log::info('Pump Operator List - Date Range: ' . $start_date . ' to ' . $end_date);
                Log::info('Pump Operator List - Period Balances Count: ' . count($period_balances));
                Log::info('Pump Operator List - Period Balances: ', $period_balances);

                $fuel_tanks = Datatables::of($query)
                    ->addColumn(
                        'action',
                        function ($row) {
                            $business_id           = session()->get('user.business_id');
                            $pay_excess_commission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pay_excess_commission');
                            $recover_shortage      = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'recover_shortage');
                            $pump_operator_ledger  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_ledger');

                            $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">

                            <li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@show', [$row->id]) . '"><i class="fa fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>
                            <li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@edit', [$row->id]) . '" class="edit_contact_button"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                            if (auth()->user()->can('pum_operator.active_inactive')) {
                                $html .= '<li class="divider"></li>';
                                if (! $row->active) {
                                    $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@toggleActivate', [$row->id]) . '" class="toggle_active_button"><i class="fa fa-check"></i> ' . __("lang_v1.activate") . '</a></li>';
                                } else {
                                    $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@toggleActivate', [$row->id]) . '" class="toggle_active_button"><i class="fa fa-times"></i> ' . __("lang_v1.deactivate") . '</a></li>';
                                }
                            }

                            $html .= '<li class="divider"></li>';
                            if ($pay_excess_commission) {
                                $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDExcessComissionController@create', ['pump_operator_id' => $row->id]) . '" class="edit_contact_button"> ' . __("petropd::lang.pay_excess_and_commission") . '</a></li>';
                            }
                            if ($recover_shortage) {
                                $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDRecoverShortageController@create', ['pump_operator_id' => $row->id]) . '" class="edit_contact_button"> ' . __("petropd::lang.recover_shortages") . '</a></li>';
                            }
                            $html .= '<li class="divider"></li>
                            <li>
                                <a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@show', [$row->id]) . "?view=contact_info" . '">
                                    <i class="fa fa-user" aria-hidden="true"></i>
                                    ' . __("contact.contact_info", ["contact" => __("contact.contact")]) . '
                                </a>
                            </li>
                            ';

                            if ($pump_operator_ledger) {
                                $html .= '<li>
                                    <a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@show', [$row->id]) . "?view=ledger" . '">
                                        <i class="fa fa-anchor" aria-hidden="true"></i>
                                        ' . __("lang_v1.ledger") . '
                                    </a>
                                </li>';
                            }

                            $html .= '<li>
                                    <a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@listCommission', [$row->id]) . '">
                                        <i class="fa fa-anchor" aria-hidden="true"></i>
                                        ' . __("petropd::lang.list_commission") . '
                                    </a>
                                </li>';

                            $html .= '<li>
                                <a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@show', [$row->id]) . "?view=documents_and_notes" . '">
                                    <i class="fa fa-paperclip" aria-hidden="true"></i>
                                     ' . __("lang_v1.documents_and_notes") . '
                                </a>
                            </li>

                        </ul></div>';

                            return $html;
                        }
                    )
                   ->editColumn('name', function ($row) {
                        $html = $row->name;

                        // show default badge
                        if ($row->is_default == 1) {
                            $html .= " <span class='badge bg-danger'>Default</span>";
                        }

                        // show deactivated badge
                        if ($row->active == 0) {
                            $html .= " <span class='badge bg-secondary'>Deactivated</span>";
                        }

                        return $html;
                    })

                    ->addColumn(
                        'pump_no',
                        ''
                    )
                    ->addColumn(
                        'settlement_no',
                        ''
                    )
                    ->addColumn(
                        'sold_fuel_qty',
                        function ($row) use ($business_details, $start_date, $end_date) {
                            $qty = PumpOperator::leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                                ->leftjoin('transactions', 'pump_operators.id', 'transactions.pump_operator_id')
                                ->leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                                ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                                ->leftjoin('categories', 'products.category_id', 'categories.id')
                                ->where('transactions.type', 'sell')
                                ->where('categories.name', 'Fuel')
                                ->where('transactions.transaction_date', '>=', $start_date)
                                ->where('transactions.transaction_date', '<=', $end_date)
                                ->where('pump_operators.id', $row->pump_operator_id)
                                ->select([
                                    DB::raw('SUM(transaction_sell_lines.quantity) as sold_fuel_qty'),
                                ])
                                ->groupBy('pump_operators.id')->first();

                            if (empty($qty->sold_fuel_qty)) {
                                return $this->productUtil->num_f(0, false, $business_details, true);
                            }
                            return '<span class="sold_fuel_qty" data-orig-value="' . $qty->sold_fuel_qty . '" data-currency_symbol = true>' . $this->productUtil->num_f($qty->sold_fuel_qty, false, $business_details, true) . '</span>';
                        }
                    )
                    ->addColumn(
                        'sale_amount_fuel',
                        function ($row) use ($business_details, $start_date, $end_date) {
                            $amount = PumpOperator::leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                                ->leftjoin('transactions', 'pump_operators.id', 'transactions.pump_operator_id')
                                ->leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                                ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
                                ->leftjoin('categories', 'products.category_id', 'categories.id')
                                ->where('transactions.type', 'sell')
                                ->where('categories.name', 'Fuel')
                                ->where('transactions.transaction_date', '>=', $start_date)
                                ->where('transactions.transaction_date', '<=', $end_date)
                                ->where('pump_operators.id', $row->pump_operator_id)
                                ->select([
                                    'pump_operators.*',
                                    'business_locations.name as location_name',
                                    DB::raw('SUM(transaction_sell_lines.quantity * unit_price) as sale_amount_fuel'),
                                ])->first();
                            return '<span class="display_currency sale_amount_fuel" data-orig-value="' . $amount->sale_amount_fuel . '" data-currency_symbol = true>' . $this->productUtil->num_f($amount->sale_amount_fuel, false, $business_details, false) . '</span>';
                        }
                    )
                    ->addColumn(
                        'current_balance',
                        function ($row) {
                            //$balance_due = $this->getLedgerDetailsForDateRange($row->pump_operator_id, $start_date,$end_date)['balance_due'];
                            $balance_due = $this->transactionUtil->getPumpOperatorBalance($row->pump_operator_id);
                            return '<span class="display_currency current_balance" data-orig-value="' . $balance_due . '" data-currency_symbol = true>' . $this->productUtil->num_f($balance_due, false) . '</span>';
                        }
                    )
                    ->addColumn('balance_for_period', function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [
                            'balance_for_period' => 0,
                        ];
                        $balance_for_period = $summary['balance_for_period'] ?? 0;
                        return '<span class="display_currency text-right balance_for_period" style="display:block" data-orig-value="' . $balance_for_period . '" data-currency_symbol = true>' . $this->productUtil->num_f($balance_for_period, false, $business_details, true) . '</span>';
                    })

                ->editColumn(
                    'excess_amount',
                    function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [];
                        $total_excess = $summary['total_credit_for_period'] ?? 0;
                        return  '<span class="display_currency excess_amount" data-orig-value="' .  $total_excess . '" data-currency_symbol = true>' . $this->productUtil->num_f($total_excess, false, $business_details, true) . '</span>';
                    }
                )
                ->editColumn(
                    'short_amount',
                    function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [];
                        $total_shortage = $summary['total_debit_for_period'] ?? 0;
                        return  '<span class="display_currency short_amount" data-orig-value="' . $total_shortage . '" data-currency_symbol = true>' . $this->productUtil->num_f($total_shortage, false, $business_details, true) . '</span>';
                    }
                )
                    ->editColumn(
                        'commission_type',
                        function ($row) {
                            return ucfirst($row->commission_type);
                        }
                    )
                    ->editColumn(
                        'commission_rate',
                        function ($row) use ($business_details) {
                            return '<span class="display_currency commission_ap" data-orig-value="' . $row->commission_ap . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->commission_ap, false, $business_details, false) . '</span>';
                        }
                    )
                    ->addColumn(
                        'commission_amount',
                        function ($row) use ($business_details, $start_date, $end_date) {
                            $amount = $this->transactionUtil->getPumpOperatorCommission($row->pump_operator_id, $start_date, $end_date);
                            return '<span class="display_currency commission_amount" data-orig-value="' . $amount . '" data-currency_symbol = true>' . $this->productUtil->num_f($amount, false, $business_details, true) . '</span>';
                        }
                    )

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['name', 'action', 'sold_fuel_qty', 'sale_amount_fuel', 'excess_amount', 'short_amount', 'commission_rate', 'commission_amount', 'current_balance', 'balance_for_period'])
                    ->make(true);
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $pump_operators     = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');

        // dd($pump_operators);

        $pumps = Pump::where('pumps.business_id', $business_id)
            ->select('pumps.*')
            ->orderBy('pumps.id')
            ->get();

        foreach ($pumps as $pump) {

            $po_assign = PumpOperatorAssignment::leftjoin('pump_operators', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')
                ->where('pump_operator_assignments.business_id', $business_id)
                ->where('pump_operator_assignments.pump_id', $pump->id)
                ->where('pump_operator_assignments.status', 'open')
                ->select(
                    'pump_operator_assignments.id as assignment_id',
                    'pump_operator_assignments.pump_operator_id',
                    'pump_operator_assignments.shift_number',
                    'pump_operator_assignments.shift_id',
                    'pump_operator_assignments.is_confirmed',
                    'pump_operator_assignments.status as assignment_status',
                    'pump_operators.name as pumper_name'
                )
                ->first();

            if (! empty($po_assign)) {
                $pump->pumper_name       = $po_assign->pumper_name;
                $pump->pump_operator_id  = $po_assign->pump_operator_id;
                $pump->shift_number      = $po_assign->shift_number;
                $pump->shift_id          = $po_assign->shift_id;
                $pump->is_confirmed      = $po_assign->is_confirmed;
                $pump->assignment_id     = $po_assign->assignment_id;
                $pump->assignment_status = $po_assign->assignment_status;
                $pump->is_settled        = false;
            } else {
                // Check if the most recent assignment for this pump has a completed settlement
                $closed_assign = PumpOperatorAssignment::leftjoin('pump_operators', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')
                    ->leftjoin('settlements', 'settlements.id', 'pump_operator_assignments.settlement_id')
                    ->where('pump_operator_assignments.business_id', $business_id)
                    ->where('pump_operator_assignments.pump_id', $pump->id)
                    ->whereNotNull('pump_operator_assignments.settlement_id')
                    ->where('settlements.status', 0) // 0 = finalized settlement
                    ->select(
                        'pump_operator_assignments.pump_operator_id',
                        'pump_operator_assignments.shift_number',
                        'pump_operator_assignments.shift_id',
                        'pump_operators.name as pumper_name',
                        'settlements.settlement_no'
                    )
                    ->orderBy('pump_operator_assignments.id', 'desc')
                    ->first();

                if (! empty($closed_assign)) {
                    $pump->pumper_name       = $closed_assign->pumper_name;
                    $pump->pump_operator_id  = $closed_assign->pump_operator_id;
                    $pump->shift_number      = $closed_assign->shift_number;
                    $pump->settlement_no     = $closed_assign->settlement_no;
                    $pump->is_settled        = true;
                    $pump->assignment_status = 'close';
                }
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location   = current(array_keys($business_locations->toArray()));
        $payment_types      = $this->productUtil->payment_types($default_location);
        $tanks              = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'id');
        $products           = Product::leftjoin('categories', 'products.category_id', 'categories.id')->where('products.business_id', $business_id)->where('categories.name', 'Fuel')->pluck('products.name', 'products.id');
        $settlement_nos     = [];

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')->where('petro_shifts.business_id', $business_id)->select('pump_operators.name', 'petro_shifts.*')->orderBy('id', 'DESC');

        $shifts = $shifts->get();

        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');

        $pumperLoginAttempts = PumperLoginAttempt::where('business_id', $business_id)
            ->where('status', "Blocked")
            ->get();
        $customers = Contact::customersDropdown($business_id, false, true, 'customer');
        return view('petropd::pd_operators.index')->with(compact(
            'business_locations',
            'pump_operators',
            'settlement_nos',
            'message',
            'payment_types',
            'pumps',
            'tanks',
            'products',
            'shifts',
            'customers',
            'pumperLoginAttempts',
            'default_location'
        ));
    }

    public function listCommission($id)
    {
        $business_id = Auth::user()->business_id;
        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_pd_module')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {

            $start_date       = request()->start_date;
            $end_date         = request()->end_date;
            $business_details = Business::find($business_id);

            $business_id = Auth::user()->business_id;
            if (request()->ajax()) {
                $query = PumpOperatorCommission::leftjoin('pump_operators', 'pump_operators.id', 'pump_operator_commission.pump_operator_id')
                    ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                    ->leftjoin('meter_sales', 'pump_operator_commission.meter_sale_id', 'meter_sales.id')
                    ->leftjoin('pumps', 'meter_sales.pump_id', 'pumps.id')
                    ->leftjoin('settlements', 'meter_sales.settlement_no', 'settlements.id')
                    ->where('pump_operator_commission.transaction_date', '>=', $start_date)
                    ->where('pump_operator_commission.transaction_date', '<=', $end_date)
                    ->where('pump_operator_commission.pump_operator_id', $id)
                    ->select([
                        'pump_operator_commission.transaction_date',
                        'settlements.settlement_no',
                        'pumps.pump_no',
                        'meter_sales.discount_amount as sale_amount',
                        'pump_operator_commission.type',
                        'pump_operator_commission.value',
                        'pump_operator_commission.amount as commission_amount',
                    ]);

                if (! empty(request()->type)) {
                    $query->where('pump_operator_commission.type', request()->type);
                }

                $fuel_tanks = Datatables::of($query)

                    ->editColumn(
                        'sale_amount',
                        function ($row) {
                            return $this->productUtil->num_f($row->sale_amount, false);
                        }
                    )
                    ->editColumn(
                        'commission_amount',
                        function ($row) {
                            return $this->productUtil->num_f($row->commission_amount, false);
                        }
                    )
                    ->editColumn(
                        'value',
                        function ($row) {
                            return $this->productUtil->num_f($row->value, false);
                        }
                    )
                    ->editColumn(
                        'transaction_date',
                        function ($row) {
                            return $this->productUtil->format_date($row->transaction_date);
                        }
                    )
                    ->removeColumn('id');

                return $fuel_tanks->rawColumns([])
                    ->make(true);
            }
        }

        return view('petropd::pd_operators.commission')->with(compact(
            'id'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('business.id');
        $locations   = BusinessLocation::forDropdown($business_id);

        $commission_type_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'commission_type');
        $pump_operator_dashboard    = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        $generate_passcode = sprintf("%04d", rand(0, 9999));

        return view('petropd::pd_operators.create')->with(compact('locations', 'commission_type_permission', 'pump_operator_dashboard', 'generate_passcode'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required',
            'address'          => 'required',
            'location_id'      => 'required',
            'email'            => 'required|unique:users',
            'cnic'             => 'required',
            'dob'              => 'required',
            'commission_type'  => 'required',
            'mobile'           => 'required',
            'username'         => 'required|unique:users',
            'transaction_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg'     => $validator->errors()->all()[0],
            ];

            return redirect()->back()->with('status', $output);
        }

        $business_id = request()->session()->get('business.id');
        try {
            //Check if subscribed or not, then check for users quota
            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            } else if (! $this->moduleUtil->isQuotaAvailable('users', $business_id)) {
                return $this->moduleUtil->quotaExpiredResponse('users', $business_id, action('ManageUserController@index'));
            }

            $has_reviewed = $this->transactionUtil->hasReviewed($request->input('transaction_date'));

            if (! empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => __('lang_v1.review_first'),
                ];

                return redirect()->back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->input('transaction_date'), $request->input('transaction_date'));

            if (! empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => "You can't add a pump operator for an already reviewed date",
                ];

                return redirect()->back()->with(['status' => $output]);
            }

            if ($request->is_default == 1) {
                PumpOperator::where('business_id', $business_id)->update(['is_default' => 0]);
            }

            $data = [
                'business_id'      => $business_id,
                'name'             => $request->name,
                'address'          => $request->address,
                'location_id'      => $request->location_id,
                'cnic'             => $request->cnic,
                'dob'              => \Carbon::parse($request->dob)->format('Y-m-d'),
                'commission_type'  => $request->commission_type,
                'commission_ap'    => ! empty($request->commission_ap) ? $request->commission_ap : 0.00,
                'mobile'           => $request->mobile,
                'landline'         => $request->landline,
                'status'           => 1,
                'is_default'       => $request->is_default,
                'can_fullscreen'   => $request->can_fullscreen,
                'is_petro_pd_only' => $request->boolean('is_petro_pd_only'),
                'transaction_date' => $request->transaction_date,
            ];

            if (! empty($request->input('opening_balance'))) {
                if ($request->input('opening_balance_type') == 'shortage') {
                    $data['short_amount'] = $request->input('opening_balance');
                }

                if ($request->input('opening_balance_type') == 'excess') {
                    $data['excess_amount'] = $request->input('opening_balance');
                }
            }

            DB::beginTransaction();
            $pump_operator = PumpOperator::create($data);
            $this->createUser($request, $pump_operator);

            //Add opening balance
            if (! empty($request->input('opening_balance'))) {
                $this->transactionUtil->createOpeningBalanceTransactionForPumpOperator($business_id, $pump_operator->id, $request->input('opening_balance'), $request->input('opening_balance_type'), $request->location_id, $request->input('transaction_date'));
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('petropd::lang.pump_operator_add_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    public function createUser($request, $pump_operator)
    {
        $business_id        = request()->session()->get('business.id');
        $pump_operator_data = [
            'business_id'            => $business_id,
            'surname'                => '',
            'first_name'             => $request->name,
            'last_name'              => '',
            'email'                  => $request->email,
            'username'               => $request->username,
            'password'               => Hash::make($request->password),
            'contact_number'         => $request->mobile,
            'address'                => $request->address,
            'is_pump_operator'       => 1,
            'pump_operator_id'       => $pump_operator->id,
            'pump_operator_passcode' => $request->password,
        ];

        $user = User::create($pump_operator_data);
        $role = Role::where('name', 'Pump Operator#' . $business_id)->first();

        if (empty($role)) {
            $role = Role::create([
                'name'             => 'Pump Operator#' . $business_id,
                'business_id'      => $business_id,
                'is_service_staff' => 0,
            ]);
            $role->givePermissionTo('pump_operator.dashboard');
        }
        $user->assignRole($role->name);

        return true;
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($id)
    {
        $business_id    = Auth::user()->business_id;
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $pump_operator  = PumpOperator::findOrFail($id);

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        //get contact view type : ledger, notes etc.
        $view_type = request()->get('view');
        if (is_null($view_type)) {
            $view_type = 'contact_info';
        }

        $pump_operator_ledger_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_ledger');

        return view('petropd::pd_operators.show')
            ->with(compact('pump_operator', 'pump_operators', 'business_locations', 'view_type', 'pump_operator_ledger_permission'));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('business.id');
        $locations   = BusinessLocation::forDropdown($business_id);

        $pump_operator = PumpOperator::findOrFail($id);
        $transaction   = Transaction::where([
            'type'             => 'opening_balance',
            'pump_operator_id' => $pump_operator->id,
            'business_id'      => $business_id,
        ])->first();
        // dd($transaction->transaction_date);

        // dump($transaction);exit;
        // logger(json_encode($transaction));

        $user                    = User::where('business_id', $business_id)->where('pump_operator_id', $id)->first();
        $pump_operator_dashboard = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        return view('petropd::pd_operators.edit')->with(compact('locations', 'pump_operator', 'user', 'pump_operator_dashboard', 'transaction'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update($id, Request $request)
    {
        $business_id = request()->session()->get('business.id');
        try {

            $has_reviewed = $this->transactionUtil->hasReviewed($request->transaction_date);

            if (! empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => __('lang_v1.review_first'),
                ];

                return redirect()->back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->transaction_date, $request->transaction_date);

            if (! empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => "You can't edit a pump operator for an already reviewed date",
                ];

                return redirect()->back()->with(['status' => $output]);
            }

            if ($request->is_default == 1) {
                PumpOperator::where('business_id', $business_id)->update(['is_default' => 0]);
            }

            $data = [
                'business_id'     => $business_id,
                'name'            => $request->name,
                'address'         => $request->address,
                'location_id'     => $request->location_id,
                'commission_type' => $request->commission_type,
                'commission_ap'   => $request->commission_ap,
                'mobile'          => $request->mobile,
                'landline'        => $request->landline,
                'status'          => 1,
                'is_default'      => $request->is_default,
                'can_fullscreen'  => $request->can_fullscreen,
                'is_petro_pd_only' => $request->boolean('is_petro_pd_only'),
            ];

            PumpOperator::where('id', $id)->update($data);
            $pump_operator = PumpOperator::findOrFail($id);

            // print_r($request->input('opening_balance_type'));echo '<br>';exit;

            //Add opening balance
            $this->transactionUtil->updateOpeningBalanceTransactionForPumpOperator($business_id, $pump_operator->id, $request->input('opening_balance'), $request->input('opening_balance_type'), $request->location_id, $request->input('transaction_date'));

            $user = User::where('pump_operator_id', $id)->where('business_id', $business_id)->first();
            if (empty($user)) {
                $this->createUser($request, $pump_operator);
            } else {
                $user->email = $request->email;
                if (! empty($request->password)) {
                    $user->password               = Hash::make($request->password);
                    $user->pump_operator_passcode = $request->password;
                }
                $user->save();
            }

            $output = [
                'success' => 1,
                'msg'     => __('petropd::lang.pump_operator_update_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        // print_r($output);
        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {}
    /**
     * Import Operators
     * @return Response
     */
    public function importPumps()
    {
        $business_id        = request()->session()->get('business.id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        // S576: Keep the Petro PD import workflow inside PetroPD so the
        // "Petro PD only" option is available and cannot be lost through a
        // legacy Pumper Dashboard import view.
        return view('petropd::pd_operators.import_operators')->with(compact('business_locations'));
    }
    /**
     * Import Operators saves
     * @return Response
     */
    public function saveImport(Request $request)
    {
        $notAllowed = $this->productUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }
        $business_id = request()->session()->get('business.id');
        $location_id = $request->location_id;
        $type        = $request->commission_type;

        try {
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            if ($request->hasFile('pumps_csv')) {
                $file = $request->file('pumps_csv');

                $parsed_array = Excel::toArray([], $file);

                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);

                $formated_data = [];

                $is_valid  = true;
                $error_msg = '';

                $total_rows = count($imported_data);

                $row_no = 0;
                DB::beginTransaction();
                foreach ($imported_data as $key => $value) {

                    $pump_operator                    = [];
                    $pump_operator['business_id']     = $business_id;
                    $pump_operator['location_id']     = $location_id;
                    $pump_operator['commission_type'] = $type;
                    // S576: The import-level checkbox applies consistently to
                    // every operator in this uploaded file.
                    $pump_operator['is_petro_pd_only'] = $request->boolean('is_petro_pd_only');

                    //Check if any column is missing
                    if (count($value) < 5) {
                        $is_valid  = false;
                        $error_msg = "Some of the columns are missing. Please, use latest CSV file template.";
                    }

                    $name = (trim($value[0]));
                    if ($name) {
                        $pump_operator['name'] = $name;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for pump operator name in row no. $row_no";
                    }

                    $address = (trim($value[1]));
                    if ($address) {
                        $pump_operator['address'] = $address;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for address in row no. $row_no";
                    }

                    $mobile = (trim($value[2]));
                    if ($mobile) {
                        $pump_operator['mobile'] = $mobile;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for mobile in row no. $row_no";
                    }

                    $landline = (trim($value[3]));
                    if ($landline) {
                        $pump_operator['landline'] = $landline;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for landline in row no. $row_no";
                    }

                    $dob = (trim($value[4]));
                    if ($dob) {
                        $pump_operator['dob'] = \Carbon::parse(strtotime($dob))->format('Y-m-d');
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for date of birth in row no. $row_no";
                    }

                    $cnic = (trim($value[5]));
                    if ($cnic) {
                        $pump_operator['cnic'] = $cnic;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for national identity number in row no. $row_no";
                    }

                    $ob               = (trim($value[6]));
                    $transaction_date = (trim(strtotime($value[7]))) ?? date('Y-m-d');
                    $ob_type          = strtolower(trim($value[8])) ?? 'excess';

                    $email = (trim($value[9]));

                    if (! $is_valid) {
                        throw new \Exception($error_msg);
                        break;
                    }

                    $pump_operator['status'] = 1;

                    $pump_op = PumpOperator::create($pump_operator);

                    if (! empty($email)) {
                        $pass = rand(1111, 9999);

                        $pump_operator_data = [
                            'business_id'            => $business_id,
                            'surname'                => '',
                            'first_name'             => $pump_operator['name'],
                            'last_name'              => '',
                            'email'                  => $email,
                            'username'               => $email,
                            'password'               => Hash::make($pass),
                            'contact_number'         => $mobile,
                            'address'                => $address,
                            'is_pump_operator'       => 1,
                            'pump_operator_id'       => $pump_op->id,
                            'pump_operator_passcode' => $pass,
                        ];

                        $user = User::create($pump_operator_data);
                        $role = Role::where('name', 'Pump Operator#' . $business_id)->first();

                        if (empty($role)) {
                            $role = Role::create([
                                'name'             => 'Pump Operator#' . $business_id,
                                'business_id'      => $business_id,
                                'is_service_staff' => 0,
                            ]);
                            $role->givePermissionTo('pump_operator.dashboard');
                        }
                        $user->assignRole($role->name);
                    }

                    if (! empty($ob)) {
                        $this->transactionUtil->createOpeningBalanceTransactionForPumpOperator(
                            $business_id,
                            $pump_op->id,
                            $ob,
                            $ob_type,
                            $request->location_id,
                            $transaction_date
                        );
                    }

                    $row_no++;
                }
                DB::commit();
            }

            $output = [
                'success' => 1,
                'msg'     => __('petropd::lang.pump_operator_import_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg'     => $e->getMessage(),
            ];

            return redirect()->back()->with('notification', $output);
        }

        return redirect()->route('petropd.pd-operators')->with('status', $output);
    }

    /**
     * Shows ledger for contacts
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function getLedger()
    {
        $business_id      = Auth::user()->business_id;
        $asset_account_id = Account::leftjoin('account_types', 'accounts.account_type_id', 'accounts.id')
            ->where('account_types.name', 'like', '%Assets%')
            ->where('accounts.business_id', $business_id)
            ->pluck('accounts.id')->toArray();
        $pump_operator_id = request()->input('pump_operator_id');

        // Fallback to logged in pumper if id not provided
        if (empty($pump_operator_id) && !empty(Auth::user()->pump_operator_id)) {
            $pump_operator_id = Auth::user()->pump_operator_id;
        }

        // Final fallback: Use the first available pump operator (for Admins testing)
        if (empty($pump_operator_id)) {
            $first_operator = PumpOperator::where('business_id', request()->session()->get('user.business_id'))->first();
            if ($first_operator) {
                $pump_operator_id = $first_operator->id;
            }
        }

        $start_date = request()->start_date;
        $end_date   = request()->end_date;

        if (empty($start_date)) {
            $start_date = date('Y-m-01');
        }
        if (empty($end_date)) {
            $end_date = date('Y-m-t');
        }

        $pump_operator = PumpOperator::find($pump_operator_id);

        if (empty($pump_operator)) {
            return view('petropd::pd_operators.message')->with('msg', 'Pump Operator not found or ID missing.');
        }

        $business_details = $this->businessUtil->getDetails($pump_operator->business_id);
        $location_details = BusinessLocation::where('business_id', $pump_operator->business_id)->first();
        $opening_balance      = Transaction::where('pump_operator_id', $pump_operator_id)->where('type', 'opening_balance')->where('payment_status', 'due')->sum('final_total');
        $ledger_transactions  = $this->getLedgerTransactionsCollection($pump_operator_id, $start_date, $end_date);

        $ledger_summary = $this->transactionUtil->getPumpOperatorLedgerSummary(
            $pump_operator->business_id,
            $start_date,
            $end_date,
            $pump_operator_id
        );

        // Debugging Summary Issue
        // Debugging Summary Issue
        // dd($ledger_summary, $pump_operator_id, $start_date, $end_date, $pump_operator->business_id);

        $ledger_details = [
            'start_date'             => $start_date,
            'end_date'               => $end_date,
            'total_short'            => $ledger_summary['total_debit_for_period'],
            'total_recovered_excess' => $ledger_summary['total_credit_for_period'],
            'beginning_balance'      => $ledger_summary['opening_balance'],
            'balance_due'            => $ledger_summary['closing_balance'],
            'balance_for_period'     => $ledger_summary['balance_for_period'],
        ];
        $payment_types                 = $this->transactionUtil->payment_types();

        if (request()->input('action') == 'pdf') {
            $for_pdf = true;
            $html    = view('petropd::pd_operators.ledger')
                ->with(compact('ledger_details', 'pump_operator', 'for_pdf', 'ledger_transactions', 'business_details', 'location_details', 'payment_types'))->render();
            $mpdf = $this->getMpdf();
            $mpdf->WriteHTML($html);
            $mpdf->Output();
        }
        if (request()->input('action') == 'print') {
            $for_pdf = true;
            return view('petropd::pd_operators.ledger')
                ->with(compact('ledger_details', 'pump_operator', 'for_pdf', 'ledger_transactions', 'business_details', 'location_details', 'payment_types'))->render();
        }

        return view('petropd::pd_operators.ledger')
            ->with(compact('ledger_details', 'pump_operator', 'opening_balance', 'ledger_transactions', 'business_details', 'location_details', 'payment_types'));
    }

    private function getLedgerTransactionsCollection($pump_operator_id, $start_date, $end_date)
    {
        // Query account_transactions with sub_type = 'ledger_show' (same pattern as getPumpOperatorLedgerSummary)
        $query = AccountTransaction::leftjoin('transactions', 'account_transactions.transaction_id', 'transactions.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('transaction_payments', 'account_transactions.transaction_payment_id', 'transaction_payments.id')
            ->where('transactions.pump_operator_id', $pump_operator_id)
            ->where('account_transactions.sub_type', 'ledger_show')
            ->whereIn('transactions.sub_type', ['excess', 'shortage'])
            ->whereNull('account_transactions.deleted_at')
            ->select(
                'business_locations.name as location_name',
                DB::raw('COALESCE(transactions.invoice_no, transaction_payments.payment_ref_no) as ref_no'),
                DB::raw('CASE
                    WHEN transactions.type = "opening_balance" THEN "opening_balance"
                    WHEN account_transactions.type = "debit" AND transactions.sub_type = "shortage" THEN "shortage"
                    WHEN account_transactions.type = "credit" AND transactions.sub_type = "shortage" THEN "shortage_recovered"
                    WHEN account_transactions.type = "credit" AND transactions.sub_type = "excess" THEN "excess"
                    WHEN account_transactions.type = "debit" AND transactions.sub_type = "excess" THEN "excess_paid"
                    ELSE transactions.sub_type
                END as type'),
                DB::raw("DATE(COALESCE(CASE WHEN transactions.transaction_date IS NOT NULL AND YEAR(transactions.transaction_date) > 0 THEN transactions.transaction_date END, transaction_payments.paid_on, account_transactions.operation_date, account_transactions.created_at)) as date"),
                'account_transactions.amount as amount',
                DB::raw('COALESCE(transaction_payments.method, "") as method'),
                'transactions.sub_type as sub_type'
            );

        // Also include opening_balance transactions
        $opening_balance_query = Transaction::leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.pump_operator_id', $pump_operator_id)
            ->where('transactions.type', 'opening_balance')
            ->where('transactions.status', 'final')
            ->select(
                'business_locations.name as location_name',
                'transactions.invoice_no as ref_no',
                DB::raw('"opening_balance" as type'),
                DB::raw("DATE(COALESCE(CASE WHEN transactions.transaction_date IS NOT NULL AND YEAR(transactions.transaction_date) > 0 THEN transactions.transaction_date END, transactions.created_at)) as date"),
                DB::raw('ABS(transactions.final_total) as amount'),
                DB::raw('"" as method'),
                'transactions.sub_type as sub_type'
            );

        // Commission entries
        $commission = PumpOperatorCommission::leftjoin('pump_operators', 'pump_operators.id', 'pump_operator_commission.pump_operator_id')
            ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
            ->leftjoin('meter_sales', 'pump_operator_commission.meter_sale_id', 'meter_sales.id')
            ->leftjoin('settlements', 'meter_sales.settlement_no', 'settlements.id')
            ->where('pump_operator_commission.pump_operator_id', $pump_operator_id)
            ->select([
                'business_locations.name as location_name',
                'settlements.settlement_no as ref_no',
                DB::raw('"commission" as type'),
                DB::raw("DATE(COALESCE(CASE WHEN pump_operator_commission.transaction_date IS NOT NULL AND YEAR(pump_operator_commission.transaction_date) > 0 THEN pump_operator_commission.transaction_date END, pump_operator_commission.created_at)) as date"),
                'pump_operator_commission.amount as amount',
                DB::raw('"" as method'),
                DB::raw('"" as sub_type'),
            ]);

        // Apply date filters
        if (!empty($start_date) && !empty($end_date)) {
            // S774: filter using the same resolved date rendered in the ledger.
            // Legacy bulk payments may have an empty transaction_date while
            // transaction_payments.paid_on still contains the real payment date.
            $query->whereRaw(
                "DATE(COALESCE(CASE WHEN transactions.transaction_date IS NOT NULL AND YEAR(transactions.transaction_date) > 0 THEN transactions.transaction_date END, transaction_payments.paid_on, account_transactions.operation_date, account_transactions.created_at)) BETWEEN ? AND ?",
                [$start_date, $end_date]
            );
            $opening_balance_query->whereRaw(
                "DATE(COALESCE(CASE WHEN transactions.transaction_date IS NOT NULL AND YEAR(transactions.transaction_date) > 0 THEN transactions.transaction_date END, transactions.created_at)) BETWEEN ? AND ?",
                [$start_date, $end_date]
            );
            $commission->whereRaw(
                "DATE(COALESCE(CASE WHEN pump_operator_commission.transaction_date IS NOT NULL AND YEAR(pump_operator_commission.transaction_date) > 0 THEN pump_operator_commission.transaction_date END, pump_operator_commission.created_at)) BETWEEN ? AND ?",
                [$start_date, $end_date]
            );
        }

        // Combine all queries
        $result = $query->union($opening_balance_query)->union($commission)->orderBy('date', 'asc')->get();

        Log::info('Ledger transactions count: ' . $result->count());

        return $result;
    }

    private function getLedgerDetailsForDateRange($pump_operator_id, $start_date, $end_date)
    {
        $query = AccountTransaction::leftjoin('transactions', 'account_transactions.transaction_id', 'transactions.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')
            ->leftjoin('transaction_payments', 'account_transactions.transaction_payment_id', 'transaction_payments.id')
            ->where('pump_operator_id', $pump_operator_id)
            ->where('account_transactions.sub_type', 'ledger_show')
            ->whereIn('transactions.sub_type', ['excess', 'shortage'])
            ->select(
                'account_transactions.*',
                'account_transactions.type as acc_transaction_type',
                'business_locations.name as location_name',
                'transactions.ref_no',
                'transactions.invoice_no',
                'transactions.sub_type',
                'transactions.transaction_date',
                'transactions.payment_status',
                'transaction_payments.method as payment_method',
                'transaction_payments.payment_ref_no',
                'transaction_payments.id as tp_id',
                'transaction_payments.paid_on',
                'transactions.type as transaction_type',
                DB::raw('(SELECT SUM(IF(AT.type="credit", -1 * AT.amount, AT.amount)) from account_transactions as AT WHERE AT.operation_date <= account_transactions.operation_date AND AT.account_id  =account_transactions.account_id AND AT.deleted_at IS NULL AND AT.id <= account_transactions.id) as balance')
            );

        if (! empty($start_date) && ! empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        $ledger_transactions = $query->groupBy('account_transactions.id')->orderBy('account_transactions.id', 'asc')->withTrashed()->get();

        $total_short = $total_recovered_excess = 0;
        foreach ($ledger_transactions as $transaction) {
            if ($transaction->acc_transaction_type === 'debit') {
                $total_short += $transaction->amount;
            }

            if ($transaction->acc_transaction_type === 'credit') {
                $total_recovered_excess += $transaction->amount;
            }
        }

        $beginning_balance = $this->getOneDayBeforeDueBalance($pump_operator_id, $start_date);
        $balance_due       = ($total_short + $beginning_balance) - $total_recovered_excess;

        return [
            'total_short'            => $total_short,
            'total_recovered_excess' => $total_recovered_excess,
            'balance_due'            => $balance_due,
            'period_debits'          => $total_short,
            'period_credits'         => $total_recovered_excess,
        ];
    }

    private function getPeriodBalancesForRange($business_id, $start_date, $end_date, $filters = [])
    {
        $locationKey = ! empty($filters['location_id']) ? $filters['location_id'] : 'all';
        // FIX: Added 'v2' to cache key to invalidate old cache (which had aggregated data instead of per-operator)
        $cacheKey    = implode('_', ['v2', $business_id, $start_date, $end_date, $locationKey]);
        
        if (! isset($this->periodBalanceCache[$cacheKey])) {
            // FIX: Get list of all pump operators for this business/location
            $pump_operators_query = PumpOperator::where('business_id', $business_id);
            
            if (! empty($filters['location_id'])) {
                $pump_operators_query->where('location_id', $filters['location_id']);
            }
            
            $pump_operators = $pump_operators_query->pluck('id');
            
            // FIX: Build per-operator summary array
            $result = [];
            foreach ($pump_operators as $pump_operator_id) {
                $result[$pump_operator_id] = $this->transactionUtil->getPumpOperatorLedgerSummary(
                    $business_id,
                    $start_date,
                    $end_date,
                    $pump_operator_id,  // ✅ Pass specific operator ID
                    $filters
                );
            }
            
            $this->periodBalanceCache[$cacheKey] = $result;
        }

        return $this->periodBalanceCache[$cacheKey];
    }

    private function getOneDayBeforeDueBalance($pump_operator_id, $start_date)
    {
        $start_date = $end_date = (new \DateTime($start_date))->modify('-1 days');
        $query      = AccountTransaction::leftjoin('transactions', 'account_transactions.transaction_id', 'transactions.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')
            ->leftjoin('transaction_payments', 'account_transactions.transaction_payment_id', 'transaction_payments.id')
            ->where('pump_operator_id', $pump_operator_id)
            ->where('account_transactions.sub_type', 'ledger_show')
            ->whereIn('transactions.sub_type', ['excess', 'shortage'])
            ->select(
                'account_transactions.*',
                'account_transactions.type as acc_transaction_type',
                'business_locations.name as location_name',
                'transactions.ref_no',
                'transactions.invoice_no',
                'transactions.sub_type',
                'transactions.transaction_date',
                'transactions.payment_status',
                'transaction_payments.method as payment_method',
                'transaction_payments.payment_ref_no',
                'transaction_payments.id as tp_id',
                'transaction_payments.paid_on',
                'transactions.type as transaction_type',
                DB::raw('(SELECT SUM(IF(AT.type="credit", -1 * AT.amount, AT.amount)) from account_transactions as AT WHERE AT.operation_date <= account_transactions.operation_date AND AT.account_id  =account_transactions.account_id AND AT.deleted_at IS NULL AND AT.id <= account_transactions.id) as balance')
            );

        if (! empty($start_date) && ! empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        $ledger_transactions = $query->groupBy('account_transactions.id')->orderBy('account_transactions.id', 'asc')->withTrashed()->get();

        $total_short = $total_recovered_excess = 0;
        foreach ($ledger_transactions as $transaction) {
            if ($transaction->acc_transaction_type === 'debit') {
                $total_short += $transaction->amount;
            }

            if ($transaction->acc_transaction_type === 'credit') {
                $total_recovered_excess += $transaction->amount;
            }
        }

        return $total_short - $total_recovered_excess;
    }

    /**
     * Function to get ledger details
     *
     */
    private function __getLedgerDetails($pump_operator_id, $start_date, $end_date)
    {
        $business_id = Auth::user()->business_id;
        $contact     = PumpOperator::where('id', $pump_operator_id)->first();
        //Get transaction totals between dates

        $pump_op_query = Transaction::where('business_id', $business_id)
            ->where('type', 'settlement')
            ->whereIn('sub_type', ['excess', 'shortage'])
            ->where('pump_operator_id', $pump_operator_id)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select(
                DB::raw("SUM(IF(transactions.sub_type = 'excess', ABS(final_total), 0)) as excess"),
                DB::raw("SUM(IF(transactions.sub_type = 'shortage', final_total, 0)) as shortage")
            )->first();

        $total_paid_query = Transaction::leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->where('type', 'settlement')
            ->whereIn('sub_type', ['excess', 'shortage'])
            ->where('pump_operator_id', $pump_operator_id)
            ->whereDate('transaction_payments.paid_on', '>=', $start_date)
            ->whereDate('transaction_payments.paid_on', '<=', $end_date)
            ->select(
                DB::raw("SUM(IF(transactions.sub_type = 'excess', ABS(transaction_payments.amount), 0)) as excess_paid"),
                DB::raw("SUM(IF(transactions.sub_type = 'shortage', transaction_payments.amount, 0)) as shortage_recovered")
            )->first();

        $total_short            = $pump_op_query->shortage + $total_paid_query->excess_paid;
        $total_recovered_excess = $pump_op_query->excess + $total_paid_query->shortage_recovered;

        $beginning_balance = $this->getBeginningBalance($pump_operator_id, $start_date, $end_date);
        $balance_due       = $beginning_balance + $total_short - $total_recovered_excess;
        $output            = [
            'start_date'             => $start_date,
            'end_date'               => $end_date,
            'total_short'            => $total_short,
            'total_recovered_excess' => $total_recovered_excess,
            'beginning_balance'      => $beginning_balance,
            'balance_due'            => $balance_due,
        ];

        return $output;
    }

    private function getBeginningBalance($pump_operator_id, $start_date)
    {
        $business_id   = Auth::user()->business_id;
        $pump_op_query = Transaction::where('business_id', $business_id)
            ->where('transactions.type', 'settlement')
            ->whereIn('transactions.sub_type', ['excess', 'shortage'])
            ->where('pump_operator_id', $pump_operator_id)
            ->whereDate('transactions.transaction_date', '<', $start_date)
            ->select(
                DB::raw("SUM(IF(transactions.sub_type = 'excess', final_total, 0)) as excess"),
                DB::raw("SUM(IF(transactions.sub_type = 'shortage', final_total, 0)) as shortage")
            )->first();

        $total_paid_query = Transaction::leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'settlement')
            ->whereIn('transactions.sub_type', ['excess', 'shortage'])
            ->where('pump_operator_id', $pump_operator_id)
            ->whereDate('transaction_payments.paid_on', '<', $start_date)
            ->select(
                DB::raw("SUM(IF(transactions.sub_type = 'excess', transaction_payments.amount, 0)) as excess_paid"),
                DB::raw("SUM(IF(transactions.sub_type = 'shortage', transaction_payments.amount, 0)) as shortage_recovered")
            )->first();

        $total_short            = $pump_op_query->shortage + $total_paid_query->excess_paid;
        $total_recovered_excess = $pump_op_query->excess + $total_paid_query->shortage_recovered;

        $balance_due = $total_short - $total_recovered_excess;

        return $balance_due;
    }

    /**
     * Query to get transaction totals for a customer
     *
     */
    private function __transactionQuery($pump_operator_id, $start, $end = null)
    {
        $business_id           = Auth::user()->business_id;
        $transaction_type_keys = array_keys(Transaction::transactionTypes());

        $query = Transaction::where('transactions.pump_operator_id', $pump_operator_id)
            ->where('transactions.business_id', $business_id)
            ->where('status', '!=', 'draft')
            ->whereIn('type', $transaction_type_keys);

        if (! empty($start) && ! empty($end)) {
            $query->whereDate(
                'transactions.transaction_date',
                '>=',
                $start
            )
                ->whereDate('transactions.transaction_date', '<=', $end)->get();
        }

        if (! empty($start) && empty($end)) {
            $query->whereDate('transactions.transaction_date', '<', $start);
        }

        return $query;
    }

    /**
     * Query to get payment details for a customer
     *
     */
    private function __paymentQuery($pump_operator_id, $start, $end = null)
    {
        $business_id = Auth::user()->business_id;

        $query = TransactionPayment::join(
            'transactions as t',
            'transaction_payments.transaction_id',
            '=',
            't.id'
        )
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('t.pump_operator_id', $pump_operator_id)
            ->where('t.business_id', $business_id)
            ->where('t.status', '!=', 'draft');

        if (! empty($start) && ! empty($end)) {
            $query->whereDate('paid_on', '>=', $start)
                ->whereDate('paid_on', '<=', $end);
        }

        if (! empty($start) && empty($end)) {
            $query->whereDate('paid_on', '<', $start);
        }

        return $query;
    }

    /**
     * Function to send ledger notification
     *
     */
    public function sendLedger(Request $request)
    {
        $notAllowed = $this->notificationUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            $data         = $request->only(['to_email', 'subject', 'email_body', 'cc', 'bcc']);
            $emails_array = array_map('trim', explode(',', $data['to_email']));

            $pump_operator_id = $request->input('pump_operator_id');
            $business_id      = request()->session()->get('business.id');

            $start_date = request()->input('start_date');
            $end_date   = request()->input('end_date');

            $pump_operator = PumpOperator::find($pump_operator_id);

            $asset_account_id = Account::leftjoin('account_types', 'accounts.account_type_id', 'accounts.id')
                ->where('account_types.name', 'like', '%Assets%')
                ->where('accounts.business_id', $business_id)
                ->pluck('accounts.id')->toArray();

            $ledger_details = $this->__getLedgerDetails($pump_operator_id, $start_date, $end_date);

            $business_details = $this->businessUtil->getDetails($pump_operator->business_id);
            $location_details = BusinessLocation::where('business_id', $pump_operator->business_id)->first();
            $opening_balance  = Transaction::where('pump_operator_id', $pump_operator_id)->where('type', 'opening_balance')->where('payment_status', 'due')->sum('final_total');

            $query = AccountTransaction::leftjoin('transactions', 'account_transactions.transaction_id', 'transactions.id')
                ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
                ->leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')
                ->where('transactions.type', 'sell')
                ->orWhere('transactions.type', 'opening_balance')
                ->where('pump_operator_id', $pump_operator_id)
                ->select(
                    'account_transactions.*',
                    'account_transactions.type as acc_transaction_type',
                    'business_locations.name as location_name',
                    'transactions.ref_no',
                    'transactions.transaction_date',
                    'transactions.payment_status',
                    'transaction_payments.method as payment_method',
                    'transactions.type as transaction_type',
                    DB::raw('(SELECT SUM(IF(AT.type="credit", -1 * AT.amount, AT.amount)) from account_transactions as AT WHERE AT.operation_date <= account_transactions.operation_date AND AT.account_id  =account_transactions.account_id AND AT.deleted_at IS NULL AND AT.id <= account_transactions.id) as balance')
                );

            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereDate(
                    'transactions.transaction_date',
                    '>=',
                    $start_date
                )->whereDate('transactions.transaction_date', '<=', $end_date)->get();
            }
            $ledger_transactions = $query->get();

            $orig_data = [
                'email_body' => $data['email_body'],
                'subject'    => $data['subject'],
            ];

            $tag_replaced_data  = $this->notificationUtil->replaceTags($business_id, $orig_data, null, null);
            $data['email_body'] = $tag_replaced_data['email_body'];
            $data['subject']    = $tag_replaced_data['subject'];

            //replace balance_due
            $data['email_body'] = str_replace('{balance_due}', $this->notificationUtil->num_f($ledger_details['balance_due']), $data['email_body']);

            $data['email_settings'] = request()->session()->get('business.email_settings');

            $for_pdf = true;
            $html    = view('petropd::pd_operators.ledger')
                ->with(compact('ledger_details', 'pump_operator', 'for_pdf', 'ledger_transactions', 'business_details', 'location_details'))->render();
            $mpdf = $this->getMpdf();
            $mpdf->WriteHTML($html);

            $file = config('constants.mpdf_temp_path') . '/' . time() . '_ledger.pdf';
            $mpdf->Output($file, 'F');

            $data['attachment']      = $file;
            $data['attachment_name'] = 'ledger.pdf';
            \Notification::route('mail', $emails_array)
                ->notify(new CustomerNotification($data));

            if (file_exists($file)) {
                unlink($file);
            }

            $output = ['success' => 1, 'msg' => __('lang_v1.notification_sent_successfully')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage(),
            ];
        }

        return $output;
    }

    public function getReport()
    {
        $business_id = Auth::user()->business_id;

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_pd_module')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {
            $business_id = Auth::user()->business_id;
            if (request()->ajax()) {
                $query = PumpOperator::leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                    ->where('pump_operators.business_id', $business_id)
                    ->select([
                        'pump_operators.*',
                        'business_locations.name as location_name',
                    ]);

                if (! empty(request()->location_id)) {
                }
                if (! empty(request()->pump_operator)) {
                }
                if (! empty(request()->settlement_no)) {
                }
                if (! empty(request()->type)) {
                }
                if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                }

                $fuel_tanks = Datatables::of($query)
                    ->addColumn(
                        'pump_no',
                        ''
                    )
                    ->addColumn(
                        'settlement_no',
                        ''
                    )
                    ->addColumn(
                        'pumped_fuel_ltrs',
                        ''
                    )
                    ->addColumn(
                        'amount',
                        ''
                    )
                    ->addColumn(
                        'commission_rate',
                        '{{$commission_type}}'
                    )
                    ->addColumn(
                        'commission_amount',
                        '{{$commission_ap}}'
                    )

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['action'])
                    ->make(true);
            }
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $pump_operators     = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $settlement_nos     = [];

        // The legacy report view belongs to PumperDashboard and is not part of
        // the PetroPD surface. Do not retain a cross-module runtime fallback.
        abort(404);
    }

    /**
     * Display a excess and shortage of the resource.
     * @return Response
     */
    public function getPumperExcessShortagePayments()
    {
        $business_id = Auth::user()->business_id;

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_pd_module')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {
            $business_id   = Auth::user()->business_id;
            $payment_types = $this->productUtil->payment_types();
            if (request()->ajax()) {
                $query = PumpOperator::leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                    ->leftjoin('transactions', 'pump_operators.id', 'transactions.pump_operator_id')
                    ->leftjoin('transaction_payments', function ($join) {
                        $join->on('transactions.id', 'transaction_payments.transaction_id')->whereNull('transaction_payments.deleted_at');
                    })
                    ->where('pump_operators.business_id', $business_id)
                    ->where('transactions.type', 'settlement')
                    ->whereIn('transactions.sub_type', ['shortage', 'excess'])
                    ->select([
                        'pump_operators.*',
                        'transactions.id as t_id',
                        'transactions.type',
                        'transactions.sub_type',
                        'transactions.final_total',
                        'transactions.transaction_date',
                        'pump_operators.id as pump_operator_id',
                        'business_locations.name as location_name',
                        'transaction_payments.amount',
                        'transaction_payments.method',
                        'transaction_payments.id as tp_id',
                        'transaction_payments.paid_on',
                        'transaction_payments.payment_ref_no',
                    ])->groupBy('transaction_payments.id');

                if (! empty(request()->location_id)) {
                    $query->where('transactions.location_id', request()->location_id);
                }
                if (! empty(request()->pump_operator)) {
                    $query->where('transactions.pump_operator_id', request()->pump_operator);
                }

                if (! empty(request()->type)) {
                    $query->where('transactions.sub_type', request()->type);
                }
                if (! empty(request()->payment_type)) {
                    $query->where('transaction_payments.method', request()->payment_type);
                }
                if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                    $query->whereDate('transaction_payments.paid_on', '>=', request()->start_date);
                    $query->whereDate('transaction_payments.paid_on', '<=', request()->end_date);
                }
                $business_id      = session()->get('user.business_id');
                $business_details = Business::find($business_id);

                $fuel_tanks = Datatables::of($query)
                    ->addColumn(
                        'action',
                        function ($row) {

                            $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                                '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                            if (! empty($row->tp_id)) {
                                $html .= '<li><a href="' . action('\\App\\Http\\Controllers\\TransactionPaymentController@show', [$row->t_id]) . '" class="view_payment_modal"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';

                                if ($row->sub_type == 'shortage') {
                                    $html .= '<li><a href="#" data-href="' . action("\Modules\PetroPD\Http\Controllers\PDRecoverShortageController@edit", [$row->tp_id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</a></li>
                                    <li><a href="#" data-href="' . action("\Modules\PetroPD\Http\Controllers\PDRecoverShortageController@destroy", [$row->tp_id]) . '" class="delete_payment" ><i class="fa fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '</a></li>';
                                }
                                if ($row->sub_type == 'excess') {
                                    $html .= '<li><a href="#" data-href="' . action("\Modules\PetroPD\Http\Controllers\PDExcessComissionController@edit", [$row->tp_id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</a></li>
                                    <li><a href="#" data-href="' . action("\Modules\PetroPD\Http\Controllers\PDExcessComissionController@destroy", [$row->tp_id]) . '" class="delete_payment" ><i class="fa fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '</a></li>';
                                }
                            }

                            $html .= '</ul></div>';

                            return $html;
                        }
                    )
                    ->editColumn(
                        'paid_on',
                        '{{@format_date($paid_on)}}'
                    )
                    ->editColumn(
                        'excess_amount',
                        function ($row) use ($business_details) {
                            if ($row->sub_type == 'excess') {
                                return '<span class="display_currency excess_amount" data-orig-value="' . $row->final_total . '" data-currency_symbol = true>' . $this->productUtil->num_f($row->final_total, false, $business_details, false) . '</span>';
                            }
                            return $this->productUtil->num_f(0, false, $business_details, false);
                        }
                    )
                    ->editColumn(
                        'short_amount',
                        function ($row) use ($business_details) {
                            if ($row->sub_type == 'shortage') {
                                return '<span class="display_currency short_amount" data-orig-value="' . $row->final_total . '" data-currency_symbol = true>' . $this->productUtil->num_f($row->final_total, false, $business_details, false) . '</span>';
                            }
                            return $this->productUtil->num_f(0, false, $business_details, false);
                        }
                    )
                    ->addColumn('shortage_recover', function ($row) use ($business_details) {
                        if ($row->sub_type == 'shortage') {
                            return $this->productUtil->num_f($row->amount, false, $business_details, false);
                        }
                        return $this->productUtil->num_f(0, false, $business_details, false);
                    })
                    ->addColumn('excess_paid', function ($row) use ($business_details, $payment_types) {
                        $method = '';
                        if (! empty($row->method)) {
                            $method = $payment_types[$row->method];
                        }
                        if ($row->sub_type == 'excess') {
                            return $this->productUtil->num_f($row->amount, false, $business_details, false) . ' ' . $method;
                        }
                        return $this->productUtil->num_f(0, false, $business_details, false);
                    })

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['action', 'sold_fuel_qty', 'sale_amount_fuel', 'excess_amount', 'short_amount', 'commission_rate', 'commission_amount'])
                    ->make(true);
            }
        }
    }

    public function toggleActivate($id)
    {
        try {
            $pump_operator         = PumpOperator::findOrFail($id);
            $pump_operator->active = ! $pump_operator->active;
            $pump_operator->save();

            $output = [
                'success' => true,
                'msg'     => __('lang_V1.success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function dashboard_settings()
    {
        $business_id    = Auth::user()->business_id;
        $pump_operator  = PumpOperator::findOrFail(Auth::user()->pump_operator_id);
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'name');

        $card_types = [];
        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        return view('petropd::pd_operators.dashboard_settings')->with(compact(
            'business_id',
            'pump_operator',
            'card_types',
            'pump_operators'
        ));
    }
    public function store_settings(Request $request)
    {
        try {
            DB::beginTransaction();

            // collect only the required settings
            $data = $request->only(
                'created_at',
                'user_added',
                'show_bulk_pumps',
                'card_type',
                'credit_sales_direct_to_customer',
                'logoff_time',
                'logoff',
                'meter_sales_compulsory',
                'enter_cash_denominations',
                'card_amount_to_enter',
                'enter_card_numbers',
                'bill_prefix',
                'starting_bill_number'
            );

            if ($request->is_admin == 1) {
                PumpOperator::where('business_id', Auth::user()->business_id)
                    ->update(['dashboard_settings' => json_encode($data)]);
            } else {
                if (Auth::user()->pump_operator_id != 0) {
                    $pump_operator = PumpOperator::findOrFail(Auth::user()->pump_operator_id);
                } else {
                    $pump_operator = PumpOperator::where('business_id', Auth::user()->business_id)
                        ->where('is_default', '1')
                        ->first() ?? PumpOperator::findOrFail(1);
                }
                $pump_operator->dashboard_settings = json_encode($data);
                $pump_operator->save();
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() .
                ' Line: ' . $e->getLine() .
                ' Message: ' . $e->getMessage());

            DB::rollBack();

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    // public function store_settings(Request $request){
    //     try {
    //         DB::beginTransaction();
    //             $data = $request->only('created_at','user_added','show_bulk_pumps','card_type','credit_sales_direct_to_customer','logoff_time', 'logoff', 'meter_sales_compulsory','enter_cash_denominations','card_amount_to_enter', 'pump_should_not_show_if_shift_is_open');

    //             if($request->is_admin == 1){
    //                 PumpOperator::where('business_id', Auth::user()->business_id)->update(['dashboard_settings' => json_encode($data)]);
    //             }else{
    //                 if(Auth::user()->pump_operator_id != 0)
    //                     $pump_operator = PumpOperator::findOrFail(Auth::user()->pump_operator_id);
    //                 else  $pump_operator = PumpOperator::where('business_id', Auth::user()->business_id)->where('is_default', '1')->first() ?? PumpOperator::findOrFail(1);
    //                 $pump_operator->dashboard_settings = json_encode($data);
    //                 $pump_operator->save();
    //             }

    //         DB::commit();

    //         $output = [
    //             'success' => 1,
    //             'msg' => __('lang_v1.success')
    //         ];
    //     } catch (\Exception $e) {
    //         \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
    //         $output = [
    //             'success' => 0,
    //             'msg' => __('messages.something_went_wrong')
    //         ];
    //     }

    //     return redirect()->back()->with('status', $output);
    // }

    public function update_passcode()
    {
        $user = User::find(auth()->user()->id);

        return view('petropd::pd_operators.update_passcode')->with(compact(
            'user'
        ));
    }

    public function store_passcode(Request $request)
    {
        try {
            $user = User::find(auth()->user()->id);

            if (strlen($request->current_pass) < 4) {
                $output = [
                    'success' => 0,
                    'msg'     => __('petropd::lang.need_longer_pass'),
                ];
                return redirect()->back()->with('status', $output);
            }

            if ($request->current_pass == $user->pump_operator_passcode) {
                $output = [
                    'success' => 0,
                    'msg'     => __('petropd::lang.need_new_password'),
                ];
                return redirect()->back()->with('status', $output);
            }

            DB::beginTransaction();
            $user->pump_operator_passcode     = $request->current_pass;
            $user->pump_operator_pass_changed = 1;
            $user->save();
            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    public function dashboard()
    {
        /*
         * SEQ079 Emergency pumper dashboard tenant/auth restore.
         *
         * Debug proved:
         * session tenant_db = nivasa_130
         * current_db        = nivasa_cool
         *
         * So before reading Auth::user(), force the DB connection to the tenant
         * saved during pumperLogin(), then manually set the authenticated user
         * from that tenant database.
         */
        $sessionTenantDb = session('tenancy_db_name');
        $sessionUserId = session('pumper_user_id') ?: session('user.id');

        if (! empty($sessionTenantDb)) {
            config(['database.default' => 'mysql']);
            config(['database.connections.mysql.database' => $sessionTenantDb]);

            DB::disconnect('mysql');
            DB::purge('mysql');
            DB::reconnect('mysql');
            DB::setDefaultConnection('mysql');
        }

        if (! empty($sessionUserId)) {
            $sessionUser = User::where('id', $sessionUserId)->first();

            if (! empty($sessionUser)) {
                Auth::setUser($sessionUser);
            }
        }

        Log::warning('SEQ079 DASHBOARD DEBUG', [
            'auth_check' => Auth::check(),
            'auth_user' => Auth::id(),
            'tenant_id' => session('tenant_id'),
            'tenant_db' => session('tenancy_db_name'),
            'current_db' => DB::connection()->getDatabaseName(),
            'session_user_id' => $sessionUserId,
            'loaded_user_id' => ! empty(Auth::user()) ? Auth::user()->id : null,
        ]);

        if (empty(Auth::user())) {
            return redirect('/pump-operator/login?cc=' . urlencode((string) session('business.company_number')))->with('status', [
                'success' => 0,
                'msg' => 'Please login to the Pumper Dashboard again.',
            ]);
        }

        Log::info('Pump operator dashboard in the pumper dashboard accessed by user_id: ' . Auth::user()->id);
        if (empty(Auth::user()->pump_operator_id)) {

            // if is admin
            $pump_op = PumpOperator::where('business_id', Auth::user()->business_id)->where('is_default', '1')->first();

            if (! empty($pump_op)) {
                $user = User::where('pump_operator_id', $pump_op->id)->first();
                if (! empty($user)) {
                    request()->session()->put('from_admin', auth()->user()->id);
                    // request()->session()->flush();
                    try {
                        LSCache::purge('*');
                    } catch (\Throwable $e) {
                    }

                    Auth::loginUsingId($user->id);
                    request()->session()->regenerate();
                }
            }
        }

        $business_id      = session('business.id') ?: session('user.business_id') ?: Auth::user()->business_id;
        $pump_operator_id = session('pump_operator_id') ?: session('pumper_operator_id') ?: Auth::user()->pump_operator_id;

        Log::warning('SEQ026 Pumper Dashboard resolved identity', [
            'auth_user_id' => Auth::id(),
            'auth_pump_operator_id' => Auth::user()->pump_operator_id ?? null,
            'session_user_id' => session('user.id'),
            'session_pumper_user_id' => session('pumper_user_id'),
            'session_pump_operator_id' => session('pump_operator_id'),
            'resolved_business_id' => $business_id,
            'resolved_pump_operator_id' => $pump_operator_id,
            'current_db' => DB::connection()->getDatabaseName(),
        ]);

        $fy                                  = $this->businessUtil->getCurrentFinancialYear($business_id);
        $date_filters['this_fy']             = $fy;
        $date_filters['this_month']['start'] = date('Y-m-01');
        $date_filters['this_month']['end']   = date('Y-m-t');
        $date_filters['this_week']['start']  = date('Y-m-d', strtotime('monday this week'));
        $date_filters['this_week']['end']    = date('Y-m-d', strtotime('sunday this week'));

        $fuel_tanks = FuelTank::where('business_id', $business_id)->get();

        $general_message = '';

        if (! $this->standaloneModuleEnabled($business_id)) {
            if (System::getProperty('general_message_pump_operator_dashbaord_checkbox') == 1) {
                $font_size       = System::getProperty('customer_supplier_security_deposit_current_liability_font_size');
                $color           = System::getProperty('customer_supplier_security_deposit_current_liability_color');
                $msg             = System::getProperty('customer_supplier_security_deposit_current_liability_message');
                $general_message = '<p style="font-size: ' . $font_size . ';color: ' . $color . ' ">' . $msg . '</p>';
            }
        }

        $today_close_pump_count = PumpOperatorAssignment::where('business_id', $business_id)->where('pump_operator_id', $pump_operator_id)->where('status', 'open')->count();

        $can_close_shift = true;
        if ($today_close_pump_count == 0) {
            $can_close_shift = true;
        }

        if (empty(session()->get('pump_operator_main_system'))) {
            $layout = 'pumper';
        } else {
            $layout = 'app';
        }

        if (empty($pump_operator_id)) {
            abort(403, 'User is not assigned to any pump operator.');
        }
        $_settings = PumpOperator::findOrFail($pump_operator_id)->dashboard_settings;

        $dashboard_settings = ! empty($_settings) ? json_decode($_settings, true) : [];

        $operator_permissions = UserStorePermission::where([
            'business_id' => $business_id,
            'user_id'     => Auth::user()->id,
        ])->first();

        if (empty($operator_permissions)) {
            $operator_permissions = PumpOperator::where('business_id', $business_id)
                ->whereNotNull('dashboard_settings')
                ->first();

            if ($operator_permissions) {
                $settings = json_decode($operator_permissions->dashboard_settings, true);

                $operator_permissions->sell = isset($settings['meter_sales_compulsory']) && $settings['meter_sales_compulsory'] === 'yes' ? 1 : 0;
            }
        }

        $pending_pumps = PumpOperatorAssignment::whereNotNull('shift_id')
            ->where('pump_operator_id', $pump_operator_id)
            ->whereNull('pump_operator_assignments.pump_operator_other_sale_id')->where('is_confirmed', 1)->count();

        // if(!empty($dashboard_settings) && !empty($dashboard_settings['meter_sales_compulsory']) && $dashboard_settings['meter_sales_compulsory'] == 'yes' && $pending_pumps > 0){
        //     $output = [
        //         'success' => false,
        //         'msg' => __('petropd::lang.you_must_first_enter_meter_reading')
        //     ];

        //     return redirect()->route('petropd.pump-operator-payments.create')->with('status', $output);
        // }

        // $unconfirmed_meters = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->whereNotNull('shift_id')->where('is_confirmed', 0)->count();

        $unconfirmed_meters = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
            ->whereNotNull('shift_id')
            ->where('status', 'open')
            ->where(function ($query) {
                $query->where('closed_in_settlement', 0)
                    ->orWhereNull('closed_in_settlement');
            })
            ->where('is_confirmed', 0)
            ->count();

        $physical_pumps_query = PumpOperatorAssignment::join('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->whereNotNull('pump_operator_assignments.shift_id')
            ->where('pump_operator_assignments.status', 'open');

        // Backward-compatible: some databases may not have `pumps.is_other_sales_pump` migrated yet.
        // Use the same connection as the query to support tenant DBs.
        // if ($physical_pumps_query->getConnection()->getSchemaBuilder()->hasColumn('pumps', 'is_other_sales_pump')) {
        //     $physical_pumps_query->where('pumps.is_other_sales_pump', 0);
        // }
        $hasOtherSalesColumn = Cache::remember(
            'pumps_has_is_other_sales_column',
            86400, // 24 hours
            function () use ($physical_pumps_query) {
                return $physical_pumps_query->getConnection()
                    ->getSchemaBuilder()
                    ->hasColumn('pumps', 'is_other_sales_pump');
            }
        );

        if ($hasOtherSalesColumn) {
            $physical_pumps_query->where('pumps.is_other_sales_pump', 0);
        }

        $physical_pumps_count = $physical_pumps_query->count();

        /*
         * SEQ025 Pumper Dashboard Current Open Shift Display Fix
         *
         * The dashboard must show the shift currently opened/assigned to the
         * logged-in pump operator. Do not fall back to the latest historical
         * shift number, because that can show wrong numbers such as 321.
         */
        $current_open_assignments = PumpOperatorAssignment::with(['shift', 'pump'])
            ->where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('status', 'open')
            ->where(function ($query) {
                $query->where('closed_in_settlement', 0)
                    ->orWhereNull('closed_in_settlement');
            })
            ->orderByRaw('CASE WHEN shift_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('id', 'asc')
            ->get();

        $current_shift = $current_open_assignments
            ->whereNotNull('shift_id')
            ->first();

        if (empty($current_shift)) {
            $current_shift = $current_open_assignments->first();
        }

        $current_shift_number = null;
        if (! empty($current_shift)) {
            $current_shift_number = $current_shift->shift_number
                ?? optional($current_shift->shift)->shift_number
                ?? optional($current_shift->shift)->shift_no
                ?? optional($current_shift->shift)->id;
        }

        $current_operator_name = optional(PumpOperator::find($pump_operator_id))->name ?: session('pumper_operator_name');

        $current_assigned_pumps = $current_open_assignments
            ->map(function ($assignment) {
                if (! empty($assignment->pump) && ! empty($assignment->pump->pump_name)) {
                    return $assignment->pump->pump_name;
                }

                if (! empty($assignment->pump) && ! empty($assignment->pump->pump_no)) {
                    return $assignment->pump->pump_no;
                }

                if (! empty($assignment->pump_id)) {
                    return 'Pump #' . $assignment->pump_id;
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        /*
         * ZIP108 / Pumper Dashboard Close Meter Access Fix
         * -------------------------------------------------
         * Closing the meter closes the pump assignment row, but it is NOT the same as
         * completing the Close Shift operation. The dashboard Payment / Other Sales
         * actions must remain available after all pump meters are closed, until the
         * operator completes Close Shift.
         *
         * Therefore, do not lock the dashboard merely because there are no remaining
         * `status = open` assignments. Lock only when the shift itself is completed
         * (petro_shifts.status = 2) or when the assignment has been marked as
         * closed_in_settlement by the actual Close Shift process.
         */
        $active_shift_assignment_query = PumpOperatorAssignment::with('shift')
            ->where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->whereNotNull('shift_id');

        if (Schema::hasColumn('pump_operator_assignments', 'closed_in_settlement')) {
            $active_shift_assignment_query->where(function ($query) {
                $query->where('closed_in_settlement', 0)
                    ->orWhereNull('closed_in_settlement')
                    ->orWhere('closed_in_settlement', '');
            });
        }

        $latest_active_shift_assignment = $active_shift_assignment_query
            ->orderBy('id', 'desc')
            ->first();

        /*
         * IS2312 - Payments must stay locked until the current shift's physical
         * pumps have completed Receive Pump. A simple "unconfirmed count == 0"
         * is not enough because it is also true when no pump has been assigned.
         * Keep the gate valid after Close Pump by checking the active shift rows
         * regardless of open/close assignment status; Close Shift is controlled
         * separately by $is_shift_closed below.
         */
        $payment_receive_complete = false;

        if (! empty($latest_active_shift_assignment) && ! empty($latest_active_shift_assignment->shift_id)) {
            $payment_receive_query = PumpOperatorAssignment::join('pumps', 'pumps.id', '=', 'pump_operator_assignments.pump_id')
                ->where('pump_operator_assignments.business_id', $business_id)
                ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
                ->where('pump_operator_assignments.shift_id', $latest_active_shift_assignment->shift_id);

            if (Schema::hasColumn('pump_operator_assignments', 'closed_in_settlement')) {
                $payment_receive_query->where(function ($query) {
                    $query->where('pump_operator_assignments.closed_in_settlement', 0)
                        ->orWhereNull('pump_operator_assignments.closed_in_settlement')
                        ->orWhere('pump_operator_assignments.closed_in_settlement', '');
                });
            }

            if ($hasOtherSalesColumn) {
                $payment_receive_query->where(function ($query) {
                    $query->where('pumps.is_other_sales_pump', 0)
                        ->orWhereNull('pumps.is_other_sales_pump');
                });
            }

            $payment_receive_assigned_count = (clone $payment_receive_query)->count();
            $payment_receive_pending_count = (clone $payment_receive_query)
                ->where(function ($query) {
                    $query->where('pump_operator_assignments.is_confirmed', 0)
                        ->orWhereNull('pump_operator_assignments.is_confirmed');
                })
                ->count();

            $payment_receive_complete = $payment_receive_assigned_count > 0
                && $payment_receive_pending_count === 0;
        }

        $is_shift_closed = false;
        if (empty($latest_active_shift_assignment)) {
            $is_shift_closed = true;
        } elseif (! empty($latest_active_shift_assignment->shift)) {
            $is_shift_closed = ((int) $latest_active_shift_assignment->shift->status === 2);
        }

        if (! empty($current_shift) && ! empty($current_shift->shift)) {
            $is_shift_closed = $is_shift_closed || ((int) $current_shift->shift->status === 2);
        }

        Log::warning('SEQ025 Pumper Dashboard current shift display', [
            'business_id' => $business_id,
            'user_id' => Auth::id(),
            'pump_operator_id' => $pump_operator_id,
            'current_shift_number' => $current_shift_number,
            'open_assignment_count' => $current_open_assignments->count(),
            'assigned_pumps' => $current_assigned_pumps,
        ]);

        $can_access_dashboard = $this->canAccessDashboard();

        return view('petropd::pd_operators.dashboard')->with(compact(
            'general_message',
            'fuel_tanks',
            'layout',
            'can_close_shift',
            'date_filters',
            'pump_operator_id',
            'unconfirmed_meters',
            'operator_permissions',
            'physical_pumps_count',
            'payment_receive_complete',
            'is_shift_closed',
            'can_access_dashboard',
            'current_shift_number',
            'current_operator_name',
            'current_assigned_pumps'
        ));
    }

    public function myAutoDashboard()
    {
        if (! $this->canAccessDashboard()) {
            abort(403, 'Unauthorized action.');
        }

        if (! session()->get('my_auto_agent_mode')) {
            return redirect()->action('\Modules\PetroPD\Http\Controllers\PumpOperatorController@dashboard@dashboard');
        }

        $layout = empty(session()->get('pump_operator_main_system')) ? 'pumper' : 'app';
        $currencies = $this->businessUtil->allCurrencies();
        $timezone_list = $this->businessUtil->allTimeZones();
        $business_categories = BusinessCategory::pluck('category_name', 'id');
        $business_types = DefaultBusinessType::pluck('business_type', 'id');
        $accounting_methods = $this->businessUtil->allAccountingMethods();
        $countries = DB::table('countries')->orderBy('country')->pluck('country', 'country')->toArray();
        $business_settings = DB::table('site_settings')->where('id', 1)->select('*')->first();
        $system_settings = [
            'superadmin_enable_register_tc' => System::getProperty('superadmin_enable_register_tc'),
        ];
        $show_referrals_in_register_page = json_decode(System::getProperty('show_referrals_in_register_page'), true) ?? [];
        $show_give_away_gift_in_register_page = json_decode(System::getProperty('show_give_away_gift_in_register_page'), true) ?? [];
        $give_away_gifts = GiveAwayGift::pluck('name', 'id')->toArray();
        $is_admin = true;
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.' . $i);
        }
        $my_auto_packages = Package::active()
            ->visible()
            ->where(function ($query) {
                $query->where('auto_services_and_repair_module', 1)
                    ->orWhere('home_dashboard', 1);
            })
            ->orderBy('sort_order')
            ->pluck('name', 'id');
        $my_auto_businesses = $this->myAutoBusinessesForDashboard();
        $my_auto_business_options = $my_auto_businesses
            ->sortBy('name')
            ->pluck('name', 'id');
        $my_auto_package_ids = Package::active()
            ->where(function ($query) {
                $query->where('auto_services_and_repair_module', 1)
                    ->orWhere('home_dashboard', 1);
            })
            ->pluck('id');
        $my_auto_subscriptions = Subscription::with(['package'])
            ->whereIn('package_id', $my_auto_package_ids)
            ->whereIn('business_id', $my_auto_businesses->keys())
            ->orderByDesc('created_at')
            ->get();
        $my_auto_agents = User::whereIn('id', $my_auto_businesses->pluck('created_by')->filter()->unique())
            ->get()
            ->keyBy('id');
        $latest_my_auto_subscriptions = $my_auto_subscriptions
            ->groupBy('business_id')
            ->map(function ($items) {
                return $items->sortByDesc('created_at')->first();
            });
        $my_auto_registrations = $my_auto_businesses
            ->map(function ($business) use ($latest_my_auto_subscriptions, $my_auto_agents) {
                $subscription = $latest_my_auto_subscriptions->get($business->id);
                $agent = $my_auto_agents->get($business->created_by);

                return [
                    'date_time' => optional($business->created_at)->format('d/m/Y H:i'),
                    'my_auto_number' => $business->name,
                    'package' => data_get($subscription, 'package.name'),
                    'subscription_amount' => data_get($subscription, 'package_price', 0),
                    'agent_name' => $this->formatUserName($agent),
                    'subscription_ends_date' => $this->formatMaybeDate(data_get($subscription, 'end_date'), 'd/m/Y'),
                ];
            })
            ->values();
        $my_auto_business_ids = $my_auto_businesses->keys()->values();
        $my_auto_payments = PayOnline::whereIn('business_id', $my_auto_business_ids)
            ->where(function ($query) {
                $query->where('note', 'like', 'My Auto Subscription%')
                    ->orWhere('first_name', '!=', '');
            })
            ->orderByDesc('created_at')
            ->get();
        $my_auto_payments_by_subscription = $my_auto_payments
            ->groupBy(function ($payment) {
                return (int) $payment->business_id . '|' . trim((string) $payment->reference_no);
            })
            ->map(function ($items) {
                return $items->sortByDesc('created_at')->first();
            });
        $my_auto_subscriptions_by_business = $my_auto_subscriptions
            ->groupBy('business_id')
            ->map(function ($items) {
                return $items->sortByDesc('created_at')->values();
            });
        $my_auto_subscription_rows = $my_auto_subscriptions
            ->map(function ($subscription) use ($my_auto_businesses, $my_auto_payments_by_subscription) {
                $package_name = data_get($subscription, 'package.name')
                    ?? data_get($subscription, 'package_details.name')
                    ?? '';
                $payment = $my_auto_payments_by_subscription->get(
                    (int) $subscription->business_id . '|' . trim((string) $package_name)
                );
                $note_meta = $this->parseMyAutoPaymentNote($payment?->note);
                $business = $my_auto_businesses->get($subscription->business_id);

                return [
                    'id' => $payment->id ?? $subscription->id,
                    'date_time' => optional($payment?->created_at ?? $subscription->created_at)->format('d/m/Y H:i'),
                    'my_auto_number' => $business->name ?? trim(($payment->first_name ?? '') . ' ' . ($payment->last_name ?? '')),
                    'package' => $package_name,
                    'subscription_amount' => $payment->amount ?? $subscription->package_price,
                    'payment_method' => $subscription->paid_via === 'offline' ? 'Offline' : 'Online',
                    'currency' => $payment->currency ?? '',
                    'payment_reference_no' => $payment->payment_transaction_id ?? $subscription->payment_transaction_id ?? '',
                    'subscription_cycle' => $note_meta['subscription_cycle'] ?? '',
                    'subscription_starts_on' => $this->formatMaybeDate($subscription->start_date, 'd/m/Y'),
                    'subscription_ends_on' => $this->formatMaybeDate($subscription->end_date, 'd/m/Y'),
                    'subscription_starts_on_input' => $this->formatMaybeDate($subscription->start_date, 'Y-m-d'),
                    'subscription_ends_on_input' => $this->formatMaybeDate($subscription->end_date, 'Y-m-d'),
                    'payment_status' => $this->formatMyAutoSubscriptionStatus($subscription->status),
                ];
            })
            ->concat($my_auto_payments->filter(function ($payment) use ($my_auto_subscriptions_by_business) {
                return empty($this->resolveMatchingMyAutoSubscription(
                    $payment,
                    $my_auto_subscriptions_by_business->get($payment->business_id, collect())
                ));
            })->map(function ($payment) use ($my_auto_businesses, $my_auto_subscriptions_by_business) {
                $business = $my_auto_businesses->get($payment->business_id);
                $subscription = $this->resolveMatchingMyAutoSubscription(
                    $payment,
                    $my_auto_subscriptions_by_business->get($payment->business_id, collect())
                );
                $note_meta = $this->parseMyAutoPaymentNote($payment->note);

                return [
                    'id' => $payment->id,
                    'date_time' => optional($payment->created_at)->format('d/m/Y H:i'),
                    'my_auto_number' => $business->name ?? trim(($payment->first_name ?? '') . ' ' . ($payment->last_name ?? '')),
                    'package' => $payment->reference_no
                        ?: data_get($subscription, 'package.name')
                        ?? data_get($subscription, 'package_details.name')
                        ?? '',
                    'subscription_amount' => $payment->amount,
                    'payment_method' => $payment->paid_via === 'offline' ? 'Offline' : 'Online',
                    'currency' => $payment->currency,
                    'payment_reference_no' => $payment->payment_transaction_id ?? '',
                    'subscription_cycle' => $note_meta['subscription_cycle'] ?? '',
                    'subscription_starts_on' => $this->formatMaybeDate($subscription?->start_date, 'd/m/Y'),
                    'subscription_ends_on' => $this->formatMaybeDate($subscription?->end_date, 'd/m/Y'),
                    'subscription_starts_on_input' => $this->formatMaybeDate($subscription?->start_date, 'Y-m-d'),
                    'subscription_ends_on_input' => $this->formatMaybeDate($subscription?->end_date, 'Y-m-d'),
                    'payment_status' => $this->formatMyAutoPaymentStatus($payment->status),
                ];
            }))
            ->sortByDesc('date_time')
            ->values();

        return view('petropd::pd_operators.my_auto_dashboard')->with(compact(
            'layout',
            'currencies',
            'timezone_list',
            'business_categories',
            'business_types',
            'accounting_methods',
            'countries',
            'business_settings',
            'system_settings',
            'show_referrals_in_register_page',
            'show_give_away_gift_in_register_page',
            'give_away_gifts',
            'is_admin',
            'months',
            'my_auto_packages',
            'my_auto_business_options',
            'my_auto_registrations',
            'my_auto_subscription_rows'
        ));
    }

    public function startMyAutoSubscriptionCheckout(Request $request)
    {
        if (! $this->canAccessDashboard()) {
            abort(403, 'Unauthorized action.');
        }

        if (! session()->get('my_auto_agent_mode')) {
            return redirect()->action('\Modules\PetroPD\Http\Controllers\PumpOperatorController@dashboard@dashboard');
        }

        $request->validate([
            'business_id' => 'required|integer',
            'package_id' => 'required|integer',
            'subscription_cycle' => 'required|in:Daily,Monthly,Biannually,Annually',
            'amount_to_auto_load' => 'nullable|numeric|min:0',
        ]);

        $package = Package::active()
            ->visible()
            ->where(function ($query) {
                $query->where('auto_services_and_repair_module', 1)
                    ->orWhere('home_dashboard', 1);
            })
            ->findOrFail($request->package_id);

        $business = $this->myAutoBusinessesForDashboard()->get((int) $request->business_id);

        if (empty($business)) {
            abort(404);
        }

        $request->session()->put('my_auto_checkout', [
            'business_id' => $business->id,
            'package_id' => $package->id,
            'auto_number' => $business->name,
            'subscription_cycle' => $request->subscription_cycle,
            'amount_to_auto_load' => $request->filled('amount_to_auto_load')
                ? $request->amount_to_auto_load
                : null,
        ]);

        return redirect()->action(
            '\Modules\Superadmin\Http\Controllers\SubscriptionController@pay',
            ['package_id' => $package->id, 'my_auto' => 1]
        );
    }

    public function updateMyAutoSubscription(Request $request, $payment_id)
    {
        if (! $this->canAccessDashboard()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'payment_reference_no' => 'nullable|string|max:255',
            'subscription_cycle' => 'nullable|in:Daily,Monthly,Biannually,Annually',
            'subscription_starts_on' => 'nullable|date',
            'subscription_ends_on' => 'nullable|date|after_or_equal:subscription_starts_on',
        ]);

        $payment = PayOnline::findOrFail($payment_id);
        $payment_note_meta = $this->parseMyAutoPaymentNote($payment->note);
        $payment->payment_transaction_id = $request->input('payment_reference_no');
        $payment->note = $this->buildMyAutoPaymentNote(
            $request->input('subscription_cycle'),
            $payment_note_meta['amount_to_auto_load'] ?? null,
            $payment->paid_via === 'offline' ? 'Offline' : 'Online'
        );
        $payment->save();

        $my_auto_package_ids = Package::active()
            ->where(function ($query) {
                $query->where('auto_services_and_repair_module', 1)
                    ->orWhere('home_dashboard', 1);
            })
            ->pluck('id');
        $subscription = Subscription::with('package')
            ->where('business_id', $payment->business_id)
            ->whereIn('package_id', $my_auto_package_ids)
            ->orderByDesc('created_at')
            ->get();
        $subscription = $this->resolveMatchingMyAutoSubscription($payment, $subscription);

        if (!empty($subscription)) {
            if ($request->filled('subscription_starts_on')) {
                $subscription->start_date = $request->input('subscription_starts_on');
            }
            if ($request->filled('subscription_ends_on')) {
                $subscription->end_date = $request->input('subscription_ends_on');
            }
            $subscription->save();
        }

        return redirect()
            ->back()
            ->with('status', ['success' => 1, 'msg' => 'My Auto subscription details updated successfully.']);
    }

    private function parseMyAutoPaymentNote(?string $note): array
    {
        $parsed = [
            'subscription_cycle' => null,
            'amount_to_auto_load' => null,
        ];

        if (empty($note)) {
            return $parsed;
        }

        if (preg_match('/Cycle:\s*([^|]+)/i', $note, $cycle_matches)) {
            $parsed['subscription_cycle'] = trim($cycle_matches[1]);
        }

        if (preg_match('/Auto Load:\s*([^|]+)/i', $note, $amount_matches)) {
            $parsed['amount_to_auto_load'] = trim($amount_matches[1]);
        }

        return $parsed;
    }

    private function buildMyAutoPaymentNote(?string $subscription_cycle, ?string $amount_to_auto_load, ?string $gateway_label): string
    {
        return collect([
            'My Auto Subscription',
            !empty($subscription_cycle) ? 'Cycle: ' . $subscription_cycle : null,
            $amount_to_auto_load !== null && $amount_to_auto_load !== '' ? 'Auto Load: ' . $amount_to_auto_load : null,
            !empty($gateway_label) ? 'Gateway: ' . $gateway_label : null,
        ])->filter()->implode(' | ');
    }

    private function resolveMatchingMyAutoSubscription(PayOnline $payment, Collection $subscriptions): ?Subscription
    {
        if ($subscriptions->isEmpty()) {
            return null;
        }

        $payment_transaction_id = trim((string) $payment->payment_transaction_id);
        $payment_package_name = trim((string) $payment->reference_no);

        $package_matched_subscriptions = $subscriptions->filter(function ($subscription) use ($payment_package_name) {
            if ($payment_package_name === '') {
                return true;
            }

            $subscription_package_name = trim((string) (
                data_get($subscription, 'package.name')
                ?? data_get($subscription, 'package_details.name')
                ?? ''
            ));

            return $subscription_package_name !== ''
                && strcasecmp($subscription_package_name, $payment_package_name) === 0;
        });

        $candidates = $package_matched_subscriptions->isNotEmpty()
            ? $package_matched_subscriptions->values()
            : $subscriptions->values();

        if ($payment_transaction_id !== '') {
            $transaction_matched_subscription = $candidates->first(function ($subscription) use ($payment_transaction_id) {
                return trim((string) $subscription->payment_transaction_id) === $payment_transaction_id;
            });

            if (!empty($transaction_matched_subscription)) {
                return $transaction_matched_subscription;
            }
        }

        $payment_timestamp = optional($payment->created_at)->getTimestamp() ?? 0;

        return $candidates
            ->sortBy(function ($subscription) use ($payment_timestamp) {
                $subscription_timestamp = optional($subscription->created_at)->getTimestamp() ?? $payment_timestamp;

                return abs($subscription_timestamp - $payment_timestamp);
            })
            ->first();
    }

    private function formatMyAutoPaymentStatus(?string $status): string
    {
        return match ($status) {
            'approved' => 'Success',
            'declined' => 'Failed',
            default => 'Pending',
        };
    }

    private function formatMyAutoSubscriptionStatus(?string $status): string
    {
        return match ($status) {
            'approved' => 'Success',
            'declined' => 'Failed',
            default => 'Pending',
        };
    }

    private function formatUserName(?User $user): string
    {
        if (empty($user)) {
            return '';
        }

        return trim(implode(' ', array_filter([
            $user->surname,
            $user->first_name,
            $user->last_name,
        ])));
    }

    private function formatMaybeDate($value, string $format): string
    {
        if (empty($value)) {
            return '';
        }

        if ($value instanceof Carbon) {
            return $value->format($format);
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function setting_dash()
    {
        $card_types  = [];
        $business_id = Auth::user()->business_id;
        $card_group  = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'name');

        // CODEX FIX: Fixed variable reassignment bug ($settings was overwriting $pump_operators)
        $settings = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();

        // CODEX FIX: Ensure all required dashboard settings keys exist with defaults
        // This prevents "Undefined array key" errors on client servers with older data
        if (!empty($settings) && !empty($settings->dashboard_settings)) {
            $decodedSettings = json_decode($settings->dashboard_settings, true);
            
            // Handle invalid JSON gracefully
            if (!is_array($decodedSettings)) {
                $decodedSettings = [];
            }
            
            // Define default values for all dashboard settings
            $defaults = [
                'credit_sales_direct_to_customer' => 'no',
                'show_bulk_pumps' => 'no',
                'meter_sales_compulsory' => 'no',
                'enter_cash_denominations' => 'no',
                'enter_card_numbers' => 'no',
                'card_amount_to_enter' => 'bulk',
                'logoff_time' => '',
                'logoff' => '',
                'bill_prefix' => '',
                'starting_bill_number' => '',
            ];
            
            // Merge defaults with existing settings (existing values take precedence)
            $decodedSettings = array_merge($defaults, $decodedSettings);
            
            // Re-encode and update the settings object for view consumption
            $settings->dashboard_settings = json_encode($decodedSettings);
        }

        return view('petropd::pd_operators.setting_dash')->with(compact(
            'card_types',
            'pump_operators',
            'business_id',
            'settings'
        ));
    }

    public function getAssignedPumps($id)
    {
        $business_id = Auth::user()->business_id;

        $pumps = Pump::join('pump_operator_assignments', function ($join) {
            $join->on('pumps.id', 'pump_operator_assignments.pump_id')->where('status', '!=', 'close');
        })
            ->leftjoin('pump_operators', 'pump_operator_assignments.pump_operator_id', 'pump_operators.id')
            ->where('pumps.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $id)
            ->select('pumps.pump_name', 'pumps.id')
            ->groupBy('pumps.id')
            ->pluck('pump_name', 'id') ?? [];
        return $pumps;
    }

    public function getDashboardData(Request $request)
    {
        $pump_operator_id = $request->pump_operator_id;
        $pump_operator    = PumpOperator::findOrFail($pump_operator_id);
        $start_date       = $request->start ?? date('Y-m-01');
        $end_date         = $request->end ?? date('Y-m-t');
        $data             = [
            'total_liter_sold'    => 0,
            'total_income_earned' => 0,
            'total_short'         => 0,
            'total_excess'        => 0,
        ];
        $sold_fuel_query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('categories', 'products.category_id', 'categories.id')
            ->where('transactions.type', 'sell')
            ->where('categories.name', 'Fuel')
            ->where('transactions.pump_operator_id', $pump_operator_id)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select([
                DB::raw('SUM(transaction_sell_lines.quantity) as sold_fuel_qty'),
                DB::raw('SUM(transaction_sell_lines.quantity * unit_price) as sale_amount_fuel'),
            ])->first();

        if (! empty($sold_fuel_query->sold_fuel_qty)) {
            $data['total_liter_sold'] = $sold_fuel_query->sold_fuel_qty;
        }

        if ($pump_operator->commission_type == 'fixed') {
            $data['total_income_earned'] = $sold_fuel_query->sold_fuel_qty * $pump_operator->commission_ap;
        }
        if ($pump_operator->commission_type == 'percentage') {
            $data['total_income_earned'] = ($sold_fuel_query->sale_amount_fuel * $pump_operator->commission_ap) / 100;
        }

        $short_amount_query = Transaction::where('transactions.type', 'settlement')
            ->where('transactions.sub_type', 'shortage')
            ->where('transactions.pump_operator_id', $pump_operator_id)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select([
                DB::raw('SUM(transactions.final_total) as short_amount'),
            ])->first();
        $excess_amount_query = Transaction::where('transactions.type', 'settlement')
            ->where('transactions.sub_type', 'excess')
            ->where('transactions.pump_operator_id', $pump_operator_id)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select([
                DB::raw('SUM(transactions.final_total) as excess_amount'),
            ])->first();

        $data['total_short']  = $short_amount_query->short_amount;
        $data['total_excess'] = $excess_amount_query->excess_amount;

        return $data;
    }

    /**
     * check user name exist or not
     * @return Renderable
     */
    public function checUsername(Request $request)
    {
        $user = User::where('username', $request->username)->first();

        if (empty($user)) {
            return ['success' => 1, 'msg' => 'ok'];
        } else {
            return ['success' => 0, 'msg' => __('lang_v1.username_already_exist')];
        }
    }

    /**
     * check user name exist or not
     * @return Renderable
     */
    public function checPasscode(Request $request)
    {
        $user = User::where('pump_operator_passcode', $request->passcode)->first();

        if (empty($user)) {
            return ['success' => 1, 'msg' => 'ok'];
        } else {
            return ['success' => 0, 'msg' => __('lang_v1.passcode_already_exist')];
        }
    }

    /**
     * check user name exist or not
     * @return Renderable
     */
    public function setMainSystemSession(Request $request)
    {
        $request->session()->put('pump_operator_main_system', true);

        return redirect()->route('petropd.pd-operators');
    }

    public function blockedPumperLoginAttempt()
    {
        $pumperLoginAttempts = PumperLoginAttempt::where('status', "Blocked");
        if (! auth()->user()->can('superadmin')) {
            $business_id         = Auth::user()->business_id;
            $pumperLoginAttempts = $pumperLoginAttempts->where('business_id', $business_id);
        }
        $pumperLoginAttempts = $pumperLoginAttempts->latest('updated_at')->get();

        return view('petropd::pd_operators.blocked_pumper_login_attempt')->with(compact(
            'pumperLoginAttempts',
        ));
    }

    public function pumperLoginAttemptHistory()
    {
        $isSuperadmin = auth()->user()->can('superadmin');
        $businessId = $isSuperadmin
            ? null
            : (int) (Auth::user()->business_id ?: session('business.id'));

        $pumperLoginAttempts = PumperLoginAttempt::query()
            ->when(! $isSuperadmin, function ($query) use ($businessId): void {
                $query->where('business_id', $businessId);
            })
            ->latest('updated_at')
            ->get();

        $historyReady = class_exists(PumperLoginAttemptHistory::class)
            && Schema::hasTable('pumper_login_attempt_histories');
        $historyQuery = $historyReady
            ? PumperLoginAttemptHistory::query()->when(
                ! $isSuperadmin,
                function ($query) use ($businessId): void {
                    $query->where('business_id', $businessId);
                }
            )
            : null;
        $pumperLoginHistories = $historyQuery
            ? (clone $historyQuery)->latest('blocked_at')->paginate(100)
            : collect();
        $historySummary = [
            'login_records' => $pumperLoginAttempts->count(),
            'currently_blocked' => $pumperLoginAttempts->where('status', 'Blocked')->count(),
            'block_events' => $historyQuery ? (clone $historyQuery)->count() : 0,
            'unblocked_events' => $historyQuery
                ? (clone $historyQuery)->whereNotNull('unblocked_at')->count()
                : 0,
        ];

        return view('petropd::pd_operators.pumper_login_attempt_history')->with(compact(
            'pumperLoginAttempts',
            'pumperLoginHistories',
            'historyReady',
            'historySummary'
        ));
    }

    public function unblockPumperLoginAttempt(Request $request, $id)
    {
        $pumperLoginAttemptQuery = PumperLoginAttempt::where('id', $id);

        if (! auth()->user()->can('superadmin')) {
            $businessId = (int) (Auth::user()->business_id ?: session('business.id'));
            $pumperLoginAttemptQuery->where('business_id', $businessId);
        }

        $pumperLoginAttempt = $pumperLoginAttemptQuery->first();

        if (empty($pumperLoginAttempt)) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg'     => 'The blocked pump operator login was not found for this business.',
            ]);
        }

        $blockedAt = $pumperLoginAttempt->updated_at ?: $pumperLoginAttempt->created_at;
        $attemptCount = (int) $pumperLoginAttempt->attempt_count;
        $pumperLoginAttempt->attempt_count = 0;
        $pumperLoginAttempt->status        = "Active";
        $pumperLoginAttempt->save();

        app(PumperLoginAttemptAuditService::class)->recordUnblocked(
            $pumperLoginAttempt,
            Auth::user(),
            'PetroPD',
            $blockedAt,
            $attemptCount
        );

        $output = [
            'success' => 1,
            'msg'     => __('lang_v1.success'),
        ];
        if ($request->query('return_to') === 'history') {
            return redirect()->route('petropd.pumper-login-attempt-history')->with('status', $output);
        }
        if (auth()->user()->can('superadmin')) {
            return redirect()->route('petropd.blocked-pumper-login-attempts')->with('status', $output);
        }
        return redirect()->route('petropd.pd-operators')->with('status', $output);
    }
}
