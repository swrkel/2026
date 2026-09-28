@php
    /*
     * Management Report owns only this compact menu definition.
     *
     * The application AutomaticModuleRegistry appends it to the latest global
     * sidebar and Super Admin > Manage remains the single source of truth for
     * business/module/page visibility. Never copy the global sidebar into a
     * module: doing so recursively invokes the global module loader again.
     */
    $__mgmtReportItems = [];
    $__mgmtReportActive = request()->is('management-report')
        || request()->is('management-report/*');

    try {
        $__mgmtReportUser = auth()->user();
        $__mgmtReportDefinitions = [
            [
                'route' => 'managementreport.dashboard',
                'label' => 'Dashboard',
                'icon' => 'fa fa-dashboard',
                'user_permission' => 'management_report.view',
                'business_permission' => 'management_report_dashboard',
                'active' => request()->routeIs('managementreport.dashboard'),
            ],
            [
                'route' => 'managementreport.daily.index',
                'label' => 'Daily Management Report',
                'icon' => 'fa fa-file-text-o',
                'user_permission' => 'management_report.generate',
                'business_permission' => 'management_report_daily',
                'active' => request()->routeIs('managementreport.daily.*'),
            ],
            [
                'route' => 'managementreport.saved.index',
                'label' => 'Saved Reports',
                'icon' => 'fa fa-archive',
                'user_permission' => 'management_report.view_saved',
                'business_permission' => 'management_report_saved_reports',
                'active' => request()->routeIs('managementreport.saved.*')
                    || request()->routeIs('managementreport.reviews.*'),
            ],
            [
                'route' => 'managementreport.shares.index',
                'label' => 'Delivery History',
                'icon' => 'fa fa-paper-plane',
                'user_permission' => 'management_report.view_delivery_history',
                'business_permission' => 'management_report_delivery_history',
                'active' => request()->routeIs('managementreport.shares.*'),
            ],
            [
                'route' => 'managementreport.settings.index',
                'label' => 'Settings',
                'icon' => 'fa fa-cogs',
                'user_permission' => 'management_report.settings',
                'business_permission' => 'management_report_settings',
                'active' => request()->routeIs('managementreport.settings.*'),
            ],
        ];

        $__mgmtKnownRolePermissions = [];
        if ($__mgmtReportUser && !$__mgmtReportUser->can('superadmin')) {
            try {
                $__mgmtKnownRolePermissions = \Illuminate\Support\Facades\DB::table('permissions')
                    ->whereIn('name', array_column($__mgmtReportDefinitions, 'user_permission'))
                    ->pluck('name')
                    ->all();
            } catch (\Throwable $__mgmtPermissionLookupException) {
                $__mgmtKnownRolePermissions = [];
            }
        }

        foreach ($__mgmtReportDefinitions as $__mgmtReportDefinition) {
            if (!\Illuminate\Support\Facades\Route::has($__mgmtReportDefinition['route'])) {
                continue;
            }

            if (!\App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled(
                $__mgmtReportDefinition['business_permission']
            )) {
                continue;
            }

            /*
             * Existing tenants may not have the module's optional role-level
             * permission rows yet. The module middleware intentionally allows
             * that compatibility state, so the sidebar does the same. Once a
             * permission exists, the user's assigned role is authoritative.
             */
            $__mgmtReportUserAllowed = $__mgmtReportUser && (
                $__mgmtReportUser->can('superadmin')
                || !in_array(
                    $__mgmtReportDefinition['user_permission'],
                    $__mgmtKnownRolePermissions,
                    true
                )
                || $__mgmtReportUser->can($__mgmtReportDefinition['user_permission'])
            );

            if ($__mgmtReportUserAllowed) {
                $__mgmtReportItems[] = $__mgmtReportDefinition;
            }
        }
    } catch (\Throwable $__mgmtReportSidebarException) {
        $__mgmtReportItems = [];
    }
@endphp

@if($__mgmtReportItems !== [])
    <li class="nav-item {{ $__mgmtReportActive ? 'active active-sub' : '' }}"
        data-sidebar-module="management_report">
        <a class="nav-link collapsed"
           href="#"
           data-toggle="collapse"
           data-target="#management-report-sidebar-menu"
           aria-expanded="{{ $__mgmtReportActive ? 'true' : 'false' }}"
           aria-controls="management-report-sidebar-menu">
            <i class="fa fa-line-chart"></i>
            <span>Management Report</span>
        </a>
        <div id="management-report-sidebar-menu"
             class="collapse {{ $__mgmtReportActive ? 'show' : '' }}"
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Management Report:</h6>
                @foreach($__mgmtReportItems as $__mgmtReportItem)
                    <a class="collapse-item {{ !empty($__mgmtReportItem['active']) ? 'active' : '' }}"
                       href="{{ route($__mgmtReportItem['route']) }}">
                        <i class="{{ $__mgmtReportItem['icon'] }} mr-1"></i>
                        {{ $__mgmtReportItem['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </li>
@endif
