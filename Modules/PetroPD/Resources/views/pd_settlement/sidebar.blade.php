<li class="nav-item">
    {{-- Parent link --}}
    <a class="nav-link collapsed
        {{ request()->routeIs('petropd.*') ? 'active active-sub' : '' }}"
        href="#"
        data-toggle="collapse"
        data-target="#petro-pd-menu"
        aria-expanded="{{ request()->routeIs('petropd.*') ? 'true' : 'false' }}"
        aria-controls="petro-pd-menu">

        <i class="fa fa-tint fa-lg"></i>
        <span>@lang('petropd::lang.petro_pd')</span>
    </a>

    <div id="petro-pd-menu"
        class="collapse {{ request()->routeIs('petropd.*') ? 'show' : '' }}"
        data-parent="#accordionSidebar">

        <div class="bg-white py-2 collapse-inner rounded">

            <h6 class="collapse-header">
                @lang('petropd::lang.petro_pd'):
            </h6>

            {{-- PD Settlement --}}
            <a class="collapse-item {{ request()->routeIs('petropd.pd-settlement') ? 'active' : '' }}"
                href="{{ route('petropd.pd-settlement') }}">
                @lang('petropd::lang.pd_settlement')
            </a>

            {{-- PD Operators Parent --}}
            <a class="collapse-item collapsed
                {{ request()->routeIs('petropd.operators.*') ? 'active' : '' }}"
                href="#"
                data-toggle="collapse"
                data-target="#pd-operators-submenu">
                @lang('petropd::lang.pd_operators')
            </a>

        </div>
    </div>
</li>