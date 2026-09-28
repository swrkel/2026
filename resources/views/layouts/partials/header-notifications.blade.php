@php

    $all_notifications =
        auth()->user()->notifications;

    $unread_notifications =
        $all_notifications->where(
            'read_at',
            null
        );

    $total_unread =
        count($unread_notifications);

@endphp

<!-- ===================================================== -->
<!-- ENTERPRISE GOVERNANCE NOTIFICATION CENTER -->
<!-- ===================================================== -->

<li class="dropdown notifications-menu enterprise-notification-wrapper">

    <a href="#"
       class="dropdown-toggle load_notifications enterprise-notification-toggle"
       data-toggle="dropdown"
       id="show_unread_notifications"
       data-loaded="false">

        <div class="enterprise-notification-icon">

            <i class="fa fa-bell-o"></i>

            @if(!empty($total_unread))

                <span class="enterprise-notification-badge notifications_count">

                    {{$total_unread}}

                </span>

            @endif

        </div>

    </a>

    <!-- ================================================= -->
    <!-- DROPDOWN -->
    <!-- ================================================= -->

    <ul class="dropdown-menu enterprise-notification-dropdown">

        <!-- HEADER -->

        <li class="enterprise-notification-header">

            <div>

                <div class="enterprise-title">

                    Governance Notifications

                </div>

                <div class="enterprise-subtitle">

                    Executive activity & governance alerts

                </div>

            </div>

            @if(!empty($total_unread))

                <span class="enterprise-pill">

                    {{$total_unread}} New

                </span>

            @endif

        </li>

        <!-- BODY -->

        <li class="enterprise-notification-body">

            <ul class="menu"
                id="notifications_list">

                @if(count($all_notifications) > 10)

                    <li class="text-center load_more_li">

                        <a href="#"
                           class="load_more_notifications enterprise-load-more">

                            <i class="fa fa-refresh"></i>

                            <small>

                                @lang('lang_v1.load_more')

                            </small>

                        </a>

                    </li>

                @endif

            </ul>

        </li>

    </ul>

</li>

<input type="hidden"
       id="notification_page"
       value="1">

<!-- ===================================================== -->
<!-- ENTERPRISE NOTIFICATION STYLING -->
<!-- ===================================================== -->

<style>

    /* =====================================================
       WRAPPER
    ===================================================== */

    .enterprise-notification-wrapper {

        position: relative;
    }

    /* =====================================================
       TOGGLE
    ===================================================== */

    .enterprise-notification-toggle {

        padding: 10px 14px !important;

        border-radius: 14px !important;

        transition: all 0.25s ease;

        background: #ffffff !important;

        border: 1px solid #e5e7eb !important;

        min-height: 44px;

        display: flex !important;

        align-items: center;

        justify-content: center;
    }

    .enterprise-notification-toggle:hover {

        background: #f8fafc !important;

        transform: translateY(-1px);

        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }

    /* =====================================================
       ICON
    ===================================================== */

    .enterprise-notification-icon {

        position: relative;

        font-size: 18px;

        color: #2563eb;
    }

    /* =====================================================
       BADGE
    ===================================================== */

    .enterprise-notification-badge {

        position: absolute;

        top: -8px;

        right: -10px;

        min-width: 20px;

        height: 20px;

        border-radius: 50%;

        background: linear-gradient(
            135deg,
            #ef4444,
            #dc2626
        ) !important;

        color: #ffffff !important;

        font-size: 10px !important;

        font-weight: 700;

        display: flex;

        align-items: center;

        justify-content: center;

        box-shadow: 0 6px 12px rgba(239,68,68,0.35);
    }

    /* =====================================================
       DROPDOWN
    ===================================================== */

    .enterprise-notification-dropdown {

        width: 380px !important;

        border-radius: 18px !important;

        border: none !important;

        overflow: hidden;

        padding: 0 !important;

        margin-top: 14px !important;

        box-shadow: 0 20px 45px rgba(0,0,0,0.18);
    }

    /* =====================================================
       HEADER
    ===================================================== */

    .enterprise-notification-header {

        display: flex;

        align-items: center;

        justify-content: space-between;

        padding: 20px;

        background: linear-gradient(
            135deg,
            #2563eb,
            #06b6d4
        );

        color: #ffffff;
    }

    .enterprise-title {

        font-size: 16px;

        font-weight: 700;

        margin-bottom: 4px;
    }

    .enterprise-subtitle {

        font-size: 12px;

        opacity: 0.85;
    }

    .enterprise-pill {

        padding: 8px 12px;

        border-radius: 30px;

        background: rgba(255,255,255,0.18);

        font-size: 11px;

        font-weight: 700;

        letter-spacing: 0.5px;
    }

    /* =====================================================
       BODY
    ===================================================== */

    .enterprise-notification-body {

        background: #ffffff;

        max-height: 450px;

        overflow-y: auto;
    }

    .enterprise-notification-body .menu {

        padding: 12px;

        margin: 0;

        list-style: none;
    }

    /* =====================================================
       LOAD MORE
    ===================================================== */

    .enterprise-load-more {

        display: flex !important;

        align-items: center;

        justify-content: center;

        gap: 8px;

        border-radius: 12px;

        padding: 12px !important;

        background: #f8fafc;

        color: #2563eb !important;

        font-weight: 700;

        transition: all 0.22s ease;
    }

    .enterprise-load-more:hover {

        background: #eff6ff !important;

        transform: translateY(-1px);
    }

    /* =====================================================
       SCROLLBAR
    ===================================================== */

    .enterprise-notification-body::-webkit-scrollbar {

        width: 8px;
    }

    .enterprise-notification-body::-webkit-scrollbar-thumb {

        background: rgba(37,99,235,0.25);

        border-radius: 20px;
    }

    /* =====================================================
       MOBILE
    ===================================================== */

    @media (max-width: 768px) {

        .enterprise-notification-dropdown {

            width: 320px !important;

            right: -80px !important;
        }
    }

</style>