@inject('request', 'Illuminate\Http\Request')

@php
    $__is_superadmin_area = auth()->check()
        && auth()->user()->can('superadmin')
        && $request->segment(1) === 'superadmin';
    $__is_superadmin_business_index = $__is_superadmin_area
        && $request->segment(2) === 'business'
        && empty($request->segment(3));
@endphp

@if (
    (($request->segment(1) == 'pos' || $request->segment(1) == 'tpos' || $request->segment(1) == 'fpos') &&
        ($request->segment(2) == 'create' || $request->segment(3) == 'edit')) ||
        ($request->segment(1) == 'purchase-pos' &&
            ($request->segment(2) == 'create' || $request->segment(3) == 'edit')))
    @php
        $pos_layout = true;
    @endphp
@else
    @php
        $pos_layout = false;
    @endphp
@endif

@if ($request->segment(1) == 'member')
    @php
        $member = true;
    @endphp
@else
    @php
        $member = false;
    @endphp
@endif

<!DOCTYPE html>

<html lang="{{ app()->getLocale() }}"
    dir="{{ in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) ? 'rtl' : 'ltr' }}">

<head>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"
          name="viewport">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>

        @yield('title') - {{ Session::get('business.name') }}

    </title>

    @include('layouts.partials.favicon')

    @if ($__is_superadmin_business_index)
        @include('layouts.partials.css-superadmin-business')
    @else
        @include('layouts.partials.css')
    @endif
    
    @unless ($__is_superadmin_business_index)
        <link rel="stylesheet"
          href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    @endunless

    @yield('css')

    @stack('css')

    <!-- ===================================================== -->
    <!-- GOOGLE FONTS -->
    <!-- ===================================================== -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
          rel="stylesheet">

    <!-- ===================================================== -->
    <!-- EXTERNAL LIBRARIES -->
    <!-- ===================================================== -->

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.2/animate.min.css">

    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>

    @unless ($__is_superadmin_business_index)
        <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js?v=' . $asset_v) }}"></script>
    @endunless

    @unless ($__is_superadmin_business_index)
        <script type="text/javascript"
                src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

        <script type="text/javascript"
                src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

        <link rel="stylesheet"
              type="text/css"
              href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

        @if ($request->segment(1) != 'helpguide' && $request->segment(2) != 'helpguide')
            <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.min.js"></script>
        @endif
    @endunless

    <script src="{{ asset('AdminLTE/plugins/select2/js/select2.full.min.js?v=' . $asset_v) }}"></script>

    @unless ($__is_superadmin_business_index)
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.6.0/bootstrap-tagsinput.min.js"></script>
        <link rel="stylesheet"
              href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.6.0/bootstrap-tagsinput.min.css" />

        <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
        <script src="https://kit.fontawesome.com/{{ config('vars.font_awesome_id') }}.js"
                crossorigin="anonymous"></script>
        <link rel="stylesheet"
              href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/decimal.js/10.3.1/decimal.min.js"></script>
        <link rel="stylesheet"
              href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css">
    @endunless


    @php
        // ERP_GLOBAL_UI_002: Resolve font from Business Settings only.
        // Controlled allow-list prevents invalid CSS/free-text font conflicts.
        $__erp_allowed_fonts = [
            'Calibri',
            'Arial',
            'Segoe UI',
            'Tahoma',
            'Verdana',
            'Roboto',
            'Inter',
            'Times New Roman',
            'Georgia',
            'Courier New',
        ];
        $__erp_business_session = session()->get('business');
        $__erp_font_style = is_object($__erp_business_session) && !empty($__erp_business_session->font_style)
            ? $__erp_business_session->font_style
            : 'Calibri';
        if (!in_array($__erp_font_style, $__erp_allowed_fonts, true)) {
            $__erp_font_style = 'Calibri';
        }
        $__erp_font_size = is_object($__erp_business_session) && !empty($__erp_business_session->font_size)
            ? (int) $__erp_business_session->font_size
            : 14;
        if (!in_array($__erp_font_size, [12, 13, 14, 15, 16, 18, 20], true)) {
            $__erp_font_size = 14;
        }
    @endphp

    <!-- ===================================================== -->
    <!-- ENTERPRISE GLOBAL FRAMEWORK -->
    <!-- ===================================================== -->

    <style>

        /* =========================================================
           GLOBAL TYPOGRAPHY
        ========================================================= */

        html,
        body,
        button,
        input,
        select,
        textarea,
        .table,
        .dataTable,
        .dataTables_wrapper,
        .modal,
        .dropdown-menu,
        .btn,
        .card,
        .box,
        .navbar,
        .sidebar,
        .sidebar-menu,
        .content-wrapper,
        .main-content {
            font-family: '{{ $__erp_font_style }}', Calibri, "Segoe UI", Arial, sans-serif !important;
            font-size: {{ $__erp_font_size }}px;
        }

        body {

            background: #f4f6f9 !important;

            color: #2c3e50;

            letter-spacing: 0.2px;

            overflow-x: hidden;
        }

        /* =========================================================
           MAIN LAYOUT
        ========================================================= */

        .page-container {

            background: #f4f6f9;

            min-height: 100vh;
        }

        .main-content {

            background: #f4f6f9;

            min-height: 100vh;
        }

        /* =========================================================
           GLOBAL RESPONSIVE WIDTH / SIDEBAR AUTO-RESIZE - 20260907 V2
           Important: older AdminLTE/custom rules can leave offsets on the
           page-container itself as well as on content-wrapper/container.
           Reset every layout owner here so collapsed = full viewport and
           expanded = viewport minus the LIVE measured sidebar width.
           ========================================================= */
        html,
        body {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
            overflow-x: hidden !important;
        }

        body:not(.lockscreen) .page-container {
            position: relative !important;
            left: 0 !important;
            right: auto !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
            overflow-x: hidden !important;
        }

        body:not(.lockscreen) .main-content {
            position: relative !important;
            left: 0 !important;
            right: auto !important;
            transform: none !important;
            margin-left: var(--erp-content-offset, 0px) !important;
            margin-right: 0 !important;
            width: calc(100% - var(--erp-content-offset, 0px)) !important;
            max-width: calc(100% - var(--erp-content-offset, 0px)) !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
            transition: margin-left .16s ease, width .16s ease, max-width .16s ease;
        }

        body:not(.lockscreen).sidebar-collapse .main-content,
        body:not(.lockscreen).finance-sidebar-collapsed .main-content {
            --erp-content-offset: 0px;
        }

        /* The main-content element is the ONLY owner of sidebar spacing.
           Remove second/legacy offsets and Bootstrap max-width limits below it. */
        body:not(.lockscreen) .main-content > .main-header,
        body:not(.lockscreen) .main-content > .main-footer,
        body:not(.lockscreen) .main-content .content-wrapper,
        body:not(.lockscreen) .main-content .content-area,
        body:not(.lockscreen) .main-content .right-side {
            left: 0 !important;
            right: auto !important;
            transform: none !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        body:not(.lockscreen) .main-content .content,
        body:not(.lockscreen) .main-content .container,
        body:not(.lockscreen) .main-content .container-fluid,
        body:not(.lockscreen) .main-content .main-content-inner {
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        /* Phones/tablets use an overlay sidebar; laptops/desktops resize beside it. */
        @media (max-width: 767px) {
            body:not(.lockscreen) .main-content {
                --erp-content-offset: 0px !important;
                margin-left: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }

        .content-wrapper,
        .content-area,
        .content {

            background: transparent !important;
        }

        /* =========================================================
           CONTENT SYSTEM
        ========================================================= */

        .main-content-inner {

            padding-top: 0px !important;

            margin-top: 20px;

            border-radius: 16px;

            background-color: transparent !important;

            padding: 25px;
        }

        .content {

            margin-top: 0px;

            border-radius: 16px;

            background-color: transparent !important;

            padding: 25px !important;
        }

        .content-header {

            border-radius: 16px;

            background: linear-gradient(
                135deg,
                #ffffff,
                #f8fafc
            );

            padding: 24px;

            margin-bottom: 22px;

            box-shadow: 0 3px 15px rgba(0,0,0,0.08);

            border-left: 4px solid #3c8dbc;
        }

        .content-header h1 {

            font-size: 28px !important;

            font-weight: 700;

            color: #2c3e50;

            margin: 0;
        }

        .content-header small {

            display: block;

            margin-top: 8px;

            font-size: 14px;

            color: #7f8c8d;
        }

        /* =========================================================
           ENTERPRISE CARD SYSTEM
        ========================================================= */

        .box,
        .small-box,
        .info-box,
        .card,
        .dashboard-card,
        .governance-box {

            border-radius: 16px !important;

            border: none !important;

            box-shadow: 0 3px 15px rgba(0,0,0,0.08) !important;

            overflow: hidden;

            background: #ffffff;
        }

        /* =========================================================
           TABLE SYSTEM
        ========================================================= */

        .table {

            border-radius: 14px;

            overflow: hidden;

            background: #fff;
        }

        .table thead {

            background: #f4f6f9;
        }

        .table thead th {

            border: none !important;

            text-transform: uppercase;

            font-size: 12px;

            color: #7f8c8d;

            letter-spacing: 0.5px;

            font-weight: 700;
        }

        .table td {

            vertical-align: middle !important;

            padding: 14px !important;
        }

        /* =========================================================
           BUTTON SYSTEM
        ========================================================= */

        .btn {

            border-radius: 10px !important;

            font-weight: 600;

            border: none !important;

            padding: 9px 16px;

            transition: all 0.25s ease;
        }

        .btn:hover {

            transform: translateY(-1px);

            box-shadow: 0 6px 14px rgba(0,0,0,0.12);
        }

        /* =========================================================
           FORM SYSTEM
        ========================================================= */

        .form-control,
        .select2-selection {

            border-radius: 10px !important;

            min-height: 42px;

            border: 1px solid #dce1e7 !important;

            box-shadow: none !important;

            padding-left: 12px;
        }

        .form-control:focus {

            border-color: #3c8dbc !important;

            box-shadow: 0 0 0 3px rgba(60,141,188,0.12) !important;
        }
        /* =========================================================
           SIDEBAR
        ========================================================= */

        /* Keep original ERP/AdminLTE sidebar colours. Do not override globally here. */

        .sidebar-menu > li > a {

            border-radius: 10px;

            margin-bottom: 5px;

            color: #d1d5db !important;

            font-size: 13px !important;

            padding: 12px 14px !important;

            transition: all 0.2s ease;
        }

        .sidebar-menu > li > a:hover {

            background: rgba(255,255,255,0.08) !important;

            color: #fff !important;
        }

        .sidebar-menu > li.active > a {

            background: linear-gradient(
                135deg,
                #3c8dbc,
                #00c0ef
            ) !important;

            color: #fff !important;
        }



        /* =========================================================
           SIDEBAR LAYER SAFETY
           Sidebar must stay above header cards/buttons when opened.
        ========================================================= */

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

        .header-area {
            z-index: 20 !important;
        }

        /* =========================================================
           SIDEBAR DEFAULT COLLAPSE / TOGGLE FIX
           Keep sidebar collapsed by default and ensure the sidebar close
           button can hide it without leaving the menu under the header.
        ========================================================= */

        /* TEST - custom sidebar rules disabled
body.finance-sidebar-collapsed .main-sidebar,
        body.finance-sidebar-collapsed .left-side,
        body.finance-sidebar-collapsed #accordionSidebar {
            margin-left: calc(var(--finance-sidebar-width, 405px) * -1) !important;
        }

        body.sidebar-open .main-sidebar,
        body.sidebar-open .left-side,
        body.sidebar-open #accordionSidebar {
            margin-left: 0 !important;
        }

        body.finance-sidebar-collapsed .page-container,
        body.finance-sidebar-collapsed .main-content,
        body.finance-sidebar-collapsed .content-wrapper,
        body.finance-sidebar-collapsed .content-area,
        body.finance-sidebar-collapsed .right-side,
        body.finance-sidebar-collapsed .main-footer {
            margin-left: 0 !important;
        }

        body.sidebar-open .main-sidebar,
        body.sidebar-open .left-side,
        body.sidebar-open #accordionSidebar,
        body.sidebar-open .sidebar {
            z-index: 5000 !important;
        }
        */

        .main-sidebar .close,
        .main-sidebar .sidebar-close,
        .main-sidebar .close-sidebar,
        #accordionSidebar .close,
        #accordionSidebar .sidebar-close,
        #accordionSidebar .close-sidebar {
            cursor: pointer !important;
        }


        /* =========================================================
           GLOBAL SIDEBAR OPEN BUTTON - STABLE
           Shows only when sidebar is collapsed.
        ========================================================= */

        .erp-sidebar-open-btn {
            position: fixed !important;
            left: 0 !important;
            top: 132px !important;
            width: 54px !important;
            height: 72px !important;
            z-index: 6500 !important;
            display: none;
            align-items: center !important;
            justify-content: center !important;
            border: 0 !important;
            border-radius: 0 12px 12px 0 !important;
            background: #2563eb !important;
            color: #ffffff !important;
            box-shadow: 0 8px 22px rgba(37, 99, 235, 0.35) !important;
            cursor: pointer !important;
            pointer-events: auto !important;
            padding: 0 !important;
        }

        .erp-sidebar-open-btn i {
            font-size: 22px !important;
            line-height: 1 !important;
            color: #ffffff !important;
            pointer-events: none !important;
        }

        body.sidebar-open .erp-sidebar-open-btn {
            display: none !important;
        }

        body.finance-sidebar-collapsed .erp-sidebar-open-btn,
        body.sidebar-collapse .erp-sidebar-open-btn {
            display: flex !important;
        }



        .erp-sidebar-close-btn {
            position: fixed !important;
            left: calc(var(--finance-sidebar-width, 405px) - 82px) !important;
            top: 28px !important;
            width: 50px !important;
            height: 50px !important;
            z-index: 8500 !important;
            display: none;
            align-items: center !important;
            justify-content: center !important;
            border: 0 !important;
            border-radius: 50% !important;
            background: #ef4444 !important;
            color: #ffffff !important;
            box-shadow: 0 10px 22px rgba(239, 68, 68, 0.34) !important;
            cursor: pointer !important;
            pointer-events: auto !important;
            padding: 0 !important;
            text-align: center !important;
        }

        .erp-sidebar-close-btn i {
            font-size: 22px !important;
            line-height: 1 !important;
            color: #ffffff !important;
            pointer-events: none !important;
        }

        body.sidebar-open .erp-sidebar-close-btn {
            display: flex !important;
        }

        body.finance-sidebar-collapsed .erp-sidebar-close-btn,
        body.sidebar-collapse .erp-sidebar-close-btn {
            display: none !important;
        }


        /* =========================================================
           NAVBAR
        ========================================================= */

        .main-header,
        .navbar,
        .main-header .navbar {

            background: #ffffff !important;

            border: none !important;

            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .navbar-nav > li > a {

            color: #2c3e50 !important;

            font-weight: 600;
        }

        /* =========================================================
           LABELS
        ========================================================= */

        .label {

            border-radius: 8px;

            padding: 6px 10px;

            font-size: 11px;

            font-weight: 700;
        }

        /* =========================================================
           MODALS
        ========================================================= */

        .modal-content {

            border-radius: 16px !important;

            border: none !important;

            box-shadow: 0 10px 35px rgba(0,0,0,0.15);
        }

        /* =========================================================
           SCROLLBAR
        ========================================================= */

        ::-webkit-scrollbar {

            width: 10px;

            height: 10px;
        }

        ::-webkit-scrollbar-thumb {

            background: linear-gradient(
                180deg,
                #3c8dbc,
                #00c0ef
            );

            border-radius: 20px;
        }

        ::-webkit-scrollbar-track {

            background: #f1f5f9;
        }

        /* =========================================================
           ANIMATIONS
        ========================================================= */

        .box,
        .card,
        .dashboard-card,
        .governance-box {

            transition: all 0.25s ease;
        }

        .box:hover,
        .card:hover,
        .dashboard-card:hover,
        .governance-box:hover {

            transform: translateY(-2px);

            box-shadow: 0 10px 25px rgba(0,0,0,0.10) !important;
        }

        /* =========================================================
           MOBILE RESPONSIVE
        ========================================================= */

        @media (max-width: 768px) {

            .content {

                padding: 15px !important;
            }

            .content-header {

                padding: 18px;
            }

            .content-header h1 {

                font-size: 22px !important;
            }
        }

    </style>

    @if(request()->is('petropd/settlement-pd/create'))
        <style>
            /* EXF V5.3: collapse only empty header/spacer blocks on Petro PD settlement create. */
            body.erp-route-petropd-settlement-create .content-header:empty,
            body.erp-route-petropd-settlement-create .content-header.erp-empty-header,
            body.erp-route-petropd-settlement-create .erp-empty-page-spacer {
                display: none !important;
                min-height: 0 !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                box-shadow: none !important;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var selectors = ['.content-header', '.content > .row', '.content > section'];
                selectors.forEach(function (selector) {
                    document.querySelectorAll(selector).forEach(function (element) {
                        var text = (element.innerText || '').replace(/\s+/g, ' ').trim();
                        var interactive = element.querySelector('a, button, input, select, textarea, table, form, img, svg, canvas');
                        if (!text && !interactive) {
                            element.classList.add('erp-empty-page-spacer');
                        }
                    });
                });
            });
        </script>
    @endif

    {{-- ERP Communication Hub global UI standard loaded after legacy inline styles for consistent system-wide design. --}}
    <link rel="stylesheet" href="{{ asset('css/erp-communication-standard.css?v=20260707tabv3') }}">

</head>

@php
    $business_id = session()->get('user.business_id');
    // Child tenant views rely on this variable, but Superadmin pages do not.
    // Avoid an unnecessary business-table query on every Superadmin request.
    $business_details = $__is_superadmin_area || empty($business_id)
        ? null
        : App\Business::find($business_id);
@endphp

<body
    style="font-family: '{{ $__erp_font_style }}', Calibri, 'Segoe UI', Arial, sans-serif !important; font-size: {{ $__erp_font_size }}px !important;"
    class="erp-global-typography @if ($pos_layout) hold-transition lockscreen @else hold-transition skin-@if (!empty(session('business.theme_color'))){{ session('business.theme_color') }}@else blue @endif sidebar-mini sidebar-collapse finance-sidebar-collapsed @endif @if(request()->is('petropd/settlement-pd/create')) erp-route-petropd-settlement-create @endif">

    @unless ($__is_superadmin_business_index)
        {{-- Tenant transaction pages need live currency and lock-screen state. --}}
        @include('layouts.partials.global_currency_inputs')
        @include('layouts.partials.lock_screen')
    @endunless

    <div class="page-container custom-overflow">

        @if (!$pos_layout)

            @if ($__is_superadmin_area)
                @include('layouts.partials.sidebar-superadmin')
            @else
                @include('layouts.partials.sidebar')
                @includeIf('layouts.partials.business-sidebar-permission-guard')
            @endif

            <button type="button" id="erp_sidebar_open_btn" class="erp-sidebar-open-btn no-print" aria-label="Open menu">
                <i class="fa fa-bars"></i>
            </button>

            <button type="button" id="erp_sidebar_close_btn" class="erp-sidebar-close-btn no-print" aria-label="Close menu" onclick="erpCollapseSidebar(); return false;">
                <i class="fa fa-times"></i>
            </button>

        @endif

        <div class="@if (!$pos_layout) main-content @endif">

            @if (!$pos_layout)

                @if ($__is_superadmin_area)
                    @include('layouts.partials.header-superadmin')
                @else
                    @include('layouts.partials.header')
                @endif

            @endif

            @yield('helpguide_content')

            @yield('content')

            {{-- Global AJAX modal containers used by Add/Edit buttons across Expense, VAT and settings pages. --}}
            <div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
            <div class="modal fade fuel_tank_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
            <div class="modal fade expense_category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
            <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
            <div class="modal fade contact_modal_noreload" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

            <section class="invoice print_section"
                     id="receipt_section">
            </section>

        </div>

        @unless ($__is_superadmin_area)
            @include('home.todays_profit_modal')
        @endunless

        @if (!$pos_layout)
            @if ($__is_superadmin_business_index)
                @include('layouts.partials.footer-superadmin-business')
            @else
                @include('layouts.partials.footer')
            @endif
        @else
            @include('layouts.partials.footer_pos')
        @endif

    </div>

    @if ($__is_superadmin_business_index)
        @include('layouts.partials.javascripts-superadmin-business')
    @else
        @include('layouts.partials.javascripts')
    @endif

    
    @unless ($__is_superadmin_business_index)
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js"></script>
    @endunless

    @include('layouts.partials.subscription_grace_period_notice')

<script>

    /*
    |--------------------------------------------------------------------------
    | GLOBAL SIDEBAR OPEN / CLOSE - HARD STABLE VERSION
    |--------------------------------------------------------------------------
    | This version does not depend on AdminLTE toggle behaviour. It directly
    | moves the sidebar in/out, so the left blue button and the red close
    | button work consistently on every page.
    */

    function erpViewportWidth() {
        return Math.max(320, document.documentElement.clientWidth || window.innerWidth || 0);
    }

    function erpSidebarWidth() {
        var sidebar = document.querySelector('#accordionSidebar, .main-sidebar, .left-side');
        var width = 405;

        if (sidebar) {
            var rect = sidebar.getBoundingClientRect ? sidebar.getBoundingClientRect() : null;
            var measured = rect && rect.width ? rect.width : sidebar.offsetWidth;
            if (measured && measured > 120) {
                width = Math.round(measured);
            }
        }

        var viewport = erpViewportWidth();
        width = Math.min(width, Math.max(180, viewport - 320));

        document.documentElement.style.setProperty('--erp-sidebar-live-width', width + 'px');
        document.documentElement.style.setProperty('--finance-sidebar-width', width + 'px');

        return width;
    }

    function erpReflowWidthAwareWidgets() {
        try {
            if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
                jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            }
        } catch (ignore) {}

        try {
            if (window.Highcharts && Highcharts.charts) {
                Highcharts.charts.forEach(function(chart) {
                    if (chart && chart.reflow) { chart.reflow(); }
                });
            }
        } catch (ignore) {}

        try {
            if (window.Chart && Chart.instances) {
                Object.keys(Chart.instances).forEach(function(key) {
                    var chart = Chart.instances[key];
                    if (chart && chart.resize) { chart.resize(); }
                });
            }
        } catch (ignore) {}
    }

    function erpApplyResponsiveContentWidth(isOpen, sidebarWidth) {
        var viewport = erpViewportWidth();
        var desktopResize = viewport > 767;
        var effectiveOffset = isOpen && desktopResize ? sidebarWidth : 0;
        var available = Math.max(320, viewport - effectiveOffset);

        document.documentElement.style.setProperty('--erp-content-offset', effectiveOffset + 'px');
        document.body.style.setProperty('--erp-content-offset', effectiveOffset + 'px');

        var pageContainer = document.querySelector('.page-container');
        if (pageContainer) {
            pageContainer.style.setProperty('margin-left', '0px', 'important');
            pageContainer.style.setProperty('margin-right', '0px', 'important');
            pageContainer.style.setProperty('padding-left', '0px', 'important');
            pageContainer.style.setProperty('padding-right', '0px', 'important');
            pageContainer.style.setProperty('width', viewport + 'px', 'important');
            pageContainer.style.setProperty('max-width', viewport + 'px', 'important');
            pageContainer.style.setProperty('min-width', '0', 'important');
            pageContainer.style.setProperty('left', '0px', 'important');
            pageContainer.style.setProperty('transform', 'none', 'important');
        }

        var mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.style.setProperty('position', 'relative', 'important');
            mainContent.style.setProperty('left', '0px', 'important');
            mainContent.style.setProperty('right', 'auto', 'important');
            mainContent.style.setProperty('transform', 'none', 'important');
            mainContent.style.setProperty('margin-left', effectiveOffset + 'px', 'important');
            mainContent.style.setProperty('margin-right', '0px', 'important');
            mainContent.style.setProperty('width', available + 'px', 'important');
            mainContent.style.setProperty('max-width', available + 'px', 'important');
            mainContent.style.setProperty('min-width', '0', 'important');
            mainContent.style.setProperty('box-sizing', 'border-box', 'important');
        }

        var resetNodes = document.querySelectorAll(
            '.main-content > .main-header, .main-content > .main-footer, ' +
            '.main-content .content-wrapper, .main-content .content-area, .main-content .right-side'
        );
        resetNodes.forEach(function(node) {
            node.style.setProperty('left', '0px', 'important');
            node.style.setProperty('right', 'auto', 'important');
            node.style.setProperty('transform', 'none', 'important');
            node.style.setProperty('margin-left', '0px', 'important');
            node.style.setProperty('margin-right', '0px', 'important');
            node.style.setProperty('width', '100%', 'important');
            node.style.setProperty('max-width', '100%', 'important');
            node.style.setProperty('min-width', '0', 'important');
            node.style.setProperty('box-sizing', 'border-box', 'important');
        });

        var fluidNodes = document.querySelectorAll(
            '.main-content .content, .main-content .container, ' +
            '.main-content .container-fluid, .main-content .main-content-inner'
        );
        fluidNodes.forEach(function(node) {
            node.style.setProperty('margin-left', '0px', 'important');
            node.style.setProperty('margin-right', '0px', 'important');
            node.style.setProperty('width', '100%', 'important');
            node.style.setProperty('max-width', 'none', 'important');
            node.style.setProperty('min-width', '0', 'important');
            node.style.setProperty('box-sizing', 'border-box', 'important');
        });

        try {
            window.dispatchEvent(new CustomEvent('erp:layout-resized', {
                detail: {
                    sidebarOpen: !!isOpen,
                    sidebarWidth: sidebarWidth,
                    availableWidth: available,
                    viewportWidth: viewport
                }
            }));
        } catch (ignore) {}

        // Reflow now and again after layout transitions / late page scripts.
        erpReflowWidthAwareWidgets();
        setTimeout(erpReflowWidthAwareWidgets, 80);
        setTimeout(erpReflowWidthAwareWidgets, 260);
    }

    function erpSetSidebarState(isOpen) {
        var width = erpSidebarWidth();
        var sidebarNodes = document.querySelectorAll('#accordionSidebar, .main-sidebar, .left-side');
        var openButton = document.getElementById('erp_sidebar_open_btn');
        var closeButton = document.getElementById('erp_sidebar_close_btn');

        if (isOpen) {
            document.body.classList.add('sidebar-open');
            document.body.classList.remove('sidebar-collapse');
            document.body.classList.remove('finance-sidebar-collapsed');

            sidebarNodes.forEach(function(node) {
                node.style.marginLeft = '0px';
                node.style.left = '0px';
                node.style.transform = 'none';
                node.style.zIndex = '7000';
                node.style.visibility = 'visible';
            });

            erpApplyResponsiveContentWidth(true, width);

            if (openButton) {
                openButton.style.display = 'none';
            }
            if (closeButton) {
                closeButton.style.display = 'flex';
            }
        } else {
            document.body.classList.remove('sidebar-open');
            document.body.classList.add('sidebar-collapse');
            document.body.classList.add('finance-sidebar-collapsed');

            sidebarNodes.forEach(function(node) {
                node.style.marginLeft = '-' + width + 'px';
                node.style.left = '0px';
                node.style.transform = 'none';
                node.style.zIndex = '7000';
                node.style.visibility = 'visible';
            });

            erpApplyResponsiveContentWidth(false, width);

            if (openButton) {
                openButton.style.display = 'flex';
            }
            if (closeButton) {
                closeButton.style.display = 'none';
            }
        }
    }

    function erpCollapseSidebar() {
        erpSetSidebarState(false);
    }

    function erpOpenSidebar() {
        erpSetSidebarState(true);
    }

    function erpToggleSidebar() {
        erpSetSidebarState(!document.body.classList.contains('sidebar-open'));
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Bind sidebar first. Do not let datepicker errors stop sidebar.
        var openBtn = document.getElementById('erp_sidebar_open_btn');
        if (openBtn) {
            openBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                erpOpenSidebar();
                return false;
            }, true);
        }

        var closeBtn = document.getElementById('erp_sidebar_close_btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                erpCollapseSidebar();
                return false;
            }, true);
        }

        document.addEventListener('click', function(e) {
            var toggle = e.target.closest('[data-toggle="offcanvas"], .sidebar-toggle, #sidebar_collapser');
            if (toggle) {
                e.preventDefault();
                e.stopPropagation();
                erpToggleSidebar();
                return false;
            }

            var close = e.target.closest('#accordionSidebar .close, #accordionSidebar .sidebar-close, #accordionSidebar .close-sidebar, #accordionSidebar .sidebar-close-btn, .main-sidebar .close, .main-sidebar .sidebar-close, .main-sidebar .close-sidebar, .main-sidebar .sidebar-close-btn');
            if (close) {
                e.preventDefault();
                e.stopPropagation();
                erpCollapseSidebar();
                return false;
            }
        }, true);

        // Collapse by default on every full page load.
        erpCollapseSidebar();

        // Optional widgets must never break sidebar behaviour.
        try {
            if (window.jQuery && $.fn.datepicker) {
                $('.snooze_date').datepicker('setDate', new Date());
            }
        } catch (e) {}

        try {
            if (window.jQuery && $.fn.datetimepicker) {
                $('.snooze_time').datetimepicker({ format: 'HH:mm' });
            }
        } catch (e) {}
    });

    var erpLayoutResizeTimer = null;
    window.addEventListener('resize', function() {
        clearTimeout(erpLayoutResizeTimer);
        erpLayoutResizeTimer = setTimeout(function() {
            erpSetSidebarState(document.body.classList.contains('sidebar-open'));
        }, 60);
    });

    // Some legacy/AdminLTE scripts modify body/sidebar classes after page load.
    // Re-apply the exact viewport calculation whenever those classes change.
    try {
        var erpBodyClassObserver = new MutationObserver(function(mutations) {
            var needsResize = mutations.some(function(mutation) {
                return mutation.type === 'attributes' && mutation.attributeName === 'class';
            });
            if (needsResize) {
                clearTimeout(erpLayoutResizeTimer);
                erpLayoutResizeTimer = setTimeout(function() {
                    erpSetSidebarState(document.body.classList.contains('sidebar-open'));
                }, 25);
            }
        });
        erpBodyClassObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    } catch (ignore) {}

    window.addEventListener('load', function() {
        setTimeout(function() {
            erpSetSidebarState(document.body.classList.contains('sidebar-open'));
        }, 80);
        setTimeout(function() {
            erpSetSidebarState(document.body.classList.contains('sidebar-open'));
        }, 350);
        setTimeout(function() {
            erpSetSidebarState(document.body.classList.contains('sidebar-open'));
        }, 900);
    });

</script>

    @yield('model-scritps')

    {{-- BUSINESS-MODULE-DATE-DEFAULTS-20260821: load after page/model scripts so the selected default wins. --}}
    @unless ($__is_superadmin_area)
        @include('layouts.partials.module-date-defaults')
    @endunless

    {{-- 8051: Load the idle-banner runtime directly from the real authenticated layout.
         This avoids response-body rewriting and keeps the idle timer active on tenant pages. --}}
    @if (auth()->check() && !$__is_superadmin_area && \Illuminate\Support\Facades\Route::has('superadmin.banner.idle.runtime'))
        <script defer
                data-sa-idle-banner-runtime="1"
                src="{{ route('superadmin.banner.idle.runtime') }}"></script>
    @endif

</body>

</html>
