{{--
    Customers standalone sidebar section.
    S398 correction: follow the Contact module menu pattern. Show only real
    Customers functional pages in the sidebar and keep internal tab/helper
    pages inside their own screens.
--}}
@php
    $customersPermission = app(\Modules\Customers\Services\CustomerPermissionService::class);
    $customersMenuService = app(\Modules\Customers\Services\CustomerMenuService::class);
    $showCustomersSidebar = false;
    foreach (['customers_module', 'customer_module', 'customers'] as $customersSidebarKey) {
        if (!empty(${$customersSidebarKey})) { $showCustomersSidebar = true; break; }
    }
    $showCustomersSidebar = $showCustomersSidebar || $customersPermission->moduleEnabled();
    $customerMenuItems = $showCustomersSidebar ? $customersMenuService->moduleMenu() : [];
@endphp

@if($showCustomersSidebar && count($customerMenuItems))
<li class="nav-item {{ request()->segment(1) == 'customers' ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#customers-standalone-menu" aria-expanded="{{ request()->segment(1) == 'customers' ? 'true' : 'false' }}" aria-controls="customers-standalone-menu">
        <i class="fa fa-users"></i><span>Customers</span>
    </a>
    <div id="customers-standalone-menu" class="collapse {{ request()->segment(1) == 'customers' ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Customers:</h6>
            @foreach($customerMenuItems as $item)
                <a class="collapse-item {{ !empty($item['active']) ? 'active' : '' }}" href="{{ $item['url'] }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</li>
@endif
