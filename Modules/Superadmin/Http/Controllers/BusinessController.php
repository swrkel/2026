<?php
namespace Modules\Superadmin\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\AccountType;
use App\Business;
use App\BusinessCategory;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\DefaultAccountGroup;
use App\DefaultAccountType;
use App\DefaultExpenseCategory;
use App\DefaultProductCategory;
use App\ExpenseCategory;
use App\NotificationTemplate;
use App\Product;
use App\Store;
use App\System;
use App\Services\Authorization\SuperAdminImpersonation;
use App\Transaction;
use App\User;
use App\UserSetting;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\VariationLocationDetails;
/*
 | REMOVED: use function GuzzleHttp\json_decode;
 |
 | That import made every unqualified json_decode() in this file resolve to
 | Guzzle's function instead of PHP's. Guzzle's version takes its arguments
 | differently and throws InvalidArgumentException where PHP would return null,
 | so it rejected a perfectly valid payload and every Manage save failed with
 | "json_decode error: Syntax error".
 |
 | Measured on a failing save: the payload was 26,981 characters, correctly
 | closed, valid UTF-8, with 576 posted variables against a 10,000 limit. The
 | JSON was never at fault - only the function the name resolved to.
 |
 | There were 28 unqualified calls in this file, so removing the import fixes
 | all of them at once rather than one at a time.
 */
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\HelpGuide\Entities\Role;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Superadmin\Entities\AccountNumber;
use Modules\Superadmin\Entities\DefaultBusinessType;
use Modules\Superadmin\Entities\GiveAwayGift;
use Modules\Superadmin\Entities\ModulePermissionLocation;
use Modules\Superadmin\Entities\Package;
use Modules\Superadmin\Entities\Subscription;
use Modules\Superadmin\Services\ManagePagePerformanceService;
use Modules\Superadmin\Services\ModulePermissionService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class BusinessController extends BaseController
{
    protected $businessUtil;
    protected $moduleUtil;

    /** @var array<int, array<string, mixed>> */
    private array $deferredManageTenantSync = [];
    private bool $deferredManageTenantSyncRegistered = false;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil   = $moduleUtil;
    }

    private function modulePermissionService(): ModulePermissionService
    {
        return app(ModulePermissionService::class);
    }

    private function managePerformanceService(): ManagePagePerformanceService
    {
        return app(ManagePagePerformanceService::class);
    }

    public function updatePerms()
    {
        $subscriptions = Subscription::groupBy('business_id')->orderBy('end_date', 'DESC')->get();
        if (! empty($subscriptions)) {
            foreach ($subscriptions as $one) {
                $package_details = $one->package_details;

                if (! empty($package_details)) {
                    $moudle_permissions = Subscription::getBusinessPermissionsArray();
                    foreach ($moudle_permissions as $permission) {
                        if (! array_key_exists($permission, $package_details)) {
                            if (! empty($one)) {
                                $default_active = [
                                    '1_30_days',
                                    '31_45_days',
                                    '46_60_days',
                                    '61_90_days',
                                    'over_90_days',
                                ];
                                if (in_array($permission, $default_active)) {
                                    $val = 1;
                                } else {
                                    $val = 0;
                                }
                                Subscription::where('id', $one->id)->update(['package_details->' . $permission => $val]);
                            }
                        }
                    }
                }

            }
        }

    }

    public function notifyExpired()
    {
        $subscriptions = Subscription::groupBy('business_id')->orderBy('end_date', 'DESC')->get();
        if (! empty($subscriptions)) {
            foreach ($subscriptions as $one) {
                $package_details = $one->package_details;

                // dd($package_details);
                $diff = (strtotime($one->end_date) - strtotime(date('Y-m-d'))) / 86400;
                // dd($diff);
                if (
                    ! empty($package_details['message_content'])
                    && ! empty($package_details['reminder_phone'])
                    &&
                    (! empty($package_details['first_reminder']) || ! empty($package_details['second_reminder']) || ! empty($package_details['third_reminder']))
                    &&
                    ($package_details['first_reminder'] == $diff
                        || $package_details['second_reminder'] == $diff
                        || $package_details['third_reminder'] == $diff)
                ) {

                    $phones   = json_decode($package_details['reminder_phone'], true);
                    $business = Business::where('id', $one->business_id)->first();

                    $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
                    foreach ($phones as $phone) {

                        $data = [
                            'sms_settings'  => $sms_settings,
                            'mobile_number' => $phone,
                            'sms_body'      => $package_details['message_content'],
                        ];
                        $response = $this->businessUtil->superadminSendSms($data);
                    }
                }
            }
        }

    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        DB::disableQueryLog();

        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        // The filter needs only two fields. Keep it separate from the card query
        // so the business page never hydrates every business model twice.
        $business = Business::query()
            ->select(DB::raw("CONCAT(COALESCE(name, ''),' ',COALESCE(company_number, '')) AS company_name"), 'id')
            ->orderBy('name')
            ->get();

        $filterBusinessId = $request->input('filter_business');
        $filterBusinessId = is_numeric($filterBusinessId) && (int) $filterBusinessId > 0
            ? (int) $filterBusinessId
            : null;

        // The All Business list, and the Manage Side Bar modal data built from
        // it, must come from the central database regardless of which host the
        // Super Admin is browsing on. See CentralContext for why.
        $businessQuery = \Modules\Superadmin\Services\CentralContext::businessQuery()
            ->select([
                'id',
                'name',
                'company_number',
                'logo',
                'owner_id',
                'is_patient',
                'is_active',
                'enabled_modules',
            ])
            // tenant_id distinguishes a business that lives in central from one
            // that lives in a tenant database and is only registered here.
            // Selected conditionally so an install without the column still works.
            ->when(
                \Illuminate\Support\Facades\Schema::connection(
                    \Modules\Superadmin\Services\CentralContext::connectionName()
                )->hasColumn('business', 'tenant_id'),
                static function ($query) {
                    $query->addSelect('tenant_id');
                }
            )
            ->when($filterBusinessId, static function ($query) use ($filterBusinessId) {
                $query->whereKey($filterBusinessId);
            })
            ->with([
                'owner' => static function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email', 'contact_no', 'username');
                },
                'owner.setting' => static function ($query) {
                    $query->select('id', 'user_id', 'opt_verification_enabled', 're_captcha_enabled');
                },
            ])
            ->orderBy('name');

        $businesses = $businessQuery->paginate(21)->appends($request->except('page'));

        /*
         | Which tenant is the page itself reading?
         |
         | A row is a REGISTRY entry - a business that lives somewhere else and
         | is only recorded here - when its tenant_id names a different database
         | from the one being read.
         |
         | Inside a tenant database every business carries that tenant's own id,
         | so none of them are registry rows. Testing "tenant_id is set" alone
         | got this wrong: on testtenant every card showed "Tenant: testtenant"
         | instead of the owner, for no reason.
         */
        $currentTenantId = (function_exists('tenant') && tenant())
            ? (string) tenant()->getTenantKey()
            : null;
        $pageBusinessIds = $businesses->getCollection()->pluck('id')->map(static fn ($id) => (int) $id)->all();

        $businessCardData = [];
        if ($pageBusinessIds !== []) {
            // Load only the first location for each card instead of every location
            // owned by every business on the page.
            $firstLocationIds = BusinessLocation::query()
                ->selectRaw('MIN(id)')
                ->whereIn('business_id', $pageBusinessIds)
                ->groupBy('business_id');

            $locationsByBusiness = BusinessLocation::query()
                ->select([
                    'id',
                    'business_id',
                    'mobile',
                    'alternate_number',
                    'city',
                    'landmark',
                    'state',
                    'country',
                    'zip_code',
                ])
                ->whereIn('id', $firstLocationIds)
                ->get()
                ->keyBy('business_id');

            // One bulk subscription query replaces loading the complete relation
            // separately into every card. Only columns used by this page are read.
            $subscriptionsByBusiness = Subscription::query()
                ->select([
                    'id',
                    'business_id',
                    'package_id',
                    'start_date',
                    'end_date',
                    'trial_end_date',
                ])
                ->whereIn('business_id', $pageBusinessIds)
                ->approved()
                ->orderBy('business_id')
                ->orderByDesc('end_date')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get()
                ->groupBy('business_id');

            $currentSubscriptions = collect();
            foreach ($subscriptionsByBusiness as $businessIdKey => $subscriptions) {
                if ($subscriptions->isNotEmpty()) {
                    $currentSubscriptions->put((int) $businessIdKey, $subscriptions->first());
                }
            }

            $packageNames = Package::withTrashed()
                ->whereIn('id', $currentSubscriptions->pluck('package_id')->filter()->unique()->values()->all())
                ->pluck('name', 'id');

            $today = \Carbon\Carbon::today();
            $todayString = $today->toDateString();

            foreach ($businesses->getCollection() as $businessItem) {
                $subscriptions = $subscriptionsByBusiness->get($businessItem->id, collect());
                $currentSubscription = $currentSubscriptions->get($businessItem->id);
                $activeSubscription = $subscriptions->first(static function ($subscription) use ($todayString) {
                    if (empty($subscription->start_date)) {
                        return false;
                    }

                    $startDate = \Carbon\Carbon::parse($subscription->start_date)->toDateString();
                    $endDate = empty($subscription->end_date)
                        ? null
                        : \Carbon\Carbon::parse($subscription->end_date)->toDateString();
                    $trialEndDate = empty($subscription->trial_end_date)
                        ? null
                        : \Carbon\Carbon::parse($subscription->trial_end_date)->toDateString();

                    return $startDate <= $todayString
                        && ($endDate === null || $endDate >= $todayString || ($trialEndDate !== null && $trialEndDate >= $todayString));
                });

                $businessCardData[$businessItem->id] = [
                    'address' => $locationsByBusiness->get($businessItem->id),
                    'current_subscription' => $currentSubscription,
                    'active_subscription' => $activeSubscription,
                    'package_name' => $currentSubscription
                        ? $packageNames->get($currentSubscription->package_id)
                        : null,
                    'remaining_days' => $activeSubscription && $activeSubscription->end_date
                        ? $today->diffInDays(\Carbon\Carbon::parse($activeSubscription->end_date))
                        : null,
                ];
            }
        }

        // Send the common module catalogue and each business's checked keys with
        // the page. The popup is then built locally and opens without a second
        // HTTP request or another filesystem/module scan.
        $manageSidebarModalData = [
            'modules' => [],
            'businesses' => [],
        ];

        try {
            $service = $this->modulePermissionService();
            $sidebarModules = $service->discoverSidebarModules([]);
            $manageSidebarModalData['modules'] = $sidebarModules;

            foreach ($businesses->getCollection() as $businessItem) {
                $states = $service->sidebarModuleStates($businessItem, $sidebarModules);
                $manageSidebarModalData['businesses'][(string) $businessItem->id] = [
                    'name' => $businessItem->name,
                    'checked' => array_values(array_keys(array_filter($states))),
                    'save_url' => url('/superadmin/business/' . $businessItem->id . '/save-sidebar-modules'),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Manage Side Bar preload data could not be built; AJAX fallback remains available.', [
                'message' => $e->getMessage(),
            ]);
        }

        $business_id = request()->session()->get('user.business_id');

        return view('superadmin::business.index')->with(compact(
            'businesses',
            'business_id',
            'business',
            'businessCardData',
            'manageSidebarModalData',
            'currentTenantId'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $currencies          = $this->businessUtil->allCurrencies();
        $countries           = DB::table('countries')->orderBy('country')->pluck('country', 'country')->toArray();
        $timezone_list       = $this->businessUtil->allTimeZones();
        $business_categories = BusinessCategory::pluck('category_name', 'id');
        $business_types      = DefaultBusinessType::pluck('business_type', 'id');

        $accounting_methods = $this->businessUtil->allAccountingMethods();

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.' . $i);
        }

        $is_admin = true;

        $show_give_away_gift_in_register_page = ! empty(System::getProperty('show_give_away_gift_in_register_page')) ? System::getProperty('show_give_away_gift_in_register_page') : [];
        $show_referrals_in_register_page      = ! empty(System::getProperty('show_referrals_in_register_page')) ? System::getProperty('show_referrals_in_register_page') : [];
        $show_give_away_gift_in_register_page = json_decode($show_give_away_gift_in_register_page, true);
        $show_referrals_in_register_page      = json_decode($show_referrals_in_register_page, true);
        $give_away_gifts_array                = GiveAwayGift::pluck('name', 'id')->toArray();

        $give_away_gifts = [];
        foreach ($give_away_gifts_array as $key => $value) {
            $give_away_gifts[$key] = $value;
        }

        return view('superadmin::business.create')
            ->with(compact(
                'currencies',
                'countries',
                'timezone_list',
                'business_categories',
                'accounting_methods',
                'show_referrals_in_register_page',
                'months',
                'is_admin',
                'show_give_away_gift_in_register_page',
                'show_referrals_in_register_page',
                'give_away_gifts',
                'business_types'
            ));
    }

    /**
     * Give a newly created business a global identity and record where it lives.
     *
     * global_uid - identity that means the same thing in every database.
     *   business.id cannot serve this purpose: it is a per-database
     *   auto-increment, so id 2 is one company in one database and a different
     *   company in another. company_number is worse - it is derived from a row
     *   count, so it repeats after any deletion.
     *
     * tenant_id - which database this business lives in. NULL means the central
     *   database. Without it central has no way to know where a business is,
     *   which is how "Global-NEW" came to exist in a tenant database and
     *   nowhere in the registry.
     *
     * Stamped at creation rather than by a later sweep, so the registry stays
     * correct as businesses are added instead of being a snapshot that drifts.
     *
     * Deliberately defensive: an install that has not yet had the columns added
     * still creates businesses normally.
     */
    private function stampBusinessIdentity($business): void
    {
        try {
            if (empty($business) || empty($business->id)) {
                return;
            }

            $connectionName = $business->getConnectionName();
            $connection = DB::connection($connectionName);
            $identity = [];

            if (Schema::connection($connectionName)->hasColumn('business', 'global_uid')) {
                $identity['global_uid'] = (string) \Illuminate\Support\Str::uuid();
            }

            if (Schema::connection($connectionName)->hasColumn('business', 'tenant_id')) {
                // tenancy() is initialised on a tenant host and absent on a
                // central one, which is exactly the distinction wanted here.
                $identity['tenant_id'] = (function_exists('tenant') && tenant())
                    ? (string) tenant()->getTenantKey()
                    : null;
            }

            if ($identity === []) {
                return;
            }

            $connection->table('business')
                ->where('id', $business->id)
                ->update($identity);

            Log::info('Business identity stamped', [
                'business_id' => $business->id,
                'global_uid' => $identity['global_uid'] ?? null,
                'tenant_id' => $identity['tenant_id'] ?? null,
                'database' => $connection->getDatabaseName(),
            ]);

            // A business created in a tenant database is invisible to Super
            // Admin until central knows it exists. Register it now.
            $this->registerBusinessCentrally($business, $identity);
        } catch (\Throwable $e) {
            // Identity matters, but it must never stop a business being
            // created. A missing uid can be filled in later by the rollout
            // script; a failed creation cannot be undone as easily.
            Log::warning('Business created but identity could not be stamped.', [
                'business_id' => $business->id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Copy a newly created tenant business into the central registry.
     *
     * WHAT IS WRITTEN
     *   global_uid, name, tenant_id, company_number, created_at, updated_at.
     *   Identity and location only.
     *
     * WHAT IS NOT WRITTEN, DELIBERATELY
     *   No package_details, no enabled_modules, no users, no subscriptions.
     *   Those live in the tenant database and nowhere else.
     *
     *   The moment a setting exists in two places there are two sources of
     *   truth and they drift. Three days of this project were spent on exactly
     *   that: a Manage save writing to one database while the page read
     *   another, and a tenant sync copying settings onto the wrong company.
     *
     *   Super Admin managing a tenant business is fine - it should read and
     *   write that tenant's database directly. Mirroring the settings into
     *   central is what causes the damage.
     *
     * WHY THE REGISTRY ROW EXISTS AT ALL
     *   Without it, a business created on a tenant host is invisible to Super
     *   Admin. "Global-NEW" was created in nivasa_sonali and appeared nowhere
     *   in the registry - finding it meant querying every database in turn.
     *
     * FAILURE BEHAVIOUR
     *   Never blocks creation. If the registry write fails the business still
     *   exists and works; the warning in the log is enough to reconcile it
     *   later. A missing registry row is recoverable. A failed creation is not.
     */
    private function registerBusinessCentrally($business, array $identity): void
    {
        try {
            // Only tenant businesses need registering. A business created in
            // central already IS its registry row.
            if (empty($identity['tenant_id'])) {
                return;
            }

            if (empty($identity['global_uid'])) {
                return;
            }

            // trueCentralConnectionName(), not connectionName().
            //
            // connectionName() honours SUPERADMIN_USE_ACTIVE_CONNECTION, which
            // when set makes Super Admin work against whichever database the
            // host resolved to - a tester's own, for instance. Under that
            // switch central and tenant look like the same database, and this
            // method would skip registration silently. Observed on 14 Aug: a
            // business was created and stamped, but never registered, with no
            // warning logged.
            //
            // Whether central knows a business exists must not depend on a
            // temporary testing setting.
            $centralConnection = \Modules\Superadmin\Services\CentralContext::trueCentralConnectionName();
            $tenantConnection = $business->getConnectionName();

            // On a single-database install these are the same connection, and
            // the row we just created is already the registry row.
            if (DB::connection($centralConnection)->getDatabaseName()
                === DB::connection($tenantConnection)->getDatabaseName()) {
                return;
            }

            if (! Schema::connection($centralConnection)->hasColumn('business', 'global_uid')) {
                Log::warning('Central registry skipped: central business table has no global_uid column.', [
                    'business_id' => $business->id,
                ]);
                return;
            }

            // Idempotent. Re-running must not create a second registry row.
            $existing = DB::connection($centralConnection)
                ->table('business')
                ->where('global_uid', $identity['global_uid'])
                ->first();

            if (! empty($existing)) {
                return;
            }

            $row = [
                'global_uid' => $identity['global_uid'],
                'tenant_id' => $identity['tenant_id'],
                'name' => $business->name,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // company_number is a display reference, not an identifier - it is
            // generated from a row count, so it repeats across databases. Copied
            // only so the registry is recognisable to a human.
            if (Schema::connection($centralConnection)->hasColumn('business', 'company_number')) {
                $row['company_number'] = $business->company_number;
            }

            // currency_id is usually NOT NULL on this schema, so carry it over
            // rather than have the insert rejected.
            if (Schema::connection($centralConnection)->hasColumn('business', 'currency_id')
                && ! empty($business->currency_id)) {
                $row['currency_id'] = $business->currency_id;
            }

            if (Schema::connection($centralConnection)->hasColumn('business', 'start_date')) {
                $row['start_date'] = $business->start_date ?? now()->toDateString();
            }

            /*
             | owner_id is NOT NULL on this schema, so the registry row needs a
             | value - but the owner is a user in the TENANT database, and user
             | ids do not carry across. Copying the tenant's owner_id would
             | point this row at whichever unrelated user happens to hold that
             | id in central, which is exactly the class of error the global_uid
             | work exists to remove.
             |
             | 0 means "this business has no owner in THIS database", which is
             | true and says nothing false. The real owner is in the tenant
             | database, alongside everything else about the business.
             |
             | CONSEQUENCE, and it needs handling when Super Admin lists these
             | rows: $business->owner is null for a registry row. Code that
             | reads owner details without checking will fail on it. The
             | business card in Super Admin already falls back to the owner's
             | name when a business name is blank - that is how an unrelated
             | user's name once appeared on screen for a nameless business.
             | Registry rows always carry a name, so that particular path is
             | covered, but any new display code should treat them as rows
             | whose detail lives elsewhere.
             */
            if (Schema::connection($centralConnection)->hasColumn('business', 'owner_id')) {
                /*
                 | owner_id points at a user in the SAME database, and central
                 | enforces it:
                 |
                 |   FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
                 |
                 | So 0 is rejected - there is no user 0 - and the tenant's own
                 | owner_id would point at whichever unrelated central user
                 | happens to hold that number.
                 |
                 | The registry row is owned by Super Admin, so it points at the
                 | lowest-numbered central user, which is the super-admin account.
                 | The REAL owner of the business is in the tenant database
                 | alongside everything else about it.
                 |
                 | Note the ON DELETE CASCADE: deleting that central user would
                 | take the registry rows with it. Worth knowing before anyone
                 | tidies up central users.
                 */
                $registryOwnerId = DB::connection($centralConnection)
                    ->table('users')
                    ->orderBy('id')
                    ->value('id');

                if (empty($registryOwnerId)) {
                    Log::warning('Central registry skipped: central database has no users to own the registry row.', [
                        'business_id' => $business->id,
                    ]);
                    return;
                }

                $row['owner_id'] = (int) $registryOwnerId;
            }

            /*
             | Same reasoning for any other NOT NULL column without a default:
             | fill it from the tenant row where the value is meaningful in
             | central, and leave it out where it is not. Discovered on 14 Aug
             | when the first registry insert was rejected for owner_id.
             */
            foreach (['fy_start_month', 'accounting_method', 'default_profit_percent',
                      'time_zone', 'ref_no_prefixes', 'enable_product_expiry',
                      'expiry_type', 'on_product_expiry', 'stop_selling_before',
                      'default_unit', 'enable_editing_sp_from_purchase',
                      'enable_inline_tax', 'currency_symbol_placement'] as $carry) {
                if (isset($business->{$carry})
                    && Schema::connection($centralConnection)->hasColumn('business', $carry)) {
                    $value = $business->{$carry};

                    // Some of these are JSON-cast on the model, so the accessor
                    // returns an array. Re-encode rather than let PHP turn it
                    // into the string "Array" - the insert failed on
                    // ref_no_prefixes that way on 14 Aug.
                    if (is_array($value) || is_object($value)) {
                        $value = \json_encode($value);
                    }

                    $row[$carry] = $value;
                }
            }

            /*
             | Fill whatever else the central table insists on.
             |
             | This asks the database what it requires instead of carrying a
             | hardcoded column list. The hardcoded approach was wrong three
             | times on 14 Aug - each insert attempt revealed one more NOT NULL
             | column without a default: owner_id, then ref_no_prefixes as an
             | array, then stop_selling_before. On this schema there are nine
             | such columns, and another install could have a different set.
             |
             | Value preference: the tenant's own value where it has one, since
             | that is the truth about this business. Otherwise a harmless
             | default by type. These are display and behaviour settings that
             | belong to the tenant anyway - the registry only needs the insert
             | to succeed.
             */
            $required = DB::connection($centralConnection)
                ->table('information_schema.COLUMNS')
                ->select('COLUMN_NAME', 'DATA_TYPE')
                ->where('TABLE_SCHEMA', DB::connection($centralConnection)->getDatabaseName())
                ->where('TABLE_NAME', 'business')
                ->where('IS_NULLABLE', 'NO')
                ->whereNull('COLUMN_DEFAULT')
                ->where('EXTRA', 'not like', '%auto_increment%')
                ->get();

            foreach ($required as $column) {
                $columnName = $column->COLUMN_NAME ?? $column->column_name ?? null;
                $dataType = strtolower((string) ($column->DATA_TYPE ?? $column->data_type ?? ''));

                if ($columnName === null || array_key_exists($columnName, $row)) {
                    continue;
                }

                $value = $business->{$columnName} ?? null;

                if (is_array($value) || is_object($value)) {
                    $value = \json_encode($value);
                }

                if ($value === null) {
                    $numeric = ['int', 'bigint', 'smallint', 'tinyint', 'mediumint', 'decimal', 'float', 'double'];
                    $value = in_array($dataType, $numeric, true) ? 0 : '';
                }

                $row[$columnName] = $value;
            }

            $registryId = DB::connection($centralConnection)->table('business')->insertGetId($row);

            Log::info('Business registered centrally', [
                'tenant_business_id' => $business->id,
                'tenant_id' => $identity['tenant_id'],
                'global_uid' => $identity['global_uid'],
                'central_business_id' => $registryId,
                'central_database' => DB::connection($centralConnection)->getDatabaseName(),
            ]);
        } catch (\Throwable $e) {
            // The business exists and works. Only its registry entry is
            // missing, and that can be reconciled later.
            Log::warning('Business created but could not be registered centrally.', [
                'business_id' => $business->id ?? null,
                'tenant_id' => $identity['tenant_id'] ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Validate for duplicate username, email, and mobile number
            $validator = Validator::make($request->all(), [
                'username' => 'required|unique:users,username',
                'email'    => 'nullable|email|unique:users,email',
                'mobile'   => 'required|unique:business_locations,mobile|unique:users,contact_number',
            ], [
                'username.unique' => 'Username is already there. Please select a different username.',
                'email.unique'    => 'Email ID is already there. Please select a different Email ID.',
                'mobile.unique'   => 'Mobile Number is already there. Please select a different Mobile Number.',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();
            //Create owner.
            $owner_details             = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password']);
            $owner_details['language'] = env('APP_LOCALE');

            $user = User::create_user($owner_details);

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'tax_label_1', 'tax_number_1', 'tax_label_2', 'tax_number_2', 'time_zone', 'accounting_method', 'fy_start_month', 'business_type_id']);

            $business_location = $request->only(['name', 'country', 'state', 'city', 'zip_code', 'landmark', 'website', 'mobile', 'alternate_number']);

            $business_details['business_categories'] = ! empty($request->business_categories) ? json_encode($request->business_categories) : json_encode([]);

            $business_details['show_for_customers'] = ! empty($request->show_for_customers) ? $request->show_for_customers : 0;
            $business_details['common_settings'] = [];

            if ($request->boolean('is_my_auto')) {
                $business_details['common_settings']['is_my_auto'] = 1;
                $business_details['show_for_customers'] = 0;
                $business_details['business_type_id'] = null;
                $business_location['website'] = null;
                $owner_details['email'] = null;

                if (empty($business_details['currency_id'])) {
                    $business_details['currency_id'] = Currency::query()->value('id');
                }
            }
            //Create the business
            $business_details['owner_id'] = $user->id;
            $business_details['owner_id'] = $user->id;
            if (! empty($business_details['start_date'])) {
                $business_details['start_date'] = $this->businessUtil->uf_date($business_details['start_date']);
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            $business = $this->businessUtil->createNewBusiness($business_details);
            $this->stampBusinessIdentity($business);

            //Update user with business id
            $user->business_id = $business->id;
            $user->save();

            //add default account and account types for business
            app('App\Http\Controllers\BusinessController')->addAccounts($business->id);

            // add Default Product Categories
            $defaultProducts = DefaultProductCategory::all();
            foreach ($defaultProducts as $value) {
                $product_array = [
                    'name'                            => $value->name,
                    'business_id'                     => $business->id,
                    'short_code'                      => $value->short_code,
                    'parent_id'                       => $value->parent_id,
                    'category_type'                   => $value->category_type,
                    'add_related_account'             => $value->add_related_account,
                    "cogs_account_id"                 => $value->cogs_account_id,
                    "sales_income_account_id"         => $value->sales_income_account_id,
                    "weight_excess_loss_applicable"   => $value->weight_excess_loss_applicable,
                    "weight_loss_expense_account_id"  => $value->weight_loss_expense_account_id,
                    "weight_excess_income_account_id" => $value->weight_excess_income_account_id,
                    "created_by"                      => $value->created_by,
                    "description"                     => $value->description,
                    "remaining_stock_adjusts"         => $value->remaining_stock_adjusts,
                    "price_increment_acc"             => $value->price_increment_acc,
                    "price_reduction_acc"             => $value->price_reduction_acc,
                    "nic"                             => $value->nic,
                ];
                Category::create($product_array);
            }

            // add Default Expense Categories
            $defaultExpense = DefaultExpenseCategory::all();
            foreach ($defaultExpense as $value) {
                $expense_array = [
                    'name'            => $value->name,
                    'business_id'     => $business->id,
                    'code'            => $value->short_code,
                    'parent_id'       => $value->parent_id,
                    'expense_account' => $value->expense_account,
                    'is_sub_category' => $value->is_sub_category,
                    'payee_id'        => $value->payee_id,
                ];
                ExpenseCategory::create($expense_array);
            }

            //add Account Numbers
            $business_id     = request()->session()->get('business.id');
            $account_numbers = AccountNumber::where('business_id', $business_id)->get();
            foreach ($account_numbers as $accountNumber) {
                $data = [
                    'business_id'    => $business->id, // Assuming $newBusiness is the business where you want to copy the account numbers
                    'account_type'   => $accountNumber->account_type,
                    'prefix'         => $accountNumber->prefix,
                    'account_number' => $accountNumber->account_number,
                ];

                AccountNumber::create($data);
            }
            //add petro fuel category for business
            $this->businessUtil->addPetroDefaults($business->id, $user->id);

            $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);
            $new_location = $this->businessUtil->addLocation($business->id, $business_location);

            //set defualt number of pumps for location
            ModulePermissionLocation::create(['business_id' => $business->id, 'module_name' => 'number_of_pumps', 'locations' => [$new_location->id => '12']]);

            //create new permission with the new location
            Permission::create(['name' => 'location.' . $new_location->id]);

            DB::commit();

            //Module function to be called after after business is created
            if (config('app.env') != 'demo') {
                $this->moduleUtil->getModuleData('after_business_created', ['business' => $business]);
            }

            $output = [
                'success' => 1,
                'msg'     => __('business.business_created_succesfully'),
            ];

            return redirect()
                ->action('\Modules\Superadmin\Http\Controllers\BusinessController@index')
                ->with('status', $output);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];

            return back()->with('status', $output)->withInput();
        }
    }
    /**
     * Store a newly created hospital resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function hospital_register(Request $request)
    {
        $startingHospitalPrefix = System::getProperty('hospital_prefix');
        try {
            DB::beginTransaction();

            //Create owner.
            $owner_details             = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password']);
            $owner_details['language'] = env('APP_LOCALE');

            $user = User::create_user($owner_details);

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'tax_label_1', 'tax_number_1', 'tax_label_2', 'tax_number_2', 'time_zone', 'accounting_method', 'fy_start_month']);

            $business_location = $request->only(['name', 'country', 'state', 'city', 'zip_code', 'landmark', 'website', 'mobile', 'alternate_number']);

            //adding hospital business name prefix
            $business_details['name']        = $startingHospitalPrefix . '' . $business_details['name'];
            $business_details['is_hospital'] = 1;
            //Create the business
            $business_details['owner_id'] = $user->id;
            if (! empty($business_details['start_date'])) {
                $business_details['start_date'] = $this->businessUtil->uf_date($business_details['start_date']);
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            $business = $this->businessUtil->createNewBusiness($business_details);
            $this->stampBusinessIdentity($business);

            //Update user with business id
            $user->business_id = $business->id;
            $user->save();

            //add default account and account types for business
            // app('App\Http\Controllers\BusinessController')->addAccounts($business->id);

            $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);
            $new_location = $this->businessUtil->addLocation($business->id, $business_location);

            //create new permission with the new location
            Permission::create(['name' => 'location.' . $new_location->id]);

            DB::commit();

            //Module function to be called after after business is created
            if (config('app.env') != 'demo') {
                $this->moduleUtil->getModuleData('after_business_created', ['business' => $business]);
            }

            $hospitals = Business::where('is_hospital', 1)->select('id', 'name')->get();

            $output = [
                'success'   => 1,
                'msg'       => __('patient.hospital_add_success'),
                'hospitals' => $hospitals,
            ];

            return $output;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];

            return $output;
        }
    }
    /**
     * Store a newly created pharmacy resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function pharmacy_register(Request $request)
    {
        $startingPharmacyPrefix = System::getProperty('pharmacy_prefix');
        try {
            DB::beginTransaction();

            //Create owner.
            $owner_details             = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password']);
            $owner_details['language'] = env('APP_LOCALE');

            $user = User::create_user($owner_details);

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'tax_label_1', 'tax_number_1', 'tax_label_2', 'tax_number_2', 'time_zone', 'accounting_method', 'fy_start_month']);

            $business_location = $request->only(['name', 'country', 'state', 'city', 'zip_code', 'landmark', 'website', 'mobile', 'alternate_number']);

            //adding hospital business name prefix
            $business_details['name']        = $startingPharmacyPrefix . '' . $business_details['name'];
            $business_details['is_pharmacy'] = 1;
            //Create the business
            $business_details['owner_id'] = $user->id;
            if (! empty($business_details['start_date'])) {
                $business_details['start_date'] = $this->businessUtil->uf_date($business_details['start_date']);
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            $business = $this->businessUtil->createNewBusiness($business_details);
            $this->stampBusinessIdentity($business);

            //Update user with business id
            $user->business_id = $business->id;
            $user->save();

            //add default account and account types for business
            // app('App\Http\Controllers\BusinessController')->addAccounts($business->id);

            $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);
            $new_location = $this->businessUtil->addLocation($business->id, $business_location);

            //create new permission with the new location
            Permission::create(['name' => 'location.' . $new_location->id]);

            DB::commit();

            //Module function to be called after after business is created
            if (config('app.env') != 'demo') {
                $this->moduleUtil->getModuleData('after_business_created', ['business' => $business]);
            }

            $hospitals = Business::where('is_pharmacy', 1)->select('id', 'name')->get();

            $output = [
                'success'   => 1,
                'msg'       => __('patient.pharmacy_add_success'),
                'hospitals' => $hospitals,
            ];

            return $output;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];

            return $output;
        }
    }

    /**
     * Store a newly created laboratry resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function laboratory_register(Request $request)
    {
        $startingLaboratoryPrefix = System::getProperty('laboratory_prefix');
        try {
            DB::beginTransaction();

            //Create owner.
            $owner_details             = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password']);
            $owner_details['language'] = env('APP_LOCALE');

            $user = User::create_user($owner_details);

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'tax_label_1', 'tax_number_1', 'tax_label_2', 'tax_number_2', 'time_zone', 'accounting_method', 'fy_start_month']);

            $business_location = $request->only(['name', 'country', 'state', 'city', 'zip_code', 'landmark', 'website', 'mobile', 'alternate_number']);

            //adding hospital business name prefix
            $business_details['name']          = $startingLaboratoryPrefix . '' . $business_details['name'];
            $business_details['is_laboratory'] = 1;
            //Create the business
            $business_details['owner_id'] = $user->id;
            if (! empty($business_details['start_date'])) {
                $business_details['start_date'] = $this->businessUtil->uf_date($business_details['start_date']);
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            $business = $this->businessUtil->createNewBusiness($business_details);
            $this->stampBusinessIdentity($business);

            //Update user with business id
            $user->business_id = $business->id;
            $user->save();

            //add default account and account types for business
            // app('App\Http\Controllers\BusinessController')->addAccounts($business->id);

            $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);
            $new_location = $this->businessUtil->addLocation($business->id, $business_location);

            //create new permission with the new location
            Permission::create(['name' => 'location.' . $new_location->id]);

            DB::commit();

            //Module function to be called after after business is created
            if (config('app.env') != 'demo') {
                $this->moduleUtil->getModuleData('after_business_created', ['business' => $business]);
            }

            $hospitals = Business::where('is_laboratory', 1)->select('id', 'name')->get();

            $output = [
                'success'   => 1,
                'msg'       => __('patient.laboratory_add_success'),
                'hospitals' => $hospitals,
            ];

            return $output;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];

            return $output;
        }
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($business_id)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $business = Business::with(['currency', 'locations', 'subscriptions', 'owner'])->find($business_id);

        $created_id = $business->created_by;

        $created_by = ! empty($created_id) ? User::find($created_id) : null;

        return view('superadmin::business.show')
            ->with(compact('business', 'created_by'));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('superadmin::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $notAllowed = $this->businessUtil->notAllowedInDemo();
            if (! empty($notAllowed)) {
                return $notAllowed;
            }

            //Check if logged in busines id is same as deleted business then not allowed.
            $business_id = request()->session()->get('user.business_id');
            if ($business_id == $id) {
                $output = ['success' => 0, 'msg' => __('superadmin.lang.cannot_delete_current_business')];
                return back()->with('status', $output);
            }

            DB::beginTransaction();

            //Delete related products & transactions.
            $products_id = Product::where('business_id', $id)->pluck('id')->toArray();
            if (! empty($products_id)) {
                VariationLocationDetails::whereIn('product_id', $products_id)->delete();
            }
            Transaction::where('business_id', $id)->delete();

            Business::where('id', $id)
                ->delete();

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.success')];
            return redirect()
                ->action('\Modules\Superadmin\Http\Controllers\BusinessController@index')
                ->with('status', $output);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];

            return back()->with('status', $output)->withInput();
        }
    }

    /**
     * Changes the activation status of a business.
     * @return Response
     */
    public function toggleActive(Request $request, $business_id, $is_active)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $notAllowed = $this->businessUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }

        Business::where('id', $business_id)
            ->update(['is_active' => $is_active]);

        $output = [
            'success' => 1,
            'msg'     => __('lang_v1.success'),
        ];
        return back()->with('status', $output);
    }

    /**
     * custom business package permissions
     * @return Response
     */
    /**
     * Configure ONE module for a business.
     *
     * WHY THIS EXISTS
     *
     * The Manage page posts every control as one JSON field. When MA-004 added
     * 2,054 page permissions, that payload exceeded what the request could
     * carry: it arrived truncated, json_decode failed, and saving died with
     * "The Manage form data could not be read". The page permissions were
     * removed from the form to make saving work again - which is why a Super
     * Admin can enable a MODULE but not the individual pages inside it.
     *
     * Splitting by module fixes the cause rather than the symptom. One module
     * carries 5 to 111 controls instead of 2,360, so the payload is ordinary
     * and needs no JSON packing at all.
     *
     * Measured on this installation before the split:
     *   ~306 module toggles on the form, ~2,360 with page permissions
     *   40 second save - 38s of it PHP building an 8,721-entry array
     *   292 requests, 25 MB per page load
     */
    /**
     * List every module for a business, searchable by module OR page name.
     *
     * WHY THE SEARCH IS CLIENT SIDE
     *
     * There are 129 modules and roughly 1,900 pages and tabs. Searching on the
     * server would mean a request per keystroke, and a page load on this
     * installation costs 2 to 3 seconds before anything renders - most of it
     * Laravel rebuilding its configuration, which affects every page equally.
     *
     * The whole index is about 120 KB of names. Sent once and filtered in the
     * browser, typing is instant.
     *
     * WHAT IS SENT
     *
     * Only what the search needs: module key, title, item count, and each
     * item's label. No permission state, no business data.
     */
    /**
     * Save ONE module's permissions for a business.
     *
     * WHY THE READ-MODIFY-WRITE UNDER A LOCK
     *
     * package_details is a single JSON column holding roughly 8,700 entries
     * for every module at once. Writing the whole column from this page would
     * erase every other module's settings.
     *
     * So: lock the row, read what is there, change only the keys this module
     * owns, write it back. Two people saving different modules at the same
     * moment cannot overwrite each other.
     *
     * This is the difference from the existing Manage page, which rebuilds the
     * entire column from one enormous form. That is what made a single save
     * disable Petro, Contacts and Realize Cheque together on 12 August, and
     * what reset enable_petro_module three times in two days - each time
     * breaking the pumper login for a live business.
     */
    /**
     * Manage New sections plus legacy controls that are still operationally
     * significant but are not route-backed pages.
     *
     * IS2089 deliberately reuses the OLD Manage permission keys so runtime
     * checks continue reading the same data. No duplicate permission names.
     */
    private function manageNewPermissionSections(): array
    {
        $sections = \App\Services\AutomaticModuleRegistry::manageSections();

        $pumpPermissions = [
            'receive_pump' => 'Receive Pump',
            'payments' => 'Payments',
            'other_sales' => 'Other Sales',
            'list_other_sales' => 'List Other Sales',
            'day_entries' => 'Day Entries',
            'close_pumps' => 'Close Pumps',
            'close_shift' => 'Close Shift',
            'payment_summary' => 'Payment Summary',
            'enter_current_meter' => 'Enter Current Meter',
            'unload_stock' => 'Unload Stock',
            'unload_stock_details' => 'Unload Stock Details',
            'meters_with_payments' => 'Meters with Payments',
        ];

        foreach ($sections as &$section) {
            $key = \App\Services\AutomaticModuleRegistry::normalizeKey((string) ($section['module_key'] ?? ''));

            /*
             | IS2194: expose the existing Product/Pumper Dashboard permission
             | on Super Admin > All Businesses > Manage New > Products New.
             |
             | ProductsNew intentionally reuses the legacy package-details key
             | `products_pumper_dashboard_default` because the Product New form
             | and the old Manage page already read that exact key.  The automatic
             | registry only keeps declared permission keys beginning with the
             | standalone module key (`products_new_...` / `productsnew_...`), so
             | this legacy key is filtered out before Manage New renders.
             |
             | Add it here, where Manage New already bridges other operational
             | legacy permissions.  Reusing the same key avoids duplicate settings
             | and makes Add/Edit Product immediately follow this switch.
             */
            /*
             | IS2353: Purchase must expose a stable, curated page catalogue.
             |
             | Automatic route discovery has changed several times as the standalone
             | Purchase module evolved, which caused unrelated endpoints to appear in
             | Manage Page New and made sidebar filtering inconsistent.  The business
             | requirement is explicit, so replace only the Purchase page/tab items
             | with these navigable screens while keeping the module parent metadata.
             | The permission keys are deliberately stable and route paths include the
             | current purchase-module-live prefix plus legacy-friendly alternatives.
             */
            if ($key === 'purchase') {
                $section['title'] = 'Purchase Module';
                $section['items'] = array_values(array_filter((array) ($section['items'] ?? []), static function ($item) {
                    return strtolower((string) ($item['type'] ?? '')) === 'module';
                }));

                $purchasePages = [
                    ['purchase_dashboard', 'Dashboard', ['purchase-module-live', 'purchase-module-live/dashboard'], 'Dashboard'],
                    ['purchase_entries_list', 'List Purchase Entries', ['purchase-module-live/entries', 'purchase/entries'], 'Purchase Entries'],
                    ['purchase_entries_add', 'Add Purchase Entry', ['purchase-module-live/entries/create', 'purchase/entries/create'], 'Purchase Entries'],
                    ['purchase_orders_list', 'List Purchase Orders', ['purchase-module-live/orders', 'purchase/orders'], 'Purchase Orders'],
                    ['purchase_orders_add', 'Add Purchase Order', ['purchase-module-live/orders/create', 'purchase/orders/create'], 'Purchase Orders'],
                    ['purchase_returns_list', 'List Purchase Returns', ['purchase-module-live/returns', 'purchase/returns'], 'Purchase Returns'],
                    ['purchase_returns_add', 'Add Purchase Return', ['purchase-module-live/returns/create', 'purchase/returns/create'], 'Purchase Returns'],
                    ['purchase_bills_list', 'List Purchase Bills', ['purchase-module-live/bills', 'purchase/bills'], 'Purchase Bills'],
                    ['purchase_bills_add', 'Add Purchase Bill', ['purchase-module-live/bills/create', 'purchase/bills/create'], 'Purchase Bills'],
                    ['purchase_supplier_payments_list', 'List Supplier Payments', ['purchase-module-live/supplier-payments', 'purchase/supplier-payments'], 'Supplier Payments'],
                    ['purchase_supplier_payments_add', 'Add Supplier Payment', ['purchase-module-live/supplier-payments/create', 'purchase/supplier-payments/create'], 'Supplier Payments'],
                    ['purchase_reports_dashboard', 'Reports Dashboard', ['purchase-module-live/reports', 'purchase/reports'], 'Purchase Reports'],
                    ['purchase_report_register', 'Purchase Register', ['purchase-module-live/reports/purchase-register', 'purchase/reports/purchase-register'], 'Purchase Reports'],
                    ['purchase_report_payment', 'Purchase Payment Report', ['purchase-module-live/reports/purchase-payment', 'purchase/reports/purchase-payment'], 'Purchase Reports'],
                    ['purchase_report_product_purchase', 'Product Purchase Report', ['purchase-module-live/reports/product-purchase', 'purchase/reports/product-purchase'], 'Purchase Reports'],
                    ['purchase_report_purchase_sale', 'Purchase & Sale Report', ['purchase-module-live/reports/purchase-sale', 'purchase/reports/purchase-sale'], 'Purchase Reports'],
                    ['purchase_report_stock_purchase_sale', 'Stock Purchase/Sale Report', ['purchase-module-live/reports/stock-purchase-sale', 'purchase/reports/stock-purchase-sale'], 'Purchase Reports'],
                    ['purchase_report_supplier_outstanding', 'Supplier Outstanding', ['purchase-module-live/reports/supplier-outstanding', 'purchase/reports/supplier-outstanding'], 'Purchase Reports'],
                    ['purchase_settings_general', 'General Settings', ['purchase-module-live/settings', 'purchase-module-live/settings/general', 'purchase/settings'], 'Purchase Settings'],
                    ['purchase_settings_numbering', 'Numbering Settings', ['purchase-module-live/settings/numbering', 'purchase/settings/numbering'], 'Purchase Settings'],
                    ['purchase_settings_approval', 'Approval Settings', ['purchase-module-live/settings/approval', 'purchase/settings/approval'], 'Purchase Settings'],
                    ['purchase_settings_tax', 'Tax Settings', ['purchase-module-live/settings/tax', 'purchase/settings/tax'], 'Purchase Settings'],
                    ['purchase_settings_supplier', 'Supplier Settings', ['purchase-module-live/settings/supplier', 'purchase/settings/supplier'], 'Purchase Settings'],
                ];

                foreach ($purchasePages as [$pageKey, $label, $paths, $group]) {
                    $section['items'][] = [
                        'key' => $pageKey,
                        'label' => $label,
                        'type' => 'page',
                        'source' => 'is2353_purchase_pages',
                        'route_paths' => $paths,
                        'selectors' => [],
                        'group' => $group,
                    ];
                }
            }

            if ($key === 'products_new') {
                $productPumperKey = 'products_pumper_dashboard_default';
                $existingProductKeys = [];
                foreach ((array) ($section['items'] ?? []) as $item) {
                    if (! empty($item['key'])) {
                        $existingProductKeys[\App\Services\AutomaticModuleRegistry::normalizeKey(
                            (string) $item['key']
                        )] = true;
                    }
                }

                if (! isset($existingProductKeys[$productPumperKey])) {
                    $section['items'][] = [
                        'key' => $productPumperKey,
                        'label' => 'Show in Pumper Dashboard',
                        'type' => 'permission',
                        'source' => 'legacy_manage',
                        'default_enabled' => false,
                    ];
                }
            }

            if ($key !== 'pumper_dashboard') {
                continue;
            }

            $section['title'] = 'Pump Operator Dashboard';
            $existing = [];
            foreach ((array) ($section['items'] ?? []) as $item) {
                if (! empty($item['key'])) {
                    $existing[(string) $item['key']] = true;
                }
            }
            foreach ($pumpPermissions as $permissionKey => $label) {
                $shadowKey = 'pump_operator_permission_' . $permissionKey;
                if (! isset($existing[$shadowKey])) {
                    $section['items'][] = [
                        'key' => $shadowKey,
                        'label' => $label,
                        'type' => 'permission',
                        'source' => 'legacy_manage',
                        'default_enabled' => false,
                    ];
                }
            }
        }
        unset($section);

        $otherItems = [
            ['key' => 'enable_crm', 'label' => 'Enable CRM'],
            ['key' => 'catalogue_qr', 'label' => 'Catalogue QR'],
            ['key' => 'enable_sale_cmsn_agent', 'label' => 'Enable Sale commission agent'],
            ['key' => 'monthly_total_sales_volumn', 'label' => 'Monthly Total Sales Volume'],
            ['key' => 'customer_order_own_customer', 'label' => 'Enable customer order for own customers'],
            ['key' => 'customer_settings', 'label' => 'Customer Settings'],
            ['key' => 'customer_order_general_customer', 'label' => 'Enable customer order for general customers'],
            ['key' => 'customer_to_directly_in_panel', 'label' => 'Customer to pay directly in customer panel'],
            ['key' => 'member_registration', 'label' => 'Member'],
            ['key' => 'enable_separate_customer_statement_no', 'label' => 'Enable Separate Customer Statements Numbers'],
            ['key' => 'edit_customer_statement', 'label' => 'Edit Customer Statement'],
            ['key' => 'dashboard_logistics', 'label' => 'Dashboard Logistics'],
            ['key' => 'home_dashboard', 'label' => 'Home Dashboard'],
            ['key' => 'tables', 'label' => 'Tables'],
            ['key' => 'type_of_service', 'label' => 'Type of service'],
            ['key' => 'expenses', 'label' => 'Expense'],
            ['key' => 'modifiers', 'label' => 'Modifiers'],
            ['key' => 'kitchen', 'label' => 'Kitchen'],
            ['key' => 'cache_clear', 'label' => 'Cache Clear'],
            ['key' => 'customer_interest_deduct_option', 'label' => 'Customer Interest Deduct Option'],
            ['key' => 'upload_images', 'label' => 'Upload Images'],
            ['key' => 'dsr_module', 'label' => 'DSR Module'],
            ['key' => 'discount_module', 'label' => 'Discount Module'],
            ['key' => 'tpos_module', 'label' => 'TPOS Module'],
            ['key' => 'stock_conversion_module', 'label' => 'Stock Conversion Module'],
            ['key' => 'list_credit_sales_page', 'label' => 'List Credit sale page'],
            ['key' => 'docmanagement_module', 'label' => 'Doc Management Module'],
            ['key' => 'duplicate_slip_numbers', 'label' => 'Do not Allow Duplicate Slip Numbers'],
            ['key' => 'development', 'label' => 'Development'],
        ];
        foreach ($otherItems as &$item) {
            $item['type'] = 'permission';
            $item['source'] = 'legacy_manage';
            $item['default_enabled'] = false;
        }
        unset($item);
        $otherItems[] = ['key' => 'doc_monthly_limit', 'label' => 'Documents per month', 'type' => 'setting', 'input_type' => 'number', 'min' => 1];
        $otherItems[] = ['key' => 'doc_valid_from', 'label' => 'Valid from', 'type' => 'setting', 'input_type' => 'date'];
        $otherItems[] = ['key' => 'doc_valid_to', 'label' => 'Valid till', 'type' => 'setting', 'input_type' => 'date'];

        $sections[] = [
            'module_key' => 'other_permissions',
            'title' => 'Other Permissions',
            'folder' => 'Superadmin',
            'items' => $otherItems,
        ];

        /*
         | IS2102: Payment Options as its own section.
         |
         | It was rendered inside Other Permissions, where users could not find
         | it - Other Permissions holds dozens of unrelated toggles and the
         | payment tables were below all of them.
         |
         | No items: the section renders a custom partial rather than a list of
         | checkboxes, so the item grid stays empty and manage_module.blade.php
         | includes the partial when this key is active.
        */
        $sections[] = [
            'module_key' => 'payment_options',
            'title' => 'Payment Options',
            'folder' => 'Superadmin',
            'items' => [],
        ];

        return $sections;
    }

    /**
     * Level 1 parent ownership for Manage Page New.
     *
     * A real sidebar module is controlled only by Super Admin > Manage Side Bar.
     * Pseudo sections such as Other Permissions, Payment Options and Banners are
     * not sidebar parents and therefore remain directly editable here.
     */
    private function manageNewParentControlled(string $moduleKey): bool
    {
        $moduleKey = \App\Services\AutomaticModuleRegistry::normalizeKey($moduleKey);

        return \App\Services\AutomaticModuleRegistry::findSidebar($moduleKey) !== null
            || array_key_exists($moduleKey, \App\Utils\SidebarPermissionUtil::coreSidebarDefinitions());
    }

    private function manageNewParentEnabled(string $moduleKey, int $businessId): bool
    {
        return ! $this->manageNewParentControlled($moduleKey)
            || \App\Utils\SidebarPermissionUtil::isManageSidebarEnabled($moduleKey, $businessId);
    }

    public function saveModulePermissions($id, $moduleKey, Request $request)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $id = (int) $id;
        $moduleKey = \App\Services\AutomaticModuleRegistry::normalizeKey((string) $moduleKey);

        try {
            $section = null;
            foreach ($this->manageNewPermissionSections() as $candidate) {
                if (\App\Services\AutomaticModuleRegistry::normalizeKey(
                        (string) ($candidate['module_key'] ?? '')
                    ) === $moduleKey) {
                    $section = $candidate;
                    break;
                }
            }

            if ($section === null) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => 'Module not found: ' . $moduleKey,
                ]);
            }

            // FINAL HIERARCHY: Level 1 belongs exclusively to Manage Side Bar.
            // When that parent is off, do not touch any Level-2 child values.
            // This preserves the saved page/tab choices for restoration when the
            // parent is enabled again. Pseudo sections have no sidebar parent.
            if ($this->manageNewParentControlled($moduleKey)
                && ! $this->manageNewParentEnabled($moduleKey, $id)) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => 'This parent module is disabled in Manage Side Bar. Enable it there before changing second-level permissions. Existing child settings were preserved.',
                ]);
            }

            /*
             | The keys this module owns, and ONLY these. Anything not in this
             | list is left exactly as it was found.
             */
            $ownedKeys = [];
            foreach ((array) ($section['items'] ?? []) as $item) {
                // Parent module switches are Level 1 and are deliberately NOT
                // owned by Manage Page New. Settings are saved separately below.
                if (in_array(($item['type'] ?? ''), ['module', 'setting'], true)) {
                    continue;
                }
                $key = \App\Services\AutomaticModuleRegistry::normalizeKey(
                    (string) ($item['key'] ?? '')
                );
                if ($key !== '') {
                    $ownedKeys[$key] = true;
                }
            }

            $submittedSettings = (array) $request->input('settings', []);

            /*
             | Payment Options owns no permissions and no pages - its data is
             | the method-to-account mapping, posted as default_payment_accounts
             | and saved further down this method.
             |
             | Without this the guard below refused it as "no permissions or
             | settings to save", and the save returned long before reaching the
             | code that handles the mapping.
            */
            $hasPaymentAccounts = ! empty($request->input('default_payment_accounts'));

            if ($ownedKeys === [] && $submittedSettings === [] && ! $hasPaymentAccounts) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => 'This module has no permissions or settings to save.',
                ]);
            }

            /*
             | PAYMENT-DEFAULTS-PLUG-AND-PLAY-V3
             |
             | Payment Options is operational location data, not a subscription
             | permission. Save it directly to the selected business's real
             | tenant database and return before the permission transaction.
             */
            if ($moduleKey === 'payment_options') {
                $registryBusiness = \Modules\Superadmin\Services\CentralContext::findBusinessOrFail($id);
                $locationsWritten = app(\Modules\Superadmin\Services\PaymentMethodDefaultsService::class)
                    ->saveBusinessOptions(
                        $registryBusiness,
                        (array) $request->input('default_payment_accounts', [])
                    );

                return redirect()
                    ->action(
                        '\\Modules\Superadmin\Http\Controllers\BusinessController@manageModule',
                        [$id, $moduleKey]
                    )
                    ->with('status', [
                        'success' => 1,
                        'msg' => 'Payment Options saved. ' . $locationsWritten . ' location(s) updated.',
                    ]);
            }

            // What the form actually ticked. An unticked checkbox is simply
            // absent from the request, which is why the owned-key list above
            // is needed to know what to set to 0.
            $submitted = (array) $request->input('permissions', []);
            $submitted = array_fill_keys(
                array_map(
                    [\App\Services\AutomaticModuleRegistry::class, 'normalizeKey'],
                    array_keys($submitted)
                ),
                true
            );

            /*
             | trueCentralConnectionName(), not connectionName().
             |
             | SidebarPermissionUtil::activeSubscriptionPackageDetails() reads
             | permissions from the CENTRAL database - deliberately, so that
             | Super Admin can save on the central host while users read the
             | sidebar on tenant hosts.
             |
             | connectionName() honours SUPERADMIN_USE_ACTIVE_CONNECTION, which
             | is on for the testers. On a tenant host that resolved to the
             | tenant database, so the save wrote to nivasa_sonali while every
             | permission check read nivasa_base. The save worked, the log
             | confirmed it, the data was correct - in a database nothing reads.
             */
            $connection = \Modules\Superadmin\Services\CentralContext::trueCentralConnectionName();
            $switchedOff = [];
            $written = 0;
            $updatedPackageDetails = [];
            $updatedSubscriptionId = 0;

            DB::connection($connection)->transaction(function () use (
                $connection, $id, $ownedKeys, $submitted, $moduleKey, &$switchedOff, &$written,
                &$updatedPackageDetails, &$updatedSubscriptionId
            ) {
                /*
                 | The SAME row activeSubscriptionPackageDetails() will read.
                 |
                 | A business can have several approved subscriptions - business
                 | 3 in central has eight. That method takes the one whose dates
                 | span today, ordered by end_date descending, so writing to the
                 | newest by id would put permissions in a row nothing reads.
                 */
                $today = date('Y-m-d');
                $row = DB::connection($connection)
                    ->table('subscriptions')
                    ->where('business_id', $id)
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', $today)
                    ->where(function ($query) use ($today) {
                        $query->whereDate('end_date', '>=', $today)
                            ->orWhereNull('end_date');
                    })
                    ->whereNull('deleted_at')
                    ->orderByDesc('end_date')
                    ->orderByDesc('start_date')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if (empty($row)) {
                    throw new \RuntimeException(
                        'This business has no active subscription in the central database, so there is nowhere to store permissions. Add one first.'
                    );
                }

                $details = [];
                if (! empty($row->package_details)) {
                    $decoded = \json_decode((string) $row->package_details, true);
                    $details = is_array($decoded) ? $decoded : [];
                }

                foreach (array_keys($ownedKeys) as $key) {
                    $wasOn = ! array_key_exists($key, $details)
                        || in_array(
                            is_string($details[$key]) ? strtolower(trim($details[$key])) : $details[$key],
                            [1, '1', true, 'true', 'yes', 'on', 'enabled'],
                            true
                        );

                    $nowOn = isset($submitted[$key]);

                    if ($wasOn && ! $nowOn) {
                        $switchedOff[] = $key;
                    }

                    $details[$key] = $nowOn ? 1 : 0;
                    $written++;
                }

                // IS2089: Manage New exposes the same Pump Operator Dashboard
                // permissions as the old Manage page. Keep the old nested storage
                // structure because Pumper Dashboard runtime checks already use it.
                if ($moduleKey === 'pumper_dashboard') {
                    $legacyKeys = [
                        'receive_pump', 'payments', 'other_sales', 'list_other_sales',
                        'day_entries', 'close_pumps', 'close_shift', 'payment_summary',
                        'enter_current_meter', 'unload_stock', 'unload_stock_details',
                        'meters_with_payments',
                    ];
                    if (! isset($details['module_permission']) || ! is_array($details['module_permission'])) {
                        $details['module_permission'] = [];
                    }
                    $details['module_permission']['pump_operator_module'] = [];
                    foreach ($legacyKeys as $legacyKey) {
                        $shadowKey = 'pump_operator_permission_' . $legacyKey;
                        if (isset($submitted[$shadowKey])) {
                            $details['module_permission']['pump_operator_module'][$legacyKey] = 1;
                        }
                    }
                }

                DB::connection($connection)
                    ->table('subscriptions')
                    ->where('id', $row->id)
                    ->update(['package_details' => \json_encode($details)]);

                // Capture the exact active central row Manage New updated so
                // the tenant copy can be synchronised after the transaction.
                $updatedPackageDetails = $details;
                $updatedSubscriptionId = (int) $row->id;
            });

            /*
             |------------------------------------------------------------------
             | IS2101: Payment Options.
             |------------------------------------------------------------------
             | Ported from the old Manage page. The posted shape and the stored
             | JSON are deliberately identical, because the ten screens listed in
             | IS2101 already read business_locations.default_payment_accounts -
             | Purchase entry and payments, Expenses New, Customer register and
             | bulk payment, Supplier pay due, and the payment reports.
             |
             | IS2318: the submitted rows are authoritative for the location.
             | Removed custom methods must be dropped instead of merged back from
             | the previous JSON, otherwise payment dropdowns keep showing them.
             |
             | Rows whose name is blank are skipped: the Add button creates an
             | empty row, and a user who adds one then changes their mind should
             | not have an unnamed method saved.
             */
            if (!empty($request->default_payment_accounts) && is_array($request->default_payment_accounts)) {
                foreach ($request->default_payment_accounts as $locationId => $posted) {
                    if (!is_array($posted) || empty($posted['name']) || !is_array($posted['name'])) {
                        continue;
                    }

                    $businessLocation = BusinessLocation::where('business_id', $id)
                        ->where('id', $locationId)
                        ->first();

                    if (empty($businessLocation)) {
                        continue;   // not this business's location - ignore it
                    }

                    // IS2318: the form contains the complete location payment
                    // list. Treat it as authoritative so removed custom methods
                    // do not survive in default_payment_accounts.
                    $payments = [];
                    foreach ($posted['name'] as $k => $methodName) {
                        $methodName = trim((string) $methodName);
                        if ($methodName === '') {
                            continue;
                        }

                        $payments[$methodName] = [
                            'is_enabled'                 => (int) ($posted['is_enabled'][$k] ?? 0),
                            'is_purchase_enabled'        => (int) ($posted['is_purchase_enabled'][$k] ?? 0),
                            'is_sale_enabled'            => (int) ($posted['is_sale_enabled'][$k] ?? 0),
                            'is_expense_enabled'         => (int) ($posted['is_expense_enabled'][$k] ?? 0),
                            'is_purchase_return_enabled' => (int) ($posted['is_purchase_return_enabled'][$k] ?? 0),
                            'is_sale_return_enabled'     => (int) ($posted['is_sale_return_enabled'][$k] ?? 0),
                            'is_custom'                  => (int) ($posted['is_custom'][$k] ?? 0),
                            'account'                    => $posted['account'][$k] ?? null,
                        ];
                    }

                    if ($payments === []) {
                        continue;
                    }

                    BusinessLocation::where('id', $businessLocation->id)->update([
                        'default_payment_accounts' => json_encode($payments),
                    ]);
                }
            }

            // IS2089: the old Manage control also synchronised the business-level
            // Product flag to products.show_in_pumper_dashboard. Preserve that behaviour
            // for Manage New. This is intentionally isolated from every payment/shift path.
            if ($moduleKey === 'products_new' && array_key_exists('products_pumper_dashboard_default', $ownedKeys)) {
                $show = isset($submitted['products_pumper_dashboard_default']) ? 1 : 0;
                try {
                    $product = new \App\Product();
                    $schema = $product->getConnection()->getSchemaBuilder();
                    if ($schema->hasTable($product->getTable()) && $schema->hasColumn($product->getTable(), 'show_in_pumper_dashboard')) {
                        /*
                         | The mass update is deliberately gone.
                         |
                         | This set show_in_pumper_dashboard on EVERY product in
                         | the business whenever the permission was toggled,
                         | discarding whatever had been chosen per product.
                         |
                         | The permission is a display gate: it decides whether
                         | the field appears on the product form. Which products
                         | show in the pumper dashboard is a per-product choice,
                         | and now that the field exists those choices are real
                         | and worth keeping.
                        */
                        // \App\Product::where('business_id', $id)->update(['show_in_pumper_dashboard' => $show]);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Manage New could not sync show_in_pumper_dashboard.', [
                        'business_id' => $id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            // The final three Other Permissions values are business settings in
            // the old Manage page, not package permissions. Save them to the same
            // existing columns when those columns are present.
            if ($moduleKey === 'other_permissions' && $submittedSettings !== []) {
                try {
                    $businessRow = \Modules\Superadmin\Services\CentralContext::findBusinessOrFail($id);
                    $connectionName = \Modules\Superadmin\Services\CentralContext::trueCentralConnectionName();
                    $schema = DB::connection($connectionName)->getSchemaBuilder();
                    $updates = [];
                    foreach (['doc_monthly_limit', 'doc_valid_from', 'doc_valid_to'] as $column) {
                        if ($schema->hasColumn($businessRow->getTable(), $column) && array_key_exists($column, $submittedSettings)) {
                            $value = $submittedSettings[$column];
                            if ($column === 'doc_monthly_limit') {
                                $value = ($value === '' || $value === null) ? null : max(1, (int) $value);
                            } else {
                                $value = ($value === '') ? null : $value;
                            }
                            $updates[$column] = $value;
                        }
                    }
                    if ($updates !== []) {
                        DB::connection($connectionName)->table($businessRow->getTable())->where('id', $id)->update($updates);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Manage New could not save Other Permissions document limits.', [
                        'business_id' => $id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            /*
             | S717 / Manage New authority sync.
             |
             | Manage New writes the central active subscription. Tenant-side
             | runtime code (including legacy modules) can still read the tenant
             | subscription copy. Without syncing the exact row here, Manage New
             | could save correctly in central while the tenant continued using
             | an older value left by the retiring Manage page.
             |
             | Keep Manage New authoritative by copying the same package_details
             | snapshot to this business's tenant after the response. Parent
             | visibility is still controlled independently by Manage Side Bar.
             */
            if ($updatedSubscriptionId > 0 && $updatedPackageDetails !== []) {
                $this->queueManageTenantSync($id, [
                    'subscription_id' => $updatedSubscriptionId,
                    'package_details' => $updatedPackageDetails,
                ], true);
            }

            /*
             | Record anything switched off.
             |
             | Added after a Manage save disabled Petro, Contacts and Realize
             | Cheque for a live business and nothing recorded it - pumper users
             | could not log in and the cause took an afternoon to find.
             */
            if ($switchedOff !== []) {
                \Log::warning('Module permissions switched OFF', [
                    'business_id' => $id,
                    'module' => $moduleKey,
                    'switched_off' => $switchedOff,
                    'count' => count($switchedOff),
                    'by_user_id' => optional(auth()->user())->id,
                    'by_username' => optional(auth()->user())->username,
                ]);
            }

            \App\Utils\SidebarPermissionUtil::forgetBusinessCache($id);

            return redirect()
                ->action(
                    '\Modules\Superadmin\Http\Controllers\BusinessController@manageModule',
                    [$id, $moduleKey]
                )
                ->with('status', [
                    'success' => 1,
                    'msg' => ($section['title'] ?? $moduleKey) . ' saved. '
                        /*
                         | Say nothing when there are none to write.
                         |
                         | "0 permission(s) written" is accurate and reads as a
                         | warning - Payment Options owns no permissions, so
                         | zero is the right answer and not worth reporting.
                        */
                        . ($written > 0 ? $written . ' permission(s) written' : '')
                        . ($switchedOff !== [] ? ', ' . count($switchedOff) . ' switched off.' : '.'),
                ]);
        } catch (\Throwable $e) {
            \Log::error('Per-module permission save failed.', [
                'business_id' => $id,
                'module' => $moduleKey,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'Could not save: ' . $e->getMessage(),
            ]);
        }
    }

    public function manageModuleIndex($id, Request $request)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $id = (int) $id;

        // CentralContext, not Business::findOrFail(). Super Admin routes run
        // through tenant.context, so on a non-central host the default
        // connection points at a tenant database.
        $business = \Modules\Superadmin\Services\CentralContext::findBusinessOrFail($id);

        /*
         | The registry directly, not ModulePermissionService::discoverManageSections(),
         | which strips items marked source=module_pages. Those are exactly the
         | pages this screen exists to find.
         */
        $sections = $this->manageNewPermissionSections();

        /*
         | The business's current permission state, read once.
         |
         | The index previously showed only page counts, so 129 cards gave no
         | indication of which modules were switched off. Reading
         | package_details here lets each card show its state, and lets the
         | list be filtered by it.
         |
         | Same source the permission checks use, so what is shown is what is
         | enforced.
         */
        // Parent state on this index is Level 1 only. Do not infer it from
        // subscription/package flags because Manage Page New is the Level-2 editor.

        $modules = [];
        foreach ($sections as $section) {
            $key = \App\Services\AutomaticModuleRegistry::normalizeKey(
                (string) ($section['module_key'] ?? '')
            );
            if ($key === '') {
                continue;
            }

            $pages = [];
            $tabs = 0;
            foreach ((array) ($section['items'] ?? []) as $item) {
                $type = strtolower((string) ($item['type'] ?? ''));
                if ($type === 'module' || empty($item['key'])) {
                    continue;
                }
                if ($type === 'tab') {
                    $tabs++;
                }
                $pages[] = (string) ($item['label'] ?? $item['key']);
            }

            // Level 1 parent state comes only from Manage Side Bar. Pseudo
            // permission/settings sections have no parent and remain available.
            $moduleEnabled = $this->manageNewParentEnabled($key, $id);

            $modules[] = [
                'key' => $key,
                'title' => (string) ($section['title'] ?? $key),
                'count' => count($pages),
                'tabs' => $tabs,
                'enabled' => $moduleEnabled,
                // Lower-cased haystack: module title, key and every page label,
                // so one pass of indexOf() answers both levels of the search.
                'find' => strtolower(
                    ($section['title'] ?? '') . ' ' . $key . ' ' . implode(' ', $pages)
                ),
                'pages' => $pages,
            ];
        }

        usort($modules, static function ($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        $totalPages = array_sum(array_column($modules, 'count'));

        return view('superadmin::business.manage_module_index', compact(
            'business',
            'modules',
            'totalPages'
        ));
    }

    public function manageModule($id, $moduleKey, Request $request)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $id = (int) $id;
        $moduleKey = \App\Services\AutomaticModuleRegistry::normalizeKey((string) $moduleKey);

        // CentralContext, not Business::findOrFail(). Super Admin routes run
        // through tenant.context, so on a non-central host the default
        // connection points at a tenant database - which is how one business's
        // settings once landed in another's row.
        $business = \Modules\Superadmin\Services\CentralContext::findBusinessOrFail($id);

        /*
         | Read the module's section from the registry DIRECTLY.
         |
         | Deliberately not ModulePermissionService::discoverManageSections(),
         | which strips every item marked source=module_pages. That filter is
         | what keeps page permissions off the current Manage form, and it is
         | exactly what this page exists to show.
         */
        $section = null;
        foreach ($this->manageNewPermissionSections() as $candidate) {
            if (\App\Services\AutomaticModuleRegistry::normalizeKey(
                    (string) ($candidate['module_key'] ?? '')
                ) === $moduleKey) {
                $section = $candidate;
                break;
            }
        }

        if ($section === null) {
            abort(404, 'Module not found: ' . $moduleKey);
        }

        $parentControlled = $this->manageNewParentControlled($moduleKey);
        $parentEnabled = $this->manageNewParentEnabled($moduleKey, $id);

        $items = collect($section['items'] ?? [])
            ->filter(static fn ($item) => ! empty($item['key']))
            ->values();

        $moduleItems = $items->where('type', 'module')->values();
        $settingItems = $items->where('type', 'setting')->values();
        $childItems = $items->reject(static fn ($item) => in_array(($item['type'] ?? ''), ['module', 'setting'], true))->values();

        // Current state must come from the SAME active central subscription
        // row saveModulePermissions() updates. Using the newest approved row by
        // id can display a permission from a future/expired subscription while
        // the runtime correctly reads today's active subscription.
        $packageDetails = [];
        try {
            $today = date('Y-m-d');
            $subscription = \Modules\Superadmin\Entities\Subscription::on(
                \Modules\Superadmin\Services\CentralContext::trueCentralConnectionName()
            )
                ->where('business_id', $id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereDate('end_date', '>=', $today)
                        ->orWhereNull('end_date');
                })
                ->orderByDesc('end_date')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();

            if (! empty($subscription) && ! empty($subscription->package_details)) {
                $decoded = is_array($subscription->package_details)
                    ? $subscription->package_details
                    : \json_decode((string) $subscription->package_details, true);
                $packageDetails = is_array($decoded) ? $decoded : [];
            }
        } catch (\Throwable $e) {
            \Log::warning('Per-module manage page could not read package_details.', [
                'business_id' => $id,
                'module' => $moduleKey,
                'message' => $e->getMessage(),
            ]);
        }

        // Legacy Manage controls defaulted OFF when no value existed. Preserve
        // that behaviour only for the IS2089 controls; route/page permissions keep
        // Manage New's existing absent-means-enabled rule.
        foreach ((array) ($section['items'] ?? []) as $legacyItem) {
            $legacyKey = (string) ($legacyItem['key'] ?? '');
            if ($legacyKey !== '' && array_key_exists('default_enabled', $legacyItem) && ! array_key_exists($legacyKey, $packageDetails)) {
                $packageDetails[$legacyKey] = ! empty($legacyItem['default_enabled']) ? 1 : 0;
            }
        }

        if ($moduleKey === 'pumper_dashboard') {
            $nested = $packageDetails['module_permission']['pump_operator_module'] ?? [];
            $legacyKeys = [
                'receive_pump', 'payments', 'other_sales', 'list_other_sales',
                'day_entries', 'close_pumps', 'close_shift', 'payment_summary',
                'enter_current_meter', 'unload_stock', 'unload_stock_details',
                'meters_with_payments',
            ];
            foreach ($legacyKeys as $legacyKey) {
                $packageDetails['pump_operator_permission_' . $legacyKey] =
                    (is_array($nested) && array_key_exists($legacyKey, $nested)) ? 1 : 0;
            }
        }

        /*
         | A key absent from package_details counts as ENABLED.
         |
         | This matches SidebarPermissionUtil::isAutomaticPermissionEnabled(),
         | which returns true when the key is not present. Showing an absent key
         | as unticked here would make the page lie about the current state, and
         | saving it would then disable something that was working.
         */
        $isEnabled = static function (string $key) use ($packageDetails): bool {
            if (! array_key_exists($key, $packageDetails)) {
                return true;
            }
            $value = $packageDetails[$key];
            if (is_array($value) || is_object($value)) {
                return true;
            }
            $flag = is_string($value) ? strtolower(trim($value)) : $value;

            return in_array($flag, [1, '1', true, 'true', 'yes', 'on', 'enabled'], true);
        };

        /*
         | PAYMENT-DEFAULTS-PLUG-AND-PLAY-V3
         |
         | Payment Options must read the selected business's operational tenant
         | database, not apply the central registry numeric id to whichever
         | connection happens to be active. Preparing the business also fills
         | only missing system-default methods/legacy fields, preserving every
         | explicit location/user choice.
         */
        $business_locations = collect();
        $account_groups = collect();

        if ($moduleKey === 'payment_options') {
            try {
                $preparedPayments = app(\Modules\Superadmin\Services\PaymentMethodDefaultsService::class)
                    ->prepareBusinessForManage($business);
                $business_locations = $preparedPayments['locations'];
                $account_groups = $preparedPayments['account_groups'];
            } catch (\Throwable $paymentDefaultsException) {
                \Log::error('Manage New Payment Options could not prepare operational defaults.', [
                    'business_id' => $id,
                    'message' => $paymentDefaultsException->getMessage(),
                ]);

                // Safe compatibility fallback for a single-database install.
                $business_locations = BusinessLocation::where('business_id', $id)
                    ->select(['id', 'name', 'default_payment_accounts'])
                    ->orderBy('name')
                    ->get();
            }
        } else {
            $business_locations = BusinessLocation::where('business_id', $id)
                ->select(['id', 'name', 'default_payment_accounts'])
                ->orderBy('name')
                ->get();
        }

        /*
         | The switcher in the sticky bar needs every module's name, so a Super
         | Admin can move between modules without returning to the index.
         | Key, title and count only - a few KB.
         */
        $allModules = [];
        foreach ($this->manageNewPermissionSections() as $other) {
            $otherKey = \App\Services\AutomaticModuleRegistry::normalizeKey(
                (string) ($other['module_key'] ?? '')
            );
            if ($otherKey === '') {
                continue;
            }
            $children = 0;
            foreach ((array) ($other['items'] ?? []) as $otherItem) {
                if (($otherItem['type'] ?? '') !== 'module' && ! empty($otherItem['key'])) {
                    $children++;
                }
            }
            $otherLabels = [];
            foreach ((array) ($other['items'] ?? []) as $otherItem) {
                if (! empty($otherItem['label'])) {
                    $otherLabels[] = (string) $otherItem['label'];
                }
            }
            $otherTitle = (string) ($other['title'] ?? $otherKey);
            $allModules[] = [
                'key' => $otherKey,
                'title' => $otherTitle,
                'count' => $children,
                'enabled' => $this->manageNewParentEnabled($otherKey, $id),
                'find' => strtolower($otherTitle . ' ' . $otherKey . ' ' . implode(' ', $otherLabels)),
            ];
        }
        usort($allModules, static function ($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        $settingValues = [
            'doc_monthly_limit' => $business->doc_monthly_limit ?? null,
            'doc_valid_from' => $business->doc_valid_from ?? null,
            'doc_valid_to' => $business->doc_valid_to ?? null,
        ];

        /*
         | IS2101: account groups for the Payment Options section.
         |
         | Same query the old Manage page uses, so both screens offer the same
         | list. Guarded because a business with no accounting configured has
         | none, and the section must still render - the account column simply
         | shows "Please Select".
         */
        if ($moduleKey !== 'payment_options') {
            try {
                $account_groups = AccountGroup::where('business_id', $id)->pluck('name', 'id');
            } catch (\Throwable $accountGroupException) {
                $account_groups = collect();
            }
        } elseif ($account_groups->isEmpty() && $business_locations->isNotEmpty()) {
            // Compatibility fallback only when the operational service could
            // not provide the account groups above.
            try {
                $account_groups = AccountGroup::where('business_id', $id)->pluck('name', 'id');
            } catch (\Throwable $accountGroupException) {
                $account_groups = collect();
            }
        }

        return view('superadmin::business.manage_module', compact(
            'business',
            'allModules',
            'section',
            'moduleKey',
            'moduleItems',
            'settingItems',
            'settingValues',
            'childItems',
            'packageDetails',
            'isEnabled',
            'business_locations',
            'account_groups',
            'parentControlled',
            'parentEnabled'
        ));
    }

    public function manage($id, Request $request)
    {
        // dd($request);    
        $fonts = [
            "'Arial', sans-serif"        => 'Arial',
            "'Verdana', sans-serif"      => 'Verdana',
            "'Tahoma', sans-serif"       => 'Tahoma',
            "'Trebuchet MS', sans-serif" => 'Trebuchet MS',
            "'Times New Roman', serif"   => 'Times New Roman',
            "'Georgia', serif"           => 'Georgia',
            "'Calibri', serif"           => 'Calibri',
        ];

        $id = (int) $id;
        $managePerformance = $this->managePerformanceService();

        $business = Business::with('owner.setting')->findOrFail($id);
        $currencies = $managePerformance->rememberGlobal('currencies', 86400, function () {
            return $this->businessUtil->allCurrencies();
        });
        $package_manage = Package::where('only_for_business', $id)->first();
        $business_types = $managePerformance->rememberGlobal('business_types', 3600, static function () {
            return DefaultBusinessType::pluck('business_type', 'id');
        });

        $mainBusinessId = (int) $managePerformance->rememberGlobal('main_business_id', 3600, static function () {
            return (int) Business::query()->orderBy('id')->value('id');
        });

        $account_nos = $managePerformance->rememberBusiness($id, 'account_numbers', 600, static function () use ($id) {
            return AccountNumber::leftjoin('default_account_types as type', 'account_numbers.account_type', 'type.id')
                ->where('account_numbers.business_id', $id)
                ->select([
                    'account_numbers.*',
                    'type.name as type',
                ])->get();
        });

        $account_types = $managePerformance->rememberGlobal('default_account_types:' . $mainBusinessId, 600, static function () use ($mainBusinessId) {
            return DefaultAccountType::where('business_id', $mainBusinessId)
                ->whereNull('parent_account_type_id')
                ->with(['sub_types'])
                ->get();
        });
        $subscription = Subscription::active_subscription($id)
            ?? Subscription::where('business_id', $id)->orderByDesc('id')->first();
        if (!empty($subscription)) {
            $subscription->loadMissing('package');
        }

        $previous_package_data['product_count']  = 0;
        $previous_package_data['location_count'] = 0;
        $previous_package_data['vehicle_count']  = 0;
        $previous_package_data['category_count'] = 0;
        $previous_package_data['allowed_tanks']  = 0;

        //hms
        $previous_package_data['room_subscribe']      = 0;
        $previous_package_data['room_added']          = 0;
        $previous_package_data['room_could_be_added'] = 0;

        if (! empty($subscription) && empty($package_manage)) {
            // if conpmany package is not there then get the already subscribed package
            $previous_package_data['product_count']  = ! empty($subscription->package_details['product_count']) ? json_decode(json_encode($subscription->package_details['product_count']), true) : 0;
            $previous_package_data['location_count'] = ! empty($subscription->package_details['location_count']) ? json_decode(json_encode($subscription->package_details['location_count']), true) : 0;
            $previous_package_data['vehicle_count']  = ! empty($subscription->package_details['vehicle_count']) ? json_decode(json_encode($subscription->package_details['vehicle_count']), true) : 0;
            $previous_package_data['category_count'] = ! empty($subscription->package_details['category_count']) ? json_decode(json_encode($subscription->package_details['category_count']), true) : 0;
            $previous_package_data['allowed_tanks']  = ! empty($subscription->package_details['allowed_tanks']) ? json_decode(json_encode($subscription->package_details['allowed_tanks']), true) : 0;
            //hms
            $previous_package_data['room_subscribe']      = ! empty($subscription->package_details['room_subscribe']) ? json_decode(json_encode($subscription->package_details['room_subscribe']), true) : 0;
            $previous_package_data['room_added']          = ! empty($subscription->package_details['room_added']) ? json_decode(json_encode($subscription->package_details['room_added']), true) : 0;
            $previous_package_data['room_could_be_added'] = ! empty($subscription->package_details['room_could_be_added']) ? json_decode(json_encode($subscription->package_details['room_could_be_added']), true) : 0;
        }

        // $module_enable_price = !empty($package_manage->module_enable_price) ? json_decode($package_manage->module_enable_price) : []; //input values
        $module_activation_data            = [];
        $customer_credit_notification_type = [];
     
        // dd($subscription);
        if (! empty($subscription)) {
            $manage_module_enable   = ! empty($subscription->package_details) ? json_decode(json_encode($subscription->package_details), true) : [];
            $module_activation_data = ! empty($subscription->module_activation_details) ? json_decode($subscription->module_activation_details, true) : [];
            if (! is_null($subscription->customer_credit_notification_type)) {
                $customer_credit_notification_type = is_array($subscription->customer_credit_notification_type)
                    ? $subscription->customer_credit_notification_type
                    : json_decode($subscription->customer_credit_notification_type, true);
            }

            // Set default current business and database if not already set
            $currentDatabase = config('database.connections.mysql.database');
            if (empty($manage_module_enable['manage_businesses'])) {
                $manage_module_enable['manage_businesses'] = [$currentDatabase . '::' . $id];
            }
            if (empty($manage_module_enable['manage_databases'])) {
                $manage_module_enable['manage_databases'] = [$currentDatabase];
            }

            // Map pump_operator_dashboard to pump_operator_module for view compatibility
            if (isset($manage_module_enable['pump_operator_dashboard'])) {
                $manage_module_enable['pump_operator_module'] = $manage_module_enable['pump_operator_dashboard'];
            }

            // Keep Super Admin > Manage page in sync with the parent Manage Side Bar popup.
            // The Manage Side Bar popup is the master module visibility permission.
            // If a module is enabled there, its Manage page parent row must show enabled;
            // if it is disabled there, child page permissions must not make the module visible.
            $this->syncManageModuleEnableFromSidebarMaster($manage_module_enable, $business);

        } else {
            $manage_module_enable = ! empty($package_manage->manage_module_enable) ? json_decode($package_manage->manage_module_enable) : []; //checkboxes
        }
                                                                                                                                 // dd($manage_module_enable);
                                                                                                                                 // return $manage_module_enable;
        $current_values     = ! empty($package_manage->current_values) ? ($package_manage->current_values) : [];
        $sms_settings       = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
        $business_locations = $managePerformance->rememberBusiness($id, 'business_locations', 600, static function () use ($id) {
            return BusinessLocation::where('business_id', $id)->get();
        });

        // Do not scan information_schema and every tenant database during the
        // initial Manage request. Render only the already selected values; the
        // complete cross-database options are loaded on first use of that one
        // section through getManageBusinessOptions().
        $currentDatabase = (string) config('database.connections.mysql.database');
        $selectedDatabases = array_values(array_unique(array_filter(array_map(
            'strval',
            (array) ($manage_module_enable['manage_databases'] ?? [$currentDatabase])
        ))));
        if ($selectedDatabases === []) {
            $selectedDatabases = [$currentDatabase];
        }
        $all_databases = array_combine($selectedDatabases, $selectedDatabases) ?: [];

        $selectedBusinesses = array_values(array_unique(array_filter(array_map(
            'strval',
            (array) ($manage_module_enable['manage_businesses'] ?? [$currentDatabase . '::' . $id])
        ))));
        $all_businesses = [];
        foreach ($selectedBusinesses as $selectedBusiness) {
            $all_businesses[$selectedBusiness] = $selectedBusiness === $currentDatabase . '::' . $id
                ? '[' . $currentDatabase . '] ' . $business->name
                : '[' . str_replace('::', '] Business ', $selectedBusiness);
        }

        $module_permission_locations = $managePerformance->rememberGlobal('module_permission_location_list', 3600, static function () {
            return ModulePermissionLocation::getModulePermissionList();
        });

        $business_details = $business;
        $sale_import_date     = null;
        $purchase_import_date = null;
        if (! empty($business_details)) {
            $sale_import_date = ! empty($business_details->sale_import_date) ? $this->moduleUtil->format_date($business_details->sale_import_date) : null;
        }
        if (! empty($business_details)) {
            $purchase_import_date = ! empty($business_details->purchase_import_date) ? $this->moduleUtil->format_date($business_details->purchase_import_date) : null;
        }

        $module_permission_locations_value = $managePerformance->rememberBusiness($id, 'module_permission_locations', 600, static function () use ($id, $module_permission_locations) {
            $rows = ModulePermissionLocation::where('business_id', $id)
                ->whereIn('module_name', $module_permission_locations)
                ->get()
                ->keyBy('module_name');

            $values = [];
            foreach ($module_permission_locations as $module) {
                $values[$module] = $rows->get($module);
            }

            return $values;
        });

        // Extract module_permission data from package_details
        $module_permission_value = [];
        if (! empty($subscription) && ! empty($subscription->package_details)) {
            $package_details = is_array($subscription->package_details)
                ? $subscription->package_details
                : json_decode(json_encode($subscription->package_details), true);

            if (! empty($package_details['module_permission']) && is_array($package_details['module_permission'])) {
                $module_permission_value = $package_details['module_permission'];
            }
        }

        $accounts = $managePerformance->rememberBusiness($id, 'accounts', 300, static function () use ($id) {
            return Account::where('business_id', $id)
                ->select(['id', 'name', 'visible'])
                ->orderBy('name')
                ->get();
        });
        $business_common_settings = is_array($business->common_settings) ? $business->common_settings : [];
        $package_disk_size = (float) optional(optional($subscription)->package)->max_disk_size;
        $business_disk_size = isset($business_common_settings['max_disk_size'])
            ? (float) $business_common_settings['max_disk_size']
            : null;
        $effective_disk_size = $business_disk_size && $business_disk_size > 0
            ? $business_disk_size
            : $package_disk_size;


        // update default payment methods
        // foreach($business_locations as $bl){
        //     $this->updatePaymentMthds($bl);
        // }

        $account_groups = $managePerformance->rememberBusiness($id, 'account_groups', 600, static function () use ($id) {
            return AccountGroup::where('business_id', $id)->pluck('name', 'id');
        });

        /*
         | IS2339 - Payment Options on the legacy Manage page must use the
         | selected business's OPERATIONAL database, exactly like Manage New.
         |
         | The legacy page previously rendered business_locations from whichever
         | connection happened to be active. In a central + tenant setup that
         | meant a newly-added method could be saved/displayed against the wrong
         | database and would never reach Purchase/Expenses in the tenant.
         |
         | Keep the original collections for every other Manage section; only
         | Payment Options gets the operational collections below.
         */
        $payment_business_locations = $business_locations;
        $payment_account_groups = $account_groups;
        try {
            $paymentRegistryBusiness = \Modules\Superadmin\Services\CentralContext::findBusinessOrFail($id);
            $preparedPaymentOptions = app(\Modules\Superadmin\Services\PaymentMethodDefaultsService::class)
                ->prepareBusinessForManage($paymentRegistryBusiness);
            $payment_business_locations = $preparedPaymentOptions['locations'];
            $payment_account_groups = $preparedPaymentOptions['account_groups'];
        } catch (\Throwable $paymentPrepareException) {
            Log::warning('Legacy Manage Payment Options fell back to the current connection.', [
                'business_id' => $id,
                'message' => $paymentPrepareException->getMessage(),
            ]);
        }

        try {
            $fuel_category_id = $managePerformance->rememberGlobal('fuel_category_id', 3600, static function () {
                return \DB::table('categories')->where('name', 'Fuel')->value('id');
            });
        } catch (\Throwable $e) {
            $fuel_category_id = null;
        }

        try {
            $fuel_products = $managePerformance->rememberBusiness($id, 'fuel_products:' . (int) $fuel_category_id, 600, static function () use ($id, $fuel_category_id) {
                if (empty($fuel_category_id)) {
                    return collect();
                }
                return \DB::table('products')
                    ->where('business_id', $id)
                    ->where('category_id', $fuel_category_id)
                    ->orderBy('name')
                    ->pluck('name', 'id');
            });
        } catch (\Throwable $e) {
            $fuel_products = collect();
        }

        $common_settings = is_array($business->common_settings) ? $business->common_settings : [];
        $vat_settings = $common_settings['vat_settings'] ?? [
            'fuel_products' => [],
            'fuel_products_qty' => [],
        ];

        /**
         * CLEAN the data (very important)
         */
        $vat_settings['fuel_products'] = array_values(
            array_filter($vat_settings['fuel_products'] ?? [])
        );

        $vat_settings['fuel_products_qty'] = array_filter(
            $vat_settings['fuel_products_qty'] ?? [],
            function ($qty, $product_id) use ($vat_settings) {
                return in_array($product_id, $vat_settings['fuel_products']);
            },
            ARRAY_FILTER_USE_BOTH
        );

        // Auto-discover standalone/new module sections and their route-backed pages.
        // The registry now has its own persistent manifest, so optimize:clear
        // does not force a full scan of every module route/provider file.
        $auto_manage_sections = $managePerformance->rememberGlobal('auto_manage_sections_v2', 86400, function () {
            return $this->modulePermissionService()->discoverManageSections();
        });

        $viewData = compact(
            'all_businesses',
            'all_databases',
            'fonts',
            'account_groups',
            'payment_business_locations',
            'payment_account_groups',
            'subscription',
            'customer_credit_notification_type',
            'account_nos',
            'fuel_products',
            'account_types',
            'business_locations',
            'previous_package_data',
            'business_details',
            'accounts',
            'sale_import_date',
            'purchase_import_date',
            'module_permission_locations',
            'module_permission_locations_value',
            'module_permission_value',
            'id',
            'business',
            'currencies',
            'package_manage',
            'current_values',
            'manage_module_enable',
            'module_activation_data',
            'sms_settings',
            'business_types',
            'vat_settings',
            'effective_disk_size',
            'auto_manage_sections'
        );

        // Pre-warm both controller data and the large compiled Blade view.
        // This request is started from the business list during browser idle
        // time, making the subsequent real click avoid first-render overhead.
        if ($request->boolean('warm')) {
            view('superadmin::business.manage', $viewData)->render();
            return response()->noContent();
        }

        return view('superadmin::business.manage', $viewData);
    }

    /**
     * Load the expensive cross-database business list only when the related
     * Manage section is opened. The initial Manage page never waits for this.
     */
    public function getManageBusinessOptions(): \Illuminate\Http\JsonResponse
    {
        if (!auth()->user() || !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $options = $this->managePerformanceService()->rememberGlobal(
            'cross_database_business_options',
            300,
            function (): array {
                return $this->fetchAllBusinessesAcrossDatabases();
            }
        );

        return response()->json([
            'businesses' => (array) ($options['businesses'] ?? []),
            'databases' => array_values((array) ($options['databases'] ?? [])),
        ]);
    }

    public function getBusinessLocations(Request $request): \Illuminate\Http\JsonResponse
    {
        $compositeKeys = $request->input('business_ids', []);
        $byDatabase    = [];

        foreach ($compositeKeys as $key) {
            if (str_contains((string) $key, '::')) {
                [$db, $bizId] = explode('::', (string) $key, 2);
            } else {
                $db    = config('database.connections.mysql.database');
                $bizId = $key;
            }
            $byDatabase[$db][] = (int) $bizId;
        }

        $allLocations = collect();

        foreach ($byDatabase as $dbName => $businessIds) {
            $locations = $this->withDatabase($dbName, function () use ($dbName, $businessIds) {
                $tableCheck = DB::select(
                    'SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                    [$dbName, 'business_locations']
                );

                if (empty($tableCheck) || $tableCheck[0]->cnt === 0) {
                    return collect();
                }

                return BusinessLocation::select(
                        'business_locations.id',
                        'business_locations.name',
                        'business_locations.business_id',
                        'business.name as business_name'
                    )
                    ->join('business', 'business.id', '=', 'business_locations.business_id')
                    ->whereIn('business_locations.business_id', $businessIds)
                    ->orderBy('business.name')
                    ->orderBy('business_locations.name')
                    ->get()
                    ->map(fn($loc) => [
                        'id'   => $loc->id,
                        'text' => '[' . $dbName . '] ' . $loc->business_name . ' — ' . $loc->name,
                        'db'   => $dbName,
                        'biz'  => $loc->business_name
                    ]);
            });

            if (!empty($locations)) {
                $allLocations = $allLocations->concat($locations);
            }
        }

        return response()->json($allLocations->values());
    }

    private function fetchAllBusinessesAcrossDatabases(): array
    {
        $prefix          = env('TENANT_DATABASE_PREFIX', 'nivasa_');
        $currentDatabase = config('database.connections.mysql.database');
        $allBusinesses   = [];
        $allDatabases    = [$currentDatabase => $currentDatabase];

        foreach (Business::orderBy('name')->pluck('name', 'id') as $id => $name) {
            $allBusinesses[$currentDatabase . '::' . $id] = '[' . $currentDatabase . '] ' . $name;
        }

        if (app()->environment('testing')) {
            return [
                'businesses' => $allBusinesses,
                'databases'  => $allDatabases
            ];
        }

        $otherDbs = DB::select(
            'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE ? AND SCHEMA_NAME != ? ORDER BY SCHEMA_NAME',
            [$prefix . '%', $currentDatabase]
        );

        foreach ($otherDbs as $row) {
            $dbName     = $row->SCHEMA_NAME;
            $allDatabases[$dbName] = $dbName;
            $businesses = $this->withDatabase($dbName, function () use ($dbName) {
                $tableCheck = DB::select(
                    'SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                    [$dbName, 'business']
                );

                if (empty($tableCheck) || $tableCheck[0]->cnt === 0) {
                    return [];
                }

                return Business::orderBy('name')->pluck('name', 'id')->toArray();
            });

            foreach ((array) $businesses as $id => $name) {
                $allBusinesses[$dbName . '::' . $id] = '[' . $dbName . '] ' . $name;
            }
        }

        return [
            'businesses' => $allBusinesses,
            'databases'  => $allDatabases
        ];
    }

    private function withDatabase(string $database, callable $callback): mixed
    {
        $original = config('database.connections.mysql.database');

        try {
            DB::purge('mysql');
            config(['database.connections.mysql.database' => $database]);
            DB::reconnect('mysql');

            return $callback();
        } catch (\Exception $e) {
            \Log::warning("Cross-DB query failed [{$database}]: " . $e->getMessage());

            return null;
        } finally {
            DB::purge('mysql');
            config(['database.connections.mysql.database' => $original]);
            DB::reconnect('mysql');
        }
    }

    public function extractLastInteger($text)
    {

        if (preg_match('/\d+$/', $text, $matches)) {

            return intval($matches[0]);

        } else {

            return 0;

        }

    }

    private function updatePaymentMthds($location)
    {

        // insert own cards if not available
        if (empty($this->accountGrpID('Own Cards', $location->business_id))) {

            $acc_type    = AccountType::where('business_id', $location->business_id)->where('name', 'Equity')->first()->id ?? null;
            $defacc_type = DefaultAccountGroup::where('name', 'Own Cards')->first()->id ?? null;

            AccountGroup::create([
                'business_id'              => $location->business_id,
                'name'                     => 'Own Cards',
                'account_type_id'          => $acc_type,
                'reg_cheque'               => 'Y',
                'default_account_group_id' => $defacc_type,

            ]);
        }

        $pmts         = ! empty($location->default_payment_accounts) ? json_decode($location->default_payment_accounts, true) : [];
        $pmts['cash'] = [
            "is_enabled"                 => "1",
            "is_purchase_enabled"        => "1",
            "is_sale_enabled"            => "1",
            "is_expense_enabled"         => "1",
            "is_purchase_return_enabled" => "1",
            "is_sale_return_enabled"     => "1",
            "is_custom"                  => "0",
            "account"                    => $this->accountGrpID('Cash Account', $location->business_id),
        ];

        $pmts['credit_sale'] = [
            "is_enabled"                 => "1",
            "is_purchase_enabled"        => "0",
            "is_sale_enabled"            => "1",
            "is_expense_enabled"         => "0",
            "is_purchase_return_enabled" => "0",
            "is_sale_return_enabled"     => "1",
            "is_custom"                  => "0",
            "account"                    => $this->accountGrpID('Credit Sales', $location->business_id),
        ];

        $pmts['own_cards'] = [
            "is_enabled"                 => "1",
            "is_purchase_enabled"        => "1",
            "is_sale_enabled"            => "0",
            "is_expense_enabled"         => "1",
            "is_purchase_return_enabled" => "1",
            "is_sale_return_enabled"     => "0",
            "is_custom"                  => "0",
            "account"                    => $this->accountGrpID('Own Cards', $location->business_id),
        ];

        $pmts['card'] = [
            "is_enabled"                 => "1",
            "is_purchase_enabled"        => "0",
            "is_sale_enabled"            => "1",
            "is_expense_enabled"         => "0",
            "is_purchase_return_enabled" => "0",
            "is_sale_return_enabled"     => "1",
            "is_custom"                  => "0",
            "account"                    => $this->accountGrpID('Card', $location->business_id),
        ];

        $pmts['cheque'] = [
            "is_enabled"                 => "1",
            "is_purchase_enabled"        => "0",
            "is_sale_enabled"            => "1",
            "is_expense_enabled"         => "0",
            "is_purchase_return_enabled" => "0",
            "is_sale_return_enabled"     => "1",
            "is_custom"                  => "0",
            "account"                    => $this->accountGrpID("Cheques in Hand (Customer's)", $location->business_id),
        ];

        $pmts['direct_bank_deposit'] = [
            "is_enabled"                 => "1",
            "is_purchase_enabled"        => "1",
            "is_sale_enabled"            => "1",
            "is_expense_enabled"         => "1",
            "is_purchase_return_enabled" => "1",
            "is_sale_return_enabled"     => "1",
            "is_custom"                  => "0",
            "account"                    => $this->accountGrpID('Bank Account', $location->business_id),
        ];

        $pmts['bank_transfer'] = [
            "is_enabled"                 => "1",
            "is_purchase_enabled"        => "1",
            "is_sale_enabled"            => "1",
            "is_expense_enabled"         => "1",
            "is_purchase_return_enabled" => "1",
            "is_sale_return_enabled"     => "1",
            "is_custom"                  => "0",
            "account"                    => $this->accountGrpID('Bank Account', $location->business_id),
        ];

        $pmts['pre_payments'] = [
            "is_enabled"                 => "1",
            "is_purchase_enabled"        => "1",
            "is_sale_enabled"            => "1",
            "is_expense_enabled"         => "1",
            "is_purchase_return_enabled" => "1",
            "is_sale_return_enabled"     => "1",
            "is_custom"                  => "0",
            "account"                    => $this->accountGrpID('Pre Payments', $location->business_id),
        ];

        $location->default_payment_accounts = json_encode($pmts);
        $location->save();
    }

    private function accountGrpID($name, $business_id)
    {
        $acc = AccountGroup::where('business_id', $business_id)->where('name', $name)->first();

        return ! empty($acc) ? $acc->id : null;
    }

    /**
     * save manage permission
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function returnDays($type)
    {
        switch ($type) {
            case "Years":
                return 365;
                break;
            case "Days":
                return 1;
                break;
            case "Months":
                return 30;
                break;
        }

        return 0;
    }

    /**
     * Restore the complete Manage form from the compact JSON field.
     *
     * The Manage page contains more controls than PHP's usual max_input_vars
     * limit. The browser therefore posts all non-file controls in one JSON
     * value and leaves uploaded files in Laravel's normal file bag.
     */
    private function hydrateManageFormPayload(Request $request): void
    {
        $rawPayload = $request->input('manage_form_payload');

        // Compatibility with the two earlier patch field names so an already
        // cached Manage page can still save successfully during deployment.
        if (! is_string($rawPayload) || trim($rawPayload) === '') {
            $rawPayload = $request->input('manage_form_payload_json');
        }

        if (! is_string($rawPayload) || trim($rawPayload) === '') {
            return;
        }

        try {
            /*
             | The leading backslash is essential.
             |
             | This file has `use function GuzzleHttp\json_decode;` at the top.
             | Inside a namespaced file an unqualified json_decode() therefore
             | resolves to GUZZLE's function, not PHP's. Guzzle's version takes
             | its arguments differently and rejected the payload outright, so
             | every Manage save failed with:
             |
             |   Invalid Manage form payload {"message":"json_decode error: Syntax error"}
             |
             | The payload was never at fault. Measured on a failing save: 26,981
             | characters, correctly closed braces, valid UTF-8, 576 posted
             | variables against a 10,000 limit. The JSON was fine; the function
             | resolving the name was wrong.
             */
            $payload = \json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::error('Invalid Manage form payload', [
                'business_id' => (int) ($request->route('id') ?: 0),
                'message' => $e->getMessage(),
            ]);

            throw new \RuntimeException('The Manage form data could not be read. Please refresh the page and save again.');
        }

        if (! is_array($payload)) {
            throw new \RuntimeException('The Manage form data is invalid. Please refresh the page and save again.');
        }

        unset(
            $payload['_token'],
            $payload['_method'],
            $payload['manage_form_payload'],
            $payload['manage_form_payload_json']
        );

        $request->merge($payload);
    }

    private function manageSaveRedirect(int $businessId, bool $success, string $message, bool $withInput = false)
    {
        $output = [
            'success' => $success ? 1 : 0,
            'msg' => $message,
        ];
        $url = action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@manage', ['id' => $businessId]);
        $separator = str_contains($url, '?') ? '&' : '?';
        $query = http_build_query([
            'manage_status' => $success ? 'success' : 'error',
            'manage_message' => $message,
        ]);
        $response = redirect()->to($url . $separator . $query)
            ->with('status', $output);

        if ($withInput) {
            $response->withInput();
        }

        return $response;
    }

    /**
     * Fast path for the normal Manage workflow where only module/page
     * checkboxes changed. It performs one central subscription update and one
     * business sidebar update, responds immediately with JSON, and defers the
     * tenant-database copies until after the response has been sent.
     */
    public function saveManagePermissionsFast($id, Request $request): \Illuminate\Http\JsonResponse
    {
        if (!auth()->user() || !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $id = (int) $id;
        $rawValues = json_decode((string) $request->input('permission_values_json', ''), true);
        if (!is_array($rawValues) || $rawValues === []) {
            return response()->json([
                'success' => false,
                'fallback' => true,
                'msg' => 'No permission changes were detected. Please use the full Save button for other settings.',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($id, $rawValues): array {
                $business = Business::findOrFail($id);
                $subscription = Subscription::active_subscription($id)
                    ?? Subscription::where('business_id', $id)->orderByDesc('id')->first();

                if (empty($subscription)) {
                    throw new \RuntimeException('Please activate a subscription before saving permissions.');
                }

                $packageDetails = is_array($subscription->package_details)
                    ? $subscription->package_details
                    : (json_decode((string) $subscription->getRawOriginal('package_details'), true) ?: []);
                $permissionValues = $this->managePerformanceService()->filterPermissionValues(
                    $rawValues,
                    array_keys($packageDetails)
                );
                if ($permissionValues === []) {
                    throw new \RuntimeException('No valid permission changes were detected. Please use the full Save button.');
                }

                foreach ($permissionValues as $key => $enabled) {
                    $packageDetails[$key] = $enabled ? 1 : 0;
                }

                // Older views post this parent as pump_operator_module while
                // runtime checks read pump_operator_dashboard.
                if (array_key_exists('pump_operator_module', $permissionValues)) {
                    $packageDetails['pump_operator_dashboard'] = $permissionValues['pump_operator_module'];
                }

                $permissionRequest = Request::create(
                    '/superadmin/business/save-manage-permissions-fast/' . $id,
                    'POST',
                    array_merge($permissionValues, [
                        'business_id' => $id,
                        'auto_manage_permissions_json' => json_encode($permissionValues),
                    ])
                );

                $this->applyStandaloneSidebarPermissionFlags($packageDetails, $permissionRequest);
                $this->modulePermissionService()->applyAutoManagePermissions($packageDetails, $permissionRequest);

                // Update the same parent source used by Manage Side Bar and
                // direct-route guards before enforcing child hierarchy.
                $this->modulePermissionService()->syncSidebarParentStatesFromManage(
                    $business,
                    $permissionRequest,
                    $packageDetails
                );

                $this->enforceDailyCollectionModuleChoice($packageDetails, $permissionRequest);
                $this->enforceMasterModulePermissionHierarchy($packageDetails, $permissionRequest);
                $this->syncLegacyVatPackageDetails($packageDetails);

                $subscription->package_details = $packageDetails;
                $subscription->save();

                $enabledModules = $business->enabled_modules;
                if (!is_array($enabledModules)) {
                    $decodedEnabledModules = json_decode((string) $enabledModules, true);
                    $enabledModules = is_array($decodedEnabledModules) ? $decodedEnabledModules : [];
                }
                $enabledModules = array_values(array_unique(array_filter(array_map('strval', $enabledModules))));

                return [
                    'subscription_id' => (int) $subscription->id,
                    'package_details' => $packageDetails,
                    'enabled_modules' => $enabledModules,
                    'saved_count' => count($permissionValues),
                ];
            });

            // Queue one consolidated tenant copy. queueManageTenantSync()
            // only records the task in memory; the actual cross-database work
            // runs from Laravel's terminating callback after the response.
            $this->queueManageTenantSync($id, [
                'subscription_id' => (int) $result['subscription_id'],
                'package_details' => $result['package_details'],
                'enabled_modules' => $result['enabled_modules'],
            ]);
            $this->managePerformanceService()->forgetBusiness($id);
            \App\Utils\SidebarPermissionUtil::forgetBusinessCache($id);

            return response()->json([
                'success' => true,
                'msg' => 'Manage permissions saved successfully.',
                'saved_count' => (int) ($result['saved_count'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            Log::error('Fast Manage permission save failed', [
                'business_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $canFallback = $e instanceof \RuntimeException
                && str_contains($e->getMessage(), 'No valid permission changes');

            return response()->json([
                'success' => false,
                'fallback' => $canFallback,
                'msg' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'Manage permissions could not be saved. Please try again.',
            ], $canFallback ? 422 : 500);
        }
    }

    public function saveManage($id, Request $request)
    {
        $this->hydrateManageFormPayload($request);

        // IS2339: keep the Payment Options payload intact so it can be written
        // to the selected business's operational DB after the central Manage
        // transaction commits. The old direct BusinessLocation update below
        // used the active/default connection and was unsafe in multi-tenant use.
        $paymentOptionsPayload = (array) $request->input('default_payment_accounts', []);

        Log::info('Save manage business settings requested', [
            'business_id' => (int) $id,
            'field_count' => count($request->all()),
            'has_files' => !empty($request->allFiles()),
        ]);

        // Never schedule or run a tenant-database copy until every central
        // Manage-page write has committed. A failed full save must not leave a
        // tenant with partial/new settings copied from a rolled-back request.
        $tenantSyncPayload = [];

        DB::beginTransaction();
        try {

            if (!empty($request->default_payment_accounts)) {
                foreach ($request->default_payment_accounts as $key => $value) {
                    if (in_array(null, $value['name'], true)) {
                        $output = [
                            'success' => 0,
                            'msg'     => __('messages.missing_account_names'),
                        ];
                        DB::rollBack();
                        return $this->manageSaveRedirect((int) $id, false, (string) $output['msg'], true);
                    }

                    if (in_array(null, $value['is_enabled'], true)) {
                        $output = [
                            'success' => 0,
                            'msg'     => __('messages.missing_active_status'),
                        ];
                        DB::rollBack();
                        return $this->manageSaveRedirect((int) $id, false, (string) $output['msg'], true);
                    }

                    if (isset($value['account'])) {
                        foreach ($value['account'] as $index => $account) {
                            // if (empty($account) && isset($value['is_enabled'][$index]) && $value['is_enabled'][$index] == 1) {
                            //     $output = [
                            //         'success' => 0,
                            //         'msg' => __('messages.missing_account_groups')
                            //     ];
                            //     return back()->with('status', $output)->withInput();
                            // }
                        }
                    }
                }
            }

            $business = Business::with('owner')->findOrFail($id);

            //OTP Verification
            $this->addUserSetting($business->owner->id, $request);
            $subscription            = Subscription::active_subscription($id)
                ?? Subscription::where('business_id', $id)->orderByDesc('id')->first();
            $already_running_pacakge = null;

            if (! empty($subscription)) {
                $already_running_pacakge = DB::table('packages')
                    ->select('*')
                    ->where('id', $subscription->package_id)
                    ->first();
            }

            $addto = Package::getPackagePeriodInDays($already_running_pacakge);

            $package_manage = Package::where('only_for_business', $id)->first();
            if (! empty($package_manage)) {
                $package_manage->price       = $request->annual_fee_package;
                $package_manage->currency_id = $request->currency_id;
                $package_manage->save();
            }

            if (! empty($subscription) && ! empty($package_manage)) {
                $subscription->package_id = $package_manage->id;
                $subscription->package_price = (float) ($request->annual_fee_package ?? $package_manage->price ?? $subscription->package_price ?? 0);
                $subscription->status = $subscription->status ?: 'approved';
                if (empty($subscription->start_date)) {
                    $subscription->start_date = now()->toDateString();
                }
                if (empty($subscription->paid_via)) {
                    $subscription->paid_via = 'superadmin_manage';
                }
                if (empty($subscription->payment_transaction_id)) {
                    $subscription->payment_transaction_id = 'SA-MANAGE-' . $id . '-' . now()->timestamp;
                }
                $subscription->save();
                $tenantSyncPayload['subscription_id'] = (int) $subscription->id;
            }

            // IS1520 FIX: When a subscription is added/edited from
            // Super Admin -> All Business -> Manage, make sure there is a
            // real row in the subscriptions table. The Package Subscription
            // page reads from subscriptions, so updating only the business
            // package/module settings makes the package look saved but it will
            // not appear in Super Admin -> Packages -> Package Subscription.
            if (empty($subscription)) {
                $subscriptionPackage = ! empty($package_manage)
                    ? $package_manage
                    : Package::active()->orderBy('sort_order')->first();

                if (! empty($subscriptionPackage)) {
                    $periodDays = (int) Package::getPackagePeriodInDays($subscriptionPackage);
                    $startDate = now()->toDateString();
                    $endDate = $periodDays > 0
                        ? now()->addDays($periodDays)->toDateString()
                        : null;

                    $subscription = Subscription::create([
                        'business_id' => $id,
                        'package_id' => $subscriptionPackage->id,
                        'paid_via' => 'superadmin_manage',
                        'payment_transaction_id' => 'SA-MANAGE-' . $id . '-' . now()->timestamp,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'trial_end_date' => null,
                        'status' => 'approved',
                        'package_price' => (float) ($request->annual_fee_package ?? $subscriptionPackage->price ?? 0),
                        'package_details' => [],
                    ]);

                    $tenantSyncPayload['subscription_id'] = (int) $subscription->id;

                    $already_running_pacakge = DB::table('packages')
                        ->select('*')
                        ->where('id', $subscription->package_id)
                        ->first();
                    $addto = Package::getPackagePeriodInDays($already_running_pacakge);
                }
            }

            // dd($addto);

            $module_activation_data = [];
            //HMS
            $module_activation_data['room_subscribe']      = $request->room_subscribe;
            $module_activation_data['room_added']          = $request->room_added;
            $module_activation_data['room_could_be_added'] = $request->room_could_be_added;
            // prices
            $module_activation_data['mf_price']                = $request->mf_price;
            $module_activation_data['real_time_entries_price'] = $request->real_time_entries_price;
            $module_activation_data['sales_agent_price'] = $request->sales_agent_price;
            $module_activation_data['pos2_price']              = $request->pos2_price;
            $module_activation_data['stock_report_price']      = $request->stock_report_price;
            $module_activation_data['my_auto_price']           = $request->my_auto_price;
            $module_activation_data['membership_module_price'] = $request->membership_module_price;
            $module_activation_data['sa_price']                = $request->sa_price;
            $module_activation_data['ac_price']                = $request->ac_price;

            $module_activation_data['deposits_price'] = $request->deposits_price;

            $module_activation_data['crm_module_price']           = $request->crm_module_price;
            $module_activation_data['ezyinvoice_module_price']    = $request->ezyinvoice_module_price;
            $module_activation_data['airline_module_price']       = $request->airline_module_price;
            $module_activation_data['shipping_module_price']      = $request->shipping_module_price;
            $module_activation_data['asset_module_price']         = $request->asset_module_price;
            $module_activation_data['hms_module_price']           = $request->hms_module_price;
            $module_activation_data['settlement_sw_module_price'] = $request->settlement_sw_module_price;
            $module_activation_data['access_module_price']        = $request->access_module_price;
            $module_activation_data['hr_price']                   = $request->hr_price;
            $module_activation_data['vreg_price']                 = $request->vreg_price;
            $module_activation_data['petro_price']                = $request->petro_price;
            $module_activation_data['repair_price']               = $request->repair_price;
            $module_activation_data['fleet_price']                = $request->fleet_price;
            $module_activation_data['mpcs_price']                 = $request->mpcs_price;
            $module_activation_data['backup_price']               = $request->backup_price;
            $module_activation_data['property_price']             = $request->property_price;
            $module_activation_data['auto_price']                 = $request->auto_price;
            $module_activation_data['contact_price']              = $request->contact_price;
            $module_activation_data['ran_price']                  = $request->ran_price;
            $module_activation_data['report_price']               = $request->report_price;
            // Modified by Engr. Alex -- task 7882: Issue 7 - Customized Reports module activation data
            $module_activation_data['customized_reports_price']        = $request->customized_reports_price;
            $module_activation_data['customized_reports_length']       = $request->customized_reports_length;
            $module_activation_data['customized_reports_interval']     = $request->customized_reports_interval;
            $module_activation_data['customized_reports_activated_on'] = $request->customized_reports_activated_on;
            $module_activation_data['settings_price']             = $request->settings_price;
            $module_activation_data['um_price']                   = $request->um_price;
            $module_activation_data['banking_price']              = $request->banking_price;
            $module_activation_data['sale_price']                 = $request->sale_price;
            $module_activation_data['leads_price']                = $request->leads_price;

            $module_activation_data['hospital_price']           = $request->hospital_price;
            $module_activation_data['restaurant_price']         = $request->restaurant_price;
            $module_activation_data['duplicate_invoice_price']  = $request->duplicate_invoice_price;
            $module_activation_data['tasks_price']              = $request->tasks_price;
            $module_activation_data['cheque_price']             = $request->cheque_price;
            $module_activation_data['list_easy_price']          = $request->list_easy_price;
            $module_activation_data['pump_price']               = $request->pump_price;
            $module_activation_data['stock_taking_price']       = $request->stock_taking_price;
            $module_activation_data['installment_module_price'] = $request->installment_module_price;
            $module_activation_data['patient_module_price']     = $request->patient_module_price;
            $module_activation_data['patient_test_module_price'] = $request->patient_test_module_price;

            // doc 6104
            $module_activation_data['enable_sms_price']            = $request->enable_sms_price;
            $module_activation_data['list_sms_price']              = $request->list_sms_price;
            $module_activation_data['notification_template_price'] = $request->notification_template_price;

            $module_activation_data['products_price']            = $request->products_price;
            $module_activation_data['purchase_price']            = $request->purchase_price;
            $module_activation_data['stock_transfer_price']      = $request->stock_transfer_price;
            $module_activation_data['daily_review_price']        = $request->daily_review_price;
            $module_activation_data['service_staff_price']       = $request->service_staff_price;
            $module_activation_data['enable_subscription_price'] = $request->enable_subscription_price;

            $module_activation_data['post_dated_price']    = $request->post_dated_price;
            $module_activation_data['vat_price']           = $request->vat_price;
            $module_activation_data['bakery_price']        = $request->bakery_price;
            $module_activation_data['subscriptions_price'] = $request->subscriptions_price;

            $module_activation_data['smsmodule_price'] = $request->smsmodule_price;

            // VAT Module Main activation data
            $module_activation_data['vat_main_price']        = $request->vat_main_price;
            $module_activation_data['vat_main_length']       = $request->vat_main_length;
            $module_activation_data['vat_main_interval']     = $request->vat_main_interval;
            $module_activation_data['vat_main_activated_on'] = $request->vat_main_activated_on;

            $module_activation_data['distribution_module']              = $request->distribution_module;
            $module_activation_data['distribution_module_price']        = $request->distribution_module_price;
            $module_activation_data['distribution_module_length']       = $request->distribution_module_length;
            $module_activation_data['distribution_module_interval']     = $request->distribution_module_interval;
            $module_activation_data['distribution_module_activated_on'] = $request->distribution_module_activated_on;

            $module_activation_data['spreadsheet']       = $request->spreadsheet;
            $module_activation_data['essentials_module'] = $request->essentials_module;

            $module_activation_data['price_changes_price']           = $request->price_changes_price;
            $module_activation_data['day_end_price']                 = $request->day_end_price;
            $module_activation_data['customer_interest_price']       = $request->customer_interest_price;
            $module_activation_data['issue_customer_bill_price']     = $request->issue_customer_bill_price;
            $module_activation_data['issue_customer_bill_vat_price'] = $request->issue_customer_bill_vat_price;

            $module_activation_data['ezy_price'] = $request->ezy_price;

            // Lengths:
            $module_activation_data['mf_length']                = $request->mf_length;
            $module_activation_data['agent_length']             = $request->agent_length;
            $module_activation_data['sales_agent_length']       = $request->sales_agent_length;
            $module_activation_data['real_time_entries_length'] = $request->real_time_entries_length;
            $module_activation_data['sales_agent_length'] = $request->sales_agent_length;
            $module_activation_data['pos2_length']              = $request->pos2_length;
            $module_activation_data['stock_report_length']      = $request->stock_report_length;
            $module_activation_data['my_auto_length']           = $request->my_auto_length;
            $module_activation_data['membership_module_length'] = $request->membership_module_length;
            $module_activation_data['sa_length']                = $request->sa_length;
            $module_activation_data['ac_length']                = $request->ac_length;

            $module_activation_data['deposits_length'] = $request->deposits_length;

            $module_activation_data['crm_module_length']           = $request->crm_module_length;
            $module_activation_data['ezyinvoice_module_length']    = $request->ezyinvoice_module_length;
            $module_activation_data['airline_module_length']       = $request->airline_module_length;
            $module_activation_data['shipping_module_length']      = $request->shipping_module_length;
            $module_activation_data['asset_module_length']         = $request->asset_module_length;
            $module_activation_data['hms_module_length']           = $request->hms_module_length;
            $module_activation_data['settlement_sw_module_length'] = $request->settlement_sw_module_length;
            $module_activation_data['access_module_length']        = $request->access_module_length;
            $module_activation_data['hr_length']                   = $request->hr_length;
            $module_activation_data['vreg_length']                 = $request->vreg_length;
            $module_activation_data['petro_length']                = $request->petro_length;
            $module_activation_data['repair_length']               = $request->repair_length;
            $module_activation_data['fleet_length']                = $request->fleet_length;
            $module_activation_data['mpcs_length']                 = $request->mpcs_length;
            $module_activation_data['backup_length']               = $request->backup_length;
            $module_activation_data['property_length']             = $request->property_length;
            $module_activation_data['auto_length']                 = $request->auto_length;
            $module_activation_data['contact_length']              = $request->contact_length;
            $module_activation_data['ran_length']                  = $request->ran_length;
            $module_activation_data['report_length']               = $request->report_length;
            $module_activation_data['settings_length']             = $request->settings_length;
            $module_activation_data['um_length']                   = $request->um_length;
            $module_activation_data['banking_length']              = $request->banking_length;
            $module_activation_data['sale_length']                 = $request->sale_length;
            $module_activation_data['leads_length']                = $request->leads_length;

            $module_activation_data['hospital_length']           = $request->hospital_length;
            $module_activation_data['restaurant_length']         = $request->restaurant_length;
            $module_activation_data['duplicate_invoice_length']  = $request->duplicate_invoice_length;
            $module_activation_data['tasks_length']              = $request->tasks_length;
            $module_activation_data['cheque_length']             = $request->cheque_length;
            $module_activation_data['list_easy_length']          = $request->list_easy_length;
            $module_activation_data['pump_length']               = $request->pump_length;
            $module_activation_data['stock_taking_length']       = $request->stock_taking_length;
            $module_activation_data['installment_module_length'] = $request->installment_module_length;
            $module_activation_data['patient_module_length']     = $request->patient_module_length;
            $module_activation_data['patient_test_module_length'] = $request->patient_test_module_length;

            // doc 6104
            $module_activation_data['enable_sms_length']            = $request->enable_sms_length;
            $module_activation_data['list_sms_length']              = $request->list_sms_length;
            $module_activation_data['notification_template_length'] = $request->notification_template_length;

            $module_activation_data['products_length']            = $request->products_length;
            $module_activation_data['purchase_length']            = $request->purchase_length;
            $module_activation_data['stock_transfer_length']      = $request->stock_transfer_length;
            $module_activation_data['daily_review_length']        = $request->daily_review_length;
            $module_activation_data['service_staff_length']       = $request->service_staff_length;
            $module_activation_data['enable_subscription_length'] = $request->enable_subscription_length;

            $module_activation_data['distribution_module_length'] = $request->distribution_module_length;
            $module_activation_data['spreadsheet_length']         = $request->spreadsheet_length;
            $module_activation_data['essentials_length']          = $request->essentials_length;

            $module_activation_data['price_changes_length']           = $request->price_changes_length;
            $module_activation_data['day_end_length']                 = $request->day_end_length;
            $module_activation_data['customer_interest_length']       = $request->customer_interest_length;
            $module_activation_data['issue_customer_bill_length']     = $request->issue_customer_bill_length;
            $module_activation_data['issue_customer_bill_vat_length'] = $request->issue_customer_bill_vat_length;

            $module_activation_data['post_dated_length']    = $request->post_dated_length;
            $module_activation_data['vat_length']           = $request->vat_length;
            $module_activation_data['bakery_length']        = $request->bakery_length;
            $module_activation_data['subscriptions_length'] = $request->subscriptions_length;

            $module_activation_data['smsmodule_length'] = $request->smsmodule_length;

            $module_activation_data['ezy_length'] = $request->ezy_length;

            // Super Admin Info

            $module_activation_data['super_admin_name']           = $request->super_admin_name;
            $module_activation_data['super_admin_address']        = $request->super_admin_address;
            $module_activation_data['super_admin_contact']        = $request->super_admin_contact;
            $module_activation_data['super_admin_vat_registered'] = $request->super_admin_vat_registered;
            $module_activation_data['super_admin_vat_number']     = $request->super_admin_vat_number;
            $module_activation_data['app_footer']                 = $request->app_footer;
            $module_activation_data['invoice_footer']             = $request->invoice_footer;
            $module_activation_data['report_footer']              = $request->report_footer;
            $module_activation_data['payment_gateways']           = $request->payment_gateways;

            // intervals
            $module_activation_data['mf_interval']               = $request->mf_interval;
            $module_activation_data['agent_interval']            = $request->agent_interval;
            $module_activation_data['sales_agent_interval']      = $request->sales_agent_interval;
            $module_activation_data['real_time_entries_interval'] = $request->real_time_entries_interval;
            $module_activation_data['sales_agent_interval'] = $request->sales_agent_interval;
            $module_activation_data['pos2_interval']              = $request->pos2_interval;
            $module_activation_data['stock_report_interval']      = $request->stock_report_interval;
            $module_activation_data['my_auto_interval']           = $request->my_auto_interval;
            $module_activation_data['membership_module_interval'] = $request->membership_module_interval;
            $module_activation_data['sa_interval']               = $request->sa_interval;
            $module_activation_data['ac_interval']               = $request->ac_interval;

            $module_activation_data['deposits_interval'] = $request->deposits_interval;

            $module_activation_data['ezyinvoice_module_interval']    = $request->ezyinvoice_module_interval;
            $module_activation_data['crm_module_interval']           = $request->crm_module_interval;
            $module_activation_data['airline_module_interval']       = $request->airline_module_interval;
            $module_activation_data['shipping_module_interval']      = $request->shipping_module_interval;
            $module_activation_data['asset_module_interval']         = $request->asset_module_interval;
            $module_activation_data['hms_module_interval']           = $request->hms_module_interval;
            $module_activation_data['settlement_sw_module_interval'] = $request->settlement_sw_module_interval;
            $module_activation_data['access_module_interval']        = $request->access_module_interval;
            $module_activation_data['hr_interval']                   = $request->hr_interval;
            $module_activation_data['vreg_interval']                 = $request->vreg_interval;
            $module_activation_data['petro_interval']                = $request->petro_interval;
            $module_activation_data['repair_interval']               = $request->repair_interval;
            $module_activation_data['fleet_interval']                = $request->fleet_interval;
            $module_activation_data['mpcs_interval']                 = $request->mpcs_interval;
            $module_activation_data['backup_interval']               = $request->backup_interval;
            $module_activation_data['property_interval']             = $request->property_interval;
            $module_activation_data['auto_interval']                 = $request->auto_interval;
            $module_activation_data['contact_interval']              = $request->contact_interval;
            $module_activation_data['ran_interval']                  = $request->ran_interval;
            $module_activation_data['report_interval']               = $request->report_interval;
            $module_activation_data['settings_interval']             = $request->settings_interval;
            $module_activation_data['um_interval']                   = $request->um_interval;
            $module_activation_data['banking_interval']              = $request->banking_interval;
            $module_activation_data['sale_interval']                 = $request->sale_interval;
            $module_activation_data['leads_interval']                = $request->leads_interval; //new modules addition below
            $module_activation_data['hospital_interval']             = $request->hospital_interval;
            $module_activation_data['restaurant_interval']           = $request->restaurant_interval;
            $module_activation_data['duplicate_invoice_interval']    = $request->duplicate_invoice_interval;
            $module_activation_data['tasks_interval']                = $request->tasks_interval;
            $module_activation_data['cheque_interval']               = $request->cheque_interval;
            $module_activation_data['list_easy_interval']            = $request->list_easy_interval;
            $module_activation_data['pump_interval']                 = $request->pump_interval;
            $module_activation_data['stock_taking_interval']         = $request->stock_taking_interval;

            $module_activation_data['installment_module_interval'] = $request->installment_module_interval;
            $module_activation_data['patient_module_interval']     = $request->patient_module_interval;
            $module_activation_data['patient_test_module_interval'] = $request->patient_test_module_interval;

            // doc 6104
            $module_activation_data['list_sms_interval']              = $request->list_sms_interval;
            $module_activation_data['enable_sms_interval']            = $request->enable_sms_interval;
            $module_activation_data['notification_template_interval'] = $request->notification_template_interval;

            // ///////////////////////////////////////////////////////////////////////////////
            $module_activation_data['products_interval']            = $request->products_interval;
            $module_activation_data['purchase_interval']            = $request->purchase_interval;
            $module_activation_data['stock_transfer_interval']      = $request->stock_transfer_interval;
            $module_activation_data['daily_review_interval']        = $request->daily_review_interval;
            $module_activation_data['service_staff_interval']       = $request->service_staff_interval;
            $module_activation_data['enable_subscription_interval'] = $request->enable_subscription_interval;

            $module_activation_data['distribution_module_interval'] = $request->distribution_module_interval;
            $module_activation_data['spreadsheet_interval']         = $request->spreadsheet_interval;
            $module_activation_data['essentials_interval']          = $request->essentials_interval;

            $module_activation_data['price_changes_interval']           = $request->price_changes_interval;
            $module_activation_data['day_end_interval']                 = $request->day_end_interval;
            $module_activation_data['customer_interest_interval']       = $request->customer_interest_interval;
            $module_activation_data['issue_customer_bill_interval']     = $request->issue_customer_bill_interval;
            $module_activation_data['issue_customer_bill_vat_interval'] = $request->issue_customer_bill_vat_interval;

            $module_activation_data['post_dated_interval']    = $request->post_dated_interval;
            $module_activation_data['vat_interval']           = $request->vat_interval;
            $module_activation_data['bakery_interval']        = $request->bakery_interval;
            $module_activation_data['subscriptions_interval'] = $request->subscriptions_interval;

            $module_activation_data['smsmodule_interval'] = $request->smsmodule_interval;

            $module_activation_data['ezy_interval'] = $request->ezy_interval;

            // Petro PD module activation data
            $module_activation_data['petro_pd_price']       = $request->petro_pd_price;
            $module_activation_data['petro_pd_length']      = $request->petro_pd_length;
            $module_activation_data['petro_pd_interval']    = $request->petro_pd_interval;
            $module_activation_data['petro_pd_activated_on'] = $request->petro_pd_activated_on;

            // EV Charging module activation data
            $module_activation_data['ev_charging_price']       = $request->ev_charging_price;
            $module_activation_data['ev_charging_length']      = $request->ev_charging_length;
            $module_activation_data['ev_charging_interval']    = $request->ev_charging_interval;
            $module_activation_data['ev_charging_activated_on'] = $request->ev_charging_activated_on;

            // activation dates
            $module_activation_data['mf_activated_on']                = $request->mf_activated_on;
            $module_activation_data['agent_activated_on']             = $request->agent_activated_on;
            $module_activation_data['sales_agent_activated_on']       = $request->sales_agent_activated_on;
            $module_activation_data['real_time_entries_activated_on'] = $request->real_time_entries_activated_on;
            $module_activation_data['sales_agent_activated_on'] = $request->sales_agent_activated_on;
            $module_activation_data['pos2_activated_on']              = $request->pos2_activated_on;
            $module_activation_data['stock_report_activated_on']      = $request->stock_report_activated_on;
            $module_activation_data['my_auto_activated_on']           = $request->my_auto_activated_on;
            $module_activation_data['membership_module_activated_on'] = $request->membership_module_activated_on;
            $module_activation_data['sa_activated_on']                = $request->sa_activated_on;
            $module_activation_data['ac_activated_on']                = $request->ac_activated_on;

            $module_activation_data['deposits_activated_on'] = $request->deposits_activated_on;

            $module_activation_data['crm_module_activated_on']           = $request->crm_module_activated_on;
            $module_activation_data['ezyinvoice_module_activated_on']    = $request->ezyinvoice_module_activated_on;
            $module_activation_data['shipping_module_activated_on']      = $request->shipping_module_activated_on;
            $module_activation_data['airline_module_activated_on']       = $request->airline_module_activated_on;
            $module_activation_data['asset_module_activated_on']         = $request->asset_module_activated_on;
            $module_activation_data['hms_module_activated_on']           = $request->hms_module_activated_on;
            $module_activation_data['settlement_sw_module_activated_on'] = $request->settlement_sw_module_activated_on;
            $module_activation_data['access_module_activated_on']        = $request->access_module_activated_on;
            $module_activation_data['hr_activated_on']                   = $request->hr_activated_on;
            $module_activation_data['vreg_activated_on']                 = $request->vreg_activated_on;
            $module_activation_data['petro_activated_on']                = $request->petro_activated_on;
            $module_activation_data['repair_activated_on']               = $request->repair_activated_on;
            $module_activation_data['fleet_activated_on']                = $request->fleet_activated_on;
            $module_activation_data['mpcs_activated_on']                 = $request->mpcs_activated_on;
            $module_activation_data['backup_activated_on']               = $request->backup_activated_on;
            $module_activation_data['property_activated_on']             = $request->property_activated_on;
            $module_activation_data['auto_activated_on']                 = $request->auto_activated_on;
            $module_activation_data['contact_activated_on']              = $request->contact_activated_on;
            $module_activation_data['ran_activated_on']                  = $request->ran_activated_on;
            $module_activation_data['report_activated_on']               = $request->report_activated_on;
            $module_activation_data['settings_activated_on']             = $request->settings_activated_on;
            $module_activation_data['um_activated_on']                   = $request->um_activated_on;
            $module_activation_data['banking_activated_on']              = $request->banking_activated_on;
            $module_activation_data['sale_activated_on']                 = $request->sale_activated_on;
            $module_activation_data['leads_activated_on']                = $request->leads_activated_on; // added modules
            $module_activation_data['hospital_activated_on']             = $request->hospital_activated_on;
            $module_activation_data['restaurant_activated_on']           = $request->restaurant_activated_on;
            $module_activation_data['duplicate_invoice_activated_on']    = $request->duplicate_invoice_activated_on;
            $module_activation_data['tasks_activated_on']                = $request->tasks_activated_on;
            $module_activation_data['cheque_activated_on']               = $request->cheque_activated_on;
            $module_activation_data['list_easy_activated_on']            = $request->list_easy_activated_on;
            $module_activation_data['pump_activated_on']                 = $request->pump_activated_on;
            $module_activation_data['stock_taking_activated_on']         = $request->stock_taking_activated_on;

            $module_activation_data['installment_module_activated_on'] = $request->installment_module_activated_on;
            $module_activation_data['patient_module_activated_on']     = $request->patient_module_activated_on;
            $module_activation_data['patient_test_module_activated_on'] = $request->patient_test_module_activated_on;

            // doc 6104
            $module_activation_data['enable_sms_activated_on']            = $request->enable_sms_activated_on;
            $module_activation_data['list_sms_activated_on']              = $request->list_sms_activated_on;
            $module_activation_data['notification_template_activated_on'] = $request->notification_template_activated_on;

            // ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
            $module_activation_data['products_activated_on']            = $request->products_activated_on;
            $module_activation_data['purchase_activated_on']            = $request->purchase_activated_on;
            $module_activation_data['stock_transfer_activated_on']      = $request->stock_transfer_activated_on;
            $module_activation_data['daily_review_activated_on']        = $request->daily_review_activated_on;
            $module_activation_data['service_staff_activated_on']       = $request->service_staff_activated_on;
            $module_activation_data['enable_subscription_activated_on'] = $request->enable_subscription_activated_on;

            $module_activation_data['distribution_module_activated_on'] = $request->distribution_module_activated_on;
            $module_activation_data['spreadsheet_activated_on']         = $request->spreadsheet_activated_on;
            $module_activation_data['essentials_activated_on']          = $request->essentials_activated_on;

            $module_activation_data['price_changes_activated_on']           = $request->price_changes_activated_on;
            $module_activation_data['day_end_activated_on']                 = $request->day_end_activated_on;
            $module_activation_data['customer_interest_activated_on']       = $request->customer_interest_activated_on;
            $module_activation_data['issue_customer_bill_activated_on']     = $request->issue_customer_bill_activated_on;
            $module_activation_data['issue_customer_bill_vat_activated_on'] = $request->issue_customer_bill_vat_activated_on;

            $module_activation_data['post_dated_activated_on']    = $request->post_dated_activated_on;
            $module_activation_data['vat_activated_on']           = $request->vat_activated_on;
            $module_activation_data['bakery_activated_on']        = $request->bakery_activated_on;
            $module_activation_data['subscriptions_activated_on'] = $request->subscriptions_activated_on;

            $module_activation_data['smsmodule_activated_on'] = $request->smsmodule_activated_on;

            $module_activation_data['ezy_activated_on'] = $request->ezy_activated_on;

            // days added
            $addto_data['mf_addto']                = (! empty($module_activation_data['mf_length']) && ! empty($module_activation_data['mf_interval'])) ? ($this->returnDays($module_activation_data['mf_interval']) * $module_activation_data['mf_length']) : $addto;
            if ($request->has('agent_module') && (int) $request->input('agent_module') === 1) {
                $module_activation_data['agent_interval'] = ! empty($module_activation_data['agent_interval']) ? $module_activation_data['agent_interval'] : 'Years';
                $module_activation_data['agent_length'] = ! empty($module_activation_data['agent_length']) ? $module_activation_data['agent_length'] : 1;
                $module_activation_data['agent_activated_on'] = ! empty($module_activation_data['agent_activated_on']) ? $module_activation_data['agent_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d');
            }

            $addto_data['agent_addto']             = (! empty($module_activation_data['agent_length']) && ! empty($module_activation_data['agent_interval'])) ? ($this->returnDays($module_activation_data['agent_interval']) * $module_activation_data['agent_length']) : $addto;
            $addto_data['petro_pd_addto']          = (! empty($module_activation_data['petro_pd_length']) && ! empty($module_activation_data['petro_pd_interval'])) ? ($this->returnDays($module_activation_data['petro_pd_interval']) * $module_activation_data['petro_pd_length']) : $addto;
            $addto_data['ev_charging_addto']       = (! empty($module_activation_data['ev_charging_length']) && ! empty($module_activation_data['ev_charging_interval'])) ? ($this->returnDays($module_activation_data['ev_charging_interval']) * $module_activation_data['ev_charging_length']) : $addto;
            $addto_data['sales_agent_addto']       = (! empty($module_activation_data['sales_agent_length']) && ! empty($module_activation_data['sales_agent_interval'])) ? ($this->returnDays($module_activation_data['sales_agent_interval']) * $module_activation_data['sales_agent_length']) : $addto;
            $addto_data['real_time_entries_addto'] = (! empty($module_activation_data['real_time_entries_length']) && ! empty($module_activation_data['real_time_entries_interval'])) ? ($this->returnDays($module_activation_data['real_time_entries_interval']) * $module_activation_data['real_time_entries_length']) : $addto;
            $addto_data['sales_agent_addto'] = (! empty($module_activation_data['sales_agent_length']) && ! empty($module_activation_data['sales_agent_interval'])) ? ($this->returnDays($module_activation_data['sales_agent_interval']) * $module_activation_data['sales_agent_length']) : $addto;
            $addto_data['pos2_addto'] = (! empty($module_activation_data['pos2_length']) && ! empty($module_activation_data['pos2_interval'])) ? ($this->returnDays($module_activation_data['pos2_interval']) * $module_activation_data['pos2_length']) : $addto;
            $addto_data['stock_report_addto'] = (! empty($module_activation_data['stock_report_length']) && ! empty($module_activation_data['stock_report_interval'])) ? ($this->returnDays($module_activation_data['stock_report_interval']) * $module_activation_data['stock_report_length']) : $addto;
            $addto_data['my_auto_addto'] = (! empty($module_activation_data['my_auto_length']) && ! empty($module_activation_data['my_auto_interval'])) ? ($this->returnDays($module_activation_data['my_auto_interval']) * $module_activation_data['my_auto_length']) : $addto;
            $addto_data['membership_module_addto'] = (! empty($module_activation_data['membership_module_length']) && ! empty($module_activation_data['membership_module_interval'])) ? ($this->returnDays($module_activation_data['membership_module_interval']) * $module_activation_data['membership_module_length']) : $addto;
            $addto_data['sa_addto']                = (! empty($module_activation_data['sa_length']) && ! empty($module_activation_data['sa_interval'])) ? ($this->returnDays($module_activation_data['sa_interval']) * $module_activation_data['sa_length']) : $addto;
            $addto_data['ac_addto']                = (! empty($module_activation_data['ac_length']) && ! empty($module_activation_data['ac_interval'])) ? ($this->returnDays($module_activation_data['ac_interval']) * $module_activation_data['ac_length']) : $addto;

            $addto_data['deposits_addto'] = (! empty($module_activation_data['deposits_length']) && ! empty($module_activation_data['deposits_interval'])) ? ($this->returnDays($module_activation_data['deposits_interval']) * $module_activation_data['deposits_length']) : $addto;

            $addto_data['crm_addto']           = (! empty($module_activation_data['crm_module_length']) && ! empty($module_activation_data['crm_module_interval'])) ? ($this->returnDays($module_activation_data['crm_module_interval']) * $module_activation_data['crm_module_length']) : $addto;
            $addto_data['ezyinvoice_addto']    = (! empty($module_activation_data['ezyinvoice_module_length']) && ! empty($module_activation_data['ezyinvoice_module_interval'])) ? ($this->returnDays($module_activation_data['ezyinvoice_module_interval']) * $module_activation_data['ezyinvoice_module_length']) : $addto;
            $addto_data['shipping_addto']      = (! empty($module_activation_data['shipping_module_length']) && ! empty($module_activation_data['shipping_module_interval'])) ? ($this->returnDays($module_activation_data['shipping_module_interval']) * $module_activation_data['shipping_module_length']) : $addto;
            $addto_data['airline_addto']       = (! empty($module_activation_data['airline_module_length']) && ! empty($module_activation_data['airline_module_interval'])) ? ($this->returnDays($module_activation_data['airline_module_interval']) * $module_activation_data['airline_module_length']) : $addto;
            $addto_data['asset_addto']         = (! empty($module_activation_data['asset_module_length']) && ! empty($module_activation_data['asset_module_interval'])) ? ($this->returnDays($module_activation_data['asset_module_interval']) * $module_activation_data['asset_module_length']) : $addto;
            $addto_data['hms_addto']           = (! empty($module_activation_data['hms_module_length']) && ! empty($module_activation_data['hms_module_interval'])) ? ($this->returnDays($module_activation_data['hms_module_interval']) * $module_activation_data['hms_module_length']) : $addto;
            $addto_data['settlement_sw_addto'] = (! empty($module_activation_data['settlement_sw_module_length']) && ! empty($module_activation_data['settlement_sw_module_interval'])) ? ($this->returnDays($module_activation_data['settlement_sw_module_interval']) * $module_activation_data['settlement_sw_module_length']) : $addto;

            $addto_data['access_module_addto'] = (! empty($module_activation_data['access_module_length']) && ! empty($module_activation_data['access_module_interval'])) ? ($this->returnDays($module_activation_data['access_module_interval']) * $module_activation_data['access_module_length']) : $addto;
            $addto_data['hr_addto']            = (! empty($module_activation_data['hr_length']) && ! empty($module_activation_data['hr_interval'])) ? ($this->returnDays($module_activation_data['hr_interval']) * $module_activation_data['hr_length']) : $addto;
            $addto_data['vreg_addto']          = (! empty($module_activation_data['vreg_length']) && ! empty($module_activation_data['vreg_interval'])) ? ($this->returnDays($module_activation_data['vreg_interval']) * $module_activation_data['vreg_length']) : $addto;
            $addto_data['petro_addto']         = (! empty($module_activation_data['petro_length']) && ! empty($module_activation_data['petro_interval'])) ? ($this->returnDays($module_activation_data['petro_interval']) * $module_activation_data['petro_length']) : $addto;
            $addto_data['repair_addto']        = (! empty($module_activation_data['repair_length']) && ! empty($module_activation_data['repair_interval'])) ? ($this->returnDays($module_activation_data['repair_interval']) * $module_activation_data['repair_length']) : $addto;
            $addto_data['fleet_addto']         = (! empty($module_activation_data['fleet_length']) && ! empty($module_activation_data['fleet_interval'])) ? ($this->returnDays($module_activation_data['fleet_interval']) * $module_activation_data['fleet_length']) : $addto;
            $addto_data['mpcs_addto']          = (! empty($module_activation_data['mpcs_length']) && ! empty($module_activation_data['mpcs_interval'])) ? ($this->returnDays($module_activation_data['mpcs_interval']) * $module_activation_data['mpcs_length']) : $addto;
            $addto_data['backup_addto']        = (! empty($module_activation_data['backup_length']) && ! empty($module_activation_data['backup_interval'])) ? ($this->returnDays($module_activation_data['backup_interval']) * $module_activation_data['backup_length']) : $addto;
            $addto_data['property_addto']      = (! empty($module_activation_data['property_length']) && ! empty($module_activation_data['property_interval'])) ? ($this->returnDays($module_activation_data['property_interval']) * $module_activation_data['property_length']) : $addto;
            $addto_data['auto_addto']          = (! empty($module_activation_data['auto_length']) && ! empty($module_activation_data['auto_interval'])) ? ($this->returnDays($module_activation_data['auto_interval']) * $module_activation_data['auto_length']) : $addto;
            $addto_data['contact_addto']       = (! empty($module_activation_data['contact_length']) && ! empty($module_activation_data['contact_interval'])) ? ($this->returnDays($module_activation_data['contact_interval']) * $module_activation_data['contact_length']) : $addto;
            $addto_data['ran_addto']           = (! empty($module_activation_data['ran_length']) && ! empty($module_activation_data['ran_interval'])) ? ($this->returnDays($module_activation_data['ran_interval']) * $module_activation_data['ran_length']) : $addto;
            $addto_data['report_addto']        = (! empty($module_activation_data['report_length']) && ! empty($module_activation_data['report_interval'])) ? ($this->returnDays($module_activation_data['report_interval']) * $module_activation_data['report_length']) : $addto;
            // Modified by Engr. Alex -- task 7882: Issue 7 - Customized Reports expiry calculation
            $addto_data['customized_reports_addto'] = (! empty($module_activation_data['customized_reports_length']) && ! empty($module_activation_data['customized_reports_interval'])) ? ($this->returnDays($module_activation_data['customized_reports_interval']) * $module_activation_data['customized_reports_length']) : $addto;
            $addto_data['settings_addto']      = (! empty($module_activation_data['settings_length']) && ! empty($module_activation_data['settings_interval'])) ? ($this->returnDays($module_activation_data['settings_interval']) * $module_activation_data['settings_length']) : $addto;
            $addto_data['um_addto']            = (! empty($module_activation_data['um_length']) && ! empty($module_activation_data['um_interval'])) ? ($this->returnDays($module_activation_data['um_interval']) * $module_activation_data['um_length']) : $addto;
            $addto_data['banking_addto']       = (! empty($module_activation_data['banking_length']) && ! empty($module_activation_data['banking_interval'])) ? ($this->returnDays($module_activation_data['banking_interval']) * $module_activation_data['banking_length']) : $addto;
            $addto_data['sale_addto']          = (! empty($module_activation_data['sale_length']) && ! empty($module_activation_data['sale_interval'])) ? ($this->returnDays($module_activation_data['sale_interval']) * $module_activation_data['sale_length']) : $addto;
            $addto_data['leads_addto']         = (! empty($module_activation_data['leads_length']) && ! empty($module_activation_data['leads_interval'])) ? ($this->returnDays($module_activation_data['leads_interval']) * $module_activation_data['leads_length']) : $addto; // new modules addition
            $addto_data['hospital_addto']      = (! empty($module_activation_data['hospital_length']) && ! empty($module_activation_data['hospital_interval'])) ? ($this->returnDays($module_activation_data['hospital_interval']) * $module_activation_data['hospital_length']) : $addto;

            $addto_data['restaurant_addto'] = (! empty($module_activation_data['restaurant_length']) && ! empty($module_activation_data['restaurant_interval'])) ? ($this->returnDays($module_activation_data['restaurant_interval']) * $module_activation_data['restaurant_length']) : $addto;

            $addto_data['duplicate_invoice_addto'] = (! empty($module_activation_data['duplicate_invoice_length']) && ! empty($module_activation_data['duplicate_invoice_interval'])) ? ($this->returnDays($module_activation_data['duplicate_invoice_interval']) * $module_activation_data['duplicate_invoice_length']) : $addto;

            $addto_data['tasks_addto'] = (! empty($module_activation_data['tasks_length']) && ! empty($module_activation_data['tasks_interval'])) ? ($this->returnDays($module_activation_data['tasks_interval']) * $module_activation_data['tasks_length']) : $addto;

            $addto_data['cheque_addto'] = (! empty($module_activation_data['cheque_length']) && ! empty($module_activation_data['cheque_interval'])) ? ($this->returnDays($module_activation_data['cheque_interval']) * $module_activation_data['cheque_length']) : $addto;

            $addto_data['list_easy_addto'] = (! empty($module_activation_data['list_easy_length']) && ! empty($module_activation_data['list_easy_interval'])) ? ($this->returnDays($module_activation_data['list_easy_interval']) * $module_activation_data['list_easy_length']) : $addto;

            $addto_data['pump_addto'] = (! empty($module_activation_data['pump_length']) && ! empty($module_activation_data['pump_interval'])) ? ($this->returnDays($module_activation_data['pump_interval']) * $module_activation_data['pump_length']) : $addto;

            $addto_data['stock_taking_addto'] = (! empty($module_activation_data['stock_taking_length']) && ! empty($module_activation_data['stock_taking_interval'])) ? ($this->returnDays($module_activation_data['stock_taking_interval']) * $module_activation_data['stock_taking_length']) : $addto;

            $addto_data['installment_module_addto'] = (! empty($module_activation_data['installment_module_length']) && ! empty($module_activation_data['installment_module_interval'])) ? ($this->returnDays($module_activation_data['installment_module_interval']) * $module_activation_data['installment_module_length']) : $addto;
            $addto_data['patient_module_addto'] = (! empty($module_activation_data['patient_module_length']) && ! empty($module_activation_data['patient_module_interval'])) ? ($this->returnDays($module_activation_data['patient_module_interval']) * $module_activation_data['patient_module_length']) : $addto;
            $addto_data['patient_test_module_addto'] = (! empty($module_activation_data['patient_test_module_length']) && ! empty($module_activation_data['patient_test_module_interval'])) ? ($this->returnDays($module_activation_data['patient_test_module_interval']) * $module_activation_data['patient_test_module_length']) : $addto;

            $addto_data['ezy_addto'] = (! empty($module_activation_data['ezy_length']) && ! empty($module_activation_data['ezy_interval'])) ? ($this->returnDays($module_activation_data['ezy_interval']) * $module_activation_data['ezy_length']) : $addto;

            // doc 6104
            $addto_data['list_sms_addto']              = (! empty($module_activation_data['list_sms_length']) && ! empty($module_activation_data['list_sms_interval'])) ? ($this->returnDays($module_activation_data['list_sms_interval']) * $module_activation_data['list_sms_length']) : $addto;
            $addto_data['enable_sms_addto']            = (! empty($module_activation_data['enable_sms_length']) && ! empty($module_activation_data['enable_sms_interval'])) ? ($this->returnDays($module_activation_data['enable_sms_interval']) * $module_activation_data['enable_sms_length']) : $addto;
            $addto_data['notification_template_addto'] = (! empty($module_activation_data['notification_template_length']) && ! empty($module_activation_data['notification_template_interval'])) ? ($this->returnDays($module_activation_data['notification_template_interval']) * $module_activation_data['notification_template_length']) : $addto;

            // /////////////////////////////////////////////////////////////////////////////////////////////////
            $addto_data['products_addto']            = (! empty($module_activation_data['products_length']) && ! empty($module_activation_data['products_interval'])) ? ($this->returnDays($module_activation_data['products_interval']) * $module_activation_data['products_length']) : $addto;
            $addto_data['purchase_addto']            = (! empty($module_activation_data['purchase_length']) && ! empty($module_activation_data['purchase_interval'])) ? ($this->returnDays($module_activation_data['purchase_interval']) * $module_activation_data['purchase_length']) : $addto;
            $addto_data['stock_transfer_addto']      = (! empty($module_activation_data['stock_transfer_length']) && ! empty($module_activation_data['stock_transfer_interval'])) ? ($this->returnDays($module_activation_data['stock_transfer_interval']) * $module_activation_data['stock_transfer_length']) : $addto;
            $addto_data['daily_review_addto']        = (! empty($module_activation_data['daily_review_length']) && ! empty($module_activation_data['daily_review_interval'])) ? ($this->returnDays($module_activation_data['daily_review_interval']) * $module_activation_data['daily_review_length']) : $addto;
            $addto_data['service_staff_addto']       = (! empty($module_activation_data['service_staff_length']) && ! empty($module_activation_data['service_staff_interval'])) ? ($this->returnDays($module_activation_data['service_staff_interval']) * $module_activation_data['service_staff_length']) : $addto;
            $addto_data['enable_subscription_addto'] = (! empty($module_activation_data['enable_subscription_length']) && ! empty($module_activation_data['enable_subscription_interval'])) ? ($this->returnDays($module_activation_data['enable_subscription_interval']) * $module_activation_data['enable_subscription_length']) : $addto;

            $addto_data['distribution_module_addto'] = (! empty($module_activation_data['distribution_module_length']) && ! empty($module_activation_data['distribution_module_interval'])) ? ($this->returnDays($module_activation_data['distribution_module_interval']) * $module_activation_data['distribution_module_length']) : $addto;
            $addto_data['spreadsheet_addto']         = (! empty($module_activation_data['spreadsheet_length']) && ! empty($module_activation_data['spreadsheet_interval'])) ? ($this->returnDays($module_activation_data['spreadsheet_interval']) * $module_activation_data['spreadsheet_length']) : $addto;
            $addto_data['essentials_addto']          = (! empty($module_activation_data['essentials_length']) && ! empty($module_activation_data['essentials_interval'])) ? ($this->returnDays($module_activation_data['essentials_interval']) * $module_activation_data['essentials_length']) : $addto;

            $addto_data['price_changes_addto']     = (! empty($module_activation_data['price_changes_length']) && ! empty($module_activation_data['price_changes_interval'])) ? ($this->returnDays($module_activation_data['price_changes_interval']) * $module_activation_data['price_changes_length']) : $addto;
            $addto_data['day_end_addto']           = (! empty($module_activation_data['day_end_length']) && ! empty($module_activation_data['day_end_interval'])) ? ($this->returnDays($module_activation_data['day_end_interval']) * $module_activation_data['day_end_length']) : $addto;
            $addto_data['customer_interest_addto'] = (! empty($module_activation_data['customer_interest_length']) && ! empty($module_activation_data['customer_interest_interval'])) ? ($this->returnDays($module_activation_data['customer_interest_interval']) * $module_activation_data['customer_interest_length']) : $addto;

            $addto_data['issue_customer_bill_addto']     = (! empty($module_activation_data['issue_customer_bill_length']) && ! empty($module_activation_data['issue_customer_bill_interval'])) ? ($this->returnDays($module_activation_data['issue_customer_bill_interval']) * $module_activation_data['issue_customer_bill_length']) : $addto;
            $addto_data['issue_customer_bill_vat_addto'] = (! empty($module_activation_data['issue_customer_bill_vat_length']) && ! empty($module_activation_data['issue_customer_bill_vat_interval'])) ? ($this->returnDays($module_activation_data['issue_customer_bill_vat_interval']) * $module_activation_data['issue_customer_bill_vat_length']) : $addto;

            $addto_data['post_dated_addto'] = (! empty($module_activation_data['post_dated_length']) && ! empty($module_activation_data['post_dated_interval'])) ? ($this->returnDays($module_activation_data['post_dated_interval']) * $module_activation_data['post_dated_length']) : $addto;
            $addto_data['vat_addto']        = (! empty($module_activation_data['vat_length']) && ! empty($module_activation_data['vat_interval'])) ? ($this->returnDays($module_activation_data['vat_interval']) * $module_activation_data['vat_length']) : $addto;
            $addto_data['vat_main_addto']   = (! empty($module_activation_data['vat_main_length']) && ! empty($module_activation_data['vat_main_interval'])) ? ($this->returnDays($module_activation_data['vat_main_interval']) * $module_activation_data['vat_main_length']) : $addto;

            $addto_data['bakery_addto'] = (! empty($module_activation_data['bakery_length']) && ! empty($module_activation_data['bakery_interval'])) ? ($this->returnDays($module_activation_data['bakery_interval']) * $module_activation_data['bakery_length']) : $addto;

            $addto_data['subscriptions_addto'] = (! empty($module_activation_data['subscriptions_length']) && ! empty($module_activation_data['subscriptions_interval'])) ? ($this->returnDays($module_activation_data['subscriptions_interval']) * $module_activation_data['subscriptions_length']) : $addto;

            $addto_data['smsmodule_addto'] = (! empty($module_activation_data['smsmodule_length']) && ! empty($module_activation_data['smsmodule_interval'])) ? ($this->returnDays($module_activation_data['smsmodule_interval']) * $module_activation_data['smsmodule_length']) : $addto;

            // expiry dates
            $module_activation_data['mf_expiry_date']                = $request->mf_activated_on ? date('Y-m-d', strtotime("+{$addto_data['mf_addto']} day", strtotime($request->mf_activated_on))) : null;
            $module_activation_data['agent_expiry_date']             = $request->agent_activated_on ? date('Y-m-d', strtotime("+{$addto_data['agent_addto']} day", strtotime($request->agent_activated_on))) : null;
            $module_activation_data['petro_pd_expiry_date']          = $request->petro_pd_activated_on ? date('Y-m-d', strtotime("+{$addto_data['petro_pd_addto']} day", strtotime($request->petro_pd_activated_on))) : null;
            $module_activation_data['ev_charging_expiry_date']       = $request->ev_charging_activated_on ? date('Y-m-d', strtotime("+{$addto_data['ev_charging_addto']} day", strtotime($request->ev_charging_activated_on))) : null;
            $module_activation_data['sales_agent_expiry_date']       = $request->sales_agent_activated_on ? date('Y-m-d', strtotime("+{$addto_data['sales_agent_addto']} day", strtotime($request->sales_agent_activated_on))) : null;
            $module_activation_data['real_time_entries_expiry_date'] = $request->real_time_entries_activated_on ? date('Y-m-d', strtotime("+{$addto_data['real_time_entries_addto']} day", strtotime($request->real_time_entries_activated_on))) : null;
            $module_activation_data['sales_agent_expiry_date'] = $request->sales_agent_activated_on ? date('Y-m-d', strtotime("+{$addto_data['sales_agent_addto']} day", strtotime($request->sales_agent_activated_on))) : null;
            $module_activation_data['pos2_expiry_date'] = $request->pos2_activated_on ? date('Y-m-d', strtotime("+{$addto_data['pos2_addto']} day", strtotime($request->pos2_activated_on))) : null;
            $module_activation_data['stock_report_expiry_date'] = $request->stock_report_activated_on ? date('Y-m-d', strtotime("+{$addto_data['stock_report_addto']} day", strtotime($request->stock_report_activated_on))) : null;
            $module_activation_data['my_auto_expiry_date'] = $request->my_auto_activated_on ? date('Y-m-d', strtotime("+{$addto_data['my_auto_addto']} day", strtotime($request->my_auto_activated_on))) : null;
            $module_activation_data['membership_module_expiry_date'] = $request->membership_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['membership_module_addto']} day", strtotime($request->membership_module_activated_on))) : null;
            $module_activation_data['sa_expiry_date']                = $request->sa_activated_on ? date('Y-m-d', strtotime("+{$addto_data['sa_addto']} day", strtotime($request->sa_activated_on))) : null;
            $module_activation_data['ac_expiry_date']                = $request->ac_activated_on ? date('Y-m-d', strtotime("+{$addto_data['ac_addto']} day", strtotime($request->ac_activated_on))) : null;

            $module_activation_data['deposits_expiry_date'] = $request->deposits_activated_on ? date('Y-m-d', strtotime("+{$addto_data['deposits_addto']} day", strtotime($request->deposits_activated_on))) : null;

            $module_activation_data['crm_module_expiry_date']           = $request->crm_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['crm_addto']} day", strtotime($request->crm_module_activated_on))) : null;
            $module_activation_data['ezyinvoice_module_expiry_date']    = $request->ezyinvoice_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['ezyinvoice_addto']} day", strtotime($request->ezyinvoice_module_activated_on))) : null;
            $module_activation_data['airline_module_expiry_date']       = $request->airline_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['airline_addto']} day", strtotime($request->airline_module_activated_on))) : null;
            $module_activation_data['shipping_module_expiry_date']      = $request->shipping_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['shipping_addto']} day", strtotime($request->shipping_module_activated_on))) : null;
            $module_activation_data['asset_module_expiry_date']         = $request->asset_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['asset_addto']} day", strtotime($request->asset_module_activated_on))) : null;
            $module_activation_data['hms_module_expiry_date']           = $request->hms_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['hms_addto']} day", strtotime($request->hms_module_activated_on))) : null;
            $module_activation_data['settlement_sw_module_expiry_date'] = $request->settlement_sw_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['settlement_sw_addto']} day", strtotime($request->settlement_sw_module_activated_on))) : null;

            $module_activation_data['access_module_expiry_date'] = $request->access_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['access_module_addto']} day", strtotime($request->access_module_activated_on))) : null;
            $module_activation_data['hr_expiry_date']            = $request->hr_activated_on ? date('Y-m-d', strtotime("+{$addto_data['hr_addto']} day", strtotime($request->hr_activated_on))) : null;
            $module_activation_data['vreg_expiry_date']          = $request->vreg_activated_on ? date('Y-m-d', strtotime("+{$addto_data['vreg_addto']} day", strtotime($request->vreg_activated_on))) : null;
            $module_activation_data['petro_expiry_date']         = $request->petro_activated_on ? date('Y-m-d', strtotime("+{$addto_data['petro_addto']} day", strtotime($request->petro_activated_on))) : null;
            $module_activation_data['repair_expiry_date']        = $request->repair_activated_on ? date('Y-m-d', strtotime("+{$addto_data['repair_addto']} day", strtotime($request->repair_activated_on))) : null;
            $module_activation_data['fleet_expiry_date']         = $request->fleet_activated_on ? date('Y-m-d', strtotime("+{$addto_data['fleet_addto']} day", strtotime($request->fleet_activated_on))) : null;
            $module_activation_data['mpcs_expiry_date']          = $request->mpcs_activated_on ? date('Y-m-d', strtotime("+{$addto_data['mpcs_addto']} day", strtotime($request->mpcs_activated_on))) : null;
            $module_activation_data['backup_expiry_date']        = $request->backup_activated_on ? date('Y-m-d', strtotime("+{$addto_data['backup_addto']} day", strtotime($request->backup_activated_on))) : null;
            $module_activation_data['property_expiry_date']      = $request->property_activated_on ? date('Y-m-d', strtotime("+{$addto_data['property_addto']} day", strtotime($request->property_activated_on))) : null;
            $module_activation_data['auto_expiry_date']          = $request->auto_activated_on ? date('Y-m-d', strtotime("+{$addto_data['auto_addto']} day", strtotime($request->auto_activated_on))) : null;
            $module_activation_data['contact_expiry_date']       = $request->contact_activated_on ? date('Y-m-d', strtotime("+{$addto_data['contact_addto']} day", strtotime($request->contact_activated_on))) : null;
            $module_activation_data['ran_expiry_date']           = $request->ran_activated_on ? date('Y-m-d', strtotime("+{$addto_data['ran_addto']} day", strtotime($request->ran_activated_on))) : null;
            $module_activation_data['report_expiry_date']        = $request->report_activated_on ? date('Y-m-d', strtotime("+{$addto_data['report_addto']} day", strtotime($request->report_activated_on))) : null;
            // Modified by Engr. Alex -- task 7882: Issue 7 - Customized Reports expiry date
            $module_activation_data['customized_reports_expiry_date'] = $request->customized_reports_activated_on ? date('Y-m-d', strtotime("+{$addto_data['customized_reports_addto']} day", strtotime($request->customized_reports_activated_on))) : null;
            $module_activation_data['settings_expiry_date']      = $request->settings_activated_on ? date('Y-m-d', strtotime("+{$addto_data['settings_addto']} day", strtotime($request->settings_activated_on))) : null;
            $module_activation_data['um_expiry_date']            = $request->um_activated_on ? date('Y-m-d', strtotime("+{$addto_data['um_addto']} day", strtotime($request->um_activated_on))) : null;
            $module_activation_data['banking_expiry_date']       = $request->banking_activated_on ? date('Y-m-d', strtotime("+{$addto_data['banking_addto']} day", strtotime($request->banking_activated_on))) : null;
            $module_activation_data['sale_expiry_date']          = $request->sale_activated_on ? date('Y-m-d', strtotime("+{$addto_data['sale_addto']} day", strtotime($request->sale_activated_on))) : null;
            $module_activation_data['leads_expiry_date']         = $request->leads_activated_on ? date('Y-m-d', strtotime("+{$addto_data['leads_addto']} day", strtotime($request->leads_activated_on))) : null; // new modules added

            $module_activation_data['hospital_expiry_date'] = $request->hospital_activated_on ? date('Y-m-d', strtotime("+{$addto_data['hospital_addto']} day", strtotime($request->hospital_activated_on))) : null;

            $module_activation_data['restaurant_expiry_date'] = $request->restaurant_activated_on ? date('Y-m-d', strtotime("+{$addto_data['restaurant_addto']} day", strtotime($request->restaurant_activated_on))) : null;

            $module_activation_data['duplicate_invoice_expiry_date'] = $request->duplicate_invoice_activated_on ? date('Y-m-d', strtotime("+{$addto_data['duplicate_invoice_addto']} day", strtotime($request->duplicate_invoice_activated_on))) : null;

            $module_activation_data['tasks_expiry_date'] = $request->tasks_activated_on ? date('Y-m-d', strtotime("+{$addto_data['tasks_addto']} day", strtotime($request->tasks_activated_on))) : null;

            $module_activation_data['cheque_expiry_date'] = $request->cheque_activated_on ? date('Y-m-d', strtotime("+{$addto_data['cheque_addto']} day", strtotime($request->cheque_activated_on))) : null;

            $module_activation_data['list_easy_expiry_date'] = $request->list_easy_activated_on ? date('Y-m-d', strtotime("+{$addto_data['list_easy_addto']} day", strtotime($request->list_easy_activated_on))) : null;

            $module_activation_data['pump_expiry_date'] = $request->pump_activated_on ? date('Y-m-d', strtotime("+{$addto_data['pump_addto']} day", strtotime($request->pump_activated_on))) : null;

            $module_activation_data['stock_taking_expiry_date'] = $request->stock_taking_activated_on ? date('Y-m-d', strtotime("+{$addto_data['stock_taking_addto']} day", strtotime($request->stock_taking_activated_on))) : null;

            $module_activation_data['installment_module_expiry_date'] = $request->installment_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['installment_module_addto']} day", strtotime($request->installment_module_activated_on))) : null;
            $module_activation_data['patient_module_expiry_date'] = $request->patient_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['patient_module_addto']} day", strtotime($request->patient_module_activated_on))) : null;
            $module_activation_data['patient_test_module_expiry_date'] = $request->patient_test_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['patient_test_module_addto']} day", strtotime($request->patient_test_module_activated_on))) : null;

            // doc 6104
            $module_activation_data['list_sms_expiry_date']              = $request->list_sms_activated_on ? date('Y-m-d', strtotime("+{$addto_data['list_sms_addto']} day", strtotime($request->list_sms_activated_on))) : null;
            $module_activation_data['enable_sms_expiry_date']            = $request->enable_sms_activated_on ? date('Y-m-d', strtotime("+{$addto_data['enable_sms_addto']} day", strtotime($request->enable_sms_activated_on))) : null;
            $module_activation_data['notification_template_expiry_date'] = $request->notification_template_activated_on ? date('Y-m-d', strtotime("+{$addto_data['notification_template_addto']} day", strtotime($request->notification_template_activated_on))) : null;

            // //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            $module_activation_data['products_expiry_date']            = $request->products_activated_on ? date('Y-m-d', strtotime("+{$addto_data['products_addto']} day", strtotime($request->products_activated_on))) : null;
            $module_activation_data['purchase_expiry_date']            = $request->purchase_activated_on ? date('Y-m-d', strtotime("+{$addto_data['purchase_addto']} day", strtotime($request->purchase_activated_on))) : null;
            $module_activation_data['stock_transfer_expiry_date']      = $request->stock_transfer_activated_on ? date('Y-m-d', strtotime("+{$addto_data['stock_transfer_addto']} day", strtotime($request->stock_transfer_activated_on))) : null;
            $module_activation_data['daily_review_expiry_date']        = $request->daily_review_activated_on ? date('Y-m-d', strtotime("+{$addto_data['daily_review_addto']} day", strtotime($request->daily_review_activated_on))) : null;
            $module_activation_data['service_staff_expiry_date']       = $request->service_staff_activated_on ? date('Y-m-d', strtotime("+{$addto_data['service_staff_addto']} day", strtotime($request->service_staff_activated_on))) : null;
            $module_activation_data['enable_subscription_expiry_date'] = $request->enable_subscription_activated_on ? date('Y-m-d', strtotime("+{$addto_data['enable_subscription_addto']} day", strtotime($request->enable_subscription_activated_on))) : null;

            $module_activation_data['distribution_module_expiry_date'] = $request->distribution_module_activated_on ? date('Y-m-d', strtotime("+{$addto_data['distribution_module_addto']} day", strtotime($request->distribution_module_activated_on))) : null;
            $module_activation_data['spreadsheet_expiry_date']         = $request->spreadsheet_activated_on ? date('Y-m-d', strtotime("+{$addto_data['spreadsheet_addto']} day", strtotime($request->spreadsheet_activated_on))) : null;
            $module_activation_data['essentials_expiry_date']          = $request->essentials_activated_on ? date('Y-m-d', strtotime("+{$addto_data['essentials_addto']} day", strtotime($request->essentials_activated_on))) : null;

            $module_activation_data['price_changes_expiry_date']     = $request->price_changes_activated_on ? date('Y-m-d', strtotime("+{$addto_data['price_changes_addto']} day", strtotime($request->price_changes_activated_on))) : null;
            $module_activation_data['day_end_expiry_date']           = $request->day_end_activated_on ? date('Y-m-d', strtotime("+{$addto_data['day_end_addto']} day", strtotime($request->day_end_activated_on))) : null;
            $module_activation_data['customer_interest_expiry_date'] = $request->customer_interest_activated_on ? date('Y-m-d', strtotime("+{$addto_data['customer_interest_addto']} day", strtotime($request->customer_interest_activated_on))) : null;

            $module_activation_data['issue_customer_bill_expiry_date']     = $request->issue_customer_bill_activated_on ? date('Y-m-d', strtotime("+{$addto_data['issue_customer_bill_addto']} day", strtotime($request->issue_customer_bill_activated_on))) : null;
            $module_activation_data['issue_customer_bill_vat_expiry_date'] = $request->issue_customer_bill_vat_activated_on ? date('Y-m-d', strtotime("+{$addto_data['issue_customer_bill_vat_addto']} day", strtotime($request->issue_customer_bill_vat_activated_on))) : null;

            $module_activation_data['post_dated_expiry_date'] = $request->post_dated_activated_on ? date('Y-m-d', strtotime("+{$addto_data['post_dated_addto']} day", strtotime($request->post_dated_activated_on))) : null;

            $module_activation_data['vat_expiry_date'] = $request->vat_activated_on ? date('Y-m-d', strtotime("+{$addto_data['vat_addto']} day", strtotime($request->vat_activated_on))) : null;

            $module_activation_data['vat_main_expiry_date'] = $request->vat_main_activated_on ? date('Y-m-d', strtotime("+{$addto_data['vat_main_addto']} day", strtotime($request->vat_main_activated_on))) : null;

            $module_activation_data['bakery_expiry_date'] = $request->bakery_activated_on ? date('Y-m-d', strtotime("+{$addto_data['bakery_addto']} day", strtotime($request->bakery_activated_on))) : null;

            $module_activation_data['subscriptions_expiry_date'] = $request->subscriptions_activated_on ? date('Y-m-d', strtotime("+{$addto_data['subscriptions_addto']} day", strtotime($request->subscriptions_activated_on))) : null;

            $module_activation_data['smsmodule_expiry_date'] = $request->smsmodule_activated_on ? date('Y-m-d', strtotime("+{$addto_data['smsmodule_addto']} day", strtotime($request->smsmodule_activated_on))) : null;

            $module_activation_data['ezy_expiry_date'] = $request->ezy_activated_on ? date('Y-m-d', strtotime("+{$addto_data['ezy_addto']} day", strtotime($request->ezy_activated_on))) : null;

            // update module activation data
            if (! empty($subscription)) {
                $ma_data = Subscription::find($subscription->id);
                // dd($ma_data);
                $ma_data->module_activation_details         = json_encode($module_activation_data);
                $ma_data->customer_credit_notification_type = json_encode($request->customer_credit_notification_type);
                $ma_data->save();
                $tenantSyncPayload['subscription_id'] = (int) $ma_data->id;
            }

            // update account numbers
            $account_nos = ! empty($request->account_nos) ? $request->account_nos : [];

            if (! empty($account_nos)) {
                foreach ($account_nos as $key => $account_no) {
                    AccountNumber::where('id', $key)->update(['prefix' => $account_no['prefix'], 'account_number' => $account_no['account_number']]);
                }
            }

            $business_details = $request->only(['sms_settings']);
            if (! empty($business_details) && $request->input('access_sms_settings') == 1) {
                $business->fill($business_details);
                $business->save();
            }

            /*
             | IS2339 - Payment Options are deliberately NOT written here.
             |
             | This central Manage transaction may be running while another DB
             | connection is active. Updating BusinessLocation directly here is
             | what caused newly-added methods to disappear from the operational
             | Purchase/Expense forms. The exact submitted list is saved through
             | PaymentMethodDefaultsService immediately after DB::commit().
             */

            //dd($payments);
            //remove all previous accounting value for business
            if (! empty($request->zero_previous_accounting_values) && $request->zero_previous_accounting_values == 1) {
                $this->deleteAllPreviousAccountTransactions($id);
            }
            //hide accounts if superadmin not checked
            $business_accounts = Account::where('business_id', $id)->get();
            //            $accounts_enabled =  $request->accounts_enabled;
//            foreach ($business_accounts as $value) {
//                Account::where('id', $value->id)->update(['visible' => array_key_exists($value->id, $accounts_enabled) ? 1 : 0]);
//            }

            //set location of module permission allowed
            if (! empty($request->module_permission_location)) {
                Log::info('Module Permission Location Data:');
                Log::info($request->module_permission_location);
                foreach ($request->module_permission_location as $key => $prems) {
                    if (empty($key)) {
                        continue;
                    }

                    $module_permission_location_data = [
                        'business_id' => $id,
                        'module_name' => $key,
                        'locations'   => $prems,
                    ];

                    ModulePermissionLocation::updateOrCreate(['business_id' => $id, 'module_name' => $key], $module_permission_location_data);
                }
            }

            //save sale_import_date to business
            $sale_import_date     = $request->sale_import_date;
            $purchase_import_date = $request->purchase_import_date;
            $sale_date            = null;
            $purchase_date        = null;
            if (! empty($sale_import_date)) {
                $sale_date = \Carbon::parse($sale_import_date)->format('Y-m-d');
            }
            if (! empty($purchase_import_date)) {
                $purchase_date = \Carbon::parse($purchase_import_date)->format('Y-m-d');
            }
            Business::where('id', $id)->update(['sale_import_date' => $sale_date, 'purchase_import_date' => $purchase_date]);

            $moudle_permissions = Subscription::getBusinessPermissionsArray();

            $module_enable_price  = [];
            $manage_module_enable = [];
            $moudles              = $request->only($moudle_permissions);
            if (! empty($subscription)) {
                $package_details = $subscription->package_details;
            } else {
                $package_details = [];
            }
            // Do not log the complete Manage request: it may contain API/SMS
            // credentials and creates extremely large log entries.
            \Log::info('Preparing Manage module settings save', [
                'business_id' => (int) $id,
                'submitted_permission_count' => count($moudles),
                'available_permission_count' => count($moudle_permissions),
            ]);
            foreach ($moudle_permissions as $permission) {
                if (array_key_exists($permission, $moudles)) {
                    $permission_value = ! empty($moudles[$permission]) ? 1 : 0;

                    if ($permission_value) {                                                              //module has amount value
                        $module_enable_price[$permission]  = (float) $request->input($permission . '_value'); //input values
                        $manage_module_enable[$permission] = $permission_value;                               //checkboxes

                        $package_details[$permission]              = 1;
                        $package_details['manufacturing_module']   = ! empty($manage_module_enable['mf_module']) ? $manage_module_enable['mf_module'] : 0;
                        $package_details['agent_module']           = ! empty($manage_module_enable['agent_module']) ? $manage_module_enable['agent_module'] : 0;
                        $package_details['enable_petro_module']    = 1;
                        $package_details['real_time_entries']      = ! empty($manage_module_enable['real_time_entries']) ? $manage_module_enable['real_time_entries'] : 0;
                        $package_details['sales_agent_module']      = ! empty($manage_module_enable['sales_agent_module']) ? $manage_module_enable['sales_agent_module'] : 0;
                        $package_details['pos2']      = ! empty($manage_module_enable['pos2']) ? $manage_module_enable['pos2'] : 0;
                        $package_details['stock_report']      = ! empty($manage_module_enable['stock_report']) ? $manage_module_enable['stock_report'] : 0;
                        $package_details['my_auto']      = ! empty($manage_module_enable['my_auto']) ? $manage_module_enable['my_auto'] : 0;
                        $package_details['stock_adjustment']       = ! empty($manage_module_enable['stock_adjustment']) ? $manage_module_enable['stock_adjustment'] : 0;
                        $package_details['list_credit_sales_page'] = ! empty($manage_module_enable['list_credit_sales_page']) ? $manage_module_enable['list_credit_sales_page'] : 0;
                        $package_details['membership_module']      = ! empty($manage_module_enable['membership_module']) ? $manage_module_enable['membership_module'] : 0;
                        $package_details['suppliers_module']       = ! empty($manage_module_enable['suppliers_module']) ? $manage_module_enable['suppliers_module'] : 0;

                        if (! empty($subscription)) {
                            Subscription::where('business_id', $id)->update(['package_details->' . $permission => 1]);
                        }
                    } else {
                        $manage_module_enable[$permission] = 0;
                        $package_details[$permission]      = 0;

                        if (! empty($subscription)) {
                            Subscription::where('business_id', $id)->update(['package_details->' . $permission => 0]);
                        }
                    }
                } else {
                    // Only set to 0 if the permission doesn't already exist in package_details.
                    // This prevents newly added permissions from overwriting existing saved values on code updates.
                    if (! empty($subscription)) {
                        $existing = $subscription->package_details;
                        if (! isset($existing[$permission])) {
                            $package_details[$permission] = 0;
                            Subscription::where('business_id', $id)->update(['package_details->' . $permission => 0]);
                        } elseif (in_array($permission, ['loan_module', 'patient_module', 'patient_test_module', 'pump_operator_dashboard'])) {
                            $package_details[$permission] = 0;
                            Subscription::where('business_id', $id)->update(['package_details->' . $permission => 0]);
                        }
                    }
                }
            }


            // Leads-New standalone module flags. These are stored in package_details
            // so Super Admin / All Businesses / Manage controls both sidebar visibility
            // and direct /leads-new route access. Hidden inputs in the Manage page send
            // 0 by default, so the module remains disabled unless explicitly enabled.
            foreach (['leads_new_module', 'leads_new_dashboard', 'leads_new_leads', 'leads_new_reports', 'leads_new_settings'] as $leadsNewPermission) {
                $leadsNewValue = (int) $request->input($leadsNewPermission, 0);
                $manage_module_enable[$leadsNewPermission] = $leadsNewValue;
                $package_details[$leadsNewPermission] = $leadsNewValue;
                if (! empty($subscription)) {
                    Subscription::where('business_id', $id)->update(['package_details->' . $leadsNewPermission => $leadsNewValue]);
                }
            }
            $package_details['enable_leads_new'] = !empty($package_details['leads_new_module']) ? 1 : 0;
            $package_details['leadsnew_module'] = !empty($package_details['leads_new_module']) ? 1 : 0;


            // Suppliers standalone module flags. Keep these outside the generic permission loop too,
            // because older subscriptions may not contain the new keys yet. Hidden inputs in the
            // Manage page send 0, so the sidebar and page links always follow the latest settings.
            $supplierPermissions = [
                'suppliers_module',
                'suppliers_dashboard',
                'suppliers_all_suppliers',
                'suppliers_add_supplier',
                'suppliers_contact_group',
                'suppliers_import_contacts',
                'suppliers_product_mappings',
                'suppliers_payments',
                'suppliers_purchase_history',
                'suppliers_ledger',
                'suppliers_statement',
                'suppliers_aging',
                'suppliers_stock_report',
                'suppliers_issue_payment_details',
                'suppliers_user_activity',
                'suppliers_reports',
                'suppliers_settings',
                // Contact module supplier features that are now surfaced under Suppliers Module.
                'contact_supplier',
                'contact_group_supplier',
                'contact_list_supplier_map_products',
                'contact_add_supplier_products',
            ];

            // Suppliers activation dates are calculated from Activated On + Duration.
            // This prevents the Manage page from showing a blank Expiry Date when the hidden/readonly
            // expiry input is not posted by older browser/script versions.
            $supplierActivatedOn = $request->input('suppliers_activated_on') ?: ($manage_module_enable['suppliers_activated_on'] ?? date('Y-m-d'));
            $supplierInterval = $request->input('suppliers_interval') ?: ($manage_module_enable['suppliers_interval'] ?? 'Years');
            $supplierLength = (int) ($request->input('suppliers_length') ?: ($manage_module_enable['suppliers_length'] ?? 1));
            $supplierLength = $supplierLength > 0 ? $supplierLength : 1;
            try {
                $supplierExpiryDateCarbon = \Carbon\Carbon::parse($supplierActivatedOn);
                if ($supplierInterval === 'Months') {
                    $supplierExpiryDateCarbon->addMonths($supplierLength);
                } elseif ($supplierInterval === 'Days') {
                    $supplierExpiryDateCarbon->addDays($supplierLength);
                } else {
                    $supplierExpiryDateCarbon->addYears($supplierLength);
                }
                $supplierExpiryDate = $supplierExpiryDateCarbon->format('Y-m-d');
            } catch (\Throwable $supplierDateException) {
                $supplierActivatedOn = date('Y-m-d');
                $supplierExpiryDate = \Carbon\Carbon::parse($supplierActivatedOn)->addYears($supplierLength)->format('Y-m-d');
            }

            $supplierActivationValues = [
                'suppliers_interval' => $supplierInterval,
                'suppliers_length' => $supplierLength,
                'suppliers_activated_on' => $supplierActivatedOn,
                'suppliers_expiry_date' => $supplierExpiryDate,
            ];

            foreach ($supplierActivationValues as $supplierActivationField => $supplierActivationValue) {
                $manage_module_enable[$supplierActivationField] = $supplierActivationValue;
                $package_details[$supplierActivationField] = $supplierActivationValue;
                if (! empty($subscription)) {
                    Subscription::where('business_id', $id)->update(['package_details->' . $supplierActivationField => $supplierActivationValue]);
                }
            }

            foreach ($supplierPermissions as $supplierPermission) {
                $supplierValue = (int) $request->input($supplierPermission, 0);
                $manage_module_enable[$supplierPermission] = $supplierValue;
                $package_details[$supplierPermission] = $supplierValue;
                if (! empty($subscription)) {
                    Subscription::where('business_id', $id)->update(['package_details->' . $supplierPermission => $supplierValue]);
                }
            }

            // The standalone Suppliers module is intentionally independent from
            // Contact Module. Do not auto-enable legacy Contact/Supplier permissions
            // when the Suppliers parent permission is saved.

            $supplierQuickNotificationType = (array) $request->input('supplier_quick_notification_type', []);
            $supplierQuickNotificationType = array_values(array_filter($supplierQuickNotificationType));
            $manage_module_enable['supplier_quick_notification_type'] = $supplierQuickNotificationType;
            $package_details['supplier_quick_notification_type'] = $supplierQuickNotificationType;
            // Do not JSON_SET this array field directly. It is saved in the final full package_details update below.

            Log::info('Manage package details prepared', [
                'business_id' => (int) $id,
                'setting_count' => count($package_details),
            ]);

            $other_permissions_array = [
                'purchase',
                'stock_transfer',
                'service_staff',
                'enable_subscription',
                'enable_sale_cmsn_agent',
                'add_sale',
                'stock_adjustment',
                'tables',
                'type_of_service',
                'pos_sale',
                'expenses',
                'modifiers',
                'kitchen',
                'customer_interest_deduct_option',
                'allowance_deduction',
                'work_shift',
                'essentials_todo',
                'essentials_document',
                'essentials_memos',
                'essentials_reminders',
                'essentials_messages',
                'essentials_settings',
                'petro_dashboard',
                'petro_daily_status',
                'tank_transfer',
                'petro_task_management',
                'pump_management',
                'pump_management_testing',
                'meter_resetting',
                'meter_reading',
                'pump_dashboard_opening',
                'pumper_dashboard_settings',
                'pumper_management',
                'daily_collection',
                'daily_collection_sw',
                'settlement',
                'list_settlement',
                'settlement_pd',
                'list_settlement_pd',
                'delete_settlement',
                'dip_management',
                'fuel_tanks_edit',
                'fuel_tanks_delete',
                'pumps_edit',
                'pumps_delete',
                'contact_supplier',
                'contact_customer',
                'contact_group_customer',
                'contact_group_supplier',
                'import_contact',
                'customer_reference',
                'customer_statement',
                'customer_payment',
                'outstanding_received',
                'stock_taking_page',
                'issue_payment_detail',
                'edit_received_outstanding',
                'product_report',
                'payment_status_report',
                'report_daily',
                'report_daily_summary',
                'report_register',
                'report_profit_loss',
                'report_credit_status',
                'activity_report',
                'contact_report',
                'trending_product',
                'user_activity',
                'report_verification',
                'report_table',
                'report_staff_service',
                'helpguide',
                'customers_module',
                'customers_import_contact_tab_page',
                'customers_customer_reference_tab_page',
                'customers_customer_statement_tab_page',
                'customers_customer_payment_tab_page',
                'customers_outstanding_received_tab_page',
                'customers_stock_taking_page',
                'customers_edit_received_outstanding',
                'customers_customer_payment_bulk',
                'customers_list_customer_payments',
                'customers_customer_interest',
                'customers_interest_settings',
                'customers_ledger_discount',
                'customers_customer_statements_pmts',
                'customers_list_customer_loans',
                'customers_settings',
                'customers_import_opening_balances',
                'customers_returned_cheque_details',
                'customers_manual_bills',
                'pos_button_on_top_belt',
                'all_purchase',
                'add_purchase',
                'add_bulk_purchase',
                'import_purchase',
                'pop_button_on_top_belt',
                'purchase_return',
                'purchase_discounts',
                'cheque_templates',
                'write_cheque',
                'manage_stamps',
                'manage_payee',
                'cheque_number_list',
                'deleted_cheque_details',
                'printed_cheque_details',
                'duplicate_slip_numbers',
                'default_setting',
                'same_order_no',
                'acc_no_manually',
                'fixed_assets',
                'stock_taking',
                'fixed_conversion',
                'list_realized_cheques',
                'same_order_no_daily_collection',
                'daily_shift_page',
                'daily_cash_tab',
                'daily_credit_sales',
                'daily_cards',
                'daily_shortage_excess',
                'daily_cheques',
                'daily_other_payments',
                'collection_summary',
                'daily_collections_settings',
                'daily_collections_settings_setting',
                'daily_cash_status',
                'daily_shift',
                'settlement_sw_cash',
                'settlement_sw_credit',
                'settlement_sw_cash_deposit',
                'settlement_sw_cards',
                'settlement_sw_cheque',
                'settlement_sw_payment_expenses',
                'settlement_sw_shortage',
                'settlement_sw_excess',
                'settlement_sw_credit_sales',
                'settlement_sw_loan_payments',
                'settlement_sw_owners_drawings',
                'settlement_sw_loan_to_customer',

                'fleet_settings',
                'add_trip_operations',
                'list_fleet',
                'milage_changes',
                'list_trip_operations',
                'fleet_invoices',
                'fuel_management',
                'fleet_p_l',
                'edit_ob',

                'dip_resetting',
                'edit_settlement',
                'customer_payment_simple',
                'customer_payment_bulk',
                'list_customer_payments',
                'customer_interest',
                'interest_settings',
                'enable_sale_cmsn_agent',
                'do_not_show_delete_button',
                'dashboard_logistics',

                // Petro Module Additional Pages
                'petro_activity_report',
                'day_end_settlement',
                'petro_whatsapp',
                'blocked_pump_operators',
                'tanks_transaction_details',
                'tanks_transaction_summary',
                'customer_bill_vat_prefix',
                'petro_notification_template',
                'disable_shift_no_direct_settlement',

                // VAT Module Main Sub-permissions
                'vat_main_invoice',
                'vat_main_invoice2',
                'vat_main_report',
                'vat_main_report_ledger',
                'vat_main_settings',
                'vat_main_statement',
                'vat_main_schedule',
                'vat_main_import_contacts',
                'vat_main_print_2026',
                'vat_main_credit_bill',
                'vat_main_sale',
                'vat_main_list_vat_sale',
                'vat_main_purchase',
                'vat_main_list_vat_purchase',
                'vat_main_expense',
                'vat_main_list_vat_expense',
                'vat_main_products',
                'vat_main_contacts',
                'vat_main_meter_sales',
                'vat_main_customized_invoices',
                'vat_main_fleet_invoices',
                'vat_main_linked_accounts',
                'vat_main_delete_customer_statement',
                'vat_main_delete_statement_payments',
                'vat_main_enabled_126_statement',

                // Distribution Module Sub-permissions
                'distribution_invoices',
                'distribution_loadings',
                'distribution_daily_summary',
                'distribution_vehicles',
                'distribution_routes',
                'distribution_agents',
                'distribution_settings',
                'distribution_reports',
                'distribution_dashboard',
                'distribution_vehicle_meters',
                'distribution_show_date_picker',
                'distribution_auto_date_time',

                'distribution_free_issues',
                'my_auto_agent_login',
                'vat_dis_invoice',

                'loan_show_contact_type',

                'tank_dip_chart',
                'edit_settlement_no_change',
                'allow_duplicate_order_numbers',
                'petro_pd_settings',

            ];
            $other_permissions = [];

            foreach ($other_permissions_array as $value) {
                if (! empty($request->$value)) {
                    $other_permissions[$value] = 1;
                } else {
                    $other_permissions[$value] = 0;
                }
            }

            // Handle distribution_free and distribution_free_bottles as Yes/No values
$other_permissions['distribution_free'] = $request->input('distribution_free', 'No');
$other_permissions['distribution_free_bottles'] = $request->input('distribution_free_bottles', 'No');

            //upadting other permission for current company
            if (! empty($subscription)) {
                $subscription = Subscription::where('id', $subscription->id)->select('id', 'package_details')->first();

                $package_details = $subscription->package_details;
                foreach ($other_permissions_array as $value) {
                    $package_details[$value] = $other_permissions[$value];
                }
                //updating these new value to subscription
                $package_details['location_count'] = isset($request->location_count) ? $request->location_count : 1;
                $package_details['register_count'] = $request->register_count;
                $package_details['product_count']  = isset($request->product_count) ? $request->product_count : 1;
                $package_details['vehicle_count']  = isset($request->vehicle_count) ? $request->vehicle_count : 1;
                $package_details['category_count'] = isset($request->category_count) ? $request->category_count : 0;

                $package_details['reminder_phone']     = isset($request->reminder_phone) ? json_encode($request->reminder_phone) : json_encode([]);
                $package_details['first_reminder']     = $request->first_reminder;
                $package_details['second_reminder']    = $request->second_reminder;
                $package_details['third_reminder']     = $request->third_reminder;
                $package_details['message_content']    = $request->message_content;
                $package_details['vat_effective_date'] = $request->vat_effective_date;

                $package_details['post_dated_cheques_effective_date'] = $request->post_dated_cheques_effective_date;
                $package_details['whatsapp_phone_no']                 = $request->whatsapp_phone_no;
                $package_details['petro_settlement']                  = isset($request->petro_settlement) ? $request->petro_settlement : 0;
                $package_details['rename_cash_tab']                   = isset($request->rename_cash_tab) ? $request->rename_cash_tab : 0;
                $package_details['only_walkin']                       = isset($request->only_walkin) ? $request->only_walkin : 0;
                $package_details['notsubscribed_message_content']     = $request->notsubscribed_message_content;
                $package_details['ns_deposit_module']                 = isset($request->ns_deposit_module) ? $request->ns_deposit_module : 0;
                $package_details['ns_asset_management']               = isset($request->ns_asset_management) ? $request->ns_asset_management : 0;
                $package_details['ns_vat_module']                     = isset($request->ns_vat_module) ? $request->ns_vat_module : 0;
                $package_details['ns_discount_module']                = isset($request->ns_discount_module) ? $request->ns_discount_module : 0;
                $package_details['ns_dsr_module']                     = isset($request->ns_dsr_module) ? $request->ns_dsr_module : 0;

                $package_details['ns_cash']                    = isset($request->ns_cash) ? $request->ns_cash : 0;
                $package_details['ns_cash_deposit']            = isset($request->ns_cash_deposit) ? $request->ns_cash_deposit : 0;
                $package_details['ns_cards']                   = isset($request->ns_cards) ? $request->ns_cards : 0;
                $package_details['ns_cheques']                 = isset($request->ns_cheques) ? $request->ns_cheques : 0;
                $package_details['ns_expenses']                = isset($request->ns_expenses) ? $request->ns_expenses : 0;
                $package_details['ns_shortage']                = isset($request->ns_shortage) ? $request->ns_shortage : 0;
                $package_details['ns_petro_sms_notifications'] = isset($request->ns_petro_sms_notifications) ? $request->ns_petro_sms_notifications : 0;

                $package_details['ns_excess']           = isset($request->ns_excess) ? $request->ns_excess : 0;
                $package_details['ns_credit_sales']     = isset($request->ns_credit_sales) ? $request->ns_credit_sales : 0;
                $package_details['prefill_credit_sale_details'] = isset($request->prefill_credit_sale_details) ? $request->prefill_credit_sale_details : 0;
                $package_details['ns_loan_payments']    = isset($request->ns_loan_payments) ? $request->ns_loan_payments : 0;
                $package_details['ns_drawing_payments'] = isset($request->ns_drawing_payments) ? $request->ns_drawing_payments : 0;
                $package_details['ns_customer_loans']   = isset($request->ns_customer_loans) ? $request->ns_customer_loans : 0;

                $package_details['allowed_tanks']       = isset($request->allowed_tanks) ? $request->allowed_tanks : 1;
                $package_details['room_subscribe']      = isset($request->room_subscribe) ? $request->room_subscribe : 1;
                $package_details['room_added']          = isset($request->room_added) ? $request->room_added : 1;
                $package_details['room_could_be_added'] = isset($request->room_could_be_added) ? $request->room_could_be_added : 1;
                $package_details['ns_font_family']      = ! empty($request->ns_font_family) ? $request->ns_font_family : "Calibri,san serif";
                $package_details['ns_font_size']        = ! empty($request->ns_font_size) ? $request->ns_font_size : "12";
                $package_details['ns_font_color']       = ! empty($request->ns_font_color) ? $request->ns_font_color : "#000000";
                $package_details['ns_background_color'] = ! empty($request->ns_background_color) ? $request->ns_background_color : "#FFFFFF";

                $package_details['daily_review']                       = ! empty($request->daily_review) ? $request->daily_review : 0;
                $package_details['settlement_sw_other_income']         = ! empty($request->settlement_sw_other_income) ? $request->settlement_sw_other_income : 0;
                $package_details['settlement_sw_customer_payments']    = ! empty($request->settlement_sw_customer_payments) ? $request->settlement_sw_customer_payments : 0;
                $package_details['settlement_sw_expenses']             = ! empty($request->settlement_sw_expenses) ? $request->settlement_sw_expenses : 0;
                $package_details['select_pump_operator_in_settlement'] = ! empty($request->select_pump_operator_in_settlement) ? $request->select_pump_operator_in_settlement : 0;
                $package_details['show_mechanical_meter'] = $request->has('show_mechanical_meter') ? (int) $request->show_mechanical_meter : 1;
                $package_details['settlement_sw_payment']              = ! empty($request->settlement_sw_payment) ? $request->settlement_sw_payment : 0;
                $package_details['sw_credit_sales']                    = ! empty($request->sw_credit_sales) ? $request->sw_credit_sales : 0;
                $package_details['show_pump_operators_when_shifts_pending_in_settlement'] = ! empty($request->show_pump_operators_when_shifts_pending_in_settlement) ? 1 : 0;

                $package_details['daily_review']                       = ! empty($request->daily_review) ? $request->daily_review : 0;
                $package_details['settlement_sw_other_income']         = ! empty($request->settlement_sw_other_income) ? $request->settlement_sw_other_income : 0;
                $package_details['settlement_sw_customer_payments']    = ! empty($request->settlement_sw_customer_payments) ? $request->settlement_sw_customer_payments : 0;
                $package_details['settlement_sw_expenses']             = ! empty($request->settlement_sw_expenses) ? $request->settlement_sw_expenses : 0;
                $package_details['select_pump_operator_in_settlement'] = ! empty($request->select_pump_operator_in_settlement) ? $request->select_pump_operator_in_settlement : 0;
                $package_details['show_mechanical_meter'] = $request->has('show_mechanical_meter') ? (int) $request->show_mechanical_meter : 1;
                $package_details['settlement_sw_payment']              = ! empty($request->settlement_sw_payment) ? $request->settlement_sw_payment : 0;
                $package_details['sw_credit_sales']                    = ! empty($request->sw_credit_sales) ? $request->sw_credit_sales : 0;
                $package_details['daily_shift']                        = isset($request->daily_shift) ? (int) $request->daily_shift : 0;
                $package_details['daily_cash_status']                  = isset($request->daily_cash_status) ? (int) $request->daily_cash_status : 0;
                $package_details['daily_collections_settings']         = isset($request->daily_collections_settings) ? (int) $request->daily_collections_settings : 0;
                $package_details['daily_collections_settings_setting'] = isset($request->daily_collections_settings_setting) ? (int) $request->daily_collections_settings_setting : 0;
                $package_details['collection_summary']                 = isset($request->collection_summary) ? (int) $request->collection_summary : 0;
                $package_details['daily_cheques']                      = isset($request->daily_cheques) ? (int) $request->daily_cheques : 0;
                $package_details['daily_shortage_excess']              = isset($request->daily_shortage_excess) ? (int) $request->daily_shortage_excess : 0;
                $package_details['daily_cards']                        = isset($request->daily_cards) ? (int) $request->daily_cards : 0;
                $package_details['daily_credit_sales']                 = isset($request->daily_credit_sales) ? (int) $request->daily_credit_sales : 0;
                $package_details['daily_other_payments']               = isset($request->daily_other_payments) ? (int) $request->daily_other_payments : 0;
                $package_details['daily_cash_tab']                     = isset($request->daily_cash_tab) ? (int) $request->daily_cash_tab : 0;

                // IS2209: do not mirror these legacy Daily Collection switches
                // into standalone SW Payments keys. They are separate features;
                // copying them can hide every SW Payments tab for businesses
                // whose old Daily Collection options are disabled.

                $package_details['daily_shift_page']                   = isset($request->daily_shift_page) ? (int) $request->daily_shift_page : 0;
                $package_details['same_order_no_daily_collection']     = isset($request->same_order_no_daily_collection) ? (int) $request->same_order_no_daily_collection : 0;
                $package_details['list_settlement_pd']                 = ! empty($request->list_settlement_pd) ? $request->list_settlement_pd : 0;
                $package_details['settlement_pd']                      = ! empty($request->settlement_pd) ? $request->settlement_pd : 0;
                $package_details['pumper_dashboard_settings']           = ! empty($request->pumper_dashboard_settings) ? $request->pumper_dashboard_settings : 0;


                $package_details['distribution_free'] = $request->input('distribution_free', 'No');
$package_details['distribution_free_bottles'] = $request->input('distribution_free_bottles', 'No');
                // Petro PD module individual page permissions
                $package_details['petro_pd_petro_settlement']                    = ! empty($request->petro_pd_petro_settlement) ? $request->petro_pd_petro_settlement : 0;
                $package_details['petro_pd_petro_sms_notifications']             = ! empty($request->petro_pd_petro_sms_notifications) ? $request->petro_pd_petro_sms_notifications : 0;
                $package_details['petro_pd_edit_settlement_date']                = ! empty($request->petro_pd_edit_settlement_date) ? $request->petro_pd_edit_settlement_date : 0;
                $package_details['petro_pd_rename_cash_tab']                     = ! empty($request->petro_pd_rename_cash_tab) ? $request->petro_pd_rename_cash_tab : 0;
                $package_details['petro_pd_only_walkin']                         = ! empty($request->petro_pd_only_walkin) ? $request->petro_pd_only_walkin : 0;
                $package_details['petro_pd_petro_daily_status']                  = ! empty($request->petro_pd_petro_daily_status) ? $request->petro_pd_petro_daily_status : 0;
                $package_details['petro_pd_tank_transfer']                       = ! empty($request->petro_pd_tank_transfer) ? $request->petro_pd_tank_transfer : 0;
                $package_details['petro_pd_petro_dashboard']                     = ! empty($request->petro_pd_petro_dashboard) ? $request->petro_pd_petro_dashboard : 0;
                $package_details['petro_pd_petro_task_management']               = ! empty($request->petro_pd_petro_task_management) ? $request->petro_pd_petro_task_management : 0;
                $package_details['petro_pd_pump_management']                     = ! empty($request->petro_pd_pump_management) ? $request->petro_pd_pump_management : 0;
                $package_details['petro_pd_pump_management_testing']             = ! empty($request->petro_pd_pump_management_testing) ? $request->petro_pd_pump_management_testing : 0;
                $package_details['petro_pd_meter_resetting']                     = ! empty($request->petro_pd_meter_resetting) ? $request->petro_pd_meter_resetting : 0;
                $package_details['petro_pd_meter_reading']                       = ! empty($request->petro_pd_meter_reading) ? $request->petro_pd_meter_reading : 0;
                $package_details['petro_pd_pump_dashboard_opening']              = ! empty($request->petro_pd_pump_dashboard_opening) ? $request->petro_pd_pump_dashboard_opening : 0;
                $package_details['petro_pd_pumper_management']                   = ! empty($request->petro_pd_pumper_management) ? $request->petro_pd_pumper_management : 0;
                $package_details['petro_pd_daily_collection']                    = ! empty($request->petro_pd_daily_collection) ? $request->petro_pd_daily_collection : 0;
                $package_details['petro_pd_settlement']                          = ! empty($request->petro_pd_settlement) ? $request->petro_pd_settlement : 0;
                $package_details['petro_pd_list_settlement']                     = ! empty($request->petro_pd_list_settlement) ? $request->petro_pd_list_settlement : 0;
                $package_details['petro_pd_delete_settlement']                   = ! empty($request->petro_pd_delete_settlement) ? $request->petro_pd_delete_settlement : 0;
                $package_details['petro_pd_dip_management']                      = ! empty($request->petro_pd_dip_management) ? $request->petro_pd_dip_management : 0;
                $package_details['petro_pd_fuel_tanks_edit']                     = ! empty($request->petro_pd_fuel_tanks_edit) ? $request->petro_pd_fuel_tanks_edit : 0;
                $package_details['petro_pd_fuel_tanks_delete']                   = ! empty($request->petro_pd_fuel_tanks_delete) ? $request->petro_pd_fuel_tanks_delete : 0;
                $package_details['petro_pd_pumps_edit']                          = ! empty($request->petro_pd_pumps_edit) ? $request->petro_pd_pumps_edit : 0;
                $package_details['petro_pd_pumps_delete']                        = ! empty($request->petro_pd_pumps_delete) ? $request->petro_pd_pumps_delete : 0;
                $package_details['petro_pd_pay_excess_commission']               = ! empty($request->petro_pd_pay_excess_commission) ? $request->petro_pd_pay_excess_commission : 0;
                $package_details['petro_pd_recover_shortage']                    = ! empty($request->petro_pd_recover_shortage) ? $request->petro_pd_recover_shortage : 0;
                $package_details['petro_pd_pump_operator_ledger']                = ! empty($request->petro_pd_pump_operator_ledger) ? $request->petro_pd_pump_operator_ledger : 0;
                $package_details['petro_pd_commission_type']                     = ! empty($request->petro_pd_commission_type) ? $request->petro_pd_commission_type : 0;
                $package_details['petro_pd_select_pump_operator_in_settlement']  = ! empty($request->petro_pd_select_pump_operator_in_settlement) ? $request->petro_pd_select_pump_operator_in_settlement : 0;
                $package_details['petro_pd_edit_settlement']                     = ! empty($request->petro_pd_edit_settlement) ? $request->petro_pd_edit_settlement : 0;
                $package_details['petro_pd_dip_resetting']                       = ! empty($request->petro_pd_dip_resetting) ? $request->petro_pd_dip_resetting : 0;
                $package_details['petro_pd_tank_dip_chart']                      = ! empty($request->tank_dip_chart) ? 1 : 0;
                $package_details['petro_pd_edit_settlement_no_change']           = ! empty($request->edit_settlement_no_change) ? 1 : 0;
                $package_details['petro_pd_allow_duplicate_order_numbers']       = ! empty($request->allow_duplicate_order_numbers) ? 1 : 0;
                $package_details['petro_pd_settlement_pd']                       = ! empty($request->petro_pd_settlement_pd) ? $request->petro_pd_settlement_pd : 0;
                $package_details['petro_pd_list_settlement_pd']                  = ! empty($request->petro_pd_list_settlement_pd) ? $request->petro_pd_list_settlement_pd : 0;
                $package_details['petro_pd_list_tank_transfer']                  = ! empty($request->petro_pd_list_tank_transfer) ? $request->petro_pd_list_tank_transfer : 0;
                $package_details['petro_pd_daily_collection_sw']                 = ! empty($request->petro_pd_daily_collection_sw) ? $request->petro_pd_daily_collection_sw : 0;
                $package_details['petro_pd_petro_activity_report']               = ! empty($request->petro_pd_petro_activity_report) ? $request->petro_pd_petro_activity_report : 0;
                $package_details['petro_pd_day_end_settlement']                  = ! empty($request->petro_pd_day_end_settlement) ? $request->petro_pd_day_end_settlement : 0;
                $package_details['petro_pd_petro_whatsapp']                      = ! empty($request->petro_pd_petro_whatsapp) ? $request->petro_pd_petro_whatsapp : 0;
                $package_details['petro_pd_blocked_pump_operators']              = ! empty($request->petro_pd_blocked_pump_operators) ? $request->petro_pd_blocked_pump_operators : 0;
                $package_details['petro_pd_tanks_transaction_details']           = ! empty($request->petro_pd_tanks_transaction_details) ? $request->petro_pd_tanks_transaction_details : 0;
                $package_details['petro_pd_tanks_transaction_summary']           = ! empty($request->petro_pd_tanks_transaction_summary) ? $request->petro_pd_tanks_transaction_summary : 0;
                $package_details['petro_pd_customer_bill_vat_prefix']            = ! empty($request->petro_pd_customer_bill_vat_prefix) ? $request->petro_pd_customer_bill_vat_prefix : 0;
                $package_details['petro_pd_petro_notification_template']         = ! empty($request->petro_pd_petro_notification_template) ? $request->petro_pd_petro_notification_template : 0;
                $package_details['petro_pd_disable_shift_no_direct_settlement']  = ! empty($request->petro_pd_disable_shift_no_direct_settlement) ? $request->petro_pd_disable_shift_no_direct_settlement : 0;
                $package_details['petro_pd_pd_settlement']                       = ! empty($request->petro_pd_pd_settlement) ? $request->petro_pd_pd_settlement : 0;
                $package_details['petro_pd_pd_operators']                        = ! empty($request->petro_pd_pd_operators) ? $request->petro_pd_pd_operators : 0;
                $package_details['petro_pd_user_activity']                       = ! empty($request->petro_pd_user_activity) ? $request->petro_pd_user_activity : 0;
                $package_details['petro_pd_list_pd_settlement']                  = ! empty($request->petro_pd_list_pd_settlement) ? $request->petro_pd_list_pd_settlement : 0;
                $package_details['petro_pd_settings']                            = ! empty($request->petro_pd_settings) ? $request->petro_pd_settings : 0;

                // URGENT-005: keep PetroPD Superadmin enable flags compatible with the
                // standalone PetroPD sidebar and old Petro/PumperDashboard checks.
                if (! empty($request->petro_pd_module) || ! empty($package_details['petro_pd_module'])) {
                    $package_details['petro_pd_module'] = 1;
                    $package_details['petropd_module'] = 1;
                    $package_details['enable_petro_pd_module'] = 1;

                    // Do not hide all child menus when older Manage Business pages do not
                    // submit the new PetroPD child checkboxes. If a child key already exists
                    // from the form, respect that value; otherwise default core pages to 1.
                    foreach ([
                        'petro_pd_pd_settlement',
                        'petro_pd_pd_operators',
                        'petro_pd_user_activity',
                        'petro_pd_list_pd_settlement',
                    ] as $petroPdCoreKey) {
                        if (! array_key_exists($petroPdCoreKey, $package_details)) {
                            $package_details[$petroPdCoreKey] = 1;
                        }
                    }
                } else {
                    $package_details['petro_pd_module'] = 0;
                    $package_details['petropd_module'] = 0;
                    $package_details['enable_petro_pd_module'] = 0;
                }

                $package_details['do_not_show_delete_button'] = ! empty($request->do_not_show_delete_button) ? $request->do_not_show_delete_button : 0;
                // dd($package_details['do_not_show_delete_button']);
                // set defaults
                if (! isset($package_details['mf_module'])) {
                    $package_details['mf_module'] = 0;
                }
                if (! isset($package_details['agent_module'])) {
                    $package_details['agent_module'] = 0;
                }

                if (! isset($package_details['petro_pd_module'])) {
                    $package_details['petro_pd_module'] = 0;
                }

                if (! isset($package_details['ev_charging_module'])) {
                    $package_details['ev_charging_module'] = 0;
                }

                if (! isset($package_details['real_time_entries'])) {
                    $package_details['real_time_entries'] = 0;
                }

                if (! isset($package_details['sales_agent_module'])) {
                    $package_details['sales_agent_module'] = 0;
                }

                if (! isset($package_details['pos2'])) {
                    $package_details['pos2'] = 0;
                }

                if (! isset($package_details['stock_report'])) {
                    $package_details['stock_report'] = 0;
                }

                if (! isset($package_details['my_auto'])) {
                    $package_details['my_auto'] = 0;
                }

                if (! isset($package_details['membership_module'])) {
                    $package_details['membership_module'] = 0;
                }

                if (! isset($package_details['stock_adjustment'])) {
                    $package_details['stock_adjustment'] = 0;
                }

                if (! isset($package_details['list_credit_sales_page'])) {
                    $package_details['list_credit_sales_page'] = 0;
                }

                if (! isset($package_details['settlement_sw_other_income'])) {
                    $package_details['settlement_sw_other_income'] = 1;
                }
                if (! isset($package_details['settlement_sw_customer_payments'])) {
                    $package_details['settlement_sw_customer_payments'] = 1;
                }
                if (! isset($package_details['settlement_sw_expenses'])) {
                    $package_details['settlement_sw_expenses'] = 1;
                }

                if (! isset($package_details['restore_module'])) {
                    $package_details['restore_module'] = 0;
                }

                if (! isset($package_details["access_account"])) {
                    $package_details["access_account"] = 0;
                }

                if (! isset($package_details["access_module"])) {
                    $package_details["access_module"] = 0;
                }
                if (! isset($package_details["hr_module"])) {
                    $package_details["hr_module"] = 0;
                }

                if (! isset($package_details["visitors_registration_module"])) {
                    $package_details["visitors_registration_module"] = 0;
                }

                if (! isset($package_details["enable_petro_module"])) {
                    $package_details["enable_petro_module"] = 0;
                }

                if (! isset($package_details["repair_module"])) {
                    $package_details["repair_module"] = 0;
                }

                if (! isset($package_details["fleet_module"])) {
                    $package_details["fleet_module"] = 0;
                }

                if (! isset($package_details["mpcs_module"])) {
                    $package_details["mpcs_module"] = 0;
                }

                if (! isset($package_details["backup_module"])) {
                    $package_details["backup_module"] = 0;
                }

                if (! isset($package_details["property_module"])) {
                    $package_details["property_module"] = 0;
                }

                if (! isset($package_details["auto_services_and_repair_module"])) {
                    $package_details["auto_services_and_repair_module"] = 0;
                }

                if (! isset($package_details["stock_taking_module"])) {
                    $package_details["stock_taking_module"] = 0;
                }

                if (! isset($package_details["installment_module"])) {
                    $package_details["installment_module"] = 0;
                }

                if (! isset($package_details["contact_module"])) {
                    $package_details["contact_module"] = 0;
                }

                if (! isset($package_details["ran_module"])) {
                    $package_details["ran_module"] = 0;
                }

                if (! isset($package_details["report_module"])) {
                    $package_details["report_module"] = 0;
                }

                if (! isset($package_details["settings_module"])) {
                    Log::info('Package details contain settings module');
                    $package_details["settings_module"] = 0;
                }

                if (! isset($package_details["user_management_module"])) {
                    $package_details["user_management_module"] = 0;
                }

                if (!isset($package_details['um_supervisor_role'])) {
                    $package_details['um_supervisor_role'] = 0;
                }

                if (! isset($package_details["banking_module"])) {
                    $package_details["banking_module"] = 0;
                }

                if (! isset($package_details["sale_module"])) {
                    $package_details["sale_module"] = 0;
                }

                if (! isset($package_details["leads_module"])) {
                    $package_details["leads_module"] = 0;
                }

                if (! isset($package_details["deposits_module"])) {
                    $package_details["deposits_module"] = 0;
                }

                if (! isset($package_details["ezyinvoice_module"])) {
                    $package_details["ezyinvoice_module"] = 0;
                }

                if (! isset($package_details["crm_module"])) {
                    $package_details["crm_module"] = 0;
                }

                if (! isset($package_details["shipping_module"])) {
                    $package_details["shipping_module"] = 0;
                }

                if (! isset($package_details["airline_module"])) {
                    $package_details["airline_module"] = 0;
                }

                if (! isset($package_details["asset_module"])) {
                    $package_details["asset_module"] = 0;
                }

                if (! isset($package_details["hms_module"])) {
                    $package_details["hms_module"] = 0;
                }

                // Settlement SW Module - should default to 0 (not selected) unless explicitly enabled
                if ($request->has('settlement_sw_module') && $request->input('settlement_sw_module') == 1) {
                    $package_details["settlement_sw_module"] = strtotime($module_activation_data['settlement_sw_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["settlement_sw_module"] ?? 0);
                } else {
                    $package_details["settlement_sw_module"] = 0;
                }

                $package_details['restore_module']            = ! empty($request->restore_module) ? 1 : 0;
                $package_details['notify_backup_restore_sms'] = $request->notify_backup_restore_sms === 'Yes' ? 'Yes' : 'No';

                $package_details['backup_sms_numbers'] = null;
                if ($package_details['notify_backup_restore_sms'] === 'Yes') {
                    $numbers                               = array_filter(array_map('trim', explode(',', $request->backup_sms_numbers)));
                    $package_details['backup_sms_numbers'] = json_encode($numbers);
                }

                if ($request->has('mf_module') && $request->input('mf_module') == 1) {
                    $package_details["mf_module"] = strtotime($module_activation_data['mf_expiry_date']) > time()
                        ? 1
                        : ($package_details["mf_module"] ?? 0);
                } else {
                    $package_details["mf_module"] = 0;
                }
                if ($request->has('agent_module') && $request->input('agent_module') == 1) {
                    if (empty($module_activation_data['agent_expiry_date'])) {
                        $package_details["agent_module"] = 1;
                    } else {
                        $package_details["agent_module"] = strtotime($module_activation_data['agent_expiry_date']) > time()
                            ? 1
                            : ($package_details["agent_module"] ?? 0);
                    }
                } else {
                    $package_details["agent_module"] = 0;
                }
                if ($request->has('petro_pd_module') && $request->input('petro_pd_module') == 1) {
                    $package_details["petro_pd_module"] = strtotime($module_activation_data['petro_pd_expiry_date']) > time()
                        ? 1
                        : ($package_details["petro_pd_module"] ?? 0);
                } else {
                    $package_details["petro_pd_module"] = 0;
                }
                if ($request->has('ev_charging_module') && $request->input('ev_charging_module') == 1) {
                    $package_details["ev_charging_module"] = strtotime($module_activation_data['ev_charging_expiry_date']) > time()
                        ? 1
                        : ($package_details["ev_charging_module"] ?? 0);
                } else {
                    $package_details["ev_charging_module"] = 0;
                }
                if ($request->has('real_time_entries') && $request->input('real_time_entries') == 1) {
                    $package_details["real_time_entries"] = strtotime($module_activation_data['real_time_entries_expiry_date']) > time()
                        ? 1
                        : ($package_details["real_time_entries"] ?? 0);
                } else {
                    $package_details["real_time_entries"] = 0;
                }

                if ($request->has('sales_agent_module') && $request->input('sales_agent_module') == 1) {
                    $package_details["sales_agent_module"] = strtotime($module_activation_data['sales_agent_expiry_date']) > time()
                        ? 1
                        : ($package_details["sales_agent_module"] ?? 0);
                } else {
                    $package_details["sales_agent_module"] = 0;
                }

                if ($request->has('pos2') && $request->input('pos2') == 1) {
                    $package_details["pos2"] = strtotime($module_activation_data['pos2_expiry_date']) > time()
                        ? 1
                        : ($package_details["pos2"] ?? 0);
                } else {
                    $package_details["pos2"] = 0;
                }

                if ($request->has('stock_report') && $request->input('stock_report') == 1) {
                    $package_details["stock_report"] = strtotime($module_activation_data['stock_report_expiry_date']) > time()
                        ? 1
                        : ($package_details["stock_report"] ?? 0);
                } else {
                    $package_details["stock_report"] = 0;
                }

                if ($request->has('my_auto') && $request->input('my_auto') == 1) {
                    $package_details["my_auto"] = strtotime($module_activation_data['my_auto_expiry_date']) > time()
                        ? 1
                        : ($package_details["my_auto"] ?? 0);
                } else {
                    $package_details["my_auto"] = 0;
                }

                if ($request->has('membership_module') && $request->input('membership_module') == 1) {
                    $package_details["membership_module"] = strtotime($module_activation_data['membership_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["membership_module"] ?? 0);
                } else {
                    $package_details["membership_module"] = 0;
                }
                if ($request->has('stock_adjustment') && $request->input('stock_adjustment') == 1) {
                    $package_details["stock_adjustment"] = strtotime($module_activation_data['sa_expiry_date']) > time()
                        ? 1
                        : ($package_details["stock_adjustment"] ?? 0);
                } else {
                    $package_details["stock_adjustment"] = 0;
                }
                if ($request->has('access_account') && $request->input('access_account') == 1) {
                    $package_details["access_account"] = strtotime($module_activation_data['ac_expiry_date']) > time()
                        ? 1
                        : ($package_details["access_account"] ?? 0);
                } else {
                    $package_details["access_account"] = 0;
                }
                if ($request->has('deposits_module') && $request->input('deposits_module') == 1) {
                    $package_details["deposits_module"] = strtotime($module_activation_data['deposits_expiry_date']) > time()
                        ? 1
                        : ($package_details["deposits_module"] ?? 0);
                } else {
                    $package_details["deposits_module"] = 0;
                }
                if ($request->has('crm_module') && $request->input('crm_module') == 1) {
                    $package_details["crm_module"] = strtotime($module_activation_data['crm_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["crm_module"] ?? 0);
                } else {
                    $package_details["crm_module"] = 0;
                }
                if ($request->has('ezyinvoice_module') && $request->input('ezyinvoice_module') == 1) {
                    $package_details["ezyinvoice_module"] = strtotime($module_activation_data['ezyinvoice_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["ezyinvoice_module"] ?? 0);
                } else {
                    $package_details["ezyinvoice_module"] = 0;
                }
                if ($request->has('airline_module') && $request->input('airline_module') == 1) {
                    $package_details["airline_module"] = strtotime($module_activation_data['airline_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["airline_module"] ?? 0);
                } else {
                    $package_details["airline_module"] = 0;
                }
                if ($request->has('shipping_module') && $request->input('shipping_module') == 1) {
                    $package_details["shipping_module"] = strtotime($module_activation_data['shipping_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["shipping_module"] ?? 0);
                } else {
                    $package_details["shipping_module"] = 0;
                }
                if ($request->has('asset_module') && $request->input('shipping_module') == 1) {
                    $package_details["asset_module"] = strtotime($module_activation_data['asset_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["asset_module"] ?? 0);
                } else {
                    $package_details["asset_module"] = 0;
                }
                if ($request->has('hms_module') && $request->input('hms_module') == 1) {
                    $package_details["hms_module"] = strtotime($module_activation_data['hms_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["hms_module"] ?? 0);
                } else {
                    $package_details["hms_module"] = 0;
                }
                // Settlement SW Module - should default to 0 (not selected) unless explicitly enabled
                if ($request->has('settlement_sw_module') && $request->input('settlement_sw_module') == 1) {
                    $package_details["settlement_sw_module"] = strtotime($module_activation_data['settlement_sw_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["settlement_sw_module"] ?? 0);
                } else {
                    $package_details["settlement_sw_module"] = 0;
                }
                if ($request->has('access_module') && $request->input('access_module') == 1) {
                    $package_details["access_module"] = strtotime($module_activation_data['access_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["access_module"] ?? 0);
                } else {
                    $package_details["access_module"] = 0;
                }
                if ($request->has('hr_module') && $request->input('hr_module') == 1) {
                    $package_details["hr_module"] = strtotime($module_activation_data['hr_expiry_date']) > time()
                        ? 1
                        : ($package_details["hr_module"] ?? 0);
                } else {
                    $package_details["hr_module"] = 0;
                }
                if ($request->has('visitors_registration_module') && $request->input('visitors_registration_module') == 1) {
                    $package_details["visitors_registration_module"] = strtotime($module_activation_data['vreg_expiry_date']) > time()
                        ? 1
                        : ($package_details["visitors_registration_module"] ?? 0);
                } else {
                    $package_details["visitors_registration_module"] = 0;
                }
                if ($request->has('enable_petro_module') && $request->input('enable_petro_module') == 1) {
                    $package_details["enable_petro_module"] = strtotime($module_activation_data['petro_expiry_date']) > time()
                        ? 1
                        : ($package_details["enable_petro_module"] ?? 0);
                } else {
                    $package_details["enable_petro_module"] = 0;
                }
                if ($request->has('repair_module') && $request->input('repair_module') == 1) {
                    $package_details["repair_module"] = strtotime($module_activation_data['repair_expiry_date']) > time()
                        ? 1
                        : ($package_details["repair_module"] ?? 0);
                } else {
                    $package_details["repair_module"] = 0;
                }
                if ($request->has('fleet_module') && $request->input('fleet_module') == 1) {
                    $package_details["fleet_module"] = strtotime($module_activation_data['fleet_expiry_date']) > time()
                        ? 1
                        : ($package_details["fleet_module"] ?? 0);
                } else {
                    $package_details["fleet_module"] = 0;
                }

                if ($request->has('mpcs_module') && $request->input('mpcs_module') == 1) {
                    $package_details["mpcs_module"] = strtotime($module_activation_data['mpcs_expiry_date']) > time()
                        ? 1
                        : ($package_details["mpcs_module"] ?? 0);
                } else {
                    $package_details["mpcs_module"] = 0;
                }
                if ($request->has('backup_module') && $request->input('backup_module') == 1) {
                    $package_details["backup_module"] = strtotime($module_activation_data['backup_expiry_date']) > time()
                        ? 1
                        : ($package_details["backup_module"] ?? 0);
                } else {
                    $package_details["backup_module"] = 0;
                }
                if ($request->has('property_module') && $request->input('property_module') == 1) {
                    $package_details["property_module"] = strtotime($module_activation_data['property_expiry_date']) > time()
                        ? 1
                        : ($package_details["property_module"] ?? 0);
                } else {
                    $package_details["property_module"] = 0;
                }
                if ($request->has('auto_services_and_repair_module') && $request->input('auto_services_and_repair_module') == 1) {
                    $package_details["auto_services_and_repair_module"] = strtotime($module_activation_data['auto_expiry_date']) > time()
                        ? 1
                        : ($package_details["auto_services_and_repair_module"] ?? 0);
                } else {
                    $package_details["auto_services_and_repair_module"] = 0;
                }
                if ($request->has('contact_module') && $request->input('contact_module') == 1) {
                    $package_details["contact_module"] = strtotime($module_activation_data['contact_expiry_date']) > time()
                        ? 1
                        : ($package_details["contact_module"] ?? 0);
                } else {
                    $package_details["contact_module"] = 0;
                }
                if ($request->has('ran_module') && $request->input('ran_module') == 1) {
                    $package_details["ran_module"] = strtotime($module_activation_data['ran_expiry_date']) > time()
                        ? 1
                        : ($package_details["ran_module"] ?? 0);
                } else {
                    $package_details["ran_module"] = 0;
                }
                if ($request->has('report_module') && $request->input('report_module') == 1) {
                    $package_details["report_module"] = strtotime($module_activation_data['report_expiry_date']) > time()
                        ? 1
                        : ($package_details["report_module"] ?? 0);
                } else {
                    $package_details["report_module"] = 0;
                }
                if ($request->has('settings_module') && $request->input('settings_module') == 1) {
                    $package_details["settings_module"] = strtotime($module_activation_data['settings_expiry_date']) > time()
                        ? 1
                        : ($package_details["settings_module"] ?? 0);
                } else {
                    $package_details["settings_module"] = 0;
                }
                if ($request->has('user_management_module') && $request->input('user_management_module') == 1) {
                    $package_details["user_management_module"] = strtotime($module_activation_data['um_expiry_date']) > time()
                        ? 1
                        : ($package_details["user_management_module"] ?? 0);
                } else {
                    $package_details["user_management_module"] = 0;
                }

                if ($request->has('um_supervisor_role') && $request->input('um_supervisor_role') == 1) {
                    $package_details['um_supervisor_role'] = 1;
                } else {
                    $package_details['um_supervisor_role'] = 0;
                }

                $business_id = $business->id;
                $role_name = 'Supervisor#' . $business_id;

                if (
                    $request->has('user_management_module') &&
                    $request->input('user_management_module') == 1 &&
                    $package_details['user_management_module'] == 1
                ) {
                    // $supervisorRole = Role::firstOrCreate(
                    //     [
                    //         'name' => $role_name,
                    //         'guard_name' => 'web',
                    //     ],
                    //     [
                    //         'business_id' => $business_id,
                    //         'is_default' => 1,
                    //         'is_service_staff' => 0,
                    //         'is_superadmin_default' => 0,
                    //     ]
                    // );

                    // if ($supervisorRole->wasRecentlyCreated) {
                    //     $this->assignSupervisorPermissions($supervisorRole);
                    // }
                    $supervisorRole = Role::where('name', $role_name)
                        ->where('guard_name', 'web')
                        ->first();

                    if (!$supervisorRole) {
                        $supervisorRole = new Role();
                        $supervisorRole->name = $role_name;
                        $supervisorRole->guard_name = 'web';
                        $supervisorRole->business_id = $business_id;
                        $supervisorRole->is_default = 1;
                        $supervisorRole->is_service_staff = 0;
                        $supervisorRole->is_superadmin_default = 0;
                        $supervisorRole->save();

                        $this->assignSupervisorPermissions($supervisorRole);
                    }
                }

                if ($request->has('banking_module') && $request->input('banking_module') == 1) {
                    $package_details["banking_module"] = strtotime($module_activation_data['banking_expiry_date']) > time()
                        ? 1
                        : ($package_details["banking_module"] ?? 0);
                } else {
                    $package_details["banking_module"] = 0;
                }
                if ($request->has('sale_module') && $request->input('sale_module') == 1) {
                    $package_details["sale_module"] = strtotime($module_activation_data['sale_expiry_date']) > time()
                        ? 1
                        : ($package_details["sale_module"] ?? 0);
                } else {
                    $package_details["sale_module"] = 0;
                }
                if ($request->has('leads_module') && $request->input('leads_module') == 1) {
                    $package_details["leads_module"] = strtotime($module_activation_data['leads_expiry_date']) > time()
                        ? 1
                        : ($package_details["leads_module"] ?? 0);
                } else {
                    $package_details["leads_module"] = 0;
                }
                if ($request->has('hospital_system') && $request->input('hospital_system') == 1) {
                    $package_details["hospital_system"] = strtotime($module_activation_data['hospital_expiry_date']) > time()
                        ? 1
                        : ($package_details["hospital_system"] ?? 0);
                } else {
                    $package_details["hospital_system"] = 0;
                }
                if ($request->has('enable_restaurant') && $request->input('enable_restaurant') == 1) {
                    $package_details["enable_restaurant"] = strtotime($module_activation_data['restaurant_expiry_date']) > time()
                        ? 1
                        : ($package_details["enable_restaurant"] ?? 0);
                } else {
                    $package_details["enable_restaurant"] = 0;
                }
                if ($request->has('enable_duplicate_invoice') && $request->input('enable_duplicate_invoice') == 1) {
                    $package_details["enable_duplicate_invoice"] = strtotime($module_activation_data['duplicate_invoice_expiry_date']) > time()
                        ? 1
                        : ($package_details["enable_duplicate_invoice"] ?? 0);
                } else {
                    $package_details["enable_duplicate_invoice"] = 0;
                }
                if ($request->has('tasks_management') && $request->input('tasks_management') == 1) {
                    $package_details["tasks_management"] = strtotime($module_activation_data['tasks_expiry_date']) > time()
                        ? 1
                        : ($package_details["tasks_management"] ?? 0);
                } else {
                    $package_details["tasks_management"] = 0;
                }
                if ($request->has('enable_cheque_writing') && $request->input('enable_cheque_writing') == 1) {
                    $package_details["enable_cheque_writing"] = strtotime($module_activation_data['cheque_expiry_date']) > time()
                        ? 1
                        : ($package_details["enable_cheque_writing"] ?? 0);
                } else {
                    $package_details["enable_cheque_writing"] = 0;
                }
                if ($request->has('list_easy_payment') && $request->input('list_easy_payment') == 1) {
                    $package_details["list_easy_payment"] = strtotime($module_activation_data['list_easy_expiry_date']) > time()
                        ? 1
                        : ($package_details["list_easy_payment"] ?? 0);
                } else {
                    $package_details["list_easy_payment"] = 0;
                }
                if ($request->has('pump_operator_dashboard') && $request->input('pump_operator_dashboard') == 1) {
                    $package_details["pump_operator_dashboard"] = strtotime($module_activation_data['pump_expiry_date']) > time()
                        ? 1
                        : ($package_details["pump_operator_dashboard"] ?? 0);
                } else {
                    $package_details["pump_operator_dashboard"] = 0;
                }
                if ($request->has('stock_taking_module') && $request->input('stock_taking_module') == 1) {
                    $package_details["stock_taking_module"] = strtotime($module_activation_data['stock_taking_expiry_date']) > time()
                        ? 1
                        : ($package_details["stock_taking_module"] ?? 0);
                } else {
                    $package_details["stock_taking_module"] = 0;
                }
                if ($request->has('installment_module') && $request->input('installment_module') == 1) {
                    $package_details["installment_module"] = strtotime($module_activation_data['installment_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["installment_module"] ?? 0);
                } else {
                    $package_details["installment_module"] = 0;
                }

                if ($request->has('ezy_products') && $request->input('ezy_products') == 1) {
                    $package_details["ezy_products"] = strtotime($module_activation_data['ezy_expiry_date']) > time()
                        ? 1
                        : ($package_details["ezy_products"] ?? 0);
                } else {
                    $package_details["ezy_products"] = 0;
                }

                // doc 6104
                if ($request->has('list_sms') && $request->input('list_sms') == 1) {
                    $package_details["list_sms"] = strtotime($module_activation_data['list_sms_expiry_date']) > time()
                        ? 1
                        : ($package_details["list_sms"] ?? 0);
                } else {
                    $package_details["list_sms"] = 0;
                }
                if ($request->has('enable_sms') && $request->input('enable_sms') == 1) {
                    $package_details["enable_sms"] = strtotime($module_activation_data['enable_sms_expiry_date']) > time()
                        ? 1
                        : ($package_details["enable_sms"] ?? 0);
                } else {
                    $package_details["enable_sms"] = 0;
                }
                if ($request->has('notification_template_module') && $request->input('notification_template_module') == 1) {
                    $package_details["notification_template_module"] = strtotime($module_activation_data['notification_template_expiry_date']) > time()
                        ? 1
                        : ($package_details["notification_template_module"] ?? 0);
                } else {
                    $package_details["notification_template_module"] = 0;
                }

                // //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                if ($request->has('products') && $request->input('products') == 1) {
                    $package_details["products"] = strtotime($module_activation_data['products_expiry_date']) > time()
                        ? 1
                        : ($package_details["products"] ?? 0);
                } else {
                    $package_details["products"] = 0;
                }
                if ($request->has('purchase') && $request->input('purchase') == 1) {
                    $package_details["purchase"] = strtotime($module_activation_data['purchase_expiry_date']) > time()
                        ? 1
                        : ($package_details["purchase"] ?? 0);
                } else {
                    $package_details["purchase"] = 0;
                }
                if ($request->has('stock_transfer') && $request->input('stock_transfer') == 1) {
                    $package_details["stock_transfer"] = strtotime($module_activation_data['stock_transfer_expiry_date']) > time()
                        ? 1
                        : ($package_details["stock_transfer"] ?? 0);
                } else {
                    $package_details["stock_transfer"] = 0;
                }
                if ($request->has('daily_review') && $request->input('daily_review') == 1) {
                    $package_details["daily_review"] = strtotime($module_activation_data['daily_review_expiry_date']) > time()
                        ? 1
                        : ($package_details["daily_review"] ?? 0);
                } else {
                    $package_details["daily_review"] = 0;
                }
                if ($request->has('service_staff') && $request->input('daily_review') == 1) {
                    $package_details["service_staff"] = strtotime($module_activation_data['service_staff_expiry_date']) > time()
                        ? 1
                        : ($package_details["service_staff"] ?? 0);
                } else {
                    $package_details["service_staff"] = 0;
                }
                if ($request->has('enable_subscription') && $request->input('enable_subscription') == 1) {
                    $package_details["enable_subscription"] = strtotime($module_activation_data['enable_subscription_expiry_date']) > time()
                        ? 1
                        : ($package_details["enable_subscription"] ?? 0);
                } else {
                    $package_details["enable_subscription"] = 0;
                }
                if ($request->has('distribution_module') && $request->input('distribution_module') == 1) {
                    $package_details["distribution_module"] = strtotime($module_activation_data['distribution_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["distribution_module"] ?? 0);
                } else {
                    $package_details["distribution_module"] = 0;
                }

                $package_details["spreadsheet"] = strtotime($module_activation_data['spreadsheet_expiry_date']) > time() ? 1 : 0;
                if ($request->has('essentials_module') && $request->input('essentials_module') == 1) {
                    $package_details["essentials_module"] = strtotime($module_activation_data['essentials_expiry_date']) > time()
                        ? 1
                        : ($package_details["essentials_module"] ?? 0);
                } else {
                    $package_details["essentials_module"] = 0;
                }

                $package_details["price_changes_module"]     = strtotime($module_activation_data['price_changes_expiry_date']) > time() ? 1 : 0;
                $package_details["day_end_module"]           = strtotime($module_activation_data['day_end_expiry_date']) > time() ? 1 : 0;
                $package_details["customer_interest_module"] = strtotime($module_activation_data['customer_interest_expiry_date']) > time() ? 1 : 0;
                $package_details["issue_customer_bill"]      = strtotime($module_activation_data['issue_customer_bill_expiry_date']) > time() ? 1 : 0;
                $package_details["issue_customer_bill_vat"]  = strtotime($module_activation_data['issue_customer_bill_vat_expiry_date']) > time() ? 1 : 0;

                $package_details["post_dated_cheque"] = strtotime($module_activation_data['post_dated_expiry_date']) > time() ? 1 : 0;
                if ($request->has('vat_module') && $request->input('vat_module') == 1) {
                    $package_details["vat_module"] = strtotime($module_activation_data['vat_expiry_date']) > time()
                        ? 1
                        : ($package_details["vat_module"] ?? 0);
                } else {
                    $package_details["vat_module"] = 0;
                }
                if ($request->has('subscriptions_module') && $request->input('subscriptions_module') == 1) {
                    $package_details["subscriptions_module"] = strtotime($module_activation_data['subscriptions_expiry_date']) > time()
                        ? 1
                        : ($package_details["subscriptions_module"] ?? 0);
                } else {
                    $package_details["subscriptions_module"] = 0;
                }
                if ($request->has('smsmodule_module') && $request->input('smsmodule_module') == 1) {
                    $package_details["smsmodule_module"] = strtotime($module_activation_data['smsmodule_expiry_date']) > time()
                        ? 1
                        : ($package_details["smsmodule_module"] ?? 0);
                } else {
                    $package_details["smsmodule_module"] = 0;
                }
                if ($request->has('bakery_module') && $request->input('bakery_module') == 1) {
                    $package_details["bakery_module"] = strtotime($module_activation_data['bakery_expiry_date']) > time()
                        ? 1
                        : ($package_details["bakery_module"] ?? 0);
                } else {
                    $package_details["bakery_module"] = 0;
                }
                $package_details["bakery_settings_show_vehicle_opening_balance"] = ! empty($request->bakery_settings_show_vehicle_opening_balance) ? 1 : 0;
                if ($request->has('vat_module_main') && $request->input('vat_module_main') == 1) {
                    $package_details["vat_module_main"] = strtotime($module_activation_data['vat_main_expiry_date']) > time()
                        ? 1
                        : ($package_details["vat_module_main"] ?? 0);
                } else {
                    $package_details["vat_module_main"] = 0;
                }
                if ($request->has('membership_module') && $request->input('membership_module') == 1) {
                    $package_details["member_registration"] = strtotime($module_activation_data['membership_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["member_registration"] ?? 0);
                } else {
                    $package_details["member_registration"] = 0;
                }
                if ($request->has('mf_module') && $request->input('mf_module') == 1) {
                    $package_details["mf_module"] = strtotime($module_activation_data['mf_expiry_date']) > time()
                        ? 1
                        : ($package_details["mf_module"] ?? 0);
                } else {
                    $package_details["mf_module"] = 0;
                }
                if ($request->has('agent_module') && $request->input('agent_module') == 1) {
                    if (empty($module_activation_data['agent_expiry_date'])) {
                        $package_details["agent_module"] = 1;
                    } else {
                        $package_details["agent_module"] = strtotime($module_activation_data['agent_expiry_date']) > time()
                            ? 1
                            : ($package_details["agent_module"] ?? 0);
                    }
                } else {
                    $package_details["agent_module"] = 0;
                }
                if ($request->has('petro_pd_module') && $request->input('petro_pd_module') == 1) {
                    $package_details["petro_pd_module"] = strtotime($module_activation_data['petro_pd_expiry_date']) > time()
                        ? 1
                        : ($package_details["petro_pd_module"] ?? 0);
                } else {
                    $package_details["petro_pd_module"] = 0;
                }
                if ($request->has('ev_charging_module') && $request->input('ev_charging_module') == 1) {
                    $package_details["ev_charging_module"] = strtotime($module_activation_data['ev_charging_expiry_date']) > time()
                        ? 1
                        : ($package_details["ev_charging_module"] ?? 0);
                } else {
                    $package_details["ev_charging_module"] = 0;
                }

                if ($request->has('real_time_entries') && $request->input('real_time_entries') == 1) {
                    $package_details["real_time_entries"] = strtotime($module_activation_data['real_time_entries_expiry_date']) > time()
                        ? 1
                        : ($package_details["real_time_entries"] ?? 0);
                } else {
                    $package_details["real_time_entries"] = 0;
                }

                 if ($request->has('sales_agent_module') && $request->input('sales_agent_module') == 1) {
                    $package_details["sales_agent_module"] = strtotime($module_activation_data['sales_agent_expiry_date']) > time()
                        ? 1
                        : ($package_details["sales_agent_module"] ?? 0);
                } else {
                    $package_details["sales_agent_module"] = 0;
                }

                if ($request->has('pos2') && $request->input('pos2') == 1) {
                    $package_details["pos2"] = strtotime($module_activation_data['pos2_expiry_date']) > time()
                        ? 1
                        : ($package_details["pos2"] ?? 0);
                } else {
                    $package_details["pos2"] = 0;
                }

                if ($request->has('stock_report') && $request->input('stock_report') == 1) {
                    $package_details["stock_report"] = strtotime($module_activation_data['stock_report_expiry_date']) > time()
                        ? 1
                        : ($package_details["stock_report"] ?? 0);
                } else {
                    $package_details["stock_report"] = 0;
                }

                if ($request->has('my_auto') && $request->input('my_auto') == 1) {
                    $package_details["my_auto"] = strtotime($module_activation_data['my_auto_expiry_date']) > time()
                        ? 1
                        : ($package_details["my_auto"] ?? 0);
                } else {
                    $package_details["my_auto"] = 0;
                }

                if ($request->has('membership_module') && $request->input('membership_module') == 1) {
                    $package_details["membership_module"] = strtotime($module_activation_data['membership_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["membership_module"] ?? 0);
                } else {
                    $package_details["membership_module"] = 0;
                }

                if ($request->has('stock_adjustment') && $request->input('stock_adjustment') == 1) {
                    $package_details["stock_adjustment"] = strtotime($module_activation_data['sa_expiry_date']) > time()
                        ? 1
                        : ($package_details["stock_adjustment"] ?? 0);
                } else {
                    $package_details["stock_adjustment"] = 0;
                }

                // ===== BEGIN: Pumper Dashboard Default (Business-level) =====
                $package_details['products_pumper_dashboard_default'] = $request->boolean('products_pumper_dashboard_default');

                $businessId = $id ?? $request->route('id') ?? $request->input('id');

                $business = Business::findOrFail($businessId);

                $fuel_products     = array_values(array_filter($request->fuel_products ?? []));
                $fuel_products_qty = $request->fuel_products_qty ?? [];

                $fuel_products_qty = array_intersect_key(
                    $fuel_products_qty,
                    array_flip($fuel_products)
                );

                $common_settings['vat_settings'] = [
                    'fuel_products'     => $fuel_products,
                    'fuel_products_qty' => $fuel_products_qty,
                ];

                $business->common_settings = $common_settings;
                $business->save();

                if (! empty($businessId)) {
                    try {
                        Product::where('business_id', $businessId)
                            ->update([
                                'show_in_pumper_dashboard' => $package_details['products_pumper_dashboard_default'] ? 1 : 0,
                            ]);
                    } catch (\Exception $e) {
                        // Products table doesn't exist, skip this update
                        \Log::info('Products table not found, skipping pumper dashboard default update');
                    }
                }
                // ===== END: Pumper Dashboard Default =====

                if ($request->has('access_account') && $request->input('access_account') == 1) {
                    $package_details["access_account"] = strtotime($module_activation_data['ac_expiry_date']) > time()
                        ? 1
                        : ($package_details["access_account"] ?? 0);
                } else {
                    $package_details["access_account"] = 0;
                }
                if ($request->has('deposits_module') && $request->input('deposits_module') == 1) {
                    $package_details["deposits_module"] = strtotime($module_activation_data['deposits_expiry_date']) > time()
                        ? 1
                        : ($package_details["deposits_module"] ?? 0);
                } else {
                    $package_details["deposits_module"] = 0;
                }
                if ($request->has('crm_module') && $request->input('crm_module') == 1) {
                    $package_details["crm_module"] = strtotime($module_activation_data['crm_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["crm_module"] ?? 0);
                } else {
                    $package_details["crm_module"] = 0;
                }
                if ($request->has('ezyinvoice_module') && $request->input('ezyinvoice_module') == 1) {
                    $package_details["ezyinvoice_module"] = strtotime($module_activation_data['ezyinvoice_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["ezyinvoice_module"] ?? 0);
                } else {
                    $package_details["ezyinvoice_module"] = 0;
                }
                if ($request->has('airline_module') && $request->input('airline_module') == 1) {
                    $package_details["airline_module"] = strtotime($module_activation_data['airline_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["airline_module"] ?? 0);
                } else {
                    $package_details["airline_module"] = 0;
                }
                if ($request->has('shipping_module') && $request->input('shipping_module') == 1) {
                    $package_details["shipping_module"] = strtotime($module_activation_data['shipping_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["shipping_module"] ?? 0);
                } else {
                    $package_details["shipping_module"] = 0;
                }
                if ($request->has('asset_module') && $request->input('asset_module') == 1) {
                    $package_details["asset_module"] = strtotime($module_activation_data['asset_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["asset_module"] ?? 0);
                } else {
                    $package_details["asset_module"] = 0;
                }

                if ($request->has('hms_module') && $request->input('hms_module') == 1) {
                    $package_details["hms_module"] = strtotime($module_activation_data['hms_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["hms_module"] ?? 0);
                } else {
                    $package_details["hms_module"] = 0;
                }
                if ($request->has('access_module') && $request->input('access_module') == 1) {
                    $package_details["access_module"] = strtotime($module_activation_data['access_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["access_module"] ?? 0);
                } else {
                    $package_details["access_module"] = 0;
                }
                if ($request->has('hr_module') && $request->input('hr_module') == 1) {
                    $package_details["hr_module"] = strtotime($module_activation_data['hr_expiry_date']) > time()
                        ? 1
                        : ($package_details["hr_module"] ?? 0);
                } else {
                    $package_details["hr_module"] = 0;
                }
                if ($request->has('visitors_registration_module') && $request->input('visitors_registration_module') == 1) {
                    $package_details["visitors_registration_module"] = strtotime($module_activation_data['vreg_expiry_date']) > time()
                        ? 1
                        : ($package_details["visitors_registration_module"] ?? 0);
                } else {
                    $package_details["visitors_registration_module"] = 0;
                }
                if ($request->has('enable_petro_module') && $request->input('enable_petro_module') == 1) {
                    $package_details["enable_petro_module"] = strtotime($module_activation_data['petro_expiry_date']) > time()
                        ? 1
                        : ($package_details["enable_petro_module"] ?? 0);
                } else {
                    $package_details["enable_petro_module"] = 0;
                }
                if ($request->has('repair_module') && $request->input('repair_module') == 1) {
                    $package_details["repair_module"] = strtotime($module_activation_data['repair_expiry_date']) > time()
                        ? 1
                        : ($package_details["repair_module"] ?? 0);
                } else {
                    $package_details["repair_module"] = 0;
                }
                if ($request->has('fleet_module') && $request->input('fleet_module') == 1) {
                    $package_details["fleet_module"] = strtotime($module_activation_data['fleet_expiry_date']) > time()
                        ? 1
                        : ($package_details["fleet_module"] ?? 0);
                } else {
                    $package_details["fleet_module"] = 0;
                }
                if ($request->has('mpcs_module') && $request->input('mpcs_module') == 1) {
                    $package_details["mpcs_module"] = strtotime($module_activation_data['mpcs_expiry_date']) > time()
                        ? 1
                        : ($package_details["mpcs_module"] ?? 0);
                } else {
                    $package_details["mpcs_module"] = 0;
                }
                if ($request->has('backup_module') && $request->input('backup_module') == 1) {
                    $package_details["backup_module"] = strtotime($module_activation_data['backup_expiry_date']) > time()
                        ? 1
                        : ($package_details["backup_module"] ?? 0);
                } else {
                    $package_details["backup_module"] = 0;
                }
                if ($request->has('property_module') && $request->input('property_module') == 1) {
                    $package_details["property_module"] = strtotime($module_activation_data['property_expiry_date']) > time()
                        ? 1
                        : ($package_details["property_module"] ?? 0);
                } else {
                    $package_details["property_module"] = 0;
                }
                if ($request->has('auto_services_and_repair_module') && $request->input('auto_services_and_repair_module') == 1) {
                    $package_details["auto_services_and_repair_module"] = strtotime($module_activation_data['auto_expiry_date']) > time()
                        ? 1
                        : ($package_details["auto_services_and_repair_module"] ?? 0);
                } else {
                    $package_details["auto_services_and_repair_module"] = 0;
                }
                if ($request->has('contact_module') && $request->input('contact_module') == 1) {
                    $package_details["contact_module"] = strtotime($module_activation_data['contact_expiry_date']) > time()
                        ? 1
                        : ($package_details["contact_module"] ?? 0);
                } else {
                    $package_details["contact_module"] = 0;
                }
                if ($request->has('ran_module') && $request->input('ran_module') == 1) {
                    $package_details["ran_module"] = strtotime($module_activation_data['ran_expiry_date']) > time()
                        ? 1
                        : ($package_details["ran_module"] ?? 0);
                } else {
                    $package_details["ran_module"] = 0;
                }
                if ($request->has('report_module') && $request->input('report_module') == 1) {
                    $package_details["report_module"] = strtotime($module_activation_data['report_expiry_date']) > time()
                        ? 1
                        : ($package_details["report_module"] ?? 0);
                } else {
                    $package_details["report_module"] = 0;
                }
                if ($request->has('settings_module') && $request->input('settings_module') == 1) {
                    $package_details["settings_module"] = strtotime($module_activation_data['settings_expiry_date']) > time()
                        ? 1
                        : ($package_details["settings_module"] ?? 0);
                } else {
                    $package_details["settings_module"] = 0;
                }
                if ($request->has('user_management_module') && $request->input('user_management_module') == 1) {
                    $package_details["user_management_module"] = strtotime($module_activation_data['um_expiry_date']) > time()
                        ? 1
                        : ($package_details["user_management_module"] ?? 0);
                } else {
                    $package_details["user_management_module"] = 0;
                }

                if ($request->has('um_supervisor_role') && $request->input('um_supervisor_role') == 1) {
                    $package_details['um_supervisor_role'] = 1;
                } else {
                    $package_details['um_supervisor_role'] = 0;
                }

                if ($request->has('banking_module') && $request->input('banking_module') == 1) {
                    $package_details["banking_module"] = strtotime($module_activation_data['banking_expiry_date']) > time()
                        ? 1
                        : ($package_details["banking_module"] ?? 0);
                } else {
                    $package_details["banking_module"] = 0;
                }
                if ($request->has('sale_module') && $request->input('sale_module') == 1) {
                    $package_details["sale_module"] = strtotime($module_activation_data['sale_expiry_date']) > time()
                        ? 1 : ($package_details["sale_module"] ?? 0);
                } else {
                    $package_details["sale_module"] = 0;
                }
                if ($request->has('leads_module') && $request->input('leads_module') == 1) {
                    $package_details["leads_module"] = strtotime($module_activation_data['leads_expiry_date']) > time()
                        ? 1 : ($package_details["leads_module"] ?? 0);
                } else {
                    $package_details["leads_module"] = 0;
                }
                if ($request->has('hospital_system') && $request->input('hospital_system') == 1) {
                    $package_details["hospital_system"] = strtotime($module_activation_data['hospital_expiry_date']) > time()
                        ? 1 : ($package_details["hospital_system"] ?? 0);
                } else {
                    $package_details["hospital_system"] = 0;
                }
                if ($request->has('enable_restaurant') && $request->input('enable_restaurant') == 1) {
                    $package_details["enable_restaurant"] = strtotime($module_activation_data['restaurant_expiry_date']) > time()
                        ? 1 : ($package_details["enable_restaurant"] ?? 0);
                } else {
                    $package_details["enable_restaurant"] = 0;
                }
                if ($request->has('enable_duplicate_invoice') && $request->input('enable_duplicate_invoice') == 1) {
                    $package_details["enable_duplicate_invoice"] = strtotime($module_activation_data['duplicate_invoice_expiry_date']) > time()
                        ? 1 : ($package_details["enable_duplicate_invoice"] ?? 0);
                } else {
                    $package_details["enable_duplicate_invoice"] = 0;
                }
                if ($request->has('tasks_management') && $request->input('tasks_management') == 1) {
                    $package_details["tasks_management"] = strtotime($module_activation_data['tasks_expiry_date']) > time()
                        ? 1 : ($package_details["tasks_management"] ?? 0);
                } else {
                    $package_details["tasks_management"] = 0;
                }

                if ($request->has('enable_cheque_writing') && $request->input('enable_cheque_writing') == 1) {
                    $package_details["enable_cheque_writing"] = strtotime($module_activation_data['cheque_expiry_date']) > time()
                        ? 1 : ($package_details["enable_cheque_writing"] ?? 0);
                } else {
                    $package_details["enable_cheque_writing"] = 0;
                }
                if ($request->has('list_easy_payment') && $request->input('list_easy_payment') == 1) {
                    $package_details["list_easy_payment"] = strtotime($module_activation_data['list_easy_expiry_date']) > time()
                        ? 1 : ($package_details["list_easy_payment"] ?? 0);
                } else {
                    $package_details["list_easy_payment"] = 0;
                }
                if ($request->has('pump_operator_dashboard') && $request->input('pump_operator_dashboard') == 1) {
                    $package_details["pump_operator_dashboard"] = strtotime($module_activation_data['pump_expiry_date']) > time()
                        ? 1 : ($package_details["pump_operator_dashboard"] ?? 0);
                } else {
                    $package_details["pump_operator_dashboard"] = 0;
                }

                if ($request->has('stock_taking_module') && $request->input('stock_taking_module') == 1) {
                    $package_details["stock_taking_module"] = strtotime($module_activation_data['stock_taking_expiry_date']) > time()
                        ? 1 : ($package_details["stock_taking_module"] ?? 0);
                } else {
                    $package_details["stock_taking_module"] = 0;
                }
                if ($request->has('installment_module') && $request->input('installment_module') == 1) {
                    $package_details["installment_module"] = strtotime($module_activation_data['installment_module_expiry_date']) > time()
                        ? 1 : ($package_details["installment_module"] ?? 0);
                } else {
                    $package_details["installment_module"] = 0;
                }

                if ($request->has('ezy_products') && $request->input('ezy_products') == 1) {
                    $package_details["ezy_products"] = strtotime($module_activation_data['ezy_expiry_date']) > time()
                        ? 1 : ($package_details["ezy_products"] ?? 0);
                } else {
                    $package_details["ezy_products"] = 0;
                }

                if ($request->has('list_sms') && $request->input('list_sms') == 1) {
                    $package_details["list_sms"] = strtotime($module_activation_data['list_sms_expiry_date']) > time()
                        ? 1 : ($package_details["list_sms"] ?? 0);
                } else {
                    $package_details["list_sms"] = 0;
                }
                if ($request->has('enable_sms') && $request->input('enable_sms') == 1) {
                    $package_details["enable_sms"] = strtotime($module_activation_data['enable_sms_expiry_date']) > time()
                        ? 1 : ($package_details["enable_sms"] ?? 0);
                } else {
                    $package_details["enable_sms"] = 0;
                }
                if ($request->has('notification_template_module') && $request->input('notification_template_module') == 1) {
                    $package_details["notification_template_module"] = strtotime($module_activation_data['notification_template_expiry_date']) > time()
                        ? 1 : ($package_details["notification_template_module"] ?? 0);
                } else {
                    $package_details["notification_template_module"] = 0;
                }

                // doc 6104

                // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                if ($request->has('products') && $request->input('products') == 1) {
                    $package_details["products"] = strtotime($module_activation_data['products_expiry_date']) > time()
                        ? 1
                        : ($package_details["products"] ?? 0);
                } else {
                    $package_details["products"] = 0;
                }
                if ($request->has('purchase') && $request->input('purchase') == 1) {
                    $package_details["purchase"] = strtotime($module_activation_data['purchase_expiry_date']) > time()
                        ? 1
                        : ($package_details["purchase"] ?? 0);
                } else {
                    $package_details["purchase"] = 0;
                }
                if ($request->has('stock_transfer') && $request->input('stock_transfer') == 1) {
                    $package_details["stock_transfer"] = strtotime($module_activation_data['stock_transfer_expiry_date']) > time()
                        ? 1
                        : ($package_details["stock_transfer"] ?? 0);
                } else {
                    $package_details["stock_transfer"] = 0;
                }
                if ($request->has('daily_review') && $request->input('daily_review') == 1) {
                    $package_details["daily_review"] = strtotime($module_activation_data['daily_review_expiry_date']) > time()
                        ? 1
                        : ($package_details["daily_review"] ?? 0);
                } else {
                    $package_details["daily_review"] = 0;
                }
                if ($request->has('service_staff') && $request->input('service_staff') == 1) {
                    $package_details["service_staff"] = strtotime($module_activation_data['service_staff_expiry_date']) > time()
                        ? 1
                        : ($package_details["service_staff"] ?? 0);
                } else {
                    $package_details["service_staff"] = 0;
                }
                if ($request->has('enable_subscription') && $request->input('enable_subscription') == 1) {
                    $package_details["enable_subscription"] = strtotime($module_activation_data['enable_subscription_expiry_date']) > time()
                        ? 1
                        : ($package_details["enable_subscription"] ?? 0);
                } else {
                    $package_details["enable_subscription"] = 0;
                }

                if ($request->has('distribution_module') && $request->input('distribution_module') == 1) {
                    $package_details["distribution_module"] = strtotime($module_activation_data['distribution_module_expiry_date']) > time()
                        ? 1
                        : ($package_details["distribution_module"] ?? 0);
                } else {
                    $package_details["distribution_module"] = 0;
                }

                if ($request->has('spreadsheet') && $request->input('spreadsheet') == 1) {
                    $package_details["spreadsheet"] = strtotime($module_activation_data['spreadsheet_expiry_date']) > time()
                        ? 1
                        : ($package_details["spreadsheet"] ?? 0);
                } else {
                    $package_details["spreadsheet"] = 0;
                }
                if ($request->has('essentials_module') && $request->input('essentials_module') == 1) {
                    $package_details["essentials_module"] = strtotime($module_activation_data['essentials_expiry_date']) > time()
                        ? 1
                        : ($package_details["essentials_module"] ?? 0);
                } else {
                    $package_details["essentials_module"] = 0;
                }
                if ($request->has('price_changes_module') && $request->input('price_changes_module') == 1) {
                    $package_details["price_changes_module"] = strtotime($module_activation_data['price_changes_expiry_date']) > time()
                        ? 1
                        : ($package_details["price_changes_module"] ?? 0);
                } else {
                    $package_details["price_changes_module"] = 0;
                }
                if ($request->has('day_end_module') && $request->input('day_end_module') == 1) {
                    $package_details["day_end_module"] = strtotime($module_activation_data['day_end_expiry_date']) > time()
                        ? 1
                        : ($package_details["day_end_module"] ?? 0);
                } else {
                    $package_details["day_end_module"] = 0;
                }
                if ($request->has('customer_interest_module') && $request->input('customer_interest_module') == 1) {
                    $package_details["customer_interest_module"] = strtotime($module_activation_data['customer_interest_expiry_date']) > time()
                        ? 1
                        : ($package_details["customer_interest_module"] ?? 0);
                } else {
                    $package_details["customer_interest_module"] = 0;
                }
                if ($request->has('issue_customer_bill') && $request->input('issue_customer_bill') == 1) {
                    $package_details["issue_customer_bill"] = strtotime($module_activation_data['issue_customer_bill_expiry_date']) > time()
                        ? 1
                        : ($package_details["issue_customer_bill"] ?? 0);
                } else {
                    $package_details["issue_customer_bill"] = 0;
                }
                if ($request->has('issue_customer_bill_vat') && $request->input('issue_customer_bill_vat') == 1) {
                    $package_details["issue_customer_bill_vat"] = strtotime($module_activation_data['issue_customer_bill_vat_expiry_date']) > time()
                        ? 1
                        : ($package_details["issue_customer_bill_vat"] ?? 0);
                } else {
                    $package_details["issue_customer_bill_vat"] = 0;
                }

                if ($request->has('post_dated_cheque') && $request->input('post_dated_cheque') == 1) {
                    $package_details["post_dated_cheque"] = strtotime($module_activation_data['post_dated_expiry_date']) > time()
                        ? 1
                        : ($package_details["post_dated_cheque"] ?? 0);
                } else {
                    $package_details["post_dated_cheque"] = 0;
                }

                if ($request->has('vat_module') && $request->input('vat_module') == 1) {
                    $package_details["vat_module"] = strtotime($module_activation_data['vat_expiry_date']) > time()
                        ? 1
                        : ($package_details["vat_module"] ?? 0);
                } else {
                    $package_details["vat_module"] = 0;
                }

                if ($request->has('subscriptions_module') && $request->input('subscriptions_module') == 1) {
                    $package_details["subscriptions_module"] = strtotime($module_activation_data['subscriptions_expiry_date']) > time()
                        ? 1
                        : ($package_details["subscriptions_module"] ?? 0);
                } else {
                    $package_details["subscriptions_module"] = 0;
                }

                if ($request->has('smsmodule_module') && $request->input('smsmodule_module') == 1) {
                    $package_details["smsmodule_module"] = strtotime($module_activation_data['smsmodule_expiry_date']) > time()
                        ? 1
                        : ($package_details["smsmodule_module"] ?? 0);
                } else {
                    $package_details["smsmodule_module"] = 0;
                }
                if ($request->has('bakery_module') && $request->input('bakery_module') == 1) {
                    $package_details["bakery_module"] = strtotime($module_activation_data['bakery_expiry_date']) > time()
                        ? 1
                        : ($package_details["bakery_module"] ?? 0);
                } else {
                    $package_details["bakery_module"] = 0;
                }
                $package_details["bakery_settings_show_vehicle_opening_balance"] = ! empty($request->bakery_settings_show_vehicle_opening_balance) ? 1 : 0;
                if ($request->has('vat_module_main') && $request->input('vat_module_main') == 1) {
                    $package_details["vat_module_main"] = strtotime($module_activation_data['vat_main_expiry_date']) > time()
                        ? 1
                        : ($package_details["vat_module_main"] ?? 0);
                } else {
                    $package_details["vat_module_main"] = 0;
                }

                $package_details['development'] = ! empty($request->development) ? 1 : 0;

                // Save pump_operator_module checkbox (maps to pump_operator_dashboard in package_details).
                // IMPORTANT: Only pump_operator_module is used here because manage.blade.php has a duplicate
                // pump_operator_dashboard checkbox (line ~11511) that lacks a hidden field and would always
                // send 1 when the page is submitted, overriding the primary checkbox (line ~1684) decision.
                $package_details['pump_operator_dashboard'] = ($request->input('pump_operator_module') == 1) ? 1 : 0;

                // Save module_permission data (e.g., pump_operator_module permissions)
                if ($request->has('module_permission') && is_array($request->module_permission)) {
                    // Initialize module_permission if it doesn't exist
                    if (! isset($package_details['module_permission']) || ! is_array($package_details['module_permission'])) {
                        $package_details['module_permission'] = [];
                    }

                    foreach ($request->module_permission as $module => $permissions) {
                        if (is_array($permissions)) {
                            // Initialize module if it doesn't exist
                            if (! isset($package_details['module_permission'][$module])) {
                                $package_details['module_permission'][$module] = [];
                            }

                            // Filter to only keep checked permissions (value = 1)
                            // Unchecked checkboxes won't be in the request, so we only save the checked ones
                            $package_details['module_permission'][$module] = array_filter($permissions, function ($value) {
                                return $value == 1;
                            });
                        }
                    }
                }

                $package_details['manage_businesses'] = $request->manage_businesses ?? [];
                $package_details['manage_business_locations'] = $request->manage_business_locations ?? [];
                $package_details['doc_management_show_business_location'] = $request->input('doc_management_show_business_location', 0) == 1 ? 1 : 0;
                $package_details['vat_main_invoice2_monthly_limit'] = max(0, (int) $request->input('vat_main_invoice2_monthly_limit', 0));
                $package_details['my_auto'] = !empty($request->my_auto) ? 1 : 0;
                $package_details['my_auto_agent_login'] = !empty($request->my_auto_agent_login) ? 1 : 0;
                $this->applyStandaloneSidebarPermissionFlags($package_details, $request);
                $this->modulePermissionService()->applyAutoManagePermissions($package_details, $request);

                // Saving a parent module in Super Admin > Manage must update
                // the same parent state used by Manage Side Bar and the direct
                // route guard. This removes stale disabled markers without
                // touching unrelated module selections or child permissions.
                $this->modulePermissionService()->syncSidebarParentStatesFromManage(
                    $business,
                    $request,
                    $package_details
                );

                $this->enforceDailyCollectionModuleChoice($package_details, $request);
                $this->enforceMasterModulePermissionHierarchy($package_details, $request);
                $this->syncLegacyVatPackageDetails($package_details);

                /*
                 |----------------------------------------------------------
                 | Record which module flags this save switches OFF.
                 |----------------------------------------------------------
                 |
                 | A Manage save rewrites package_details from the form. A box
                 | left unticked therefore writes 0, whether or not that was
                 | intended, and the consequence lands on business users as a
                 | hard failure elsewhere in the system.
                 |
                 | This happened three times in two days with enable_petro_module
                 | on one business: each save set it back to 0 and pumper users
                 | could no longer log in, because their business disappeared
                 | from the login selector. On 12 Aug the same save also switched
                 | off contact_module and realize_cheque, and users hit red
                 | errors naming internal setting keys while doing normal work.
                 |
                 | Nothing in the system recorded any of that. Each incident took
                 | an investigation to trace back to a single click.
                 |
                 | This only logs. It does not block the save, change what is
                 | written, or warn the operator - it makes the change visible
                 | afterwards, so the next occurrence is one grep rather than an
                 | afternoon.
                 */
                try {
                    $previousDetails = DB::table('subscriptions')
                        ->where('business_id', $id)
                        ->value('package_details');

                    $previousDetails = is_string($previousDetails)
                        ? (\json_decode($previousDetails, true) ?: [])
                        : [];

                    $switchedOff = [];

                    foreach ($previousDetails as $key => $wasValue) {
                        // Only flag-like values. Dates, names and counts change
                        // for ordinary reasons and would drown the signal.
                        if (! in_array((string) $wasValue, ['1', 'true'], true)) {
                            continue;
                        }

                        $nowValue = $package_details[$key] ?? null;

                        if (in_array((string) $nowValue, ['0', 'false', ''], true) || $nowValue === null) {
                            $switchedOff[] = (string) $key;
                        }
                    }

                    if ($switchedOff !== []) {
                        \Log::warning('Manage save switched modules OFF', [
                            'business_id' => (int) $id,
                            'switched_off' => $switchedOff,
                            'count' => count($switchedOff),
                            'by_user_id' => optional(auth()->user())->id,
                            'by_username' => optional(auth()->user())->username,
                        ]);
                    }
                } catch (\Throwable $e) {
                    // A diagnostic must never break the save it is observing.
                    \Log::warning('Manage save: could not compare module states.', [
                        'business_id' => (int) $id,
                        'message' => $e->getMessage(),
                    ]);
                }

                Subscription::where('business_id', $id)->update(['package_details' => json_encode($package_details)]);
                $tenantSyncPayload['package_details'] = $package_details;

                \Log::info('Manage module settings saved', [
                    'business_id' => (int) $id,
                    'setting_count' => count($package_details),
                    'subscription_found' => DB::table('subscriptions')
                        ->where('business_id', $id)
                        ->exists(),
                ]);
            }

            $module_permission_locations = $request->input('module_permission_location', []);

            foreach ($module_permission_locations as $module => $locations) {
                    if (empty($module)) {
                        continue;
                    }

                    // For text fields like number_of_pumps, keep all values (including 0 and empty strings)
                    // For checkbox fields, remove unchecked values (only keep 1)
                    if ($module === 'number_of_pumps') {
                        // Keep all values for number_of_pumps (text field)
                        // Filter out null values but keep 0 and empty strings
                        $locations = array_filter($locations, fn($v) => $v !== null);
                    } else {
                        // Remove unchecked values for checkbox fields
                        $locations = array_filter($locations, fn($v) => $v == 1);
                    }

                    ModulePermissionLocation::updateOrCreate(
                        [
                            'business_id' => $id,
                            'module_name' => $module,
                        ],
                        [
                            'locations' => $locations,
                        ]
                    );
                }

            $location_checkbox_modules = [
                'restaurant_module',
                'mf_module',
                'agent_module',
                'sales_agent_module',
                'accounting_module',
                'petro_pd_module',
                'ev_charging_module',
                'loan_module',
                'pump_operator_module',
            ];

            foreach ($location_checkbox_modules as $module) {
                if (! array_key_exists($module, $module_permission_locations)) {
                    ModulePermissionLocation::updateOrCreate(
                        [
                            'business_id' => $id,
                            'module_name' => $module,
                        ],
                        [
                            'locations' => [],
                        ]
                    );
                }
            }

            // business_type_id is nullable and protected by a foreign key.
            // HTML/JSON forms submit an unselected option as an empty string;
            // MySQL treats that as an invalid FK value rather than NULL.
            $requestedBusinessTypeId = trim((string) $request->input('business_type_id', ''));
            $business_data['business_type_id'] = (
                $requestedBusinessTypeId !== ''
                && ctype_digit($requestedBusinessTypeId)
                && (int) $requestedBusinessTypeId > 0
            ) ? (int) $requestedBusinessTypeId : null;

            $business_data['background_showing_type']         = $request->background_showing_type;
            $business_data['customer_interest_deduct_option'] = ! empty($request->customer_interest_deduct_option) ? $request->customer_interest_deduct_option : 0;
            $business_common_settings = is_array($business->common_settings) ? $business->common_settings : [];
            $package_disk_size = (float) optional(optional($subscription)->package)->max_disk_size;
            $requested_disk_size = (float) $request->input('max_disk_size', 0);

            if ($requested_disk_size > 0 && $requested_disk_size != $package_disk_size) {
                $business_common_settings['max_disk_size'] = $requested_disk_size;
            } else {
                unset($business_common_settings['max_disk_size']);
            }

            if (!empty($request->my_auto)) {
                $business_common_settings['is_my_auto'] = 1;
            } else {
                unset($business_common_settings['is_my_auto']);
            }

            $business_data['common_settings'] = $business_common_settings;
            //upload background image file
            if ($request->hasfile('background_image')) {
                $file                              = $request->file('background_image');
                $path                              = $file->store("business_data/{$id}", 'public');
                $business_data['background_image'] = $path;
            }
            //upload logo image file
            if ($request->hasfile('logo')) {
                $file                  = $request->file('logo');
                $path                  = $file->store("business_logos", 'public');
                $business_data['logo'] = $path;
            }

            $business_data['day_end_enable'] = $request->day_end_enable == '1' ? 1 : 0;
            // $business_data['pos_80_mm'] = $request->pos_80_mm == '1' ? 1 : 0;
            // dd($id,$request,Business::find($id),$business_data);
            // Handle business images on main Save as well
            $business = Business::find($id);
            if ($business) {
                $imageFields = [
                    'home_banner_image'   => 'home_banner_path',
                    'login_page_image'    => 'login_image_path',
                    'register_page_image' => 'register_image_path',
                    'site_logo'           => 'site_logo_path',
                    'favicon'             => 'favicon_path',
                ];

                foreach ($imageFields as $requestField => $dbField) {
                    if ($request->hasFile($requestField)) {
                        // Delete old if exists
                        if (! empty($business->$dbField)) {
                            \Storage::delete($business->$dbField);
                        }
                        // Store
                        $uploadedFile       = $request->file($requestField);
                        $path               = $uploadedFile->store("business_images/{$business->id}", 'public');
                        $business->$dbField = $path;
                    }
                }
                $business->save();
            }

            Business::where('id', $id)->update($business_data);
            $tenantSyncPayload['common_settings'] = $business_common_settings;

            /*
             | FINAL HIERARCHY RULE (2026-09-09)
             |
             | Do NOT rebuild business.enabled_modules from package_details here.
             | Level 1 parent visibility belongs exclusively to Manage Side Bar.
             | This legacy/full Manage save is allowed to update second-level
             | package/page/feature values, while enforceHierarchy() above has
             | already mirrored the saved Manage Side Bar parent state back into
             | legacy package aliases. Reconstructing enabled_modules here would
             | give Manage/Manage Page a second parent authority and could silently
             | re-enable a module that Super Admin had disabled in Manage Side Bar.
             |
             | Tenant enabled_modules synchronization is therefore performed only
             | by saveSidebarModules(), the authoritative Level-1 save path.
             */

            DB::commit();

            /*
             | IS2339 - one authoritative write path for BOTH Manage and
             | Manage New. This resolves the central registry business to its
             | operational tenant database, validates each location id and
             | stores custom/new methods with their page flags.
             */
            if ($paymentOptionsPayload !== []) {
                try {
                    $paymentRegistryBusiness = \Modules\Superadmin\Services\CentralContext::findBusinessOrFail((int) $id);
                    $paymentLocationsWritten = app(\Modules\Superadmin\Services\PaymentMethodDefaultsService::class)
                        ->saveBusinessOptions($paymentRegistryBusiness, $paymentOptionsPayload);

                    Log::info('Legacy Manage Payment Options saved to operational database.', [
                        'business_id' => (int) $id,
                        'locations_written' => $paymentLocationsWritten,
                    ]);
                } catch (\Throwable $paymentSaveException) {
                    Log::error('Manage settings committed but operational Payment Options save failed.', [
                        'business_id' => (int) $id,
                        'message' => $paymentSaveException->getMessage(),
                        'file' => $paymentSaveException->getFile(),
                        'line' => $paymentSaveException->getLine(),
                    ]);

                    $this->managePerformanceService()->forgetBusiness((int) $id);
                    return $this->manageSaveRedirect(
                        (int) $id,
                        false,
                        'Other Manage settings were saved, but Payment Options could not be updated. Please retry Payment Options.',
                        true
                    );
                }
            }

            // Queue one consolidated tenant copy only after the central
            // transaction is durable. Tenant-copy problems must never undo or
            // report failure for a Manage page that was already committed.
            if ($tenantSyncPayload !== []) {
                try {
                    $this->queueManageTenantSync((int) $id, $tenantSyncPayload);
                } catch (\Throwable $syncException) {
                    Log::warning('Manage settings saved but tenant sync could not be queued', [
                        'business_id' => (int) $id,
                        'message' => $syncException->getMessage(),
                    ]);
                }
            }

            $output = [
                'success' => 1,
                'msg' => 'Manage permissions saved successfully.',
            ];
            $this->managePerformanceService()->forgetBusiness((int) $id);
            return $this->manageSaveRedirect((int) $id, true, (string) $output['msg']);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            // return "Line:" . $e->getLine() . "Message:" . $e->getMessage();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => 'Manage permissions could not be saved. Please try again.',
            ];

            return $this->manageSaveRedirect((int) $id, false, (string) $output['msg'], true);
        }
    }

    /**
     * Backfill missing VAT aliases without overwriting an explicitly saved
     * permission. Both VAT sections existed in production for a long time;
     * treating the newer alias as unconditionally canonical reversed an
     * unchecked legacy child immediately after Save.
     */
    protected function syncLegacyVatPackageDetails(array &$package_details): void
    {
        $legacyVatMap = [
            'vat_sale' => 'vat_main_sale',
            'list_vat_sale' => 'vat_main_list_vat_sale',
            'vat_purchase' => 'vat_main_purchase',
            'list_vat_purchase' => 'vat_main_list_vat_purchase',
            'vat_expense' => 'vat_main_expense',
            'list_vat_expense' => 'vat_main_list_vat_expense',
            'vat_products' => 'vat_main_products',
            'vat_contacts' => 'vat_main_contacts',
            'vat_credit_bill' => 'vat_main_credit_bill',
            'vat_meter_sales' => 'vat_main_meter_sales',
            'customized_vat_invoices' => 'vat_main_customized_invoices',
            'fleet_vat_invoice2' => 'vat_main_fleet_invoices',
            'vat_linked_accounts' => 'vat_main_linked_accounts',
            'vat_delete_customer_statement' => 'vat_main_delete_customer_statement',
            'vat_delete_statement_payment' => 'vat_main_delete_statement_payments',
            'enable_126_statement' => 'vat_main_enabled_126_statement',
        ];

        foreach ($legacyVatMap as $legacyKey => $newKey) {
            if (!array_key_exists($legacyKey, $package_details)
                && array_key_exists($newKey, $package_details)) {
                $package_details[$legacyKey] = (int) !empty($package_details[$newKey]);
            } elseif (!array_key_exists($newKey, $package_details)
                && array_key_exists($legacyKey, $package_details)) {
                $package_details[$newKey] = (int) !empty($package_details[$legacyKey]);
            }
        }
    }

    /**
     * Business Manage can touch the same subscription several times during one
     * save. Queue one consolidated tenant sync and run it after the HTTP
     * response so the administrator is not held waiting for every tenant DB.
     */
    protected function syncSubscriptionToTenantDatabases(Subscription $subscription): void
    {
        $this->queueManageTenantSync((int) $subscription->business_id, [
            'subscription_id' => (int) $subscription->id,
        ]);
    }

    private function queueManageTenantSync(int $businessId, array $payload, bool $force = false): void
    {
        if ($businessId <= 0) {
            return;
        }

        /*
         | When SUPERADMIN_USE_ACTIVE_CONNECTION is on, Super Admin is editing
         | ONE database directly - usually a tenant's own. Pushing that outward
         | would treat tenant rows as if they were the central registry and
         | copy them across the estate by id, onto unrelated companies.
         |
         | The save itself already went to the right place, so there is nothing
         | to propagate for legacy active-connection saves. New authority paths
         | that explicitly write CENTRAL (Manage New / Manage Side Bar) pass
         | $force=true because their tenant compatibility copy still must update.
         */
        if ((bool) config('tenancy.superadmin_use_active_connection', false) && ! $force) {
            return;
        }

        $current = $this->deferredManageTenantSync[$businessId] ?? [];
        $this->deferredManageTenantSync[$businessId] = array_replace($current, $payload);

        if ($this->deferredManageTenantSyncRegistered) {
            return;
        }

        $this->deferredManageTenantSyncRegistered = true;
        app()->terminating(function (): void {
            $this->flushDeferredManageTenantSync();
        });
    }

    private function flushDeferredManageTenantSync(): void
    {
        $pending = $this->deferredManageTenantSync;
        $this->deferredManageTenantSync = [];
        $this->deferredManageTenantSyncRegistered = false;

        if ($pending === []) {
            return;
        }

        $businessIds = array_map('intval', array_keys($pending));
        $centralConnection = config('database.connections.system.database')
            ? 'system'
            : config('tenancy.database.central_connection', 'mysql');
        $baseDatabase = (string) config('database.connections.mysql.database');

        try {
            $centralBusinessColumns = ['id', 'company_number'];
            try {
                if (Schema::connection($centralConnection)->hasColumn('business', 'global_uid')) {
                    $centralBusinessColumns[] = 'global_uid';
                }
            } catch (\Throwable $e) {
                // Column probe failed; fall back to the legacy column set.
            }

            $centralBusinesses = DB::connection($centralConnection)
                ->table('business')
                ->select($centralBusinessColumns)
                ->whereIn('id', $businessIds)
                ->get()
                ->keyBy('id');

            $subscriptionIds = array_values(array_filter(array_map(
                static fn (array $task) => !empty($task['subscription_id']) ? (int) $task['subscription_id'] : null,
                $pending
            )));
            /*
             * S717: this read supplies the package_details copied into tenant
             * databases.  Super Admin routes may be running with the default
             * connection pointed at a tenant, so a bare Subscription query can
             * fetch a same-id tenant row instead of the central subscription we
             * just updated.  Pin it to the central connection explicitly.
             */
            $subscriptions = $subscriptionIds === []
                ? collect()
                : Subscription::on($centralConnection)
                    ->whereIn('id', $subscriptionIds)
                    ->get()
                    ->keyBy('id');

            foreach (\App\Tenant::all() as $tenant) {
                $tenantDatabase = $tenant->getDatabaseName();
                if (empty($tenantDatabase) || $tenantDatabase === $baseDatabase) {
                    continue;
                }

                try {
                    DB::purge('mysql');
                    config(['database.connections.mysql.database' => $tenantDatabase]);
                    DB::reconnect('mysql');

                    $connection = DB::connection('mysql')->setDatabaseName($tenantDatabase);
                    $schema = $connection->getSchemaBuilder();
                    $hasBusiness = $schema->hasTable('business');
                    $hasSubscriptions = $schema->hasTable('subscriptions');
                    $hasEnabledModules = $hasBusiness && $schema->hasColumn('business', 'enabled_modules');
                    $hasCommonSettings = $hasBusiness && $schema->hasColumn('business', 'common_settings');
                    $hasGlobalUid = $hasBusiness && $schema->hasColumn('business', 'global_uid');

                    if (!$hasBusiness) {
                        continue;
                    }

                    foreach ($pending as $businessId => $task) {
                        $centralBusiness = $centralBusinesses->get((int) $businessId);
                        if (empty($centralBusiness)) {
                            continue;
                        }

                        /*
                         * MATCHING ORDER MATTERS.
                         *
                         * `id` and `company_number` are BOTH per-database values.
                         * Auto-increment runs independently in every tenant, and
                         * company_number is derived from the id, so neither one
                         * identifies a business across databases. Two unrelated
                         * companies can hold the same id in different databases -
                         * this really happened: central business 15 and
                         * nivasa_sonali business 15 were different companies with
                         * different owners, both carrying company_number RA-21.
                         *
                         * Matching on those keys can therefore push one company's
                         * settings onto another company's record. global_uid is
                         * generated once in central and copied to the tenant, so
                         * it is the only safe key. The old rules stay as a
                         * fallback for rows that predate the column, and are
                         * skipped entirely once a uid is present on both sides.
                         */
                        $tenantBusiness = null;

                        if ($hasGlobalUid && !empty($centralBusiness->global_uid)) {
                            $tenantBusiness = $connection->table('business')
                                ->select('id')
                                ->where('global_uid', $centralBusiness->global_uid)
                                ->first();

                            if (empty($tenantBusiness)) {
                                // Central has a uid but this tenant has no row
                                // carrying it. Falling back to id here is what
                                // would cross-link unrelated companies, so stop.
                                Log::info('Manage tenant sync skipped: no global_uid match in tenant database.', [
                                    'tenant_database' => $tenantDatabase,
                                    'business_id' => (int) $businessId,
                                    'global_uid' => $centralBusiness->global_uid,
                                ]);
                                continue;
                            }
                        }

                        if (empty($tenantBusiness)) {
                            $tenantBusiness = $connection->table('business')
                                ->select('id')
                                ->where('id', $centralBusiness->id)
                                ->first();
                        }
                        if (empty($tenantBusiness) && !empty($centralBusiness->company_number)) {
                            $tenantBusiness = $connection->table('business')
                                ->select('id')
                                ->where('company_number', $centralBusiness->company_number)
                                ->first();
                        }
                        if (empty($tenantBusiness)) {
                            continue;
                        }

                        $businessUpdate = [];
                        if ($hasEnabledModules && array_key_exists('enabled_modules', $task)) {
                            $businessUpdate['enabled_modules'] = json_encode($task['enabled_modules']);
                        }
                        if ($hasCommonSettings && array_key_exists('common_settings', $task)) {
                            $businessUpdate['common_settings'] = json_encode($task['common_settings']);
                        }
                        if ($businessUpdate !== []) {
                            $connection->table('business')->where('id', $tenantBusiness->id)->update($businessUpdate);
                        }

                        if (!$hasSubscriptions) {
                            continue;
                        }

                        $subscription = !empty($task['subscription_id'])
                            ? $subscriptions->get((int) $task['subscription_id'])
                            : null;

                        if ($subscription) {
                            $tenantPayload = [
                                'id' => $subscription->id,
                                'business_id' => $tenantBusiness->id,
                                'package_id' => $subscription->package_id,
                                'start_date' => $subscription->getRawOriginal('start_date'),
                                'trial_end_date' => $subscription->getRawOriginal('trial_end_date'),
                                'end_date' => $subscription->getRawOriginal('end_date'),
                                'package_price' => $subscription->package_price,
                                'package_details' => $subscription->getRawOriginal('package_details'),
                                'created_id' => $subscription->created_id,
                                'paid_via' => $subscription->paid_via,
                                'payment_transaction_id' => $subscription->payment_transaction_id,
                                'status' => $subscription->status,
                                'deleted_at' => $subscription->getRawOriginal('deleted_at'),
                                'created_at' => $subscription->getRawOriginal('created_at'),
                                'updated_at' => $subscription->getRawOriginal('updated_at'),
                                'module_activation_details' => $subscription->getRawOriginal('module_activation_details'),
                                'customer_credit_notification_type' => $subscription->getRawOriginal('customer_credit_notification_type'),
                            ];
                            $connection->table('subscriptions')->updateOrInsert(
                                ['id' => $subscription->id],
                                $tenantPayload
                            );
                        } elseif (array_key_exists('package_details', $task)) {
                            $dateToday = now()->toDateString();
                            $tenantSubscription = $connection->table('subscriptions')
                                ->where('business_id', $tenantBusiness->id)
                                ->where('status', 'approved')
                                ->whereDate('start_date', '<=', $dateToday)
                                ->where(function ($query) use ($dateToday) {
                                    $query->whereDate('end_date', '>=', $dateToday)->orWhereNull('end_date');
                                })
                                ->orderByDesc('id')
                                ->first();
                            if ($tenantSubscription) {
                                $connection->table('subscriptions')
                                    ->where('id', $tenantSubscription->id)
                                    ->update(['package_details' => json_encode($task['package_details'])]);
                            }
                        }

                        /*
                         * S717: make the tenant's own subscription agree with
                         * Manage Side Bar even when no trustworthy central
                         * subscription copy exists for this business.
                         *
                         * Many module sidebars (Petro PD included) still read
                         * package_details directly.  enabled_modules may already
                         * be correct in the tenant business row while an older
                         * subscription still carries <module>_module = 0, which
                         * hides an enabled module.  Re-apply ONLY the stable
                         * parent hierarchy to the tenant's active subscription
                         * after any central subscription copy above.  Child page
                         * permissions are deliberately preserved by
                         * enforceHierarchy().
                         */
                        if (array_key_exists('enabled_modules', $task)) {
                            try {
                                $tenantBusinessModel = Business::on('mysql')->find((int) $tenantBusiness->id);
                                if ($tenantBusinessModel) {
                                    $dateToday = now()->toDateString();
                                    $tenantSubscription = $connection->table('subscriptions')
                                        ->where('business_id', $tenantBusiness->id)
                                        ->where('status', 'approved')
                                        ->whereDate('start_date', '<=', $dateToday)
                                        ->where(function ($query) use ($dateToday) {
                                            $query->whereDate('end_date', '>=', $dateToday)
                                                ->orWhereNull('end_date');
                                        })
                                        ->orderByDesc('end_date')
                                        ->orderByDesc('start_date')
                                        ->orderByDesc('id')
                                        ->first(['id', 'package_details']);

                                    if ($tenantSubscription) {
                                        $tenantDetails = json_decode((string) ($tenantSubscription->package_details ?? ''), true);
                                        $tenantDetails = is_array($tenantDetails) ? $tenantDetails : [];

                                        $this->modulePermissionService()->enforceHierarchy(
                                            $tenantDetails,
                                            $tenantBusinessModel,
                                            null
                                        );

                                        $connection->table('subscriptions')
                                            ->where('id', $tenantSubscription->id)
                                            ->update(['package_details' => json_encode($tenantDetails)]);
                                    }
                                }
                            } catch (\Throwable $e) {
                                Log::warning('Manage Side Bar: tenant subscription hierarchy could not be refreshed.', [
                                    'tenant_database' => $tenantDatabase,
                                    'business_id' => (int) $tenantBusiness->id,
                                    'message' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Deferred Manage sync failed for tenant database {$tenantDatabase}: {$e->getMessage()}");
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Deferred Manage tenant sync failed: ' . $e->getMessage());
        } finally {
            DB::purge('mysql');
            config(['database.connections.mysql.database' => $baseDatabase]);
            DB::reconnect('mysql');
        }
    }

    /**
     * Keep tenant subscription copies aligned with the central package details.
     */
    protected function syncPackageDetailsToTenantDatabases(int $businessId, array $package_details): void
    {
        $this->queueManageTenantSync($businessId, ['package_details' => $package_details]);
    }

    /**
     * Keep tenant business copies aligned with the central Manage/Manage Side
     * Bar parent states. Without this sync a tenant request can keep reading an
     * old disabled marker and return the module-disabled 403 page even though
     * Super Admin has just enabled the module.
     */
    protected function syncBusinessEnabledModulesToTenantDatabases(int $businessId, array $enabledModules): void
    {
        $this->queueManageTenantSync($businessId, ['enabled_modules' => array_values(array_unique(array_map('strval', $enabledModules)))]);
    }

    protected function syncBusinessCommonSettingsToTenantDatabases(int $businessId, array $commonSettings): void
    {
        $this->queueManageTenantSync($businessId, ['common_settings' => $commonSettings]);
    }

    private function assignSupervisorPermissions(Role $role)
    {
        //supervisor rolw permissions
        $permissionNames = [
            'deposits.cash_deposit',
            'deposit.cash_deposit',
            'deposits.cheque_deposit',
            'deposit.cheque_deposit',
            'deposits.card_deposit',
            'deposit.card_deposit',
            'deposit.realize_cheque',
            'supplier.view',
            'customer_pay_due',
            'customer.view',
            'purchase.view',
            'purchase.create',
            'expense.create',
            'daily_report.view',
            'credit_status.view',
            'sell.payments',
            'dashboard.data',
            'account.access',
            'day_end.view',
            'bulk_assign_pumps',
            'petro.access',
            'discount.access',
            'pum_operator.active_inactive',
            'pump_operator.dashboard',
            'pumper_dashboard_settings',
            'pump_operator.main_system',
            'pump_operator.access_code',
            'daily_pump_status.edit'

        ];

        $permissions = Permission::whereIn('name', $permissionNames)->pluck('id');

        $role->permissions()->sync($permissions);
    }

    public function getVarialbeSelected($option_id, $opt_vars)
    {
        if (! empty($opt_vars)) {
            $opt_vars = json_decode($opt_vars);
            if (! empty($opt_vars)) {
                foreach ($opt_vars as $opt) {
                    $op = CompanyPackageVariable::where('id', $opt)->first();
                    if ($op->variable_options == $option_id) {
                        return '1';
                    }
                }
            }
        }

        return '0';
    }

    public function deleteAllPreviousAccountTransactions($business_id)
    {
        $transaction_account = AccountTransaction::leftjoin('transactions', 'account_transactions.transaction_id', 'transactions.id')
            ->where('transactions.business_id', $business_id)->where('account_transactions.deleted_at', null)->select('account_transactions.id')->get();
        $tansaction_payments = AccountTransaction::leftjoin('transaction_payments', 'account_transactions.transaction_payment_id', 'transaction_payments.id')
            ->where('transaction_payments.business_id', $business_id)->where('account_transactions.deleted_at', null)->select('account_transactions.id')->get();
        foreach ($transaction_account as $at) {
            AccountTransaction::where('id', $at->id)->delete();
        }
        foreach ($tansaction_payments as $ap) {
            AccountTransaction::where('id', $ap->id)->delete();
        }
    }

    public function loginAsBusiness($id)
    {
        if (! class_exists(SuperAdminImpersonation::class)) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'The secure Login As Business service is not available. Please clear the application cache and try again.',
            ]);
        }

        $business_util = new BusinessUtil;
        $currentUser = Auth::user();
        $superadminUser = $currentUser;

        if ($currentUser && SuperAdminImpersonation::isActive($currentUser, request())) {
            $originalUserId = SuperAdminImpersonation::originalUserId(request());
            $superadminUser = $originalUserId > 0 ? User::find($originalUserId) : null;
        }

        if (! $superadminUser || ! $superadminUser->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $superadminUserId = (int) $superadminUser->id;

        /*
         * Always impersonate the configured business owner. The previous
         * where(business_id)->first() query was non-deterministic and could
         * select a different user when the tenant contained several users.
         */
        $business = Business::with('currency')->findOrFail($id);
        $userColumns = [
            'id',
            'username',
            'surname',
            'first_name',
            'last_name',
            'email',
            'business_id',
            'language',
            'pump_operator_id',
            'is_pump_operator',
            'is_property_user',
        ];

        $user = User::query()
            ->select($userColumns)
            ->where('business_id', $business->id)
            ->where('id', $business->owner_id)
            ->first();

        // Backward compatibility for older businesses with a missing owner_id.
        if (! $user) {
            $user = User::query()
                ->select($userColumns)
                ->where('business_id', $business->id)
                ->where('status', 'active')
                ->orderBy('id')
                ->first();
        }

        if (! $user) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('lang_v1.user_not_found'),
            ]);
        }

        Auth::loginUsingId($user->id);

        $session_data = $user->toArray();
        $currency = $business->currency;
        $currency_data = [
            'id' => $currency->id,
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'thousand_separator' => $currency->thousand_separator,
            'decimal_separator' => $currency->decimal_separator,
        ];

        request()->session()->forget('user');
        request()->session()->forget('business');
        request()->session()->forget('currency');
        request()->session()->forget('financial_year');
        request()->session()->put('user', $session_data);
        request()->session()->put('business', $business);
        request()->session()->put('currency', $currency_data);

        // Set current financial year to session.
        $financial_year = $business_util->getCurrentFinancialYear($business->id);
        request()->session()->put('financial_year', $financial_year);

        SuperAdminImpersonation::begin(
            request(),
            $superadminUserId,
            $user,
            (int) $business->id
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return redirect('home');
    }
    public function backToSuperadmin()
    {
        if (! class_exists(SuperAdminImpersonation::class)) {
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect('/login');
        }

        $business_util = new BusinessUtil;
        $impersonatedUser = Auth::user();

        // Never trust a loose legacy session value here. The return operation is
        // allowed only when the complete signed impersonation context still
        // matches the authenticated tenant user, business, and current session.
        if (! $impersonatedUser
            || ! SuperAdminImpersonation::isActive($impersonatedUser, request())) {
            SuperAdminImpersonation::clear(request());
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect('/login');
        }

        // Restore the exact Super Admin who started this signed session.
        $superadminUserId = SuperAdminImpersonation::originalUserId(request());
        $user = $superadminUserId > 0 ? User::find($superadminUserId) : null;

        if (! $user || ! $user->can('superadmin')) {
            SuperAdminImpersonation::clear(request());
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect('/login');
        }

        Auth::loginUsingId($user->id);
        $session_data = [
            'id' => $user->id,
            'username' => $user->username,
            'surname' => $user->surname,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'business_id' => $user->business_id,
            'language' => $user->language,
            'pump_operator_id' => $user->pump_operator_id,
            'is_pump_operator' => $user->is_pump_operator,
            'is_property_user' => $user->is_property_user,
        ];
        $business = Business::with('currency')->findOrFail($user->business_id);

        $currency = $business->currency;
        $currency_data = [
            'id' => $currency->id,
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'thousand_separator' => $currency->thousand_separator,
            'decimal_separator' => $currency->decimal_separator,
        ];

        request()->session()->forget('user');
        request()->session()->forget('business');
        request()->session()->forget('currency');
        request()->session()->forget('financial_year');

        request()->session()->put('user', $session_data);
        request()->session()->put('business', $business);
        request()->session()->put('currency', $currency_data);
        SuperAdminImpersonation::clear(request());
        request()->session()->regenerate();

        $financial_year = $business_util->getCurrentFinancialYear($business->id);
        request()->session()->put('financial_year', $financial_year);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return redirect('home');
    }

    /**
     *  @doc 6920 OTP Verification to log in to the System
     *
     *
     * @param Interger $user_id User Tabel Id
     * @param Boolean $status
     * @return Void
     * @dev Sakhawat Kamran
     * re_captcha_enabled
     * opt_verification
     **/
    public function addUserSetting($user_id, $request)
    {
        $setting = UserSetting::where('user_id', $user_id)->first();
        if (! $setting) {
            $setting          = new UserSetting();
            $setting->user_id = $user_id;
        }
        if ($request->opt_verification) {
            $setting->opt_verification_enabled      = $request->opt_verification;
            $setting->opt_verification_enabled_date = now();
        } else {
            $setting->opt_verification_enabled      = $request->opt_verification;
            $setting->opt_verification_enabled_date = null;

        }
        if ($request->re_captcha_enabled) {
            $setting->re_captcha_enabled      = $request->re_captcha_enabled;
            $setting->re_captcha_enabled_date = now();
        } else {
            $setting->re_captcha_enabled      = $request->re_captcha_enabled;
            $setting->re_captcha_enabled_date = null;

        }
        $setting->save();
    }


    /**
     * Super Admin > All Business: Manage Side Bar modal.
     * Parent sidebar permission screen. Existing Manage page remains child-level.
     */
    public function manageSidebarModules($id)
    {
        // Central connection, explicitly. Super Admin routes run through
        // `tenant.context`, so on any non-central host the default connection
        // points at a tenant database and a bare Business::findOrFail() would
        // render this modal from the wrong database.
        $business = \Modules\Superadmin\Services\CentralContext::findBusinessOrFail($id);
        $enabledModules = [];
        $sidebarModules = [];
        $sidebarModuleStates = [];

        try {
            $service = $this->modulePermissionService();
            $enabledModules = $service->decodeEnabledModules($business->enabled_modules ?? null);
            $sidebarModules = $service->discoverSidebarModules($enabledModules);

            // Keep the modal compatible during rolling/partial deployments.
            // Older service versions do not have sidebarModuleStates().
            if (method_exists($service, 'sidebarModuleStates')) {
                $sidebarModuleStates = $service->sidebarModuleStates($business, $sidebarModules);
            } else {
                foreach (array_keys($sidebarModules) as $moduleKey) {
                    $sidebarModuleStates[$moduleKey] = method_exists($service, 'isEnabled')
                        ? $service->isEnabled($business, $moduleKey)
                        : in_array($moduleKey, $enabledModules, true);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Unable to build Manage Side Bar modal; using safe fallback.', [
                'business_id' => $business->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $enabledModules = $this->decodeSidebarModulesFallback($business->getRawOriginal('enabled_modules'));
            $sidebarModules = $this->discoverSidebarModulesFallback($enabledModules);
            $sidebarModuleStates = $this->sidebarModuleStatesFallback($business, $sidebarModules, $enabledModules);
        }

        return response(
            view('superadmin::business.partials.manage_sidebar_modules', compact(
                'business',
                'enabledModules',
                'sidebarModules',
                'sidebarModuleStates'
            ))->render()
        );
    }

    /** Save parent sidebar module visibility for one business. */
    public function saveSidebarModules(Request $request, $id)
    {
        /*
         * S777 - IMPORTANT CONNECTION RULE.
         *
         * The All Businesses page is built with CentralContext::businessQuery(),
         * and the row id posted by the Manage Side Bar modal therefore belongs
         * to THE SAME connection returned by CentralContext::connectionName().
         * Save that exact row on that exact connection.
         *
         * A previous revision tried to take an active-tenant row and remap it
         * to central by global_uid before saving. That contradicts
         * SUPERADMIN_USE_ACTIVE_CONNECTION: when that compatibility switch is
         * enabled, CentralContext deliberately makes BOTH the list and writes
         * use the active database. Older tenant databases may also have no
         * global_uid yet, so the remap aborted with HTTP 409 and the browser
         * showed only "Not saved. Please check the log.".
         *
         * Using the same resolver for read + write fixes the save without ever
         * mapping businesses by unsafe per-database numeric ids.
         */
        $business = \Modules\Superadmin\Services\CentralContext::findBusinessOrFail($id);

        $useActiveConnection = (bool) config('tenancy.superadmin_use_active_connection', false);
        $centralConnection = \Modules\Superadmin\Services\CentralContext::trueCentralConnectionName();
        $businessDatabase = (string) $business->getConnection()->getDatabaseName();
        $centralDatabase = (string) \Illuminate\Support\Facades\DB::connection($centralConnection)->getDatabaseName();

        // In active-connection compatibility mode the business is already being
        // edited in its own tenant DB. Do not push that row back through the
        // central-to-tenant propagation path after saving it.
        $directActiveTenantSave = $useActiveConnection
            && $businessDatabase !== ''
            && $centralDatabase !== ''
            && $businessDatabase !== $centralDatabase;

        $modules = $request->input('enabled_modules', []);

        if (!is_array($modules)) {
            $modules = [];
        }

        \Log::warning('MANAGE_SIDEBAR_SAVE_DEBUG', [
            'business_id' => (int) $business->id,
            'business_name' => $business->name ?? null,
            'global_uid' => $business->global_uid ?? null,
            'has_user_management_new' => in_array('user_management_new', $modules, true),
            'user_management_matches' => array_values(array_filter($modules, static function ($m) {
                return is_scalar($m) && stripos((string) $m, 'user') !== false;
            })),
        ]);

        try {
            $this->modulePermissionService()->storeEnabledModules($business, $modules);
        } catch (\Throwable $e) {
            Log::error('Normal Manage Side Bar save failed; using safe fallback.', [
                'business_id' => $business->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->storeSidebarModulesFallback($business, $modules);
        }

        /*
         * ROOT CAUSE of "disabled modules still show in the Main System Sidebar".
         *
         * This installation is multi-database: every business has its own
         * tenant database with its own `business` and `subscriptions` tables,
         * and the Main System Sidebar renders from the TENANT copy.
         *
         * storeEnabledModules() only writes the CENTRAL database. Every other
         * Manage save path queues a tenant copy through queueManageTenantSync(),
         * but this endpoint never did - so unchecking a module in Manage Side
         * Bar updated the central row while the tenant row kept the module
         * enabled, and the sidebar carried on showing it.
         *
         * Queue the same consolidated tenant copy the Manage page uses. It runs
         * after the HTTP response, so the Save button stays fast.
         */
        $freshBusiness = $business->fresh();

        $storedModules = $freshBusiness->enabled_modules ?? [];
        if (is_string($storedModules)) {
            // NOTE: this file imports GuzzleHttp\json_decode, which THROWS on
            // malformed JSON instead of returning null. Call the global one.
            $decodedModules = \json_decode($storedModules, true);
            $storedModules = is_array($decodedModules) ? $decodedModules : [];
        }
        $storedModules = is_array($storedModules) ? $storedModules : [];

        $tenantSyncPayload = [
            'enabled_modules' => array_values(array_unique(array_map('strval', $storedModules))),
        ];

        // storeEnabledModules() has already rewritten the parent flags in the
        // central subscriptions.package_details. Sending the subscription id
        // makes the tenant copy take that whole row, so the tenant package can
        // no longer report a disabled module as permitted.
        if (! $directActiveTenantSave) {
            try {
                // Subscription::active_subscription() resolves on the default
                // connection, which can be a tenant database under
                // `tenant.context`. This propagation branch is entered only
                // when the authoritative save is central, so pin the read to
                // the true central connection.
                $centralConnection = \Modules\Superadmin\Services\CentralContext::trueCentralConnectionName();

                /*
                 |------------------------------------------------------------------
                 | MA-007: pick the subscription by global_uid, not business_id.
                 |------------------------------------------------------------------
                 | This is the row that gets copied DOWN to the tenant, and the
                 | sidebar's package flags are read from that copy.
                 |
                 | Central holds one set of subscription rows per business_id, and
                 | every tenant has its own business 2. Selecting by id therefore
                 | copied an unrelated company's package into the tenant.
                 |
                 | Falls back to business_id only when the business has no uid,
                 | which is the pre-MA-007 behaviour and no worse than before.
                 */
                $subscriptionUid = $business->global_uid ?? null;

                $activeSubscription = Subscription::on($centralConnection)
                    ->when(! empty($subscriptionUid), function ($query) use ($subscriptionUid) {
                        $query->where('global_uid', $subscriptionUid);
                    }, function ($query) use ($business) {
                        $query->where('business_id', $business->id);
                    })
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', now()->toDateString())
                    ->where(function ($query) {
                        $query->whereDate('end_date', '>=', now()->toDateString())
                            ->orWhereNull('end_date');
                    })
                    ->orderByDesc('id')
                    ->first();

                if (! empty($activeSubscription)) {
                    $tenantSyncPayload['subscription_id'] = (int) $activeSubscription->id;
                }
            } catch (\Throwable $e) {
                Log::warning('Manage Side Bar: active subscription could not be resolved for tenant sync.', [
                    'business_id' => $business->id,
                    'message' => $e->getMessage(),
                ]);
            }

            try {
                $this->queueManageTenantSync((int) $business->id, $tenantSyncPayload, true);
            } catch (\Throwable $e) {
                Log::warning('Manage Side Bar saved but tenant sync could not be queued.', [
                    'business_id' => $business->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Keep the current tenant session aligned immediately when its own
        // sidebar settings are changed; no logout/login is required.
        if ((int) session('user.business_id') === (int) $business->id) {
            session()->put('business.enabled_modules', $freshBusiness->enabled_modules ?? []);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'msg' => 'Manage Side Bar saved successfully.']);
        }

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Manage Side Bar saved successfully.']);
    }

    private function decodeBusinessEnabledModules($enabledModules): array
    {
        return $this->modulePermissionService()->decodeEnabledModules($enabledModules);
    }

    private function discoverBusinessSidebarModules(array $enabledModules): array
    {
        return $this->modulePermissionService()->discoverSidebarModules($enabledModules);
    }

    private function sidebarDisplayNameFromKey($key): string
    {
        return $this->modulePermissionService()->displayName($key);
    }

    /**
     * Safe decoder used only if the automatic registry/service cannot be loaded.
     * It accepts normal string lists, JSON strings, nested arrays and old
     * associative permission maps without raising array-to-string notices.
     */
    private function decodeSidebarModulesFallback($stored): array
    {
        if (!is_array($stored)) {
            if (empty($stored) || !is_scalar($stored)) {
                return [];
            }
            $decoded = json_decode((string) $stored, true);
            $stored = is_array($decoded) ? $decoded : [];
        }

        $values = [];
        $append = function ($items) use (&$append, &$values): void {
            foreach ((array) $items as $key => $value) {
                if (!is_int($key) && (is_bool($value) || is_numeric($value) || is_string($value))) {
                    $flag = is_string($value) ? strtolower(trim($value)) : $value;
                    if (in_array($flag, [1, '1', true, 'true', 'yes', 'on'], true)) {
                        $values[] = (string) $key;
                        continue;
                    }
                    if (in_array($flag, [0, '0', false, 'false', 'no', 'off', ''], true)) {
                        continue;
                    }
                }

                if (is_array($value)) {
                    $append($value);
                } elseif (is_scalar($value) && trim((string) $value) !== '') {
                    $values[] = trim((string) $value);
                }
            }
        };
        $append($stored);

        return array_values(array_unique($values));
    }

    private function normaliseSidebarModuleKeyFallback($value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $value);
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', (string) $value);
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim((string) preg_replace('/_+/', '_', (string) $value), '_');

        return substr($value, -7) === '_module' ? substr($value, 0, -7) : $value;
    }

    private function fallbackInstalledModuleStatusMap(): array
    {
        $statuses = [];
        $statusPath = base_path('modules_statuses.json');
        if (is_file($statusPath)) {
            $decoded = json_decode((string) @file_get_contents($statusPath), true);
            $statuses = is_array($decoded) ? $decoded : [];
        }

        $map = [];
        foreach (glob(base_path('Modules/*'), GLOB_ONLYDIR) ?: [] as $directory) {
            $folder = basename($directory);
            if (preg_match('/(?:^|[._-])(before|backup|bak|old|copy|disabled)(?:[._-]|$)/i', $folder)) {
                continue;
            }

            $canonical = $this->normaliseSidebarModuleKeyFallback($folder);
            if ($canonical === '') {
                continue;
            }

            $moduleName = '';
            $moduleJson = $directory . '/module.json';
            if (is_file($moduleJson)) {
                $decoded = json_decode((string) @file_get_contents($moduleJson), true);
                if (is_array($decoded)) {
                    $moduleName = trim((string) ($decoded['name'] ?? ''));
                }
            }

            $map[$canonical] = $moduleName !== ''
                && array_key_exists($moduleName, $statuses)
                && $statuses[$moduleName] === true;
        }

        return $map;
    }

    private function discoverSidebarModulesFallback(array $enabledModules): array
    {
        $modules = [];
        $add = function ($key, $label = null) use (&$modules): void {
            $key = $this->normaliseSidebarModuleKeyFallback($key);
            if ($key !== '' && !isset($modules[$key])) {
                $modules[$key] = $label ?: ucwords(str_replace('_', ' ', $key));
            }
        };

        $installedStatus = $this->fallbackInstalledModuleStatusMap();

        foreach ($enabledModules as $module) {
            if (strpos((string) $module, '__sidebar_disabled__:') === 0) {
                continue;
            }
            $canonical = $this->normaliseSidebarModuleKeyFallback($module);
            if (array_key_exists($canonical, $installedStatus) && !$installedStatus[$canonical]) {
                continue;
            }
            $add($module);
        }

        foreach ([
            'accounting' => 'Accounting Module',
            'contact' => 'Contact Module',
            'finance_reports' => 'Finance Reports',
            'pumper_dashboard' => 'Pumper Dashboard',
            'customers' => 'Customers',
            'suppliers' => 'Suppliers',
            'products' => 'Products',
            'chequer' => 'Chequer',
            'expense_manager' => 'Expense Manager',
            'hr_manager' => 'HR Manager',
            'communication_hub' => 'Communication Hub',
        ] as $key => $label) {
            $add($key, $label);
        }

        foreach (glob(base_path('Modules/*'), GLOB_ONLYDIR) ?: [] as $directory) {
            $folder = basename($directory);
            $canonical = $this->normaliseSidebarModuleKeyFallback($folder);
            if (in_array(strtolower($folder), ['superadmin', 'coreui', 'customizer', 'development', 'translation'], true)
                || empty($installedStatus[$canonical])) {
                continue;
            }

            $label = $folder;
            $moduleJson = $directory . '/module.json';
            if (is_file($moduleJson)) {
                $decoded = json_decode((string) @file_get_contents($moduleJson), true);
                if (is_array($decoded) && !empty($decoded['name'])) {
                    $label = (string) $decoded['name'];
                }
            }
            $add($folder, ucwords(str_replace(['_', '-'], ' ', $label)));
        }

        asort($modules, SORT_NATURAL | SORT_FLAG_CASE);

        return $modules;
    }

    private function sidebarModuleStatesFallback(Business $business, array $sidebarModules, array $enabledModules): array
    {
        $stored = $this->decodeSidebarModulesFallback($business->getRawOriginal('enabled_modules'));
        $enabled = [];
        $disabled = [];

        foreach ($stored as $value) {
            $value = (string) $value;
            if (strpos($value, '__sidebar_disabled__:') === 0) {
                $disabled[$this->normaliseSidebarModuleKeyFallback(substr($value, 21))] = true;
            } else {
                $enabled[$this->normaliseSidebarModuleKeyFallback($value)] = true;
            }
        }

        $directoryModules = array_filter($this->fallbackInstalledModuleStatusMap());

        $states = [];
        foreach (array_keys($sidebarModules) as $key) {
            $canonical = $this->normaliseSidebarModuleKeyFallback($key);
            $states[$key] = empty($disabled[$canonical])
                && (!empty($enabled[$canonical]) || !empty($directoryModules[$canonical]));
        }

        return $states;
    }

    private function storeSidebarModulesFallback(Business $business, array $selectedModules): void
    {
        $current = $this->decodeSidebarModulesFallback($business->getRawOriginal('enabled_modules'));

        // Feed the already-disabled keys back into the catalogue. Otherwise
        // discoverSidebarModulesFallback() drops every marker value, the key
        // falls out of $available, no marker is rewritten below, and the module
        // silently returns to the Main System Sidebar on the next save.
        $catalogue_input = $current;
        foreach ($current as $stored_module) {
            $stored_module = (string) $stored_module;
            if (strpos($stored_module, '__sidebar_disabled__:') === 0) {
                $catalogue_input[] = substr($stored_module, 21);
            }
        }

        $available = $this->discoverSidebarModulesFallback($catalogue_input);
        $selected = [];

        foreach ($selectedModules as $module) {
            if (!is_scalar($module)) {
                continue;
            }
            $canonical = $this->normaliseSidebarModuleKeyFallback($module);
            if ($canonical !== '' && array_key_exists($canonical, $available)) {
                $selected[$canonical] = true;
            }
        }

        // Preserve current values that are not managed by this screen, rather
        // than deleting an inactive module's previous business choice.
        $stored = [];
        foreach ($current as $value) {
            $value = (string) $value;
            if (strpos($value, '__sidebar_disabled__:') === 0) {
                $canonical = $this->normaliseSidebarModuleKeyFallback(substr($value, 21));
                if ($canonical !== '' && !array_key_exists($canonical, $available)) {
                    $stored['__sidebar_disabled__:' . $canonical] = true;
                }
                continue;
            }
            $canonical = $this->normaliseSidebarModuleKeyFallback($value);
            if ($canonical !== '' && !array_key_exists($canonical, $available)) {
                $stored[$canonical] = true;
            }
        }

        foreach (array_keys($selected) as $canonical) {
            $stored[$canonical] = true;
            $stored[$canonical . '_module'] = true;
        }

        foreach (array_keys($available) as $moduleKey) {
            $canonical = $this->normaliseSidebarModuleKeyFallback($moduleKey);
            if ($canonical !== '' && empty($selected[$canonical])) {
                $stored['__sidebar_disabled__:' . $canonical] = true;
            }
        }

        $values = array_keys($stored);
        sort($values, SORT_NATURAL | SORT_FLAG_CASE);
        $business->enabled_modules = $values;
        $business->save();

        // The normal path does this inside storeEnabledModules(); the fallback
        // used to skip it, so the old sidebar permissions stayed cached and the
        // disabled module kept rendering until the cache expired.
        \App\Utils\SidebarPermissionUtil::forgetBusinessCache((int) $business->id);
    }

    /**
     * Hydrate the child Manage permission page from the parent Manage Side Bar popup.
     * One service is now the single source of truth, so the popup, Manage page,
     * sidebar, and direct URL guards cannot disagree on enabled modules.
     */
    private function syncManageModuleEnableFromSidebarMaster(array &$manage_module_enable, $business): void
    {
        if ($business instanceof Business) {
            $this->modulePermissionService()->syncManageModuleEnable($manage_module_enable, $business);
        }
    }


    public function updateImages(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);

        // Validate the incoming files
        $validated = $request->validate([
            'home_banner_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'login_page_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'register_page_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'site_logo'           => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
            'favicon'             => 'nullable|image|mimes:ico,png|max:100',
        ]);

        // Handle each image upload
        $imageFields = [
            'home_banner_image'   => 'home_banner_path',
            'login_page_image'    => 'login_image_path',
            'register_page_image' => 'register_image_path',
            'site_logo'           => 'site_logo_path',
            'favicon'             => 'favicon_path',
        ];

        // Log incoming upload intent
        \Log::info('Superadmin updateImages: request received', [
            'table'          => 'business',
            'business_id'    => $business->id,
            'user_id'        => optional($request->user())->id,
            'incoming_files' => array_keys(array_filter($request->allFiles())),
        ]);

        foreach ($imageFields as $requestField => $dbField) {
            if ($request->hasFile($requestField)) {
                // Delete old file if exists
                if ($business->$dbField) {
                    \Log::info('Superadmin updateImages: deleting existing file', [
                        'table'       => 'business',
                        'business_id' => $business->id,
                        'column'      => $dbField,
                        'old_path'    => $business->$dbField,
                    ]);
                    Storage::delete($business->$dbField);
                }

                // Store new file in storage/app/public/business_images/{business_id}
                $uploadedFile = $request->file($requestField);
                $originalName = $uploadedFile->getClientOriginalName();
                $path         = $uploadedFile->store(
                    "public/business_images/{$business->id}"
                );

                // Save path to database (remove 'public/' from path)
                $business->$dbField = str_replace('public/', '', $path);

                // Log stored file details
                \Log::info('Superadmin updateImages: file stored', [
                    'table'             => 'business',
                    'business_id'       => $business->id,
                    'column'            => $dbField,
                    'request_field'     => $requestField,
                    'original_filename' => $originalName,
                    'stored_path'       => $path,
                    'db_value'          => $business->$dbField,
                ]);
            }
        }

        $business->save();

        return redirect()->back()->with('success', 'Images updated successfully');
    }
    public function deleteImage(Request $request, $businessId, $type)
    {
        $business = Business::findOrFail($businessId);

        $fieldMap = [
            'home_banner'    => 'home_banner_path',
            'login_image'    => 'login_image_path',
            'register_image' => 'register_image_path',
            'site_logo'      => 'site_logo_path',
            'favicon'        => 'favicon_path',
        ];

        if (! array_key_exists($type, $fieldMap)) {
            abort(404);
        }

        $field = $fieldMap[$type];

        if ($business->$field) {
            Storage::delete('public/' . $business->$field);
            $business->$field = null;
            $business->save();
        }

        return redirect()->back()->with('success', 'Image deleted successfully');
    }



    /**
     * S344: Daily Collection and Daily Collection SW are mutually exclusive.
     * The two switches now live under Daily Collection Settings, not Petro Module.
     * If both are posted, Daily Collection SW wins and normal Daily Collection is disabled.
     */
    private function enforceDailyCollectionModuleChoice(array &$package_details, Request $request): void
    {
        /*
         * IS1641 / Sidebar master permission fix.
         * Daily Collection SW has existed under more than one checkbox/key name in
         * older Manage forms and in the auto-generated permission sections.  The
         * sidebar must read one clear parent enablement value.  Therefore every
         * known alias is normalised here when saving Superadmin > Business > Manage.
         */
        $isChecked = function (array $keys) use ($request): bool {
            foreach ($keys as $key) {
                if ($request->has($key)) {
                    $value = $request->input($key);
                    if (is_array($value)) {
                        return !empty($value);
                    }
                    return in_array((string) $value, ['1', 'on', 'true', 'yes'], true);
                }
            }
            return false;
        };

        $dailyCollection = $isChecked([
            'daily_collection',
            'daily_collection_module',
            'enable_petro_daily_collection',
            'daily_collection_sub_menu',
        ]) ? 1 : 0;

        $dailyCollectionSw = $isChecked([
            'daily_collection_sw',
            'daily_collection_sw_module',
            'dailycollectionsw',
            'dailycollectionsw_module',
            'daily_collection_sw_enabled',
            'DailyCollectionSW',
            'daily_collection_sw_sub_menu',
            'daily_collection_settings_daily_collection_sw',
        ]) ? 1 : 0;

        // SW and normal Daily Collection must never create two sidebar menus.
        if ($dailyCollectionSw === 1) {
            $dailyCollection = 0;
        }

        $package_details['daily_collection'] = $dailyCollection;
        $package_details['daily_collection_module'] = $dailyCollection;
        $package_details['daily_collection_sub_menu'] = $dailyCollection;
        $package_details['enable_petro_daily_collection'] = $dailyCollection;

        // Write all SW aliases with the same value so old layouts, new layouts,
        // auto sections and direct sidebar guards all resolve the same answer.
        $package_details['daily_collection_sw'] = $dailyCollectionSw;
        $package_details['daily_collection_sw_module'] = $dailyCollectionSw;
        $package_details['dailycollectionsw'] = $dailyCollectionSw;
        $package_details['dailycollectionsw_module'] = $dailyCollectionSw;
        $package_details['daily_collection_sw_enabled'] = $dailyCollectionSw;
        $package_details['DailyCollectionSW'] = $dailyCollectionSw;
        $package_details['daily_collection_sw_sub_menu'] = $dailyCollectionSw;
        $package_details['daily_collection_settings_daily_collection_sw'] = $dailyCollectionSw;
    }

    /**
     * Super Admin > All Business > Manage: standalone module sidebar flags.
     * These flags are intentionally independent and default to disabled.
     */
    private function applyStandaloneSidebarPermissionFlags(array &$package_details, Request $request): void
    {
        $flags = [
            'my_health_module',
            'customers_module',
            'customers_import_contact_tab_page',
            'customers_customer_reference_tab_page',
            'customers_customer_statement_tab_page',
            'customers_customer_payment_tab_page',
            'customers_outstanding_received_tab_page',
            'customers_stock_taking_page',
            'customers_edit_received_outstanding',
            'customers_customer_payment_bulk',
            'customers_list_customer_payments',
            'customers_customer_interest',
            'customers_interest_settings',
            'customers_ledger_discount',
            'customers_customer_statements_pmts',
            'customers_list_customer_loans',
            'customers_settings',
            'customers_import_opening_balances',
            'customers_returned_cheque_details',
            'customers_manual_bills',
            'development',
            'dashboard_logistics',
            'daily_collection',
            'daily_collection_sw',
            'customized_report',
            'customized_reports_module',
            // Leads-New standalone module: keep Manage save, sidebar and reload state in sync.
            'leads_new_module',
            'leads_new_dashboard',
            'leads_new_leads',
            'leads_new_reports',
            'leads_new_settings',
        ];

        foreach ($flags as $flag) {
            $package_details[$flag] = ((string) $request->input($flag, '0') === '1') ? 1 : 0;
        }

        // Keep old/new MyHealth and Customized Report keys in sync without enabling anything by default.
        $package_details['myhealth_module'] = $package_details['my_health_module'];
        $package_details['myhealthmembers_module'] = $package_details['my_health_module'];

        if (!empty($package_details['customized_report']) || !empty($package_details['customized_reports_module'])) {
            $package_details['customized_report'] = 1;
            $package_details['customized_reports_module'] = 1;
        } else {
            $package_details['customized_report'] = 0;
            $package_details['customized_reports_module'] = 0;
        }

        // Leads-New aliases used by older/newer sidebar conditions.
        $package_details['enable_leads_new'] = !empty($package_details['leads_new_module']) ? 1 : 0;
        $package_details['leadsnew_module'] = !empty($package_details['leads_new_module']) ? 1 : 0;
    }


    /**
     * Master module permission hierarchy.
     *
     * Super Admin > All Business > Manage uses a parent module checkbox plus
     * many child page/tab checkboxes.  Child permissions must never make a
     * module visible when the parent module is disabled.  This method is kept
     * defensive: it normalises common module key aliases and removes child
     * permissions when the parent is not enabled, while leaving unrelated
     * module business logic untouched.
     */
    private function enforceMasterModulePermissionHierarchy(array &$package_details, Request $request): void
    {
        $businessId = $request->route('id') ?: $request->route('business') ?: $request->input('business_id') ?: null;

        if (!$businessId) {
            $route = $request->route();
            foreach ($route ? (array) $route->parameters() : [] as $param) {
                if (is_numeric($param)) {
                    $businessId = $param;
                    break;
                }
            }
        }

        $business = null;
        if ($businessId) {
            try {
                // Super Admin routes can execute under tenant.context. Parent
                // hierarchy must always be read from the CENTRAL business row,
                // because Manage Side Bar writes its authoritative Level-1 state
                // there and then synchronises it down to the tenant.
                $business = \Modules\Superadmin\Services\CentralContext::findBusinessOrFail((int) $businessId);
            } catch (\Throwable $e) {
                return;
            }
        }

        if (!$business) {
            return;
        }

        $this->modulePermissionService()->enforceHierarchy($package_details, $business, $request);
    }

}
