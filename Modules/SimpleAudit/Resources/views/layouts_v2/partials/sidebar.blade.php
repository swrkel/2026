{{-- Simple Audit module-owned sidebar fallback. Central Super Admin only. --}}
@if (auth()->check()
    && \Illuminate\Support\Facades\Route::has('simpleaudit.purchase-audit')
    && app(\Modules\SimpleAudit\Services\AccessService::class)->isCentralAccessAllowed())
    @php
        $__simpleAuditActive = request()->is(trim((string) config('simpleaudit.route_prefix', 'superadmin/simple-audit'), '/'))
            || request()->is(trim((string) config('simpleaudit.route_prefix', 'superadmin/simple-audit'), '/') . '/*');
        $__simpleAuditPurchaseActive = request()->routeIs('simpleaudit.home')
            || request()->routeIs('simpleaudit.purchase-audit')
            || request()->routeIs('simpleaudit.purchase-audit.*');
    @endphp
    <li class="nav-item {{ $__simpleAuditActive ? 'active active-sub' : '' }}" id="simple-audit-sidebar-menu">
        <a class="nav-link" href="#" data-toggle="collapse" data-target="#simple-audit-menu"
            aria-expanded="true" aria-controls="simple-audit-menu">
            <i class="fa fa-search-plus"></i>
            <span>@lang('simpleaudit::simpleaudit.module_name')</span>
        </a>
        <div id="simple-audit-menu" class="collapse show"
            aria-labelledby="simple-audit-sidebar-menu" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">@lang('simpleaudit::simpleaudit.module_name'):</h6>
                <a class="collapse-item {{ $__simpleAuditPurchaseActive ? 'active active-sub' : '' }}"
                    href="{{ route('simpleaudit.purchase-audit') }}">
                    @lang('simpleaudit::simpleaudit.purchase_audit')
                </a>
            </div>
        </div>
    </li>
@endif
