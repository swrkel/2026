@php
    /*
     * IS1989: only enabled tabs may render.
     *
     * The legacy screens already do this - pumps/index.blade.php gates all five
     * of its tabs, and dip_management/index.blade.php gates two of its own. The
     * v2 screens were rebuilt without carrying that gating across, so every tab
     * showed regardless of what was ticked on Superadmin / Manage.
     *
     * Keys are NOT guessed. Each one below is the key its legacy counterpart
     * already uses for the same tab, so the two screens now agree.
     *
     * PERMISSIVE ON ABSENCE, matching layouts_v2/partials/sidebar.blade.php:
     * a key missing from package_details counts as ENABLED. Businesses
     * subscribed before a toggle existed have no entry for it, and treating
     * "missing" as off would silently remove tabs that have always worked.
     * Only an explicit falsy value hides a tab.
     */
    $is1989Package = [];
    try {
        $is1989BusinessId = request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id');
        $is1989Subscription = \Modules\Superadmin\Entities\Subscription::current_subscription($is1989BusinessId);
        $is1989Package = ! empty($is1989Subscription) ? (array) $is1989Subscription->package_details : [];
    } catch (\Throwable $e) {
        $is1989Package = [];
    }

    $is1989Enabled = static function (?string $key) use ($is1989Package): bool {
        if ($key === null || $key === '') {
            return true;
        }

        return ! array_key_exists($key, $is1989Package) || ! empty($is1989Package[$key]);
    };
@endphp

@extends('layouts.app')
@section('title', __('petrogeneral::lang.pump_management'))

@section('content')
<section class="content-header">
    <h1>@lang('petrogeneral::lang.pump_management')</h1>
</section>

<section class="content no-print pg-page">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            @if($is1989Enabled('pump_management'))
            <li class="{{ $active_tab == 'pumps' ? 'active' : '' }}"><a href="#pg_pumps" data-toggle="tab">@lang('petrogeneral::lang.pumps')</a></li>
            @endif
            @if($is1989Enabled('meter_reading'))
            <li class="{{ $active_tab == 'meters' ? 'active' : '' }}"><a href="#pg_meters" data-toggle="tab">@lang('petrogeneral::lang.meter_readings')</a></li>
            @endif
            @if($is1989Enabled('pump_management_testing'))
            <li class="{{ $active_tab == 'testing' ? 'active' : '' }}"><a href="#pg_testing" data-toggle="tab">@lang('petrogeneral::lang.testing_details')</a></li>
            @endif
            <li class="{{ $active_tab == 'settings' ? 'active' : '' }}"><a href="#pg_pump_settings" data-toggle="tab">@lang('petrogeneral::lang.settings')</a></li>
        </ul>
        <div class="tab-content">
            @if($is1989Enabled('pump_management'))
            @include('petrogeneral::pump_management.tabs.pumps')
            @endif
            @if($is1989Enabled('meter_reading'))
            @include('petrogeneral::pump_management.tabs.meter_readings')
            @endif
            @if($is1989Enabled('pump_management_testing'))
            @include('petrogeneral::pump_management.tabs.testing_details')
            @endif
            @include('petrogeneral::pump_management.tabs.settings')
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/pump_management/pumps.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/pump_management/meter_readings.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/pump_management/testing_details.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/pump_management/settings.js') }}"></script>
@endsection
