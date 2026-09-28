{{--
    IS2107: Poultry sidebar, written in the CORE sidebar's markup.

    This partial previously used AdminLTE conventions - it opened its own
    <ul class="sidebar-menu" data-widget="tree"> and used "treeview" submenus.
    The core sidebar is Bootstrap collapse markup: nav-item / nav-link /
    data-toggle="collapse" / collapse-item, inside a single <ul> that core has
    already opened.

    So this partial opened a second menu inside the first, in a different
    framework's classes. That is why it rendered as an unstyled block over the
    page rather than as a menu.

    It now contributes <li> items only, exactly as the core blocks do, and
    guards every link with Route::has() so a missing Poultry route degrades
    instead of throwing and taking the whole sidebar down with it.
--}}

@php
    $__poultryActive = request()->segment(1) === 'poultry';
@endphp

<li class="nav-item {{ $__poultryActive ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse"
        data-target="#poultry-menu" aria-expanded="{{ $__poultryActive ? 'true' : 'false' }}"
        aria-controls="poultry-menu">
        <i class="fa fa-feather"></i>
        <span>@lang('poultry::lang.poultry')</span>
    </a>

    <div id="poultry-menu" class="collapse {{ $__poultryActive ? 'show' : '' }}"
        aria-labelledby="headingPages" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">

            <h6 class="collapse-header">@lang('poultry::lang.poultry'):</h6>

            @if (Route::has('poultry.dashboard'))
                <a class="collapse-item {{ request()->routeIs('poultry.dashboard') ? 'active' : '' }}"
                    href="{{ route('poultry.dashboard') }}">@lang('poultry::lang.dashboard')</a>
            @endif

            @if (Route::has('poultry.batch.index'))
                <a class="collapse-item {{ request()->routeIs('poultry.batch.*') ? 'active' : '' }}"
                    href="{{ route('poultry.batch.index') }}">@lang('poultry::lang.batches')</a>
            @endif

            <h6 class="collapse-header">@lang('poultry::lang.daily_operations'):</h6>

            @if (Route::has('poultry.daily.entry'))
                <a class="collapse-item {{ request()->routeIs('poultry.daily.*') ? 'active' : '' }}"
                    href="{{ route('poultry.daily.entry') }}">@lang('poultry::lang.daily_entry')</a>
            @endif

            @if (Route::has('poultry.egg.entry'))
                <a class="collapse-item {{ request()->routeIs('poultry.egg.*') ? 'active' : '' }}"
                    href="{{ route('poultry.egg.entry') }}">@lang('poultry::lang.egg_collection')</a>
            @endif

            @if (Route::has('poultry.feed.issue.form'))
                <a class="collapse-item {{ request()->routeIs('poultry.feed.*') ? 'active' : '' }}"
                    href="{{ route('poultry.feed.issue.form') }}">@lang('poultry::lang.feed_issue')</a>
            @endif

            @if (Route::has('poultry.health.index'))
                <a class="collapse-item {{ request()->routeIs('poultry.health.*') ? 'active' : '' }}"
                    href="{{ route('poultry.health.index') }}">@lang('poultry::lang.health')</a>
            @endif

            @if (Route::has('poultry.harvest.index'))
                <a class="collapse-item {{ request()->routeIs('poultry.harvest.*') ? 'active' : '' }}"
                    href="{{ route('poultry.harvest.index') }}">@lang('poultry::lang.harvest')</a>
            @endif

            @if (Route::has('poultry.hatchery.index'))
                <a class="collapse-item {{ request()->routeIs('poultry.hatchery.*') ? 'active' : '' }}"
                    href="{{ route('poultry.hatchery.index') }}">@lang('poultry::lang.hatchery')</a>
            @endif

            <h6 class="collapse-header">@lang('poultry::lang.reports'):</h6>

            @if (Route::has('poultry.report.production'))
                <a class="collapse-item {{ request()->routeIs('poultry.report.production') ? 'active' : '' }}"
                    href="{{ route('poultry.report.production') }}">@lang('poultry::lang.production_report')</a>
            @endif

            @if (Route::has('poultry.report.performance'))
                <a class="collapse-item {{ request()->routeIs('poultry.report.performance') ? 'active' : '' }}"
                    href="{{ route('poultry.report.performance') }}">@lang('poultry::lang.performance_report')</a>
            @endif

            @if (Route::has('poultry.report.mortality'))
                <a class="collapse-item {{ request()->routeIs('poultry.report.mortality') ? 'active' : '' }}"
                    href="{{ route('poultry.report.mortality') }}">@lang('poultry::lang.mortality_report')</a>
            @endif

            @if (Route::has('poultry.report.costing'))
                <a class="collapse-item {{ request()->routeIs('poultry.report.costing') ? 'active' : '' }}"
                    href="{{ route('poultry.report.costing') }}">@lang('poultry::lang.costing_report')</a>
            @endif

            <h6 class="collapse-header">@lang('poultry::lang.masters'):</h6>

            @if (Route::has('poultry.farms.index'))
                <a class="collapse-item {{ request()->routeIs('poultry.farms.*') ? 'active' : '' }}"
                    href="{{ route('poultry.farms.index') }}">@lang('poultry::lang.farms')</a>
            @endif

            @if (Route::has('poultry.houses.index'))
                <a class="collapse-item {{ request()->routeIs('poultry.houses.*') ? 'active' : '' }}"
                    href="{{ route('poultry.houses.index') }}">@lang('poultry::lang.houses')</a>
            @endif

            @if (Route::has('poultry.breeds.index'))
                <a class="collapse-item {{ request()->routeIs('poultry.breeds.*') ? 'active' : '' }}"
                    href="{{ route('poultry.breeds.index') }}">@lang('poultry::lang.breeds')</a>
            @endif

            @if (Route::has('poultry.settings'))
                <a class="collapse-item {{ request()->routeIs('poultry.settings') ? 'active' : '' }}"
                    href="{{ route('poultry.settings') }}">@lang('poultry::lang.settings')</a>
            @endif

        </div>
    </div>
</li>
