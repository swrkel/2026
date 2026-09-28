{{--
    Transfer-safe sidebar stylesheet loader and layout guard.

    The same Laravel code serves central and tenant domains. These stylesheet
    URLs are therefore built from the document root currently serving the
    request and never from a hard-coded central or tenant host.

    Important: this guard deliberately does not force the sidebar's left,
    margin, transform, or visibility values. Those properties belong to the
    existing open/close controller in layouts/app.blade.php. Overriding them
    caused a collapsed sidebar to remain visible underneath the blue open
    button after a domain transfer.
--}}
@once
    @php
        $__erpDocumentRoot = isset($_SERVER['DOCUMENT_ROOT'])
            ? realpath((string) $_SERVER['DOCUMENT_ROOT'])
            : false;
        $__erpLaravelPublic = realpath(public_path());
        $__erpPublicUrlPrefix = '';

        if ($__erpDocumentRoot && $__erpLaravelPublic && $__erpDocumentRoot !== $__erpLaravelPublic) {
            $__erpDocumentPublic = realpath($__erpDocumentRoot . DIRECTORY_SEPARATOR . 'public');

            if ($__erpDocumentPublic && $__erpDocumentPublic === $__erpLaravelPublic) {
                $__erpPublicUrlPrefix = '/public';
            }
        }

        $__erpSidebarCssPath = public_path('v2/css/sidebar.min.css');
        $__erpClassicSidebarCssPath = public_path('v2/css/syzygy-classic-sidebar.css');
        $__erpSidebarCssVersion = is_file($__erpSidebarCssPath)
            ? (string) filemtime($__erpSidebarCssPath)
            : 'transfer-safe-2';
        $__erpClassicSidebarCssVersion = is_file($__erpClassicSidebarCssPath)
            ? (string) filemtime($__erpClassicSidebarCssPath)
            : 'transfer-safe-2';
    @endphp

    {{-- Reload from the host/subdomain currently open in the browser. --}}
    <link rel="stylesheet"
          href="{{ $__erpPublicUrlPrefix }}/v2/css/sidebar.min.css?v={{ $__erpSidebarCssVersion }}"
          data-erp-transfer-safe-sidebar="base">
    <link rel="stylesheet"
          href="{{ $__erpPublicUrlPrefix }}/v2/css/syzygy-classic-sidebar.css?v={{ $__erpClassicSidebarCssVersion }}"
          data-erp-transfer-safe-sidebar="classic">

    <style id="erp-transfer-safe-sidebar-typography-v11">
        /* Typography/structure only. Sidebar positioning belongs exclusively to app.blade.php. */
        /*
         * Use normal block flow rather than a full-height flex column. This
         * prevents module items from stretching vertically and gives every
         * central/tenant installation the approved compact menu positioning.
         */
        html body ul#accordionSidebar,
        html body ul#accordionSidebar.navbar-nav,
        html body ul#accordionSidebar.sidebar,
        html body ul#accordionSidebar.sidebar-menu {
            display: block !important;
            float: none !important;
            width: 220px !important;
            min-width: 220px !important;
            max-width: 220px !important;
            min-height: 100vh !important;
            padding: 0 !important;
            list-style: none !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            background-color: #4e73df !important;
            background-image: linear-gradient(180deg, #4e73df 10%, #224abe 100%) !important;
            background-size: cover !important;
            box-sizing: border-box !important;
        }

        html body ul#accordionSidebar > li,
        html body ul#accordionSidebar .nav-item,
        html body ul#accordionSidebar .treeview {
            display: block !important;
            float: none !important;
            clear: both !important;
            position: relative !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            list-style: none !important;
            box-sizing: border-box !important;
        }

        html body ul#accordionSidebar .sidebar-brand {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            float: none !important;
            width: 100% !important;
            height: 70px !important;
            min-height: 70px !important;
            margin: 0 !important;
            padding: 24px 16px !important;
            color: #ffffff !important;
            text-decoration: none !important;
            box-sizing: border-box !important;
        }

        html body ul#accordionSidebar .sidebar-brand .sidebar-brand-text {
            display: inline !important;
            color: #ffffff !important;
            font-size: 24px !important;
            font-weight: 800 !important;
            line-height: 28px !important;
        }

        html body ul#accordionSidebar hr.sidebar-divider {
            display: block !important;
            width: auto !important;
            height: 0 !important;
            margin: 0 16px 16px !important;
            border: 0 !important;
            border-top: 1px solid rgba(255, 255, 255, .15) !important;
        }

        html body ul#accordionSidebar #sidebarFilter {
            display: block !important;
            float: none !important;
            width: calc(100% - 20px) !important;
            height: 42px !important;
            margin: 0 10px 14px !important;
            padding: 8px 12px !important;
            border: 1px solid #dddddd !important;
            border-radius: 8px !important;
            background: #ffffff !important;
            color: #5a5c69 !important;
            font-size: 14px !important;
            line-height: 20px !important;
            box-shadow: none !important;
            box-sizing: border-box !important;
        }

        html body ul#accordionSidebar .nav-item > a,
        html body ul#accordionSidebar .nav-item > a.nav-link,
        html body ul#accordionSidebar .nav-item > .nav-link,
        html body ul#accordionSidebar .treeview > a {
            display: block !important;
            float: none !important;
            position: relative !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            min-height: 40px !important;
            margin: 0 !important;
            padding: 10px 16px !important;
            overflow: hidden !important;
            color: rgba(255, 255, 255, .88) !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            text-align: left !important;
            text-decoration: none !important;
            white-space: normal !important;
            font-size: 13px !important;
            font-weight: 400 !important;
            line-height: 19.5px !important;
            box-sizing: border-box !important;
        }

        html body ul#accordionSidebar .nav-item > a > i,
        html body ul#accordionSidebar .treeview > a > i {
            display: inline-block !important;
            float: none !important;
            width: 18px !important;
            min-width: 18px !important;
            margin: 0 7px 0 0 !important;
            color: rgba(255, 255, 255, .55) !important;
            font-size: 13px !important;
            line-height: 19.5px !important;
            text-align: center !important;
            vertical-align: top !important;
        }

        html body ul#accordionSidebar .nav-item > a > span:not(.pull-right-container),
        html body ul#accordionSidebar .treeview > a > span:not(.pull-right-container) {
            display: inline !important;
            color: inherit !important;
            font-size: 13px !important;
            line-height: 19.5px !important;
            vertical-align: middle !important;
        }

        html body ul#accordionSidebar .nav-item > a:hover,
        html body ul#accordionSidebar .nav-item > a:focus,
        html body ul#accordionSidebar .nav-item.active > a,
        html body ul#accordionSidebar .treeview > a:hover,
        html body ul#accordionSidebar .treeview.active > a {
            color: #ffffff !important;
            background: rgba(255, 255, 255, .08) !important;
            text-decoration: none !important;
        }

        /* Bootstrap 4 uses .show; legacy Bootstrap 3 uses .in. */
        html body ul#accordionSidebar .collapse:not(.show):not(.in) {
            display: none !important;
        }

        html body ul#accordionSidebar .collapse.show,
        html body ul#accordionSidebar .collapse.in {
            display: block !important;
            height: auto !important;
        }

        html body ul#accordionSidebar .collapse,
        html body ul#accordionSidebar .collapsing {
            float: none !important;
            width: auto !important;
            max-width: calc(100% - 20px) !important;
            margin: 0 10px 8px !important;
        }

        html body ul#accordionSidebar .collapse-inner {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 8px 0 !important;
            overflow: hidden !important;
            border-radius: 6px !important;
            background: #ffffff !important;
        }

        html body ul#accordionSidebar .collapse-inner .collapse-item {
            display: block !important;
            float: none !important;
            width: auto !important;
            margin: 0 6px !important;
            padding: 7px 14px !important;
            color: #3a3b45 !important;
            font-size: 13px !important;
            line-height: 18px !important;
            text-decoration: none !important;
            white-space: normal !important;
        }

        /*
         * Legacy AdminLTE panels are visually normalised to the same white-card
         * submenu used by the current ERP sidebar. Old module Blade files can
         * keep <ul class="treeview-menu"> markup without looking different.
         */
        html body ul#accordionSidebar .treeview-menu {
            display: none;
            float: none !important;
            width: auto !important;
            max-width: calc(100% - 20px) !important;
            margin: 0 10px 8px !important;
            padding: 8px 0 !important;
            overflow: hidden !important;
            list-style: none !important;
            border-radius: 6px !important;
            background: #ffffff !important;
            transition: none !important;
            animation: none !important;
        }

        html body ul#accordionSidebar .treeview.menu-open > .treeview-menu,
        html body ul#accordionSidebar .treeview.erp-sidebar-open > .treeview-menu {
            display: block !important;
        }

        html body ul#accordionSidebar .treeview-menu > li {
            display: block !important;
            float: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            list-style: none !important;
        }

        html body ul#accordionSidebar .treeview-menu > li > a {
            display: block !important;
            float: none !important;
            width: auto !important;
            margin: 0 6px !important;
            padding: 7px 14px !important;
            color: #3a3b45 !important;
            background: transparent !important;
            border-radius: 4px !important;
            font-size: 13px !important;
            font-weight: 400 !important;
            line-height: 18px !important;
            text-decoration: none !important;
            white-space: normal !important;
            opacity: 1 !important;
            transition: none !important;
            animation: none !important;
        }

        html body ul#accordionSidebar .treeview-menu > li > a > i {
            width: 16px !important;
            margin-right: 6px !important;
            color: #858796 !important;
            opacity: 1 !important;
        }

        html body ul#accordionSidebar .treeview-menu > li.active > a,
        html body ul#accordionSidebar .treeview-menu > li > a:hover,
        html body ul#accordionSidebar .treeview-menu > li > a:focus {
            color: #2e59d9 !important;
            background: #eaecf4 !important;
        }

        /*
         * No left/margin/transform rules are declared here. The existing
         * erpOpenSidebar()/erpCollapseSidebar() functions remain the sole source
         * of truth, so the open and close buttons cannot appear together.
         */

    </style>
@endonce
