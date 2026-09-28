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
@section('title', __('petrogeneral::lang.dip_management'))
@section('content')
<section class="content-header"><h1>@lang('petrogeneral::lang.dip_management')</h1></section>
<section class="content no-print pg-page"><div class="nav-tabs-custom">
<ul class="nav nav-tabs">
<li class="{{ $active_tab == 'readings' ? 'active' : '' }}"><a href="#pg_dip_readings" data-toggle="tab">Readings</a></li>
@if($is1989Enabled('tank_dip_chart'))
<li class="{{ $active_tab == 'charts' ? 'active' : '' }}"><a href="#pg_dip_charts" data-toggle="tab">Dip Charts</a></li>
@endif
@if($is1989Enabled('dip_resetting'))
<li class="{{ $active_tab == 'resettings' ? 'active' : '' }}"><a href="#pg_dip_resettings" data-toggle="tab">Resettings</a></li>
@endif
<li class="{{ $active_tab == 'reports' ? 'active' : '' }}"><a href="#pg_dip_reports" data-toggle="tab">Reports</a></li>
</ul><div class="tab-content">
@include('petrogeneral::dip_management_pg.tabs.readings')
@if($is1989Enabled('tank_dip_chart'))
@include('petrogeneral::dip_management_pg.tabs.charts')
@endif
@if($is1989Enabled('dip_resetting'))
@include('petrogeneral::dip_management_pg.tabs.resettings')
@endif
@include('petrogeneral::dip_management_pg.tabs.reports')
</div></div>

{{-- The Add Dip Resetting modal is loaded into this container, the same way the
     legacy screen uses .dip_modal. Named differently so the two pages cannot
     target each other's container. --}}
<div class="modal fade pg_dip_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/dip_management/readings.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/dip_management/charts.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/dip_management/resettings.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/dip_management/reports.js') }}"></script>

{{-- Behaviour for the Add Dip Resetting form. --}}
@include('petrogeneral::dip_management_pg.partials.resetting_form_js')
@endsection
