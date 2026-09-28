@php
    $disnewMenu = config('distributionnew_menu', include module_path('DistributionNew', 'Config/menu.php'));
    $canShowDisnew = auth()->check();
    $isActiveDisnew = request()->is('distribution-new*');
@endphp

@if($canShowDisnew)
<li class="treeview {{ $isActiveDisnew ? 'active' : '' }}">
    <a href="#">
        <i class="{{ $disnewMenu['icon'] ?? 'fa fa-truck' }}"></i>
        <span>{{ __('distributionnew::lang.distribution_new') }}</span>
        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
    </a>
    <ul class="treeview-menu">
        @foreach($disnewMenu['items'] as $item)
            @php $showItem = true; @endphp
            @if($showItem)
                <li class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">
                    <a href="{{ route($item['route']) }}">
                        <i class="{{ $item['icon'] ?? 'fa fa-circle-o' }}"></i> {{ $item['title'] }}
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</li>
@endif
