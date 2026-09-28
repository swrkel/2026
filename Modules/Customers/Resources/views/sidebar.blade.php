{{--
    CUS_SEP_007 - Customers sidebar section

    Customers owns its dashboard/menu/navigation here. This sidebar does not call
    Contact module views/controllers. Direct URL access is still protected by
    customers.access middleware.
--}}

@php
    /** @var \Modules\Customers\Services\CustomerPermissionService $customersPermission */
    $customersPermission = app(\Modules\Customers\Services\CustomerPermissionService::class);
    $customersMenuService = app(\Modules\Customers\Services\CustomerMenuService::class);

    $customersModuleEnabled = $customersPermission->moduleEnabled();
    $customerMenu = $customersModuleEnabled ? $customersMenuService->moduleMenu() : [];
    $showCustomersSidebar = $customersModuleEnabled && count($customerMenu) > 0;
@endphp

@if($showCustomersSidebar)
    <li class="treeview {{ request()->is('customers*') ? 'active menu-open' : '' }}">
        <a href="#">
            <i class="fa fa-users"></i>
            <span>@lang('customers::lang.customer_register')</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
            </span>
        </a>

        <ul class="treeview-menu">
            @foreach($customerMenu as $item)
                <li class="{{ !empty($item['active']) ? 'active' : '' }}">
                    <a href="{{ $item['url'] }}">
                        <i class="{{ $item['icon'] ?: 'fa fa-circle-o' }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </li>
@endif
