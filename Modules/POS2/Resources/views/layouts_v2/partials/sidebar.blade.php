<li class="nav-item">
    {{-- Parent link --}}
    <a class="nav-link collapsed
        {{ request()->routeIs('pos2.*') ? 'active active-sub' : '' }}" href="#"
        data-toggle="collapse" data-target="#pos2-menu"
        aria-expanded="{{ request()->routeIs('pos2.*') ? 'true' : 'false' }}" aria-controls="pos2-menu">
        <i class="fa fa-tint fa-lg"></i>
        <span>@lang('pos2::lang.pos2')</span>
    </a>

    <div id="pos2-menu" class="collapse {{ request()->routeIs('pos2.*') ? 'show' : '' }}"
        aria-labelledby="headingPages" data-parent="#accordionSidebar">

        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">
                @lang('pos2::lang.pos2'):
            </h6>

            {{-- Submenu items --}}
            <a class="collapse-item {{ request()->routeIs('pos2.pos.create') ? 'active' : '' }}"
                href="{{ route('pos2.pos.create') }}">
                @lang('pos2::lang.pos2')
            </a>

            <a class="collapse-item {{ request()->routeIs('pos2.pos.settings') || request()->routeIs('pos2.pos.settings.update') ? 'active' : '' }}"
                href="{{ route('pos2.pos.settings') }}">
                @lang('pos2::lang.pos2_settings')
            </a>
        </div>
    </div>
</li>
