@extends('layouts.web', ['nav' => false, 'banner' => false, 'footer' => false, 'cookie' => false, 'setting' => true, 'title' => true, 'title' => __('Sign In')])

@php
    use App\AdPageSlot;
    use App\Setting;
    use App\System;
    use Modules\Superadmin\Entities\Package;
    use Illuminate\Support\Facades\Cache;

    $loginCachePrefix = 'login_page:' . strtolower(request()->getHost()) . ':';
    $systemProperty = static function (string $key) use ($loginCachePrefix) {
        return Cache::remember(
            $loginCachePrefix . 'system:' . $key,
            300,
            static fn () => System::getProperty($key)
        );
    };
    $adPageSlots = Cache::remember($loginCachePrefix . 'ad_page_slots', 300, static fn () => AdPageSlot::page('landing_page')->select('ad_page_slots.*')->limit(2)->get());
    $business_settings = Cache::remember($loginCachePrefix . 'site_settings', 300, static fn () => DB::table('site_settings')->where('id', 1)->first());

    // Login business isolation rules:
    // - A tenant host may display ONLY businesses from that tenant database.
    // - Central/system records may supply package metadata only; they may never
    //   replace or invent the tenant business identity.
    // - A business is selected by its tenant business ID, not by company number,
    //   so duplicate/stale company numbers cannot resolve another business.
    $date_today = \Carbon::today()->toDateString();
    $pumperDashboardNewLoginReady = is_file(base_path('Modules/PumperDashboardNew/module.json'))
        || \Illuminate\Support\Facades\Route::has('pumper-dashboard-new.login');
    $riceMillDashboardLoginReady = is_file(base_path('Modules/RiceMill/module.json'))
        && \Illuminate\Support\Facades\Route::has('rice-mill-dashboard.login');

    $normalizeLoginHost = static function ($value): string {
        $host = strtolower(trim((string) $value, ". \t\n\r\0\x0B"));
        return $host;
    };

    $requestHost = $normalizeLoginHost(request()->getHost());
    $centralDomains = collect(config('tenancy.central_domains', []))
        ->map($normalizeLoginHost)
        ->filter()
        ->unique()
        ->values()
        ->all();

    $isTenantLogin = function_exists('tenancy') && tenancy()->initialized;
    $isCentralLoginHost = in_array($requestHost, $centralDomains, true);

    if ($isTenantLogin) {
        // Authoritative identity source: CURRENT TENANT DATABASE only.
        $tenantBusinesses = DB::table('business')
            ->leftJoin('business_locations', 'business_locations.business_id', '=', 'business.id')
            ->whereNotNull('business.company_number')
            ->whereNotNull('business.name')
            ->select(
                'business.id',
                'business.name',
                'business.company_number',
                'business.common_settings',
                DB::raw('MIN(business_locations.name) as location_name')
            )
            ->groupBy('business.id', 'business.name', 'business.company_number', 'business.common_settings')
            ->orderBy('business.name')
            ->get();

        $tenantBusinessIds = $tenantBusinesses->pluck('id')->map(static fn ($id) => (int) $id)->values()->all();

        // Prefer the tenant-local subscription copy. This keeps the login
        // display flags aligned with the exact tenant business ID.
        $tenantSubscriptionRows = collect();
        if (!empty($tenantBusinessIds)
            && \Illuminate\Support\Facades\Schema::hasTable('subscriptions')) {
            $tenantSubscriptionQuery = DB::table('subscriptions')
                ->whereIn('business_id', $tenantBusinessIds)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date_today)
                ->where(function ($query) use ($date_today) {
                    $query->whereDate('end_date', '>=', $date_today)
                        ->orWhereNull('end_date');
                })
                ->whereNotNull('package_details');

            if (\Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'deleted_at')) {
                $tenantSubscriptionQuery->whereNull('deleted_at');
            }

            $tenantSubscriptionRows = $tenantSubscriptionQuery
                ->select('id', 'business_id', 'package_details', 'start_date', 'end_date')
                ->orderByDesc('end_date')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get()
                ->groupBy(static fn ($row) => (int) $row->business_id);
        }

        // Backward-compatible metadata fallback for older tenants whose local
        // subscriptions table was not synced. The central match must satisfy
        // BOTH tenant business ID and company number. Company number alone is
        // never allowed to choose another central business.
        $centralSubscriptionRows = collect();
        if (!empty($tenantBusinessIds)) {
            try {
                $centralConnection = config('database.connections.system.database')
                    ? 'system'
                    : config('tenancy.database.central_connection', 'mysql');

                $centralSubscriptionRows = DB::connection($centralConnection)
                    ->table('business')
                    ->join('subscriptions', function ($join) use ($date_today) {
                        $join->on('business.id', '=', 'subscriptions.business_id')
                            ->whereDate('subscriptions.start_date', '<=', $date_today)
                            ->where(function ($query) use ($date_today) {
                                $query->whereDate('subscriptions.end_date', '>=', $date_today)
                                    ->orWhereNull('subscriptions.end_date');
                            })
                            ->where('subscriptions.status', 'approved');
                    })
                    ->whereIn('business.id', $tenantBusinessIds)
                    ->whereNotNull('subscriptions.package_details')
                    ->select(
                        'business.id as business_id',
                        'business.company_number',
                        'subscriptions.package_details',
                        'subscriptions.id as subscription_id',
                        'subscriptions.start_date',
                        'subscriptions.end_date'
                    )
                    ->orderByDesc('subscriptions.end_date')
                    ->orderByDesc('subscriptions.start_date')
                    ->orderByDesc('subscriptions.id')
                    ->get()
                    ->groupBy(static fn ($row) => (int) $row->business_id);
            } catch (\Throwable $e) {
                // Business identity remains safe because it already came from
                // the tenant DB. Missing central metadata must not hide it.
                $centralSubscriptionRows = collect();
            }
        }

        $businesses = $tenantBusinesses->map(function ($tenantBusiness) use ($tenantSubscriptionRows, $centralSubscriptionRows) {
            $localSubscription = $tenantSubscriptionRows
                ->get((int) $tenantBusiness->id, collect())
                ->first();

            $packageDetails = $localSubscription->package_details ?? null;

            if (empty($packageDetails)) {
                $centralCandidate = $centralSubscriptionRows
                    ->get((int) $tenantBusiness->id, collect())
                    ->first(function ($row) use ($tenantBusiness) {
                        return (string) ($row->company_number ?? '') === (string) $tenantBusiness->company_number;
                    });

                $packageDetails = $centralCandidate->package_details ?? '{}';
            }

            $tenantBusiness->package_details = $packageDetails ?: '{}';
            return $tenantBusiness;
        })->values();
    } elseif ($isCentralLoginHost) {
        // Canonical central-host behaviour only. Never run this branch on an
        // unresolved non-central host because that could leak central businesses.
        $businesses = DB::table('business')
            ->leftJoin('subscriptions', function ($join) use ($date_today) {
                $join
                    ->on('business.id', '=', 'subscriptions.business_id')
                    ->whereDate('subscriptions.start_date', '<=', $date_today)
                    ->where(function ($query) use ($date_today) {
                        $query->whereDate('subscriptions.end_date', '>=', $date_today)
                            ->orWhereNull('subscriptions.end_date');
                    })
                    ->where('subscriptions.status', 'approved');
            })
            ->whereNotNull('business.company_number')
            ->whereNotNull('business.name')
            ->whereNotNull('subscriptions.package_details')
            ->leftJoin('business_locations', 'business_locations.business_id', '=', 'business.id')
            ->select(
                'business.id',
                'business.name',
                'business.company_number',
                'business.common_settings',
                'subscriptions.package_details',
                DB::raw('MIN(business_locations.name) as location_name')
            )
            ->groupBy('business.id', 'business.name', 'business.company_number', 'business.common_settings', 'subscriptions.package_details')
            ->orderBy('business.name')
            ->get();
    } else {
        // A non-central host without initialized tenancy must NEVER fall back to
        // central business data. The controller performs a safe domain->tenant
        // initialization fallback before this view is rendered; if that fails,
        // show no business choices rather than a business from another database.
        $businesses = collect();
    }

    /*
     | Login Dashboard visibility
     |--------------------------------------------------------------------------
     | The Login dropdown must follow the same parent-module authority as
     | Super Admin > All Businesses > Manage Side Bar. Package flags and old
     | common_settings values are not allowed to make a dashboard visible when
     | its owning module is disabled for the selected business.
     |
     | SidebarPermissionUtil intentionally resolves the authoritative business
     | Manage Side Bar state for both central/master and tenant login hosts.
     */
    $dashboardModuleEnabled = static function ($moduleKeys, int $businessId): bool {
        $moduleKeys = is_array($moduleKeys) ? $moduleKeys : [$moduleKeys];

        try {
            foreach ($moduleKeys as $moduleKey) {
                if (\App\Utils\SidebarPermissionUtil::isManageSidebarEnabled((string) $moduleKey, $businessId)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // A login page must fail closed for optional dashboard entries.
        }

        return false;
    };

    $pumperDashboardAvailable = false;
    $pumperDashboardNewAvailable = false;
    $riceMillDashboardAvailable = false;
    $medicalDashboardAvailable = false;
    $employeeDashboardAvailable = false;
    $shippingDashboardAvailable = false;
    $projectDashboardAvailable = false;
    $bakeryDashboardAvailable = false;
    $myAutoDashboardAvailable = false;
    $distributionDealerDashboardAvailable = false;
    $myHealthDashboardAvailable = false;

    foreach ($businesses as $key => $business) {
        $businessId = (int) $business->id;

        // Each login display is owned by its standalone/core module. The flag is
        // attached to the business object so the browser cannot build the display
        // list from stale package_details/common_settings values.
        $business->pumper_dashboard_enabled = $dashboardModuleEnabled('pumper_dashboard', $businessId) ? 1 : 0;
        $business->pumper_dashboard_new_enabled = ($pumperDashboardNewLoginReady
            && $dashboardModuleEnabled('pumper_dashboard_new', $businessId)) ? 1 : 0;
        $business->rice_mill_dashboard_enabled = ($riceMillDashboardLoginReady
            && $dashboardModuleEnabled('rice_mill', $businessId)) ? 1 : 0;
        $business->patient_dashboard_enabled = $dashboardModuleEnabled(['my_health', 'my_health_members'], $businessId) ? 1 : 0;
        $business->employee_dashboard_enabled = $dashboardModuleEnabled('hr', $businessId) ? 1 : 0;
        $business->shipping_dashboard_enabled = $dashboardModuleEnabled('shipping', $businessId) ? 1 : 0;
        $business->project_dashboard_enabled = $dashboardModuleEnabled('property', $businessId) ? 1 : 0;
        $business->bakery_dashboard_enabled = $dashboardModuleEnabled('bakery', $businessId) ? 1 : 0;
        $business->my_auto_dashboard_enabled = $dashboardModuleEnabled('my_auto', $businessId) ? 1 : 0;

        // Distribution Dealer is implemented by the standalone Customers portal.
        $business->distribution_dealer_dashboard_enabled = $dashboardModuleEnabled('customers', $businessId) ? 1 : 0;
        $business->my_health_dashboard_enabled = $dashboardModuleEnabled(['my_health', 'my_health_members'], $businessId) ? 1 : 0;

        $pumperDashboardAvailable = $pumperDashboardAvailable || !empty($business->pumper_dashboard_enabled);
        $pumperDashboardNewAvailable = $pumperDashboardNewAvailable || !empty($business->pumper_dashboard_new_enabled);
        $riceMillDashboardAvailable = $riceMillDashboardAvailable || !empty($business->rice_mill_dashboard_enabled);
        $medicalDashboardAvailable = $medicalDashboardAvailable || !empty($business->patient_dashboard_enabled);
        $employeeDashboardAvailable = $employeeDashboardAvailable || !empty($business->employee_dashboard_enabled);
        $shippingDashboardAvailable = $shippingDashboardAvailable || !empty($business->shipping_dashboard_enabled);
        $projectDashboardAvailable = $projectDashboardAvailable || !empty($business->project_dashboard_enabled);
        $bakeryDashboardAvailable = $bakeryDashboardAvailable || !empty($business->bakery_dashboard_enabled);
        $myAutoDashboardAvailable = $myAutoDashboardAvailable || !empty($business->my_auto_dashboard_enabled);
        $distributionDealerDashboardAvailable = $distributionDealerDashboardAvailable || !empty($business->distribution_dealer_dashboard_enabled);
        $myHealthDashboardAvailable = $myHealthDashboardAvailable || !empty($business->my_health_dashboard_enabled);

        // Keep only businesses that actually have a business-selectable dashboard.
        // Direct-only portal links (Distribution Dealer/My Health) do not need to
        // keep an otherwise ineligible business in the Choose Business modal.
        $has_login_display = !empty($business->pumper_dashboard_enabled)
            || !empty($business->pumper_dashboard_new_enabled)
            || !empty($business->rice_mill_dashboard_enabled)
            || !empty($business->patient_dashboard_enabled)
            || !empty($business->employee_dashboard_enabled)
            || !empty($business->shipping_dashboard_enabled)
            || !empty($business->project_dashboard_enabled)
            || !empty($business->bakery_dashboard_enabled)
            || !empty($business->my_auto_dashboard_enabled);

        if (!$has_login_display) {
            unset($businesses[$key]);
        }
    }

    $businesses = collect($businesses)->values();

    $settings = Cache::remember($loginCachePrefix . 'active_settings', 300, static fn () => DB::table('settings')->where('status', 1)->first());

    // Detect if user came from within the system (for conditional helper text)
    $came_from_system = false;
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        $app_host = parse_url(url('/'), PHP_URL_HOST);
        if ($app_host && strpos($referer, $app_host) !== false) {
            $came_from_system = true;
        }
    }

    $db_ads = Cache::remember($loginCachePrefix . 'ads', 300, static function () {
        return DB::table('ad_page_slots')
            ->join('ad_pages', 'ad_page_slots.ad_page_id', 'ad_pages.id')
            ->leftJoin('ads', 'ads.ad_page_slot_id', 'ad_page_slots.id')
            ->select([
                'ads.content',
                'ad_page_slots.*',
                'ad_pages.name as ad_page_name',
                'ad_page_slots.id as ad_page__slot_id',
            ])
            ->orderBy('ad_pages.id', 'ASC')
            ->get();
    });

    $ads = [];
    foreach ($db_ads as $ad) {
        $ads[$ad->ad_page_name . '_' . $ad->slot_no] = str_replace('/storage', '', $ad->content ?? '');
    }

    $show_message = json_decode($business_settings?->show_messages);
    if (!empty($show_message->lp_title)) {
        if ($show_message->lp_title == 1) {
            $login_page_title = $business_settings->login_page_title;
        } else {
            $login_page_title = '';
        }
    }
    if (!empty($show_message->lp_description)) {
        if ($show_message->lp_description == 1) {
            $login_page_description = $business_settings->login_page_description;
        } else {
            $login_page_description = '';
        }
    } else {
        $login_page_description = '';
    }
    if (!empty($show_message->lp_system_message)) {
        if ($show_message->lp_system_message == 1) {
            $login_page_general_message = $business_settings->login_page_general_message;
        } else {
            $login_page_general_message = '';
        }
    } else {
        $login_page_general_message = '';
    }
    $bg_showing_type = $business_settings?->background_showing_type;
    $lang_btn = $systemProperty('enable_lang_btn_login_page');
    $register_btn = $systemProperty('enable_register_btn_login_page');
    $enable_agent_register_btn_login_page = $systemProperty('enable_agent_register_btn_login_page');
    $visitor_register_btn = $systemProperty('enable_visitor_register_btn_login_page');
    $pricing_btn = $systemProperty('enable_pricing_btn_login_page');
    $enable_admin_login = $systemProperty('enable_admin_login');
    $enable_member_login = $systemProperty('enable_member_login');
    $enable_visitor_login = $systemProperty('enable_visitor_login');
    $enable_customer_login = $systemProperty('enable_customer_login');
    $general_message_pump_operator_dashbaord_checkbox = $systemProperty('general_message_pump_operator_dashbaord_checkbox');

    $util = new \App\Utils\BusinessUtil();

    $show_referrals_in_register_page = json_decode($systemProperty('show_referrals_in_register_page'), true);

    $show_give_away_gift_in_register_page = json_decode(
        $systemProperty('show_give_away_gift_in_register_page'),
        true,
    );

    $currencies = $util->allCurrencies();

    $timezone_list = $util->allTimeZones();
    $months = [];
    $accounting_methods = $util->allAccountingMethods();
    $package_id = 0;
    $agent_referral_code = 0;
    $districts = Cache::remember($loginCachePrefix . 'districts', 600, static fn () => App\Districts::select('id', 'name')->get());
    $towns = [];

    $enable_agent_login = $systemProperty('enable_agent_login');
    $enable_employee_login = $systemProperty('enable_employee_login');
    $enable_member_register_btn = $systemProperty('enable_member_register_btn_login_page');
    $enable_patient_register_btn = $systemProperty('enable_patient_register_btn_login_page');
    $enable_individual_register_btn = $systemProperty('enable_individual_register_btn_login_page');
    $enable_welcome_msg = $systemProperty('enable_welcome_msg');
    $business_or_entity = $systemProperty('business_or_entity');
    $enable_login_banner_image = $systemProperty('enable_login_banner_image');
    $login_banner_image = $systemProperty('login_banner_image');
    $enable_login_banner_html = $systemProperty('enable_login_banner_html');

    $login_banner_html = $systemProperty('login_banner_html');
    $array_values = [$lang_btn, $register_btn, $pricing_btn];
    if ($lang_btn == 1 || $register_btn == 1 || $pricing_btn == 1) {
        $frequency = array_count_values($array_values)[1];
    } else {
        $frequency = 0;
    }
    $margin = 0;
    if ($frequency == 3) {
        $margin = -20;
    }
    if ($frequency == 2) {
        $margin = -3;
    }
    if ($frequency == 1) {
        $margin = 11;
    }
    $user_types = [];
    $regis_single = true;
    if ($visitor_register_btn) {
        $user_types['visitor_register'] = __('superadmin::lang.visitor_register');
        if ($regis_single) {
            $regis_single = 'show_modal';
        } else {
            $regis_single = 'visitor_register';
        }
    }
    if ($enable_agent_register_btn_login_page) {
        $user_types['agent_register'] = __('superadmin::lang.agent_register');
        if ($regis_single) {
            $regis_single = 'show_modal';
        } else {
            $regis_single = 'agent_register';
        }
    }
    if ($register_btn) {
        $user_types['company_register'] = __('superadmin::lang.company_register');
        if ($regis_single) {
            $regis_single = 'show_modal';
        } else {
            $regis_single = 'company_register';
        }
    }
    if ($enable_customer_login) {
        $user_types['customer_register'] = __('superadmin::lang.customer_register');
        if ($regis_single) {
            $regis_single = 'show_modal';
        } else {
            $regis_single = 'customer_register';
        }
    }
    if ($enable_member_register_btn) {
        $user_types['memeber_regsiter'] = __('superadmin::lang.member_register');
        if ($regis_single) {
            $regis_single = 'show_modal';
        } else {
            $regis_single = 'memeber_regsiter';
        }
    }
    if ($enable_patient_register_btn) {
        $user_types['patient_register'] = __('superadmin::lang.my_health');
        if ($regis_single) {
            $regis_single = 'show_modal';
        } else {
            $regis_single = 'patient_register';
        }
    }

    $site_setting = App\SiteSettings::where('id', 1)->select('login_vehicle_registration')->first();
    $login_vehicle_registration = $site_setting->login_vehicle_registration ?? 0;
    if ($login_vehicle_registration) {
        if ($regis_single) {
            $regis_single = 'show_modal';
        } else {
            $regis_single = 'vehicle_register';
        }
        $user_types['vehicle_register'] = 'Register Your Vehicle';
    }

    $config = DB::table('config')->get();
    $required = env('AUTO_LOGIN_ENABLE') == 'on' ? '' : 'required';

    $business_categories = App\BusinessCategory::pluck('category_name', 'id');
    $countries = DB::table('countries')->orderBy('country')->pluck('country', 'country')->toArray();
    $my_auto_packages = Package::active()
        ->visible()
        ->where('auto_services_and_repair_module', 1)
        ->orderBy('sort_order')
        ->pluck('name', 'id');
    if (tenant()) {
        // Tenant context: default connection assumed to be tenant DB
        $settings = Setting::first();
        $config = DB::table('config')->get();
    } else {
        // Base system: use central connection explicitly
        $settings = Setting::on('mysql')->first();
        $config = DB::connection('mysql')->table('config')->get();
    }

    $app_name = System::getProperty('app_name');
@endphp

@section('content')
    @inject('request', 'Illuminate\Http\Request')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="{{ asset('AdminLTE/plugins/select2/css/select2.min.css') }}">
    <script src="{{ asset('AdminLTE/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- CSS file -->
    <style>
        .remove-border {
            border: none !important;
        }

        .form-header {
            font-size: 16px !important;
        }

        .select2 {
            width: 100% !important;
        }

        .invalid-feedback {
            display: block;
        }

        /* =========================================================
           Enterprise Login Modernization - Final Layout Fix
           User-approved direction:
           - Keep rotating banner system
           - Keep only 3 top buttons
           - Keep Login dropdown contents
           - No footer
           - No large empty top area
           - No large white button container
        ========================================================= */

        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100%;
        }

        body > div {
            margin: 0 !important;
            padding: 0 !important;
        }

        .erp-login-page-wrap {
            display: flex !important;
            flex-wrap: nowrap !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 0 14px 0 !important;
            gap: 0 !important;
            align-items: flex-start !important;
        }

        .erp-login-left-panel {
            flex: 0 0 50% !important;
            width: 50% !important;
            max-width: 50% !important;
            padding: 0 10px 24px 10px !important;
            margin: 0 !important;
        }

        .erp-login-banner-panel {
            flex: 0 0 50% !important;
            width: 50% !important;
            max-width: 50% !important;
            padding: 16px 16px 24px 8px !important;
            margin: 0 !important;
            display: flex !important;
            align-items: flex-start !important;
            justify-content: center !important;
        }

        .erp-login-banner-box {
            width: 100% !important;
            max-width: 760px !important;
            height: 620px !important;
            min-height: 620px !important;
            border-radius: 24px !important;
            overflow: hidden !important;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
            background: #f8fafc;
        }

        .erp-login-banner-box img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        .erp-login-card-container {
            width: 100%;
            max-width: 720px !important;
            margin-left: 0 !important;
            margin-right: auto !important;
            overflow: hidden !important;
        }

        .erp-login-top-actions {
            display: flex;
            flex-wrap: nowrap !important;
            align-items: center;
            justify-content: flex-start !important;
            gap: 12px !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 0 26px 0 !important;
            padding: 16px 0 0 0 !important;
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        .erp-login-top-actions .dropdown {
            display: inline-flex !important;
            flex: 1 1 0 !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .erp-login-top-actions > a.erp-login-top-btn {
            flex: 1 1 0 !important;
            min-width: 0 !important;
        }


        .erp-login-top-btn,
        .erp-login-top-actions .dropdown > .erp-login-top-btn {
            flex: 1 1 0 !important;
            width: auto !important;
            min-width: 0 !important;
            max-width: 100% !important;
            height: 56px !important;
            border-radius: 14px !important;
            border: none !important;
            padding: 0 12px !important;
            font-size: 15px !important;
            font-weight: 700 !important;
            line-height: 56px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            text-decoration: none !important;
            color: #ffffff !important;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.14);
            transition: all 0.22s ease;
            white-space: nowrap;
            overflow: hidden !important;
        }

        .erp-login-top-btn:hover,
        .erp-login-top-btn:focus {
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
            text-decoration: none !important;
            outline: none !important;
        }

        .erp-login-btn-repair {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        }

        .erp-login-btn-landing {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        }

        .erp-login-btn-login {
            background: linear-gradient(135deg, #0ea5e9, #2563eb) !important;
        }

        .erp-login-top-actions .dropdown-menu {
            border: none !important;
            border-radius: 14px !important;
            padding: 8px !important;
            min-width: 230px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.18) !important;
            z-index: 99999;
        }

        .erp-login-top-actions .dropdown-item {
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 600;
            color: #334155;
        }

        .erp-login-top-actions .dropdown-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .erp-login-card {
            background: #ffffff;
            border: 1px solid #e8eef5;
            border-radius: 24px;
            padding: 28px 34px 24px 34px !important;
            margin: 0 !important;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
        }

        .erp-login-title-area {
            text-align: center !important;
            margin-bottom: 18px !important;
            padding: 0 !important;
        }

        .erp-login-kicker {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            margin: 0 auto 12px auto !important;
        }

        .erp-login-title {
            font-size: 30px;
            line-height: 1.15;
            font-weight: 800;
            color: #0f172a;
            margin: 0 !important;
        }

        .erp-login-subtitle {
            display: none !important;
        }

        .erp-login-input-group {
            display: flex;
            align-items: center;
            height: 46px !important;
            min-height: 46px !important;
            margin-bottom: 12px !important;
            padding: 0 14px !important;
            background: #f8fafc;
            border: 1px solid #dbe3ec;
            border-radius: 12px !important;
            transition: all 0.2s ease;
        }

        .erp-login-input-group:focus-within {
            background: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
        }

        .erp-login-input {
            width: 100%;
            border: none !important;
            outline: none !important;
            background: transparent !important;
            color: #0f172a;
            font-size: 14px !important;
            font-weight: 600;
        }

        .erp-login-input::placeholder {
            color: #94a3b8;
            font-weight: 500;
        }

        .erp-login-input-icon {
            width: 20px !important;
            height: 20px !important;
            color: #94a3b8;
            margin-left: 14px;
            flex: 0 0 auto;
        }

        .erp-login-submit {
            width: 100%;
            height: 52px !important;
            border: none;
            border-radius: 12px !important;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.02em;
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.25);
            transition: all 0.22s ease;
        }

        .erp-login-submit:hover,
        .erp-login-submit:focus {
            transform: translateY(-1px);
            box-shadow: 0 18px 38px rgba(37, 99, 235, 0.32);
        }

        .erp-login-helper-text {
            display: block;
            margin-top: 12px;
            color: #64748b;
            font-size: 13px;
        }

        .erp-login-helper-text a {
            font-weight: 700;
            color: #2563eb !important;
        }

        .erp-login-card hr {
            border-top: 1px solid #eef2f7;
            margin: 16px 0 !important;
        }

        @media (max-width: 1199px) {
            .erp-login-page-wrap {
                flex-wrap: wrap !important;
            }

            .erp-login-left-panel,
            .erp-login-banner-panel {
                flex: 0 0 100% !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .erp-login-banner-panel {
                padding: 8px 10px 24px 10px !important;
            }

            .erp-login-banner-box {
                height: 420px !important;
                min-height: 420px !important;
            }

            .erp-login-top-actions {
                flex-wrap: wrap !important;
                width: 100% !important;
                padding-right: 10px !important;
            }

            .erp-login-top-btn,
            .erp-login-top-actions .dropdown,
            .erp-login-top-actions .dropdown > .erp-login-top-btn {
                width: 100% !important;
                min-width: 100% !important;
            }
        }

        @media (max-width: 768px) {
            .erp-login-page-wrap {
                margin: 0 !important;
                padding: 0 8px 14px 8px !important;
                flex-wrap: wrap !important;
            }

            .erp-login-left-panel,
            .erp-login-banner-panel {
                flex: 0 0 100% !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .erp-login-left-panel {
                padding: 0 0 20px 0 !important;
            }

            .erp-login-banner-panel {
                padding: 0 0 20px 0 !important;
            }

            .erp-login-banner-box {
                height: 320px !important;
                min-height: 320px !important;
            }

            .erp-login-top-actions {
                gap: 8px !important;
                margin-bottom: 18px !important;
            }

            .erp-login-card {
                padding: 22px !important;
                border-radius: 20px;
            }

            .erp-login-title {
                font-size: 25px;
            }
        }

    </style>
    {{-- Top ad strip intentionally removed from login page to avoid empty reserved space. Rotating right-side banners are kept below. --}}
    <div class="flex flex-wrap erp-login-page-wrap">
        <div class="w-full pb-6 lg:w-1/2 erp-login-left-panel">
            <div class="mx-auto erp-login-card-container">
<div class="erp-login-top-actions">

    <!-- Service & Repair Status -->
    <a class="erp-login-top-btn erp-login-btn-repair"
        data-toggle="modal"
        data-target="#repair_status_modal"
        href="#">
        Service & Repair Status
    </a>

    <!-- Login Dropdown -->
    <div class="dropdown">
        <button class="erp-login-top-btn erp-login-btn-login dropdown-toggle"
            type="button"
            id="loginDropdown"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            Login
        </button>

        <div class="dropdown-menu" aria-labelledby="loginDropdown">

            @if ($general_message_pump_operator_dashbaord_checkbox && $pumperDashboardAvailable)
                <a class="dropdown-item"
                    data-toggle="modal"
                    data-target="#pumper_choose_business"
                    href="#">
                    Pumper Dashboard
                </a>
            @endif

            @if ($pumperDashboardNewAvailable)
                <a class="dropdown-item"
                    data-toggle="modal"
                    data-target="#pumper_choose_business"
                    href="#">
                    Pumper Dashboard-New
                </a>
            @endif

            @if ($riceMillDashboardAvailable)
                <a class="dropdown-item"
                    data-toggle="modal"
                    data-target="#pumper_choose_business"
                    href="#">
                    Rice Mill Dashboard
                </a>
            @endif

            @if ($distributionDealerDashboardAvailable)
                <a class="dropdown-item"
                    href="{{ url('/distribution-dealer/login') }}">
                    Distribution Dealer
                </a>
            @endif

            @if ($medicalDashboardAvailable)
                <a class="dropdown-item"
                    data-toggle="modal"
                    data-target="#pumper_choose_business"
                    href="#">
                    Medical Institution / Entity
                </a>
            @endif

            @if ($myHealthDashboardAvailable)
                <a class="dropdown-item"
                    href="{{ url('/myhealth') }}">
                    My Health
                </a>
            @endif

        </div>
    </div>
    
    

    <!-- Sign Up Dropdown -->
    <div class="dropdown">

        <button class="erp-login-top-btn erp-login-btn-landing dropdown-toggle"
            type="button"
            id="signupDropdown"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            Sign Up
        </button>

        <div class="dropdown-menu" aria-labelledby="signupDropdown">

            <a class="dropdown-item"
                data-toggle="modal"
                data-target="#check_register_type_modal"
                href="#">
                Registration
            </a>

            <a class="dropdown-item"
                id="signup_my_health"
                href="#">
                My Health Registration
            </a>

        </div>

    </div>

</div>
                <div class="erp-login-card">
                    <div class="erp-login-title-area">
                        <span class="erp-login-kicker">{{ __('Sign In') }}</span>
                        <h3 class="erp-login-title">{{ __('Sign in your account') }}</h3>
                    </div>
                    @if (session('message'))
                        <span class="invalid-feedback ml-3" role="alert">
                            <strong>{{ session('message') }}</strong>
                        </span>
                    @endif
                    @error('email')
                        <span class="invalid-feedback ml-3" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    @error('password')
                        <span class="invalid-feedback ml-3" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror

                    @error('g-recaptcha-response')
                        <span class="invalid-feedback ml-3" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <form action="{{ url('/login') }}" id="login_form" method="POST">
                        @csrf
                        <div class="erp-login-input-group">
                            <input {{ $required }} autocomplete="email" autofocus
                                class="@error('email') is-invalid @enderror erp-login-input"
                                id="email" name="username" placeholder="{{ __('Please enter User Name') }}"
                                type="text" value="{{ old('email') }}">

                            <svg class="erp-login-input-icon" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                </path>
                            </svg>
                        </div>

                        <div class="erp-login-input-group">
                            <input {{ $required }} autocomplete="current-password"
                                class="@error('password') is-invalid @enderror erp-login-input"
                                id="password" name="password" placeholder="{{ __('Enter your password') }}"
                                type="password">

                            <svg class="erp-login-input-icon" fill="none" onmouseout="mouseoutPass();"
                                onmouseover="mouseoverPass();" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2"></path>
                                <path
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                </path>
                            </svg>
                        </div>
                        <!-- Hidden Latitude and Longitude Inputs -->
                        <input id="login_latitude" name="latitude" type="hidden">
                        <input id="login_longitude" name="longitude" type="hidden">

                        <!-- Google Maps JavaScript API -->
                        {{--  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCsHrbMB_bsgtLPVdv63bbvLOoszPN4bw8&libraries=places"></script>
                            <script>
                                // Check if the browser supports geolocation
                                // if (navigator.geolocation) {
                                //     navigator.geolocation.getCurrentPosition(function(position) {
                                //         var latitude = position.coords.latitude;
                                //         var longitude = position.coords.longitude;
                                //         document.getElementById('login_latitude').value = latitude;
                                //         document.getElementById('login_longitude').value = longitude;
                                //     }, function(error) {
                                //         console.log("Geolocation error: ", error);
                                //     });
                                // } else {
                                //     console.log("Geolocation is not supported by this browser.");
                                // }
                                // navigator.permissions.query({ name: 'geolocation' }).then(function(permissionStatus) {
                                //     permissionStatus.onchange = function() {
                                //         if (this.state === "granted") {
                                //             navigator.geolocation.getCurrentPosition(function(position) {
                                //                 var latitude = position.coords.latitude;
                                //                 var longitude = position.coords.longitude;
                                //                 document.getElementById('login_latitude').value = latitude;
                                //                 document.getElementById('login_longitude').value = longitude;
                                //             }, function(error) {
                                //                 // console.log("Geolocation error: ", error);
                                //             });
                                //         }
                                //     };
                                // });
                            </script> --}}

                        @if (Route::has('password.request'))
                            <p class="mb-4 ml-3 text-xs text-gray-400"><a class="underline hover:text-gray-500"
                                    href="{{ route('password.request') }}">
                                    {{ __('Forgot Your Password?') }}
                                </a></p>
                        @endif

                        <?php if (!empty($show_message->lp_captcha)) 
                            {
                                    if ($show_message->lp_captcha == 1) { ?>
                        @if (!empty($business_settings->captch_site_key))
                            <div class="reCAPCHA_allow">
                                <div class="g-recaptcha" data-sitekey="{{ $business_settings->captch_site_key }}"></div>
                            </div>
                        @endif

                        <?php  } else {
                                   
                                    }
                            }?>

                        <hr>

                        <div class="px-3 text-center">
                            <button class="erp-login-submit" type="submit">
                                {{ __('Sign In') }}
                            </button>

                            <span class="erp-login-helper-text">
                                <span>{{ __('If you do not have an account?') }}</span>
                                <a class="signup-button hover:underline"
                                    data-target="#check_register_type_modal" data-toggle="modal"
                                    href="#">{{ __('Sign Up') }}</a>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @php
            /*
            |--------------------------------------------------------------------------
            | Login Page Banners
            |--------------------------------------------------------------------------
            | Load active banners directly from the current database.
            | Do not depend on banner_settings, because some installations do not
            | have that table or may pause banners unintentionally.
            */

            $banners = collect([]);

            try {
                /*
                 | The central connection, resolved so it is ACTUALLY central.
                 |
                 | This was hard-coded to 'mysql'. Tenancy repoints 'mysql' at
                 | the tenant database for the life of a tenant request, so on a
                 | tenant domain this read the TENANT's banners table - empty -
                 | while Super Admin writes to the real central database. The
                 | code asked for central and was handed the tenant.
                 |
                 | 'system' is never swapped by tenancy. Falling back to
                 | tenancy's own configured central connection, and only then to
                 | 'mysql', keeps this working on installs set up differently.
                */
                $central = config('database.connections.system')
                    ? 'system'
                    : config('tenancy.database.central_connection', 'mysql');
                $tenantId = function_exists('tenant') && tenant() ? tenant()->getTenantKey() : null;

                if (Schema::connection($central)->hasTable('banners')) {
                    $query = DB::connection($central)
                        ->table('banners as b')
                        ->where('b.is_active', 1);

                    if (Schema::connection($central)->hasTable('banner_tenants')) {
                        $query->where(function ($scope) use ($tenantId) {
                            $scope->whereNotExists(function ($sub) {
                                $sub->select(DB::raw(1))
                                    ->from('banner_tenants as bt_all')
                                    ->whereColumn('bt_all.banner_id', 'b.id');
                            });

                            if (!empty($tenantId)) {
                                $scope->orWhereExists(function ($sub) use ($tenantId) {
                                    $sub->select(DB::raw(1))
                                        ->from('banner_tenants as bt')
                                        ->whereColumn('bt.banner_id', 'b.id')
                                        ->where('bt.tenant_id', $tenantId);
                                });
                            }
                        });
                    }

                    $banners = $query->orderByDesc('b.created_at')
                        ->get()
                        ->map(function ($banner) {
                            $imagePath = trim((string) ($banner->image_path ?? ''));

                            if ($imagePath === '') {
                                $banner->image_url = '';
                                return $banner;
                            }

                            if (preg_match('#^https?://#i', $imagePath)) {
                                $banner->image_url = $imagePath;
                                return $banner;
                            }

                            if (\Illuminate\Support\Str::startsWith($imagePath, '//')) {
                                $banner->image_url = request()->getScheme() . ':' . $imagePath;
                                return $banner;
                            }

                            $relative = ltrim(str_replace('\\', '/', $imagePath), '/');
                            if (\Illuminate\Support\Str::startsWith($relative, 'public/')) {
                                $relative = substr($relative, strlen('public/'));
                            }

                            if (($banner->storage_disk ?? null) === 's3' && env('AWS_SUPERADMIN_AD_STORAGE_ENABLED')) {
                                try {
                                    $banner->image_url = \Illuminate\Support\Facades\Storage::disk('s3')->url($relative);
                                    return $banner;
                                } catch (\Throwable $e) {
                                    // Fall through to local/legacy URL resolution.
                                }
                            }

                            if (\Illuminate\Support\Str::startsWith($relative, ['uploads/', 'storage/', 'img/'])) {
                                $banner->image_url = asset($relative);
                            } else {
                                try {
                                    if (\Illuminate\Support\Facades\Storage::disk('banner_uploads')->exists($relative)) {
                                        $base = rtrim((string) config('filesystems.disks.banner_uploads.url'), '/');
                                        $segments = array_map('rawurlencode', array_filter(explode('/', $relative), 'strlen'));
                                        $banner->image_url = $base . '/' . implode('/', $segments);
                                    } else {
                                        $diskUrl = \Illuminate\Support\Facades\Storage::disk('public_uploads')->url($relative);
                                        $banner->image_url = preg_match('#^https?://#i', $diskUrl)
                                            ? $diskUrl
                                            : asset(ltrim($diskUrl, '/'));
                                    }
                                } catch (\Throwable $e) {
                                    $banner->image_url = asset($relative);
                                }
                            }

                            return $banner;
                        })
                        ->filter(fn ($banner) => !empty($banner->image_url))
                        ->values();
                }

                /*
                 | Add the TENANT's own banners.
                 |
                 | A tenant that uploads banners locally should see those as
                 | well as the central ones - not instead of them. Read from the
                 | default connection, which during a tenant request IS the
                 | tenant, and only when that is genuinely a different database
                 | from central: on a single-database install the two are the
                 | same and every banner would otherwise appear twice.
                */
                try {
                    $defaultDb = DB::connection()->getDatabaseName();
                    $centralDb = DB::connection($central)->getDatabaseName();

                    if ($defaultDb !== $centralDb && Schema::hasTable('banners')) {
                        $tenantBanners = DB::table('banners')
                            ->where('is_active', 1)
                            ->orderByDesc('created_at')
                            ->get()
                            ->map(function ($banner) {
                                $imagePath = trim((string) ($banner->image_path ?? ''));

                                if ($imagePath === '') {
                                    $banner->image_url = '';
                                    return $banner;
                                }

                                if (preg_match('#^https?://#i', $imagePath)) {
                                    $banner->image_url = $imagePath;
                                    return $banner;
                                }

                                if (\Illuminate\Support\Str::startsWith($imagePath, '//')) {
                                    $banner->image_url = request()->getScheme() . ':' . $imagePath;
                                    return $banner;
                                }

                                $relative = ltrim(str_replace('\\', '/', $imagePath), '/');
                                if (\Illuminate\Support\Str::startsWith($relative, 'public/')) {
                                    $relative = substr($relative, strlen('public/'));
                                }

                                if (($banner->storage_disk ?? null) === 's3' && env('AWS_SUPERADMIN_AD_STORAGE_ENABLED')) {
                                    try {
                                        $banner->image_url = \Illuminate\Support\Facades\Storage::disk('s3')->url($relative);
                                        return $banner;
                                    } catch (\Throwable $e) {
                                        // Fall through to local/legacy URL resolution.
                                    }
                                }

                                if (\Illuminate\Support\Str::startsWith($relative, ['uploads/', 'storage/', 'img/'])) {
                                    $banner->image_url = asset($relative);
                                } else {
                                    try {
                                        if (\Illuminate\Support\Facades\Storage::disk('banner_uploads')->exists($relative)) {
                                            $base = rtrim((string) config('filesystems.disks.banner_uploads.url'), '/');
                                            $segments = array_map('rawurlencode', array_filter(explode('/', $relative), 'strlen'));
                                            $banner->image_url = $base . '/' . implode('/', $segments);
                                        } else {
                                            $diskUrl = \Illuminate\Support\Facades\Storage::disk('public_uploads')->url($relative);
                                            $banner->image_url = preg_match('#^https?://#i', $diskUrl)
                                                ? $diskUrl
                                                : asset(ltrim($diskUrl, '/'));
                                        }
                                    } catch (\Throwable $e) {
                                        $banner->image_url = asset($relative);
                                    }
                                }

                                return $banner;
                            })
                            ->filter(fn ($banner) => !empty($banner->image_url))
                            ->values();

                        $banners = $banners->concat($tenantBanners)->values();
                    }
                } catch (\Throwable $e) {
                    // A tenant without the table is normal - central banners
                    // still show. Never let this break the login page.
                }
            } catch (\Throwable $e) {
                \Log::error('Central login banner loading failed', [
                    'error' => $e->getMessage(),
                    'tenant' => $tenantId ?? null,
                ]);
                $banners = collect([]);
            }
        @endphp

        @if ($banners->count() > 0)
            {{-- Active login banners loaded: {{ $banners->count() }} --}}
            @include('web.banner')
        @else
            <div class="w-full lg:w-1/2 px-4 d-flex align-items-center justify-content-center">
                <div style="width:100%; max-width:720px; min-height:560px; height:560px; display:flex; align-items:center; justify-content:center; border:2px dashed #e2e8f0; border-radius:24px; color:#94a3b8; font-size:18px; margin-top:16px;">
                    No active banner found.
                </div>
            </div>
        @endif
    </div>
    <div class="modal-overlay" style="display: none"></div>
    <div aria-hidden="true" aria-labelledby="exampleModalLabel" class="modal fade" id="check_register_type_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="col-md-12">
                        {!! Form::label('check_register_type', 'Plesae select the register type', [
                            'style' => 'color: black
                                                                                                              !important;',
                        ]) !!}
                        {!! Form::select('check_register_type', $user_types, null, [
                            'class' => 'form-control',
                            'style' => 'width: 100%;',
                            'id' => 'check_register_type',
                            'placeholder' => __('lang_v1.please_select'),
                        ]) !!}
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLabel" class="modal fade" id="pumper_choose_business"
        role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="col-md-12">
                        @include('auth.partials.pumper_business_selector')
                        {!! Form::label('login_display', 'Choose your Login Display', [
                            'style' => 'color: black
                                                                                                              !important; display: none;',
                            'class' => 'login_display',
                        ]) !!}
                        {{-- {!! Form::select('login_display', $allowed_login_displays, null, [
                            'class' => 'form-control login_display',
                            'style' => 'width: 100%; display: none;',
                            'id' => 'login_display',
                            'placeholder' => __('lang_v1.please_select'),
                        ]) !!} --}}
                        {!! Form::select('login_display', [], null, [
    'class' => 'form-control login_display',
    'style' => 'width: 100%; display: none;',
    'id' => 'login_display',
    'placeholder' => __('lang_v1.please_select'),
]) !!}

                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!------------------------------------------------------ 6029-1 Repair status modal --------------------------------------->

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="repair_status_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="modal-title" id="exampleModalLongTitle">{{ __('repair::lang.repair_status') }}</span>
                    <span aria-label="Close" class="close" data-dismiss="modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </span>
                </div>
                <div class="modal-body">
                    <form
                        action="{{ Route::has('post-repair-status') ? route('post-repair-status') : url('/post-repair-status') }}"
                        id="check_repair_status" method="POST">
                        <div class="form-group">
                            @php
                                $search_options = [
                                    'job_sheet_no' => __('repair::lang.job_sheet_no'),
                                    'invoice_no' => __('sale.invoice_no'),
                                    'vehicle_no' => __('sale.vehicle_no'),
                                ];

                                $placeholder = __('repair::lang.job_sheet_or_invoice_no');

                                if (config('repair.enable_repair_check_using_mobile_num')) {
                                    $search_options['mobile_num'] = __('lang_v1.mobile_number');
                                    $placeholder .= ' / ' . __('lang_v1.mobile_number');
                                }
                            @endphp

                            {!! Form::select('search_type', $search_options, null, ['class' => 'form-control width-60 pull-left']) !!}

                        </div>
                        <div class="form-group">
                            {!! Form::text('search_number', null, [
                                'class' => 'form-control width-40 pull-left',
                                'required',
                                'placeholder' => $placeholder,
                            ]) !!}
                        </div>
                        <div class="form-group">
                            <div class="input-group">

                                <input class="form-control" id="repair_serial_no" name="serial_no"
                                    placeholder="@lang('repair::lang.serial_no')" type="text">
                            </div>
                        </div>
                        <div class="form-group">
                            <button class="btn-login btn btn-primary btn-flat ladda-button" type="submit">
                                @lang('lang_v1.search')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="repair_status_modal_deets"
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                <div class="modal-header">
                    <span class="modal-title" id="exampleModalLongTitle">{{ __('repair::lang.repair_status') }}</span>
                    <span aria-label="Close" class="close" data-dismiss="modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </span>
                </div>
                <div class="modal-body">
                    <div class="row repair_status_details"></div>
                </div>
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id='vehicle_register'
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                <div class="col-lg-12">
                    <form action="{{ route('vehicle.store') }}" method="POST">
                        {!! Form::token() !!}
                        {{-- this route define in web.php --}}
                        @include('petro::vehicle.register')
                        {!! Form::close() !!}
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="visitor_register_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                <form action="{{ route('business.postVisitorRegister') }}" method="POST" id="visitor_register_form" enctype="multipart/form-data">
                    @csrf
                    <p class="form-header">@lang('business.register_and_get_started_in_minutes')</p>
                    @include('business.partials.register_form_visitor')
                    <input type="hidden" name="package_id" value="{{ $package_id }}" class="package_id">
                    <p></p>
                    <hr>
                    <div class="clearfix"></div>
                    <div>
                        <button class="btn btn-primary" id="visitor_form_btn" type="submit">Submit</button>
                        <button class="btn btn-secondary" data-dismiss="modal" type="button">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="self_verification_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto;">
                {!! Form::open(['url' => 'process/verify', 'method' => 'post', 'id' => 'verification_form']) !!}
                @include('auth.verification')
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="register_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                <p class="form-header">@lang('business.register_and_get_started_in_minutes')</p>
                <form action="{{ route('business.postRegister') }}" method="POST" id="business_register_form" enctype="multipart/form-data">
                    @csrf
                    @include('business.partials.register_form')
                    <input type="hidden" name="package_id" value="{{ $package_id }}" class="package_id">
                </form>
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="cust_reg_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                <p class="form-header">@lang('business.register_and_get_started_in_minutes')</p>
                {!! Form::open([
                    'url' => route('business.customer_register'),
                    'method' => 'post',
                    'id' => 'customer_register_form',
                    'files' => true,
                ]) !!}
                @include('business.partials.customer_register')
                {!! Form::hidden('package_id', $package_id) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="member_register_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                <p class="form-header">@lang('business.member_registration')</p>
                {!! Form::open([
                    'url' => route('business.member_register'),
                    'method' => 'post',
                    'id' => 'member_register_form',
                    'files' => true,
                ]) !!}
                @include('business.partials.member_register')
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="patient_register_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                <h2 class="form-header">My Health Member Registration</h2>
                <form action="{{ url('/myhealth-register') }}" method="POST" id="patient_register_form" enctype="multipart/form-data">
                    @csrf
                    @includeIf('myhealthmembers::public.partials.registration_form')
                    <input type="hidden" name="package_id" value="{{ $package_id }}" class="package_id">
                </form>
            </div>
        </div>
    </div>

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="agent_register_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                <h2 class="form-header">@lang('superadmin::lang.agent_registration')</h2>
                <form action="{{ route('business.postAgentRegister') }}" method="POST" id="agent_register_form" enctype="multipart/form-data">
                    @csrf
                    @include('business.partials.register_form_agent')
                    <input type="hidden" name="package_id" value="{{ $package_id }}" class="package_id">
                </form>
            </div>
        </div>
    </div>

    @php
        // Visitor is an optional module. Never resolve its view namespace when the
        // module is missing or disabled, otherwise the core login page fails with
        // "No hint path defined for [visitor]".
        $visitorModuleAvailable = false;

        try {
            $visitorModuleAvailable = app()->bound('modules')
                && app('modules')->has('Visitor')
                && app('modules')->isEnabled('Visitor');
        } catch (\Throwable $exception) {
            $visitorModuleAvailable = false;
        }
    @endphp

    @if ($visitorModuleAvailable)
        <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="self_registration_modal"
            role="dialog" tabindex="-1">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                    <p class="form-header">@lang('lang_v1.self_registration')</p>
                    <form action="/visitor/register" method="POST" id="self_registration_form" enctype="multipart/form-data">
                        @csrf
                        @include('visitor::visitor_registration.self_registration')
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="vehical_registration_modal"
        role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                <p class="form-header">@lang('lang_v1.self_registration')</p>
                {{-- this route define in web.php --}}
                {{-- {!! Form::open(['url' => '/visitor/register', 'method' => 'post',
                                    'id' => 'self_registration_form','files' => true ]) !!}
                                    @include('visitor::visitor_registration.self_registration')
                                    {!! Form::close() !!} --}}
            </div>
        </div>
    </div>

    <!------------------------------------------------------ 6029-1 End Repair status modal --------------------------------------->
    @if (!empty($show_message->lp_captcha))
        @if ($show_message->lp_captcha == 1)
            @if (!empty($business_settings->captch_site_key))
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            @endif
        @endif
    @endif
    <script type="text/javascript">
        $(document).ready(function() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Bind the primary login action before any optional portal widgets.
            // A missing optional DOM element must never prevent Sign In.
            $(document)
                .off('submit.erpPrimaryLogin', 'form#login_form')
                .on('submit.erpPrimaryLogin', 'form#login_form', function(e) {
                    e.preventDefault();

                    var $form = $(this);
                    var $submit = $form.find('button[type="submit"], input[type="submit"]').first();
                    $submit.prop('disabled', true);

                    $.ajax({
                        method: 'POST',
                        // Always post to this browser host. This prevents a copied
                        // APP_URL or duplicate named route from moving the session
                        // to another domain during login.
                        url: window.location.origin + '/login',
                        dataType: 'json',
                        cache: false,
                        data: $form.serialize(),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(result) {
                            if (result && result.status) {
                                if (result.step === 'verify_step') {
                                    $('#self_verification_modal').modal('show');
                                    $submit.prop('disabled', false);
                                    return;
                                }

                                var redirectTo = result.redirect || '/home';
                                window.location.assign(new URL(redirectTo, window.location.origin).href);
                                return;
                            }

                            toastr.error((result && result.msg) ? result.msg : 'Login could not be completed.');
                            $submit.prop('disabled', false);
                        },
                        error: function(xhr) {
                            var message = 'Login could not be completed.';

                            /*
                             | A 419 on the LOGIN page is a stale CSRF token, not an
                             | expired session - the user was never signed in, so there
                             | was no session to expire. It happens when the page has
                             | been sitting open for a while before Sign In is pressed.
                             |
                             | Telling someone their "login session expired" and reloading
                             | throws away what they typed and explains nothing. Worse, if
                             | their username was also wrong, the reload hides the real
                             | message the server would have sent.
                             |
                             | Fetch a fresh token and resubmit once instead. The user sees
                             | nothing unless the retry also fails, and if the credentials
                             | are wrong they now get the server's actual message.
                             */
                            if (xhr.status === 419) {
                                if (!$form.data('csrfRetried')) {
                                    $form.data('csrfRetried', true);

                                    $.get(window.location.href)
                                        .done(function(html) {
                                            var token = $('<div>').html(html)
                                                .find('meta[name="csrf-token"]').attr('content');

                                            if (token) {
                                                $('meta[name="csrf-token"]').attr('content', token);
                                                $form.find('input[name="_token"]').val(token);
                                                $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
                                                $form.trigger('submit');
                                                return;
                                            }

                                            toastr.error('Please try signing in again.');
                                            $submit.prop('disabled', false);
                                        })
                                        .fail(function() {
                                            toastr.error('Please try signing in again.');
                                            $submit.prop('disabled', false);
                                        });

                                    return;
                                }

                                // The retry failed too - say so plainly, and do not
                                // reload, so nothing the user typed is lost.
                                toastr.error('Please try signing in again.');
                                $submit.prop('disabled', false);
                                $form.data('csrfRetried', false);
                                return;
                            }

                            if (xhr.responseJSON) {
                                message = xhr.responseJSON.msg || xhr.responseJSON.message || message;
                            } else if (xhr.responseText) {
                                try {
                                    var parsed = JSON.parse(xhr.responseText);
                                    message = parsed.msg || parsed.message || message;
                                } catch (ignore) {
                                    var plain = $('<div>').html(xhr.responseText).text().replace(/\s+/g, ' ').trim();
                                    if (plain) {
                                        message = plain.substring(0, 500);
                                    }
                                }
                            }

                            if (xhr.status) {
                                message += ' (HTTP ' + xhr.status + ')';
                            }

                            console.error('Login request failed', xhr);
                            toastr.error(message);
                            $submit.prop('disabled', false);
                        }
                    });
                });
            $('.action-button').on('click', function() {
                $(this).data('action');
                if ($(this).data('action') == 'login') {
                    $('#register-row').addClass('hidden')
                    $('#login-row').removeClass('hidden')
                    $('#action-row').addClass('hidden')
                    $('.top_action_row').addClass('hidden')

                    $('.landing').css('display', 'none');
                    $('.signin').css('display', 'block');

                } else if ($(this).data('action') == 'show_modal') {
                    $('#check_register_type_modal').modal('show')
                } else {
                    $('#' + $(this).data('action')).removeClass('hidden')
                    $('#action-row').addClass('hidden')
                }

            })
            $('.login-tab-content div').eq(0).addClass('active');
            $('.login-tab li').eq(0).addClass('active');

            $('#mobile').on('input', function(e) {

                this.value = this.value.replace(/\D/g, '');
            });

            //------------------------ 6029-1 Repair status modal jquery ---------------------------//

            $(document).on('click', '#repair_status_button', function() {
                var url = $(this).data('href');
                $('#repair_status_modal').modal('show');
            });

            $(document).on('click', '#repair_details_back_button', function() {
                $("#repair_status_modal_deets").modal('hide')
                $('#repair_status_modal').modal('show');
            });


            $(document).on('submit', 'form#check_repair_status', function(e) {
                console.log(e);
                e.preventDefault();
                var data = $('form#check_repair_status').serialize();
                var url = $('form#check_repair_status').attr('action');
                $.ajax({
                    method: 'POST',
                    url: url,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success) {
                            $('#repair_status_modal').modal('hide');
                            $details_modal = $("#repair_status_modal_deets")
                            $details_modal.find('.repair_status_details').html(result
                                .repair_html);
                            $details_modal.modal('show')
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            //------------------------ End  6029-1 Repair status modal jquery ------------------------//

            $(document).on('change', '#check_register_type', function() {
                register_type = $(this).val();

                if (register_type == 'visitor_register') {
                    $('#visitor_register_modal').modal("show");
                }
                if (register_type == 'customer_register') {
                    $('#cust_reg_modal').modal("show");
                }
                if (register_type == 'memeber_regsiter') {
                    // ('#customer_register_modal').modal("show");
                    $('#member_register_modal').modal("show");
                }
                if (register_type == 'patient_register') {
                    $('#patient_register_modal').modal("show");
                }
                if (register_type == 'company_register') {
                    $('#register_modal').modal("show");
                }
                if (register_type == 'agent_register') {
                    $('#agent_register_modal').modal("show");
                }
                if (register_type == 'vehicle_register') {
                    $('#vehicle_register').modal("show")
                }
                $('#check_register_type_modal').modal('hide');
                // $('#action-row').addClass('hidden')

            })

            $('#register_modal').on('shown.bs.modal', function() {
                var isMyAuto = $('#business_register_form').find('#is_my_auto').is(':checked');
                $('#business_register_form').find('.js-business-name-label').text(isMyAuto ? 'My Auto Number:' : '{{ __('business.business_name') }}:');
            });

            $(document).on('change', '#pumper_business_id', function() {
                pumper_business_id = $(this).val();
                $('#login_display').val('');
                if (pumper_business_id) {
                    $(".login_display").show();
                    // window.location.href = '{{ url('/pump-operator/login') }}?cc='+pumper_business_id;
                } else {
                    $(".login_display").hide();
                }

            });
            const businessesData = @json($businesses->keyBy('id'));
const loginDisplaySelect = document.getElementById('login_display');
const loginDisplayLabel = document.querySelector('.login_display');
const businessSelect = document.getElementById('pumper_business_id');

console.log("businessesData", [businessesData]);

function parseMaybeJson(value) {
    if (!value) {
        return {};
    }

    if (typeof value === 'object') {
        return value;
    }

    try {
        return JSON.parse(value);
    } catch (e) {
        return {};
    }
}

if (businessSelect && loginDisplaySelect && loginDisplayLabel) {
businessSelect.addEventListener('change', function () {

    const selectedBusiness = this.value;

    if (!selectedBusiness || !businessesData[selectedBusiness]) {
        loginDisplaySelect.style.display = "none";
        loginDisplayLabel.style.display = "none";
        return;
    }

    const business = businessesData[selectedBusiness];

    let allowedDisplays = {};

    // Build the business-specific login displays from the authoritative
    // Manage Side Bar module state prepared by the server. Do not rebuild this
    // list from legacy subscription/package/common-settings flags.
    if (business.patient_dashboard_enabled == 1) {
        allowedDisplays['patient'] = 'Patient Display';
    }

    if (business.pumper_dashboard_enabled == 1) {
        allowedDisplays['petro_module'] = 'Pump Operator Display';
    }

    if (business.pumper_dashboard_new_enabled == 1) {
        allowedDisplays['petro_module_2'] = 'Pump Operator Display 2';
    }

    if (business.rice_mill_dashboard_enabled == 1) {
        allowedDisplays['rice_mill_dashboard'] = 'Rice Mill Dashboard';
    }

    if (business.employee_dashboard_enabled == 1) {
        allowedDisplays['employee'] = 'Employee Display';
    }

    if (business.shipping_dashboard_enabled == 1) {
        allowedDisplays['shipping_module'] = 'Shipping Display';
    }

    if (business.project_dashboard_enabled == 1) {
        allowedDisplays['project'] = 'Project Sales Display';
    }

    if (business.bakery_dashboard_enabled == 1) {
        allowedDisplays['bakery_module'] = 'Bakery Display';
    }

    if (business.my_auto_dashboard_enabled == 1) {
        allowedDisplays['my_auto_agent'] = 'My Auto Agent Display';
    }

    // Clear existing options
    loginDisplaySelect.innerHTML = '<option value="">Please Select</option>';

    Object.keys(allowedDisplays).forEach(key => {
        const option = document.createElement('option');
        option.value = key;
        option.textContent = allowedDisplays[key];
        loginDisplaySelect.appendChild(option);
    });

    if (Object.keys(allowedDisplays).length > 0) {
        loginDisplaySelect.style.display = "block";
        loginDisplayLabel.style.display = "block";
    } else {
        loginDisplaySelect.style.display = "none";
        loginDisplayLabel.style.display = "none";
    }

    // 🔥 Auto select if only one option
    if (Object.keys(allowedDisplays).length === 1) {
        loginDisplaySelect.value = Object.keys(allowedDisplays)[0];
    }

});
} else {
    // The normal ERP login page does not render the optional pump-operator
    // business selector. Continue loading the primary Sign In handlers.
    if (loginDisplaySelect) {
        loginDisplaySelect.style.display = 'none';
    }
}

            $(document).on('change', '#login_display', function() {

    let login_display = $(this).val();
    let selectedBusinessId = $("#pumper_business_id").val();

    if (!selectedBusinessId) {
        alert("Choose Business");
        return;
    }

    let selectedBusiness = businessesData[selectedBusinessId];
    if (!selectedBusiness) {
        alert("The selected business is not available in this tenant database.");
        return;
    }

    let companyNumber = String(selectedBusiness.company_number || '');
    if (!companyNumber) {
        alert("The selected tenant business does not have a Company Number.");
        return;
    }

    let businessQuery = 'cc=' + encodeURIComponent(companyNumber)
        + '&business_id=' + encodeURIComponent(selectedBusinessId);

    if (login_display === "petro_module_2" && selectedBusiness.pumper_dashboard_new_enabled == 1) {
        window.location.href = '{{ url('/pumper-dashboard-new/login') }}?' + businessQuery;
        return;
    }

    if (login_display === "rice_mill_dashboard" && selectedBusiness.rice_mill_dashboard_enabled == 1) {
        window.location.href = '{{ url('/rice-mill-dashboard/login') }}?' + businessQuery;
    }
    else if (login_display === "petro_module" && selectedBusiness.pumper_dashboard_enabled == 1) {
        window.location.href = '{{ url('/pump-operator/login') }}?' + businessQuery;
    }
    else if (login_display === "patient" && selectedBusiness.patient_dashboard_enabled == 1) {
        window.location.href = '{{ url('/patient/login') }}?' + businessQuery;
    }
    else if (login_display === "employee" && selectedBusiness.employee_dashboard_enabled == 1) {
        window.location.href = '{{ url('/employee/login') }}?' + businessQuery;
    }
    else if (login_display === "shipping_module" && selectedBusiness.shipping_dashboard_enabled == 1) {
        window.location.href = '{{ url('/shipping/login') }}?' + businessQuery;
    }
    else if (login_display === "project" && selectedBusiness.project_dashboard_enabled == 1) {
        window.location.href = '{{ url('/project/login') }}?' + businessQuery;
    }
    else if (login_display === "bakery_module" && selectedBusiness.bakery_dashboard_enabled == 1) {
        window.location.href = '{{ url('/bakery/login') }}?' + businessQuery;
    }
    else if (login_display === "my_auto_agent" && selectedBusiness.my_auto_dashboard_enabled == 1) {
        window.location.href = '{{ url('/pump-operator/login') }}?' + businessQuery + '&login_display=my_auto_agent';
    }
    else {
        alert("Selected module is not activated for this business");
    }

});

            $(document).on('change', '#email', function() {
                var username = $(this).val();

                $.ajax({
                    type: "post",
                    url: "{{ url('reCAPCHA/install') }}",
                    data: {
                        "user_name": username
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status) {
                            $(document).find('.reCAPCHA_allow').show();
                        } else {
                            $(document).find('.reCAPCHA_allow').hide();
                        }
                    }
                });
            });
            $(document).on('click', '.resend-opt', function(e) {
                e.preventDefault();
                $.ajax({
                    type: "get",
                    url: "{{ action('UsersOPTController@resendOpt') }}",
                    dataType: "json",
                    success: function(result) {
                        if (result.status) {
                            $(document).find('#add-link-opt').html('');
                        }
                        toastr.error(result.msg);
                    }
                });
            });

            $(document).on('submit', 'form#verification_form', function(e) {
                e.preventDefault();
                var data = $('form#verification_form').serialize();
                var url = $('form#verification_form').attr('action');
                $.ajax({
                    method: 'POST',
                    url: url,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success) {
                            window.location.href = result.url;
                        } else {
                            toastr.error(result.msg);
                            if (result.resend_opt) {
                                $(document).find('#add-link-opt').html(result.resend_opt);
                            }
                        }
                    }
                });
            });

            //  $('#pumper_choose_business').on('shown.bs.modal', function () {
            //     var businesses = $("#business_count").val() ?? 0
            //     pumper_business_id = "{{ $businesses->keys()->first() }}";

            //     if(businesses == 1){
            //         $('#pumper_business_id').prop('disabled',true);
            //         window.location.href = '{{ url('/pump-operator/login') }}?cc='+pumper_business_id;
            //     }
            // });

            // Handle Signup -> My Health direct modal
            $(document).on('click', '#signup_my_health', function(e) {
                e.preventDefault();
                $('#patient_register_modal').modal('show');
            });

            $(document).on('click', '[data-target="#patient_register_modal"]', function() {
                let this_package_id = $(this).attr('id');
                if (this_package_id) {
                    $('#patient_register_modal').find('.package_id').val(this_package_id);
                }
            });

            // Handle AJAX submission of patient registration form
            $(document).on('submit', '#patient_register_form', function(e) {
                e.preventDefault();
                var form = $(this);

                var submitBtn = $('#click_to_subscription');
                var originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    headers: { 'Accept': 'application/json' },
                    success: function(response) {
                        if (response.success) {
                            $('#patient_register_modal').modal('hide');
                            $('#myhealth_registration_success_modal').remove();
                            if (response.success_html) {
                                $('body').append(response.success_html);
                                $('#myhealth_registration_success_modal').modal('show');
                            } else {
                                toastr.success(response.msg || 'My Health Member registered successfully');
                            }
                            form[0].reset();
                            submitBtn.prop('disabled', false).html(originalText);
                        } else {
                            toastr.error(response.msg || 'Registration failed');
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        var errMsg = 'My Health registration failed.';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            errMsg = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            var plainText = $('<div>').html(xhr.responseText).text().replace(/\s+/g, ' ').trim();
                            if (plainText.length > 0) {
                                errMsg = plainText.substring(0, 500);
                            }
                        } else if (xhr.status) {
                            errMsg = 'My Health registration failed. HTTP ' + xhr.status + ' ' + (xhr.statusText || '');
                        }
                        console.error('My Health registration AJAX error', xhr);
                        toastr.error(errMsg);
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Handle Offline Payment Detail triggers
            $(document).on('click', '.btn-pay-offline', function(e) {
                e.preventDefault();
                $('#pay_offline_details_modal').modal('show');
            });

            $(document).on('click', '#pay_offline_details_modal .btn-confirm-ok', function(e) {
                e.preventDefault();
                $('form.form-pay-offline').submit();
            });

            $('.modal').on('shown.bs.modal', function () {
                $(this).find('.select2').select2({
                    dropdownParent: $(this)
                });
            });
        })

        $(document).ready(function() {
            @if (session('status'))
                var status = @json(session('status'));

                if (status.success === 1) {
                    toastr.success(status.msg);
                } else {
                    toastr.error(status.msg);
                }
            @endif

            @if (session('patient_reg_success_alert'))
                swal({
                    title: "Secure Your Passcode",
                    text: @json(session('patient_reg_success_alert')),
                    icon: "success",
                    buttons: {
                        confirm: {
                            text: "Ok",
                            value: true,
                            visible: true,
                            className: "btn btn-primary",
                            closeModal: true
                        }
                    }
                });
            @endif
        });
    </script>
    @if (isset($banners) && count($banners) > 0)
        <script>
            /*
             | Run as soon as the document is parsed.
             |
             | This block sits late in the page, so document.readyState is
             | usually already 'complete' by the time it executes. A listener
             | added for an event that has ALREADY FIRED is never called - so
             | the carousel never started. Two slides rendered, both images
             | loaded, correct durations, and nothing ever moved. No error
             | either, which is what made it hard to find.
            */
            (function bannerCarouselInit(fn) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', fn);
                } else {
                    fn();
                }
            })(function() {

                const slides = document.querySelectorAll('.carousel-slide');
                if (slides.length <= 1) return;

                let currentIndex = 0;
                let timeout;

                function startTimer() {
                    clearTimeout(timeout);

                    const currentSlide = slides[currentIndex];
                    const duration = (parseFloat(currentSlide.dataset.duration) || 3) * 1000; // convert sec → ms


                    timeout = setTimeout(goNext, duration);
                }

                function goNext() {
                    slides[currentIndex].style.opacity = "0";
                    slides[currentIndex].style.zIndex = "0";

                    currentIndex = (currentIndex + 1) % slides.length;

                    slides[currentIndex].style.opacity = "1";
                    slides[currentIndex].style.zIndex = "10";

                    startTimer();
                }


                slides.forEach(slide => {
                    slide.addEventListener('click', function(e) {
                        // e.currentTarget always refers to the slide
                        const link = e.currentTarget.dataset.link;
                        if (link) window.open(link, '_blank');
                    });
                });

                // Initial state
                slides.forEach((slide, index) => {
                    slide.classList.add(
                        'absolute',
                        'inset-0',
                        'w-full',
                        'h-full',
                        'object-cover',
                        'transition-opacity',
                        'duration-700'
                    );

                    slide.classList.toggle('opacity-100', index === 0);
                    slide.classList.toggle('opacity-0', index !== 0);
                });

                slides.forEach(function (sl, ix) { sl.style.transition = "opacity 0.7s ease-in-out"; sl.style.opacity = ix === 0 ? "1" : "0"; sl.style.zIndex = ix === 0 ? "10" : "0"; });
                startTimer();
            });
        </script>
    @endif
@stop
