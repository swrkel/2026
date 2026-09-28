{{--
    Customers Module Standalone Sidebar Partial - CUS319
    Uses CustomerMenuService so Super Admin Manage page switches are reflected
    under Login -> System -> Customers Module.
--}}
@php
    $customersPermission = app(\Modules\Customers\Services\CustomerPermissionService::class);
    $customersMenuService = app(\Modules\Customers\Services\CustomerMenuService::class);
    $customersModuleEnabled = !empty($customers_module) || $customersPermission->moduleEnabled();
    $customerMenuItems = $customersModuleEnabled ? $customersMenuService->moduleMenu() : [];
@endphp

@if($customersModuleEnabled && count($customerMenuItems))
<li class="nav-item {{ request()->segment(1) == 'customers' ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#customers-standalone-menu" aria-expanded="{{ request()->segment(1) == 'customers' ? 'true' : 'false' }}" aria-controls="customers-standalone-menu">
        <i class="fa fa-users"></i>
        <span>@lang('customers::lang.customers_module')</span>
    </a>
    <div id="customers-standalone-menu" class="collapse {{ request()->segment(1) == 'customers' ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">@lang('customers::lang.customers_module'):</h6>
            @foreach($customerMenuItems as $item)
                <a class="collapse-item {{ !empty($item['active']) ? 'active' : '' }}" href="{{ $item['url'] }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</li>
@endif
