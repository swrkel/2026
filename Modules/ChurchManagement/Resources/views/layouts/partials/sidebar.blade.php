@inject('request', 'Illuminate\Http\Request')

@php
    /*
     * Church Management module-owned sidebar.
     *
     * The application's AutomaticModuleRegistry discovers this conventional
     * module sidebar view and renders it only when the Church Management parent
     * is enabled for the current business in Super Admin > Manage Side Bar.
     *
     * Child links continue to respect the same user permissions enforced by
     * the module routes, so adding the sidebar cannot grant access by itself.
     */
    $__churchManagementItems = [];
    $__churchManagementPrefix = trim((string) config('churchmanagement.route_prefix', 'churchmanagement'), '/');
    $__churchManagementActive = $request->segment(1) === $__churchManagementPrefix
        || $request->segment(1) === 'church-management';

    try {
        $__churchManagementUser = auth()->user();
        $__churchManagementMenu = (array) config('churchmanagement_menu.items', []);

        foreach ($__churchManagementMenu as $__churchManagementMenuItem) {
            $__churchManagementPermission = (string) ($__churchManagementMenuItem['key'] ?? '');
            $__churchManagementRoute = (string) ($__churchManagementMenuItem['route'] ?? '');

            if (!$__churchManagementUser
                || $__churchManagementPermission === ''
                || $__churchManagementRoute === ''
                || !\Illuminate\Support\Facades\Route::has($__churchManagementRoute)
                || !$__churchManagementUser->can($__churchManagementPermission)) {
                continue;
            }

            $__churchManagementItems[] = [
                'label' => (string) ($__churchManagementMenuItem['label'] ?? 'Church Management'),
                'icon' => (string) ($__churchManagementMenuItem['icon'] ?? 'fa fa-university'),
                'url' => route($__churchManagementRoute),
                'active' => request()->routeIs($__churchManagementRoute),
            ];
        }
    } catch (\Throwable $__churchManagementSidebarException) {
        // A sidebar failure must never break the ERP shell. The parent renderer
        // will simply omit this module until the next normal request/cache clear.
        $__churchManagementItems = [];
    }
@endphp

@if($__churchManagementItems !== [])
    <li class="nav-item {{ $__churchManagementActive ? 'active active-sub' : '' }}"
        data-sidebar-module="church_management">
        <a class="nav-link collapsed"
           href="#"
           data-toggle="collapse"
           data-target="#church-management-menu"
           aria-expanded="{{ $__churchManagementActive ? 'true' : 'false' }}"
           aria-controls="church-management-menu">
            <i class="fa fa-university"></i>
            <span>Church Management</span>
        </a>

        <div id="church-management-menu"
             class="collapse {{ $__churchManagementActive ? 'show' : '' }} church-management-submenu"
             data-parent="#accordionSidebar">
            <div class="py-2 collapse-inner rounded church-management-collapse-inner">
                <h6 class="collapse-header">Church Management:</h6>

                @foreach($__churchManagementItems as $__churchManagementItem)
                    <a class="collapse-item {{ !empty($__churchManagementItem['active']) ? 'active' : '' }}"
                       href="{{ $__churchManagementItem['url'] }}">
                        <i class="{{ $__churchManagementItem['icon'] }} mr-1"></i>
                        {{ $__churchManagementItem['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </li>

    <style>
        /* Church Management sidebar only - do not alter other ERP menus. */
        li[data-sidebar-module="church_management"] > .nav-link,
        li[data-sidebar-module="church_management"] > .nav-link > i,
        li[data-sidebar-module="church_management"] > .nav-link > span {
            color: #fff !important;
        }

        #church-management-menu .church-management-collapse-inner {
            background: transparent !important;
            display: block !important;
            width: 100% !important;
        }

        #church-management-menu .collapse-header {
            color: #fff !important;
            display: block !important;
            width: 100% !important;
        }

        #church-management-menu .collapse-item {
            color: #fff !important;
            display: block !important;
            float: none !important;
            clear: both !important;
            width: calc(100% - 1rem) !important;
            max-width: calc(100% - 1rem) !important;
            margin: 0 .5rem .15rem .5rem !important;
            white-space: normal !important;
        }

        #church-management-menu .collapse-item i {
            color: #fff !important;
        }

        #church-management-menu .collapse-item:hover,
        #church-management-menu .collapse-item:focus {
            color: #fff !important;
            background: rgba(255,255,255,.12) !important;
        }

        #church-management-menu .collapse-item.active {
            color: #fff !important;
            font-weight: 700;
            background: rgba(255,255,255,.18) !important;
        }
    </style>
@endif
