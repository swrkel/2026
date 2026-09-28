@php
    /*
     * The global ERP sidebar passes the already-resolved visibility flag.
     * When this partial is rendered elsewhere, resolve the same two gates
     * independently: Manage Side Bar parent + exact current-role permission.
     */
    if (isset($can_view_stock_reports_sidebar)) {
        $stockReportsCanRender = (bool) $can_view_stock_reports_sidebar;
    } else {
        $stockReportsCanRender = false;
        $stockReportsBusinessId = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id'));
        $stockReportsParentEnabled = true;
        $stockReportsUserAllowed = false;

        try {
            if (class_exists('App\\Utils\\SidebarPermissionUtil')) {
                if (method_exists(\App\Utils\SidebarPermissionUtil::class, 'isManageSidebarEnabled')) {
                    $stockReportsParentEnabled = \App\Utils\SidebarPermissionUtil::isManageSidebarEnabled(
                        'stock_reports',
                        $stockReportsBusinessId
                    );
                } elseif (method_exists(\App\Utils\SidebarPermissionUtil::class, 'isEnabled')) {
                    $stockReportsParentEnabled = \App\Utils\SidebarPermissionUtil::isEnabled(
                        'stock_reports',
                        $stockReportsBusinessId
                    );
                }
            }
        } catch (\Throwable $e) {
            $stockReportsParentEnabled = true;
        }

        if (auth()->check()) {
            $stockReportsSidebarUser = auth()->user();
            try {
                $stockReportsUserAllowed = $stockReportsSidebarUser->can('superadmin')
                    || (!empty($stockReportsBusinessId) && $stockReportsSidebarUser->hasRole('Admin#' . $stockReportsBusinessId));
            } catch (\Throwable $e) {
                $stockReportsUserAllowed = false;
            }

            if (!$stockReportsUserAllowed) {
                foreach (['stock_report.view', 'stock_transaction_report', 'umn.module.stock_reports.view'] as $stockReportsPermission) {
                    try {
                        if (method_exists($stockReportsSidebarUser, 'roleAllowsPermission')) {
                            $stockReportsUserAllowed = $stockReportsSidebarUser->roleAllowsPermission(
                                $stockReportsPermission,
                                $stockReportsBusinessId
                            );
                        } else {
                            $stockReportsUserAllowed = $stockReportsSidebarUser->can($stockReportsPermission);
                        }
                    } catch (\Throwable $e) {
                        $stockReportsUserAllowed = false;
                    }

                    if ($stockReportsUserAllowed) {
                        break;
                    }
                }
            }
        }

        $stockReportsCanRender = $stockReportsParentEnabled && $stockReportsUserAllowed;
    }
@endphp

@if($stockReportsCanRender)
    <li class="nav-item {{ in_array(request()->segment(1), ['stockreports', 'stock-reports']) ? 'active active-sub' : '' }}"
        data-sidebar-module="stock_reports" data-module-key="stock_reports">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#stock-reports-menu"
            aria-expanded="true" aria-controls="stock-reports-menu">
            <i class="fa fa-file-text-o"></i>
            <span>@lang('stockreports::lang.stock_reports')</span>
        </a>
        <div id="stock-reports-menu" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">@lang('stockreports::lang.stock_reports'):</h6>
                <a class="collapse-item {{ request()->segment(1) == 'stockreports' && request()->segment(2) == '' ? 'active' : '' }}"
                   href="{{ action('\\Modules\\StockReports\\Http\\Controllers\\StockReportsController@index') }}">
                   @lang('stockreports::lang.stock_transaction_report')
                </a>
            </div>
        </div>
    </li>
@endif
