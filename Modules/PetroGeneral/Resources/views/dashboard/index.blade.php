@extends('layouts.app')
@section('title', __('petrogeneral::lang.petro_general_dashboard'))

@section('content')
<section class="content-header">
    <h1>@lang('petrogeneral::lang.petro_general_dashboard')</h1>
</section>

{{-- MA-002: pg-dash scopes all the dashboard styling so it cannot leak. --}}
<section class="content no-print pg-page pg-dash">
    @include('petrogeneral::dashboard.partials.summary_cards')
    @include('petrogeneral::dashboard.partials.tank_capacity')
    @include('petrogeneral::dashboard.partials.tank_overview')
    @include('petrogeneral::dashboard.partials.pump_overview')
</section>
@endsection

@section('javascript')
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/dashboard/dashboard.js') }}"></script>
@endsection
