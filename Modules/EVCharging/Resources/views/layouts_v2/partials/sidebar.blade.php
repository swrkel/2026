@php
    $business_id = request()->session()->get('user.business_id');
    $subscription = Modules\Superadmin\Entities\Subscription::current_subscription($business_id);
    $show_ev_charging_sidebar = false;
    if (!empty($subscription)) {
        $pkg = $subscription->package_details;
        $show_ev_charging_sidebar = !empty($pkg['ev_charging_module']);
    }
    if (auth()->check() && auth()->user()->can('superadmin')) {
        $show_ev_charging_sidebar = !empty($pkg['ev_charging_module']);
    }
@endphp
@if ($show_ev_charging_sidebar)
<li class="nav-item">
    {{-- Parent link --}}
    <a class="nav-link collapsed
        {{ request()->routeIs('evcharging.*') ? 'active active-sub' : '' }}"
        href="#"
        data-toggle="collapse"
        data-target="#evcharging-menu"
        aria-expanded="{{ request()->routeIs('evcharging.*') ? 'true' : 'false' }}"
        aria-controls="evcharging-menu">

        <i class="fa fa-tint fa-lg"></i>
        <span>@lang('evcharging::lang.evcharging')</span>
    </a>

    <div id="evcharging-menu"
        class="collapse {{ request()->routeIs('evcharging.*') ? 'show' : '' }}"
        data-parent="#accordionSidebar">

        <div class="bg-white py-2 collapse-inner rounded">

            <h6 class="collapse-header">
                @lang('evcharging::lang.evcharging'):
            </h6>

            {{-- EV Charging Settlement --}}
            <a class="collapse-item {{ request()->routeIs('evcharging.ev-charging-settlement') ? 'active' : '' }}"
                href="{{ route('evcharging.ev-charging-settlement') }}">
                @lang('evcharging::lang.ev_charging_settlement')
            </a>

            {{-- EV Charging Operators Parent --}}
            <a class="collapse-item collapsed
                {{ request()->routeIs('evcharging.ev-charging-operators') ? 'active' : '' }}"
                href="{{ route('evcharging.ev-charging-operators') }}">
                @lang('evcharging::lang.ev_charging_operators')
            </a>

        </div>
    </div>
</li>
@endif