<div class="atn-tabs" role="navigation" aria-label="{{ __('airlineticketingnew::messages.module_name') }}">
    <a class="atn-tab {{ request()->routeIs('airline-ticketing-new.dashboard') ? 'active' : '' }}"
       href="{{ route('airline-ticketing-new.dashboard') }}">
        <i class="fa fa-dashboard"></i>
        {{ __('airlineticketingnew::messages.dashboard') }}
    </a>

    @can('airline_ticketing_new.settings.manage')
        <a class="atn-tab {{ request()->routeIs('airline-ticketing-new.settings.*') ? 'active' : '' }}"
           href="{{ route('airline-ticketing-new.settings.index') }}">
            <i class="fa fa-cogs"></i>
            {{ __('airlineticketingnew::messages.settings') }}
        </a>
    @endcan
</div>
