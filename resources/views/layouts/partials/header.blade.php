@inject('request', 'Illuminate\Http\Request')
@php
    use Tymon\JWTAuth\Facades\JWTAuth;
    use Illuminate\Support\Facades\DB;
    use App\Utils\TransactionUtil;
    use App\Utils\ModuleUtil;
    use App\Utils\ContactUtil;
    
    $sms = new TransactionUtil(new ModuleUtil(), new ContactUtil());
    $sms_bal = $sms->__getSMSBalance(date('Y-m-d'));
    $erpHeaderAuthUser = auth()->user();
    $business_id = optional($erpHeaderAuthUser)->business_id ?? request()->session()->get('user.business_id');
   
 
    // Fetch the business name
    $bs_name = DB::table('business')->where('id', $business_id)->select('name')->first();

    // Set the customer_group attribute on the user
    if (!empty($erpHeaderAuthUser)) {
        $erpHeaderAuthUser->customer_group = optional($bs_name)->name ?? 'Default';
    }

    // Generate the JWT token with the updated user attributes.
    $token = !empty($erpHeaderAuthUser) ? JWTAuth::fromUser($erpHeaderAuthUser) : '';

    /*
     * The session identity is the canonical header identity. Some independently
     * registered module routes rebuild the authenticated model after tenancy is
     * initialized. In mixed/legacy tenant data this could resolve a different
     * row with the same numeric ID and make the header name change between pages.
     * SetSessionData stores the user who actually signed in, so keep the visible
     * identity stable from that session and use the auth model only as fallback.
     */
    $erpHeaderSessionUser = request()->session()->get('user', []);
    if (is_object($erpHeaderSessionUser)) {
        $erpHeaderSessionUser = (array) $erpHeaderSessionUser;
    }
    if (!is_array($erpHeaderSessionUser)) {
        $erpHeaderSessionUser = [];
    }

    $erpHeaderSurname = trim((string) ($erpHeaderSessionUser['surname'] ?? optional($erpHeaderAuthUser)->surname ?? ''));
    $erpHeaderFirstName = trim((string) ($erpHeaderSessionUser['first_name'] ?? optional($erpHeaderAuthUser)->first_name ?? ''));
    $erpHeaderLastName = trim((string) ($erpHeaderSessionUser['last_name'] ?? optional($erpHeaderAuthUser)->last_name ?? ''));
    $erpHeaderUsername = trim((string) ($erpHeaderSessionUser['username'] ?? optional($erpHeaderAuthUser)->username ?? ''));

    /*
     |--------------------------------------------------------------------------
     | LA-1152 TRACE - remove once this is solved.
     |--------------------------------------------------------------------------
     |
     | Two attempts at this have missed. Rather than guess a third time, this
     | records exactly what the header resolves, at the moment it resolves it.
     |
     | It answers the three questions that separate the remaining explanations:
     |
     |   auth_*     who Laravel thinks is signed in
     |   session_*  who the session says signed in (the header prefers this)
     |   db / tenant which database the request is actually reading
     |
     | If auth and session disagree, it is an identity-resolution problem.
     | If they agree but the name on screen is someone else, the page is being
     | served from a cache. If the database name differs between two pages of the
     | same click-through, the connection is being switched mid-request.
     |
     | WARNING level so no log-level setting can hide it.
     */
    try {
        \Illuminate\Support\Facades\Log::warning('LA-1152 TRACE header identity', [
            'url'              => request()->fullUrl(),
            'auth_id'          => optional($erpHeaderAuthUser)->id,
            'auth_username'    => optional($erpHeaderAuthUser)->username,
            'auth_business_id' => optional($erpHeaderAuthUser)->business_id,
            'session_id'       => session()->getId(),
            'session_user_id'  => $erpHeaderSessionUser['id'] ?? null,
            'session_username' => $erpHeaderSessionUser['username'] ?? null,
            'session_business' => $erpHeaderSessionUser['business_id'] ?? null,
            'displayed_name'   => $erpHeaderUsername,
            'db_connection'    => \Illuminate\Support\Facades\DB::getDefaultConnection(),
            'db_database'      => \Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
            'tenant_initialized' => function_exists('tenancy') ? (bool) optional(tenancy())->initialized : null,
            'tenant_id'        => function_exists('tenant') ? optional(tenant())->getTenantKey() : null,
        ]);
    } catch (\Throwable $e) {
        // A trace must never break the page.
    }

    $erpHeaderFullName = trim(implode(' ', array_filter([
        $erpHeaderSurname,
        $erpHeaderFirstName,
        $erpHeaderLastName,
    ], static function ($value) {
        return $value !== '';
    })));

    if ($erpHeaderFullName === '') {
        $erpHeaderFullName = $erpHeaderUsername !== '' ? $erpHeaderUsername : __('lang_v1.user');
    }

    // Keep the compact button exactly aligned with the name captured at login.
    $erpHeaderButtonName = $erpHeaderFirstName !== '' ? $erpHeaderFirstName : $erpHeaderFullName;
