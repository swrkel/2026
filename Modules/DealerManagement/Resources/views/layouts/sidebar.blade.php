@php
    $dlrMenu = config('dealermanagement_menu', include module_path('DealerManagement', 'Config/menu.php'));
    $dlrBusinessId = (int) (session('business.id') ?? session('business_id') ?? session('user.business_id') ?? 0);
    $dlrCanShow = false;

    if (auth()->check() && $dlrBusinessId > 0) {
        try {
            $dlrCanShow = app(\Modules\DealerManagement\Services\DistributionAvailabilityService::class)
                ->enabledForBusiness($dlrBusinessId);
        } catch (\Throwable $e) {
            $dlrCanShow = false;
        }
    }

    $dlrActive = request()->is('dealer-management*');
@endphp

@if($dlrCanShow)
<li class="treeview {{ $dlrActive ? 'active' : '' }}">
    <a href="#">
        <i class="{{ $dlrMenu['icon'] ?? 'fa fa-handshake-o' }}"></i>
        <span>{{ $dlrMenu['module_name'] ?? 'Dealer Management' }}</span>
        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
    </a>
    <ul class="treeview-menu">
        @foreach(($dlrMenu['items'] ?? []) as $item)
            @if(\Illuminate\Support\Facades\Route::has($item['route']))
                <li class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">
                    <a href="{{ route($item['route']) }}" @if(in_array($item['route'], ['dealermanagement.login','dealermanagement.hub.login'], true)) target="_blank" @endif>
                        <i class="{{ $item['icon'] ?? 'fa fa-circle-o' }}"></i>
                        {{ $item['title'] }}
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</li>
@endif
