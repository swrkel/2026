{{-- Reports - Other: system-standard sidebar fragment.
     Parent visibility comes only from Manage Side Bar; child visibility comes
     from the user's page/role permission. No local always-visible bypass. --}}
@php
    $__reoBusinessId = (int) (session('user.business_id') ?? 0);
    $__reoParentEnabled = true;
    try {
        if ($__reoBusinessId > 0 && class_exists('App\\Utils\\SidebarPermissionUtil')) {
            $__reoParentEnabled = \App\Utils\SidebarPermissionUtil::isEnabled('reports_other', $__reoBusinessId);
        }
    } catch (\Throwable $e) {
        $__reoParentEnabled = false;
    }

    $__reoCanView = false;
    if (auth()->check()) {
        try {
            $__reoCanView = auth()->user()->can('superadmin')
                || auth()->user()->can('reports_other.view')
                || auth()->user()->can('reports_other.cash_receipt.view');
        } catch (\Throwable $e) {
            $__reoCanView = false;
        }
    }
@endphp

@if($__reoParentEnabled && $__reoCanView)
<li class="nav-item {{ request()->is('reports-other*') ? 'active active-sub' : '' }}" data-module-key="reports_other">
    <a class="nav-link {{ request()->is('reports-other*') ? '' : 'collapsed' }}"
       href="#"
       data-toggle="collapse"
       data-target="#reports-other-menu"
       aria-expanded="{{ request()->is('reports-other*') ? 'true' : 'false' }}"
       aria-controls="reports-other-menu">
        <i class="fa fa-file-text-o"></i>
        <span>Reports - Other</span>
    </a>
    <div id="reports-other-menu" class="collapse {{ request()->is('reports-other*') ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Reports - Other:</h6>
            @if(auth()->user()->can('superadmin') || auth()->user()->can('reports_other.cash_receipt.view'))
                <a class="collapse-item {{ request()->is('reports-other/cash-receipt*') ? 'active' : '' }}"
                   href="{{ url('/reports-other/cash-receipt') }}">Cash Receipt</a>
            @endif
        </div>
    </div>
</li>
@endif