@endphp
@php
    $business_id = request()->session()->get('user.business_id');
    if (empty($business_id)) {
        return redirect('/logout');
    }
    $site_settings = DB::table('site_settings')->where('id', 1)->first();
    $top_belt_bg = optional($site_settings)->topBelt_background_color ?? '#2596be';
@endphp

@php
    $business_id = request()->session()->get('user.business_id');

    $day_end = DB::table('business')->where('id', $business_id)->select('day_end')->first();
    if (!empty($day_end)) {
        $day_end = $day_end->day_end;
    } else {
        $day_end = 0;
    }
    $day_end_enable = DB::table('business')->where('id', $business_id)->select('day_end_enable')->first();
    if (!empty($day_end_enable)) {
        $day_end_enable = $day_end_enable->day_end_enable;
    } else {
        $day_end_enable = 0;
    }
    $tour_toggle = optional($site_settings)->tour_toggle ?? 0;

    $business_id = request()->session()->get('user.business_id');
    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);

    $pop_button_on_top_belt = \App\Utils\ModuleUtil::hasThePermissionInSubscription(
        $business_id,
        'pop_button_on_top_belt',
    );

    $cache_clear = 0;
    $pacakge_details = [];
    if (!empty($subscription)) {
        $pacakge_details = (array) $subscription->package_details;
        if (array_key_exists('cache_clear', $pacakge_details)) {
            $cache_clear = $pacakge_details['cache_clear'];
        }
        if (array_key_exists('pos_sale', $pacakge_details)) {
            $pos_sale = $pacakge_details['pos_sale'];
        }

        if (array_key_exists('hr_module', $pacakge_details)) {
            $hr_module = $pacakge_details['hr_module'];
        }
    }
    if (auth()->user()->can('superadmin')) {
        $cache_clear = 1;
        $pos_sale = 1;
    }

    $help_desk_url = App\System::getProperty('helpdesk_system_url') . '?token=' . $token;
