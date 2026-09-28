@php
    $eggSidebarItems = require module_path('EggManagement', 'Navigation/menu.php');
    $eggCurrentRoute = optional(request()->route())->getName();
    $eggModuleActive = is_string($eggCurrentRoute) && str_starts_with($eggCurrentRoute, 'egg.');

    $eggCan = function ($permission) {
        try {
            return !auth()->check() || auth()->user()->can($permission) || auth()->user()->can('egg.*');
        } catch (\Throwable $e) {
            return true;
        }
    };

    $eggCanItem = function ($item) use ($eggCan) {
        $permissions = isset($item['permissions']) && is_array($item['permissions'])
            ? $item['permissions']
            : [];

        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                if (is_string($permission) && $permission !== '' && $eggCan($permission)) {
                    return true;
                }
            }
            return false;
        }

        $permission = isset($item['permission']) && is_string($item['permission']) && $item['permission'] !== ''
            ? $item['permission']
            : 'egg.dashboard.view';

        return $eggCan($permission);
    };
@endphp

<li class="nav-item {{ $eggModuleActive ? 'active active-sub' : '' }}">
    <a class="nav-link {{ $eggModuleActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse"
       data-target="#egg-management-sidebar-menu" aria-expanded="{{ $eggModuleActive ? 'true' : 'false' }}"
       aria-controls="egg-management-sidebar-menu">
        <i class="fa fa-cubes"></i>
        <span>Egg Management</span>
    </a>
    <div id="egg-management-sidebar-menu" class="collapse {{ $eggModuleActive ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Egg Management:</h6>
            @foreach($eggSidebarItems as $eggItem)
                @php
                    $eggItemActive = request()->routeIs($eggItem['route'])
                        || ($eggItem['route'] === 'egg.reports.index' && request()->routeIs('egg.reports.*'))
                        || ($eggItem['route'] === 'egg.settings.index' && (request()->routeIs('egg.settings.*') || request()->routeIs('egg.grades.*') || request()->routeIs('egg.product-mappings.*') || request()->routeIs('egg.integrations.*') || request()->routeIs('egg.flocks.*')));
                @endphp
                @if($eggCanItem($eggItem) && \Illuminate\Support\Facades\Route::has($eggItem['route']))
                    <a class="collapse-item {{ $eggItemActive ? 'active' : '' }}"
                       href="{{ route($eggItem['route']) }}">
                        <i class="{{ $eggItem['icon'] ?? 'fa fa-circle-o' }}" style="width:18px"></i>
                        {{ $eggItem['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</li>
