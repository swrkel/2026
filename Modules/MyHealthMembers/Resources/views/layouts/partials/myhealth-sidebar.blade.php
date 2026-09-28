@php
    $myHealthMenu = config('myhealthmembers_menu', []);
    $myHealthItems = $myHealthMenu['items'] ?? [];
    $myHealthActive = request()->is('myhealth*') || request()->routeIs('myhealth.*');
    $myHealthBusinessId = session('user.business_id') ?? session('business.id') ?? optional(auth()->user())->business_id;
    $myHealthSubscription = $myHealthBusinessId ? \Modules\Superadmin\Entities\Subscription::current_subscription($myHealthBusinessId) : null;
    if (empty($myHealthSubscription) && $myHealthBusinessId) {
        $myHealthSubscription = \Modules\Superadmin\Entities\Subscription::where('business_id', $myHealthBusinessId)->orderByDesc('id')->first();
    }
    $myHealthPermissionDetails = !empty($myHealthSubscription) ? $myHealthSubscription->package_details : [];
    if (is_string($myHealthPermissionDetails)) {
        $myHealthPermissionDetails = json_decode($myHealthPermissionDetails, true) ?: [];
    }
    if (is_object($myHealthPermissionDetails)) {
        $myHealthPermissionDetails = json_decode(json_encode($myHealthPermissionDetails), true) ?: [];
    }
    $canSeeMyHealth = auth()->check() && (!empty($myHealthPermissionDetails['my_health_module']) || !empty($myHealthPermissionDetails['myhealth_module']) || !empty($myHealthPermissionDetails['myhealthmembers_module']));
@endphp

@if($canSeeMyHealth)
    <li class="treeview {{ $myHealthActive ? 'active menu-open' : '' }}" id="myhealthmembers-sidebar-menu">
        <a href="{{ \Illuminate\Support\Facades\Route::has($myHealthMenu['route'] ?? '') ? route($myHealthMenu['route']) : url('/myhealth/members') }}">
            <i class="{{ $myHealthMenu['icon'] ?? 'fa fa-heartbeat' }}"></i>
            <span>{{ $myHealthMenu['module_name'] ?? __('myhealthmembers::lang.my_health_members') }}</span>
            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
        </a>
        <ul class="treeview-menu" style="{{ $myHealthActive ? 'display:block;' : '' }}">
            @foreach($myHealthItems as $item)
                @php($routeName = $item['route'] ?? null)
                @if($routeName && \Illuminate\Support\Facades\Route::has($routeName))
                    <li class="{{ request()->routeIs($routeName) ? 'active' : '' }}">
                        <a href="{{ route($routeName) }}">
                            <i class="{{ $item['icon'] ?? 'fa fa-circle-o' }}"></i>
                            <span>{{ $item['label'] ?? $routeName }}</span>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </li>
@endif
