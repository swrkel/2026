@php
    $__pdnewModuleNavigation = [];
    try {
        $__pdnewModuleUser = auth()->user();
        $__pdnewModuleBusinessId = (int) (session('business.id') ?: session('user.business_id') ?: optional($__pdnewModuleUser)->business_id);
        $__pdnewModuleNavigation = app(\Modules\PetroPDNew\Services\PdnewBusinessFeatureService::class)
            ->navigationItems($__pdnewModuleBusinessId, $__pdnewModuleUser);
    } catch (\Throwable $__pdnewModuleNavigationException) {
        $__pdnewModuleNavigation = [];
    }
@endphp
<aside class="pdn-sidebar no-print" data-pdn-sidebar>
    <div class="pdn-brand">
        <div class="pdn-brand-mark">PDN</div>
        <div><strong>Petro PD-New</strong><small>PONE settlement control</small></div>
    </div>
    <nav class="pdn-module-nav" aria-label="Petro PD-New pages">
        @foreach($__pdnewModuleNavigation as $__pdnewModuleItem)
            @php($__pdnewModuleItemActive = request()->routeIs(...$__pdnewModuleItem['active_routes']))
            <a href="{{ route($__pdnewModuleItem['route']) }}" class="{{ $__pdnewModuleItemActive ? 'active' : '' }}">
                <span class="pdn-nav-icon" aria-hidden="true">{{ strtoupper(substr($__pdnewModuleItem['label'], 0, 1)) }}</span>
                <span class="pdn-nav-label">{{ $__pdnewModuleItem['label'] }}</span>
                <span class="pdn-nav-arrow" aria-hidden="true">›</span>
            </a>
        @endforeach
    </nav>
</aside>
<div class="pdn-sidebar-backdrop" data-pdn-sidebar-backdrop></div>
