@extends('layouts.app')
@section('title', __('petrogeneral::lang.list_tank_transfer'))
@section('content')
<section class="content-header"><h1>@lang('petrogeneral::lang.list_tank_transfer')</h1></section>
<section class="content main-content-inner">
    @include('petrogeneral::tank_transfer.tabs.filters')
    @include('petrogeneral::tank_transfer.tabs.list')
</section>
@endsection
@section('javascript')
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/tank_transfer/list.js') }}"></script>
@endsection
