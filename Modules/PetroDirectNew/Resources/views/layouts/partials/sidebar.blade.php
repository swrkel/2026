@php
    $pdirectnewActive = request()->routeIs('petro-direct-new.*');
    $pdirectnewItems = [
        ['route'=>'petro-direct-new.dashboard','label'=>'Dashboard','icon'=>'fa-dashboard','match'=>'petro-direct-new.dashboard*'],
        ['route'=>'petro-direct-new.settlements.create','label'=>'Direct Settlement','icon'=>'fa-plus-circle','match'=>'petro-direct-new.settlements.create'],
        ['route'=>'petro-direct-new.settlements.index','label'=>'List Direct Settlements','icon'=>'fa-list-alt','match'=>'petro-direct-new.settlements.index'],
        ['route'=>'petro-direct-new.pumper-management.index','label'=>'Pumper Management','icon'=>'fa-users','match'=>'petro-direct-new.pumper-management.*'],
        ['route'=>'petro-direct-new.reports.index','label'=>'Reports','icon'=>'fa-bar-chart','match'=>'petro-direct-new.reports.*'],
    ];
@endphp
<li class="nav-item {{ $pdirectnewActive ? 'active active-sub' : '' }}" data-sidebar-module="petro_direct_new">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#petro-direct-new-menu" aria-expanded="{{ $pdirectnewActive ? 'true' : 'false' }}">
        <i class="fa fa-truck"></i><span>Petro Direct-New</span>
    </a>
    <div id="petro-direct-new-menu" class="collapse {{ $pdirectnewActive ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded"><h6 class="collapse-header">Petro Direct-New:</h6>
            @foreach($pdirectnewItems as $item)
                @if(Route::has($item['route']))
                    <a class="collapse-item {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}"><i class="fa {{ $item['icon'] }}"></i> {{ $item['label'] }}</a>
                @endif
            @endforeach
        </div>
    </div>
</li>