@endphp
<!-- Main Header -->
<div class="header-area no-print">
    <div class="row align-items-center" style="display: flex; align-items: center;">
        <!-- nav and search button -->
        <div class="d-flex align-items-center px-2 flex-wrap" style="width: auto; min-width: 0; background-color: transparent; min-height: 50px;">
            {{-- Current Package & Clear Cache --}}
            <div class="d-flex align-items-center flex-wrap ms-2" style="gap: 10px;">
                @include('superadmin::layouts.partials.active_subscription')

                @if ($cache_clear)
                    <a href="{{ action('BusinessController@clearCache') }}"
                        class="btn btn-sm btn-danger clear_cache_btn">
                        @lang('lang_v1.clear_cache')
                    </a>
                @endif
            </div>
        </div>


        <!-- profile info & task notification -->
        <div class="my-div1" style="flex: 1; margin-left: 20px; background-color: #2596be; height: 50px; border-radius: 5px;">
            <ul class="notification-area pull-right my-div3">

                @if (Module::has('Essentials'))
                    @if (isset($hr_module) && $hr_module == 1)
                        @includeIf('essentials::layouts.partials.header_part')
                    @endif

                @endif
                
                <a href="{{action('\Modules\SMS\Http\Controllers\SMSController@index')}}" type="button"
                    class="btn btn-flat pull-left m-8 hidden-xs btn-sm mt-10 @if(($sms_bal ?? 0) > 0) text-white @else text-danger @endif">
                    <strong>@lang('sms::lang.sms_bal') : {{ @num_format($sms_bal) }}</strong>
                </a>

                <a target="_blank" href="{{ \Illuminate\Support\Facades\Route::has('frontend') ? route('frontend') : url('/helpguide') }}" title="Help Guide" type="button"
                    class="btn btn-flat pull-left m-8 hidden-xs btn-sm mt-10 text-white">
                    <strong>Help Guide</strong>
                </a>
                @if (class_exists(\App\Services\Authorization\SuperAdminImpersonation::class)
                    && \App\Services\Authorization\SuperAdminImpersonation::isActive(auth()->user(), request())
                    && !request()->session()->get('user.is_pump_operator'))
                    <a href="{{ action('\Modules\Superadmin\Http\Controllers\BusinessController@backToSuperadmin') }}"
                        title="@lang('lang_v1.back_to_superadmin')" type="button"
                        class="btn btn-flat pull-left m-8 text-white hidden-xs btn-sm mt-10">
                        <strong><i class="fa fa-arrow-left fa-lg" aria-hidden="true"></i></strong>
                    </a>
                @endif
                @if (!request()->session()->get('user.is_pump_operator'))
                    <a href="#" id="btnLock" title="@lang('lang_v1.lock_screen')" type="button"
                        class="btn btn-flat pull-left m-8 hidden-xs btn-sm text-white mt-10 popover-default"
                        data-placement="bottom">
                        <strong><i class="fa fa-lock fa-lg" aria-hidden="true"></i></strong>
                    </a>
                @endif

                @php
                    $all_notifs = auth()->user()->notifications;
                    $unread_notifs = $all_notifs->where('read_at', null);
                    $total_unread = count($unread_notifs);
                @endphp
                <li class="dropdown notifications-menu btn-group pull-left m-8 hidden-xs mt-10" style="list-style:none;">
                    <a href="#" class="btn btn-flat btn-sm text-white dropdown-toggle load_notifications" data-toggle="dropdown" id="show_unread_notifications" data-loaded="false" style="position:relative;">
                        <strong><i class="fa fa-bell-o fa-lg"></i></strong>
                        <span class="label label-warning notifications_count" style="position:absolute; top:0; right:0; font-size:9px; padding:2px 5px; border-radius:50%; @if(empty($total_unread)) display:none; @endif">{{ $total_unread }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right" style="min-width:320px; max-height:400px; overflow-y:auto; z-index: 1200 !important; position:absolute !important; right:0 !important;">
                        <li>
                            <ul class="menu" id="notifications_list" style="list-style:none; padding:0; margin:0;">
                                @if(count($all_notifs) > 10)
                                    <li class="text-center load_more_li">
                                        <a href="#" class="btn btn-link load_more_notifications"><small>@lang('lang_v1.load_more')</small></a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    </ul>
                </li>
                <input type="hidden" id="notification_page" value="1">

                {{-- <a href="#" id="btnCalculator" title="@lang('lang_v1.calculator')" type="button"
                    class="btn  btn-flat text-white pull-left m-8 hidden-xs btn-sm mt-10 popover-default" tabindex="-1"
                    data-toggle="click" data-trigger="click" data-content='@include('layouts.partials.calculator')'
                    data-html="true" data-placement="bottom">
                    <strong><i class="fa fa-calculator fa-lg" aria-hidden="true"></i></strong>
                </a> --}}

                @if ($request->segment(1) == 'pos')
                    <a href="#" type="button" id="register_details"
                        title="{{ __('cash_register.register_details') }}" data-toggle="tooltip" data-placement="bottom"
                        class="btn text-white btn-flat pull-left m-8 hidden-xs btn-sm mt-10 btn-modal"
                        data-container=".register_details_modal"
                        data-href="{{ action('CashRegisterController@getRegisterDetails') }}">
                        <strong><i class="fa fa-briefcase fa-lg" aria-hidden="true"></i></strong>
                    </a>
                    <a href="#" type="button" id="close_register"
                        title="{{ __('cash_register.close_register') }}" data-toggle="tooltip" data-placement="bottom"
                        class="btn text-white btn-flat pull-left m-8 hidden-xs btn-sm mt-10 btn-modal"
                        data-container=".close_register_modal"
                        data-href="{{ action('CashRegisterController@getCloseRegister') }}">
                        <strong><i class="fa fa-window-close fa-lg"></i></strong>
                    </a>
                @endif

                @if (
                    !request()->session()->get('business.is_patient') &&
                        !request()->session()->get('business.is_hospital') &&
                        !request()->session()->get('business.is_pharmacy') &&
                        !request()->session()->get('business.is_laboratory'))
                    @if ($day_end_enable == 1)
                        @can('day_end.view')
                            <a href="{{ action('BusinessController@dayEnd') }}" title="Day End" data-toggle="tooltip"
                                data-placement="bottom"
                                class="btn @if ($day_end == 0) @else text-white @endif btn-flat pull-left m-8 hidden-xs btn-sm mt-10">
                                <strong><i class="fa fa-sun-o"></i> &nbsp;@if ($day_end == 0)
                                        @lang('lang_v1.day_end')
                                    @else
                                        @lang('lang_v1.day_ended')
                                    @endif
                                </strong>
                            </a>
                        @endcan
                    @endif
                    @if ((isset($pos_sale) && $pos_sale == 1) || $pop_button_on_top_belt == 1)
                        <div class="btn-group pull-left m-8 hidden-xs mt-10">
                            <button type="button" class="btn btn-flat text-white btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: transparent; color: white;">
                                <strong><i class="fa fa-th-large"></i> &nbsp; @lang('sale.pos_pop_sale')</strong> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                @if (isset($pos_sale) && $pos_sale == 1)
                                    @can('sell.create')
                                        <li style="width: 80%; margin-bottom: 5px;">
                                            <a href="{{ action('SellPosController@create') }}" title="POS">
                                                <i class="fa fa-th-large"></i> &nbsp; @lang('sale.pos_sale')
                                            </a>
                                        </li>
                                    @endcan
                                @endif
                                @if ($pop_button_on_top_belt == 1)
                                    @can('purchase.create')
                                        <li style="width: 80%">
                                            <a href="{{ action('PurchasePosController@create') }}" title="POP">
                                                <i class="fa fa-th-large"></i> &nbsp; @lang('purchase.pop')
                                            </a>
                                        </li>
                                    @endcan
                                @endif
                            </ul>
                        </div>
                    @endif


                    @can('profit_loss_report.view')
                        <a href="#" type="button" id="view_todays_profit" title="{{ __('home.todays_profit') }}"
                            data-toggle="tooltip" data-placement="bottom"
                            class="btn   btn-flat pull-left m-8 hidden-xs btn-sm mt-10">
                            <strong><i class="fa fa-money fa-md text-white"></i></strong>
                        </a>
                    @endcan

                    <!-- Help Button -->
                    @if ($tour_toggle == 1)
                        @if (auth()->user()->hasRole('Admin#' . auth()->user()->business_id))
                            <a href="#" type="button" id="start_tour" title="@lang('lang_v1.application_tour')"
                                data-toggle="tooltip" data-placement="bottom"
                                class="btn text-white  btn-flat pull-left m-8 hidden-xs btn-sm mt-10">
                                <strong><i class="fa fa-question-circle fa-md" aria-hidden="true"></i></strong>
                            </a>
                        @endif
                    @endif
                @endif


                <ul class="nav navbar-nav" style="margin-right: 10px; margin-left: -35px;">

                    <!-- User Account Menu -->
                    <li class="dropdown user user-menu my-div2" style="min-width: 100px;">
                        <!-- Menu Toggle Button -->
                        <a href="#" class="dropdown-toggle btn  btn-sm btn-danger btn-flat mt-10 ml-10"
                            style="height: 30px; padding: 3px;" data-toggle="dropdown">
                            <!-- The user image in the navbar-->
                            @php
                                $profile_photo = auth()->user()->media;
                            @endphp
                            @if (!empty($profile_photo))
                                <img src="{{ $profile_photo->display_url }}" class="user-image" alt="User Image">
                            @endif
                            <!-- hidden-xs hides the username on small devices so only the image appears. -->
                            <span id="span_username" title="{{ $erpHeaderFullName }}">
                                {{ \Illuminate\Support\Str::limit($erpHeaderButtonName, 27, '...') }}
                            </span>
                        </a>
                        <ul class="dropdown-menu rounded shadow-sm p-3 mb-5 bg-white rounded"
                            style="margin-top: 15px;">
                            <!-- The user image in the menu -->
                            <li style="width: 100%; text-align : center;margin-left: 0px !important">
                                <b>{{ $erpHeaderFullName }}</b>
                            </li>
                            <hr>
                            <!-- Menu Body -->
                            <!-- Menu Footer-->
                            <li class="">
                                <a href="{{ action('UserController@getProfile') }}" class=""><i
                                        class="fa fa-user"></i>&nbsp; @lang('lang_v1.profile')</a>
                            </li>
                            <li>
                                @if (auth()->user()->is_pump_operator)
                                    <a href="{{ url('/pump-operator/logout') }}"
                                        class=""><i class="fa fa-sign-out"></i>&nbsp; @lang('lang_v1.sign_out')</a>
                                @elseif(auth()->user()->is_property_user)
                                    <a href="{{ url('/property-user/logout') }}"
                                        class=""><i class="fa fa-sign-out"></i>&nbsp; @lang('lang_v1.sign_out')</a>
                                @else
                                    <a href="{{ url('/logout') }}"
                                        class=""><i class="fa fa-sign-out"></i>&nbsp; @lang('lang_v1.sign_out')</a>
                                @endif
                            </li>
                        </ul>
                    </li>
                    <!-- Control Sidebar Toggle Button -->
                </ul>








            </ul>
        </div>
        {{-- <div class="col-md-12">
        </div> --}}
        
        <style>

/* =========================================================
   ENTERPRISE HEADER FRAMEWORK
========================================================= */

.header-area {

    background: linear-gradient(
        135deg,
        #ffffff,
        #f8fafc
    ) !important;

    border-radius: 18px;

    margin: 18px 18px 0px 18px;

    box-shadow: 0 4px 20px rgba(0,0,0,0.08);

    overflow: visible !important;

    border: 1px solid rgba(0,0,0,0.04);
}

/* =========================================================
   TOP BELT
========================================================= */

.header-area .row {

    min-height: 72px;
}

.header-area .d-flex {

    background: linear-gradient(
        135deg,
        #2563eb,
        #06b6d4
    ) !important;

    border-radius: 0 18px 18px 0;
}


/* =========================================================
   RIGHT HEADER AREA
========================================================= */

.my-div1 {

    background: transparent !important;

    border-radius: 0 !important;

    height: auto !important;

    display: flex;

    align-items: center;

    justify-content: flex-end;

    padding-right: 18px;
}

/* =========================================================
   NOTIFICATION AREA
========================================================= */

.notification-area {

    display: flex;

    align-items: center;

    gap: 8px;

    margin: 0;

    padding: 0;
}

.notification-area .btn {

    border-radius: 12px !important;

    background: #ffffff !important;

    color: #2c3e50 !important;

    border: 1px solid #e5e7eb !important;

    min-height: 42px;

    padding: 8px 14px !important;

    font-weight: 600;

    transition: all 0.22s ease;
}

.notification-area .btn:hover {

    transform: translateY(-1px);

    box-shadow: 0 8px 20px rgba(0,0,0,0.08);

    background: #f8fafc !important;
}

/* =========================================================
   ICONS
========================================================= */

.notification-area i {

    margin-right: 4px;
}

/* =========================================================
   DROPDOWNS
========================================================= */

.dropdown-menu {

    border-radius: 16px !important;

    border: none !important;

    box-shadow: 0 10px 30px rgba(0,0,0,0.12);

    padding: 10px;

   overflow: visible !important;
z-index: 1200 !important;
position: absolute !important;

}

.dropdown-menu li a {

    border-radius: 10px;

    padding: 10px 14px !important;

    transition: all 0.2s ease;
}

.dropdown-menu li a:hover {

    background: #f4f6f9 !important;
}

/* =========================================================
   USER PROFILE
========================================================= */

.user-menu .dropdown-toggle {

    background: linear-gradient(
        135deg,
        #2563eb,
        #06b6d4
    ) !important;

    border-radius: 14px !important;

    border: none !important;

    color: #ffffff !important;

    min-height: 44px;

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 8px 14px !important;

    box-shadow: 0 6px 18px rgba(37,99,235,0.25);
}

.user-image {

    border-radius: 50%;

    border: 2px solid rgba(255,255,255,0.4);

    width: 32px !important;

    height: 32px !important;

    object-fit: cover;
}

/* =========================================================
   NOTIFICATION BADGE
========================================================= */

.notifications_count {

    border-radius: 50% !important;

    min-width: 18px;

    min-height: 18px;

    font-size: 10px !important;

    font-weight: 700;
}

/* =========================================================
   MOBILE RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    .header-area {
        margin: 10px;
    }

    .header-area .d-flex {
        width: 100% !important;
        border-radius: 0;
    }

    .my-div1 {
        width: 100%;
        justify-content: center;
        padding: 10px;
    }

    .notification-area {
        flex-wrap: wrap;
        justify-content: center;
    }

    #span_username {
        display: none;
    }
}

/* ==========================================
   DROPDOWN FIX
========================================== */

.header-area,
.header-area .row,
.header-area .my-div1,
.header-area .notification-area,
.header-area .btn-group,
.header-area .dropdown,
.header-area .user-menu {
    overflow: visible !important;
}

.header-area {
    position: relative !important;
    z-index: 20 !important;
}

.header-area .dropdown-menu {
    z-index: 1200 !important;
}


/* =========================================================
   SIDEBAR OVERLAY SAFETY FIX
   Keep the opened sidebar above the header package area.
========================================================= */
.header-area,
.header-area .row,
.header-area .d-flex,
.header-area .my-div1 {
    z-index: 20 !important;
}

.main-sidebar,
.left-side,
.sidebar {
    z-index: 3000 !important;
}

.sidebar-open .main-sidebar,
.sidebar-open .left-side,
.sidebar-open .sidebar {
    z-index: 3000 !important;
}

.header-area .dropdown-menu {
    z-index: 1200 !important;
}



/* =========================================================
   EXF HEADER BELT DROPDOWN CONTROL V5.1
   Keep profile/POS/notification dropdowns closed by default.
   They open only when the user clicks the related button.
========================================================= */
.header-area .dropdown-menu {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    transform: translateY(6px);
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.header-area .dropdown.open > .dropdown-menu,
.header-area .btn-group.open > .dropdown-menu,
.header-area li.open > .dropdown-menu,
.header-area .user-menu.open > .dropdown-menu {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
    transform: translateY(0);
}

.header-area .notification-area > .dropdown-menu:not(.show),
.header-area .notification-area .dropdown-menu:not(.show) {
    display: none !important;
}

.header-area .notification-area .open > .dropdown-menu,
.header-area .notification-area .dropdown.open > .dropdown-menu,
.header-area .notification-area .btn-group.open > .dropdown-menu,
.header-area .notification-area .user-menu.open > .dropdown-menu {
    display: block !important;
}


/* =========================================================
   EXF V5.3 HEADER CONTROL VISIBILITY + CONSISTENT COLOURS
   Keep every top-belt control readable on all module pages.
========================================================= */
.header-area.no-print .notification-area > a.btn,
.header-area.no-print .notification-area > button.btn,
.header-area.no-print .notification-area > li > a.btn,
.header-area.no-print .notification-area > li > button.btn,
.header-area.no-print .notification-area > .btn-group > .btn,
.header-area.no-print .notification-area > li.btn-group > .btn,
.header-area.no-print .notification-area .notifications-menu > a.btn {
    background: #ffffff !important;
    color: #1f2937 !important;
    border: 1px solid #e5e7eb !important;
}

.header-area.no-print .notification-area > a.btn strong,
.header-area.no-print .notification-area > button.btn strong,
.header-area.no-print .notification-area > li > a.btn strong,
.header-area.no-print .notification-area > li > button.btn strong,
.header-area.no-print .notification-area > .btn-group > .btn strong,
.header-area.no-print .notification-area > li.btn-group > .btn strong,
.header-area.no-print .notification-area .notifications-menu > a.btn strong,
.header-area.no-print .notification-area > a.btn i,
.header-area.no-print .notification-area > button.btn i,
.header-area.no-print .notification-area > li > a.btn i,
.header-area.no-print .notification-area > li > button.btn i,
.header-area.no-print .notification-area > .btn-group > .btn i,
.header-area.no-print .notification-area > li.btn-group > .btn i,
.header-area.no-print .notification-area .notifications-menu > a.btn i,
.header-area.no-print .notification-area > a.btn .caret,
.header-area.no-print .notification-area > button.btn .caret,
.header-area.no-print .notification-area > li > a.btn .caret,
.header-area.no-print .notification-area > li > button.btn .caret,
.header-area.no-print .notification-area > .btn-group > .btn .caret,
.header-area.no-print .notification-area > li.btn-group > .btn .caret {
    color: #1f2937 !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.header-area.no-print .notification-area > a.btn:hover,
.header-area.no-print .notification-area > a.btn:focus,
.header-area.no-print .notification-area > button.btn:hover,
.header-area.no-print .notification-area > button.btn:focus,
.header-area.no-print .notification-area > li > a.btn:hover,
.header-area.no-print .notification-area > li > a.btn:focus,
.header-area.no-print .notification-area > li > button.btn:hover,
.header-area.no-print .notification-area > li > button.btn:focus,
.header-area.no-print .notification-area > .btn-group > .btn:hover,
.header-area.no-print .notification-area > .btn-group > .btn:focus,
.header-area.no-print .notification-area > li.btn-group > .btn:hover,
.header-area.no-print .notification-area > li.btn-group > .btn:focus {
    background: #f8fafc !important;
    color: #111827 !important;
}

.header-area.no-print .notification-area > a.btn:hover *,
.header-area.no-print .notification-area > a.btn:focus *,
.header-area.no-print .notification-area > button.btn:hover *,
.header-area.no-print .notification-area > button.btn:focus *,
.header-area.no-print .notification-area > li > a.btn:hover *,
.header-area.no-print .notification-area > li > a.btn:focus *,
.header-area.no-print .notification-area > li > button.btn:hover *,
.header-area.no-print .notification-area > li > button.btn:focus *,
.header-area.no-print .notification-area > .btn-group > .btn:hover *,
.header-area.no-print .notification-area > .btn-group > .btn:focus *,
.header-area.no-print .notification-area > li.btn-group > .btn:hover *,
.header-area.no-print .notification-area > li.btn-group > .btn:focus * {
    color: #111827 !important;
}

/* Preserve the approved blue user/profile control. */
.header-area.no-print .user-menu > a.dropdown-toggle,
.header-area.no-print .notification-area .user-menu > a.dropdown-toggle {
    background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
    color: #ffffff !important;
    border: none !important;
}
.header-area.no-print .user-menu > a.dropdown-toggle *,
.header-area.no-print .notification-area .user-menu > a.dropdown-toggle * {
    color: #ffffff !important;
}
</style>

<script>
    (function () {
        function closeHeaderDropdowns() {
            if (window.jQuery) {
                jQuery('.header-area .dropdown, .header-area .btn-group, .header-area .user-menu').removeClass('open');
                jQuery('.header-area .dropdown-toggle').attr('aria-expanded', 'false');
                jQuery('.header-area .dropdown-menu').removeClass('show').hide();
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', closeHeaderDropdowns);
        } else {
            closeHeaderDropdowns();
        }
    })();
</script>


    </div>
</div>
