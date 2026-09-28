@extends('layouts.app')
@section('title', __('petrogeneral::lang.pumper_management'))

@section('content')
<section class="content-header"><h1>@lang('petrogeneral::lang.pumper_management')</h1></section>
<section class="content no-print pg-page">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="{{ $active_tab == 'pumpers' ? 'active' : '' }}"><a href="#pg_pumpers" data-toggle="tab">Pumpers</a></li>
            <li class="{{ $active_tab == 'assignments' ? 'active' : '' }}"><a href="#pg_assignments" data-toggle="tab">Assignments</a></li>
            <li class="{{ $active_tab == 'shifts' ? 'active' : '' }}"><a href="#pg_shifts" data-toggle="tab">Shifts</a></li>
            <li class="{{ $active_tab == 'settings' ? 'active' : '' }}"><a href="#pg_pumper_settings" data-toggle="tab">Settings</a></li>
        </ul>
        <div class="tab-content">
            @include('petrogeneral::pumper_management.tabs.pumpers')
            @include('petrogeneral::pumper_management.tabs.assignments')
            @include('petrogeneral::pumper_management.tabs.shifts')
            @include('petrogeneral::pumper_management.tabs.settings')
        </div>
    </div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/pumper_management/pumpers.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/pumper_management/assignments.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/pumper_management/shifts.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/pumper_management/settings.js') }}"></script>
@endsection
