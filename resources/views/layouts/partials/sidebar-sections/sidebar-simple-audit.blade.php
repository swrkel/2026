{{--
    Simple Audit - Central Super Admin only.

    Dedicated host sidebar section. This file may be included from more than one
    host sidebar, but it renders only for the genuine Central Super Admin.
    The Purchase Audit child page is shown under the module name by default so
    the Central sidebar always exposes the actual page, not only the parent name.
--}}
@php
    $__showSimpleAuditCentralMenu = false;

    try {
        if (class_exists('\\App\\Utils\\SidebarPermissionUtil')) {
            $__showSimpleAuditCentralMenu = \App\Utils\SidebarPermissionUtil::isGenuineSuperAdmin();
        } else {
            $__showSimpleAuditCentralMenu = auth()->check()
                && auth()->user()->can('superadmin')
                && request()->segment(1) === 'superadmin';
        }
    } catch (\Throwable $__simpleAuditSidebarGateException) {
        $__showSimpleAuditCentralMenu = false;
    }

    $simpleAuditSidebarActive = request()->is('superadmin/simple-audit')
        || request()->is('superadmin/simple-audit/*');

    $simpleAuditPurchaseActive = request()->routeIs('simpleaudit.home')
        || request()->routeIs('simpleaudit.purchase-audit')
        || request()->routeIs('simpleaudit.purchase-audit.*');

    $simpleAuditPurchaseUrl = \Illuminate\Support\Facades\Route::has('simpleaudit.purchase-audit')
        ? route('simpleaudit.purchase-audit')
        : url('/superadmin/simple-audit/purchase-audit');
@endphp

@if($__showSimpleAuditCentralMenu)
    <li class="nav-item {{ $simpleAuditSidebarActive ? 'active active-sub' : '' }}"
        id="central-simple-audit-sidebar-menu"
        data-sidebar-module="Simple Audit"
        data-module-key="simple_audit"
        data-erp-sidebar-always-visible="1">
        <a class="nav-link"
           href="#"
           data-toggle="collapse"
           data-target="#central-simple-audit-pages"
           aria-expanded="true"
           aria-controls="central-simple-audit-pages">
            <i class="fa fa-search-plus"></i>
            <span>Simple Audit</span>
        </a>

        <div id="central-simple-audit-pages"
             class="collapse show"
             aria-labelledby="central-simple-audit-sidebar-menu"
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Simple Audit:</h6>
                <a class="collapse-item {{ $simpleAuditPurchaseActive ? 'active active-sub' : '' }}"
                   href="{{ $simpleAuditPurchaseUrl }}">
                    Purchase Audit
                </a>
            </div>
        </div>
    </li>
@endif
