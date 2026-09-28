@php
    $__pdnewSidebarItems = [];
    $__pdnewSidebarActive = request()->routeIs('petro-pd-new.*')
        || request()->is('petro-pd-new')
        || request()->is('petro-pd-new/*');

    try {
        $__pdnewSidebarUser = auth()->user();
        $__pdnewSidebarBusinessId = (int) (
            session('business.id')
            ?: session('user.business_id')
            ?: optional($__pdnewSidebarUser)->business_id
        );
        $__pdnewSidebarItems = app(\Modules\PetroPDNew\Services\PdnewBusinessFeatureService::class)
            ->navigationItems($__pdnewSidebarBusinessId, $__pdnewSidebarUser);
    } catch (\Throwable $__pdnewSidebarException) {
        $__pdnewSidebarItems = [];
    }
@endphp

@if($__pdnewSidebarItems !== [])
    <li class="nav-item {{ $__pdnewSidebarActive ? 'active active-sub' : '' }}"
        data-sidebar-module="petro_pd_new">
        <a class="nav-link collapsed"
           href="#"
           data-toggle="collapse"
           data-target="#petro-pd-new-sidebar-menu"
           aria-expanded="{{ $__pdnewSidebarActive ? 'true' : 'false' }}"
           aria-controls="petro-pd-new-sidebar-menu">
            <i class="fa fa-tint"></i>
            <span>Petro PD-New</span>
        </a>
        <div id="petro-pd-new-sidebar-menu"
             class="collapse {{ $__pdnewSidebarActive ? 'show' : '' }}"
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Petro PD-New:</h6>
                @foreach($__pdnewSidebarItems as $__pdnewSidebarItem)
                    @php
                        $__pdnewSidebarItemActive = request()->routeIs(...$__pdnewSidebarItem['active_routes']);
                    @endphp
                    <a class="collapse-item {{ $__pdnewSidebarItemActive ? 'active' : '' }}"
                       href="{{ route($__pdnewSidebarItem['route']) }}">
                        <i class="{{ $__pdnewSidebarItem['icon'] }} mr-1"></i>
                        {{ $__pdnewSidebarItem['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </li>
@endif
