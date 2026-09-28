@extends('layouts.app')
@section('title', __('petrogeneral::lang.tank_management'))

@section('content')
<section class="content-header">
    <h1>@lang('petrogeneral::lang.tank_management')</h1>
</section>

<section class="content no-print pg-page">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="{{ $active_tab == 'tanks' ? 'active' : '' }}"><a href="#pg_tanks" data-toggle="tab">@lang('petrogeneral::lang.tanks')</a></li>
            <li class="{{ $active_tab == 'transfers' ? 'active' : '' }}"><a href="#pg_transfers" data-toggle="tab">@lang('petrogeneral::lang.tank_transfers')</a></li>
            <li class="{{ $active_tab == 'transactions' ? 'active' : '' }}"><a href="#pg_transactions" data-toggle="tab">@lang('petrogeneral::lang.tank_transactions')</a></li>
            <li class="{{ $active_tab == 'settings' ? 'active' : '' }}"><a href="#pg_tank_settings" data-toggle="tab">@lang('petrogeneral::lang.settings')</a></li>
        </ul>
        <div class="tab-content">
            @include('petrogeneral::tank_management.tabs.tanks')
            @include('petrogeneral::tank_management.tabs.transfers')
            @include('petrogeneral::tank_management.tabs.transactions')
            @include('petrogeneral::tank_management.tabs.settings')
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/tank_management/tanks.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/tank_management/transfers.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/tank_management/transactions.js') }}"></script>
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/tank_management/settings.js') }}"></script>
@endsection
