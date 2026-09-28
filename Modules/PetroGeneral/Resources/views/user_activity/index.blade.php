@extends('layouts.app')
@section('title', __('petrogeneral::lang.user_activity'))
@section('content')
<section class="content-header"><h1>@lang('petrogeneral::lang.user_activity')</h1></section>
<section class="content main-content-inner">
    @include('petrogeneral::user_activity.tabs.filters')
    @include('petrogeneral::user_activity.tabs.list')
</section>
@endsection
@section('javascript')
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/user_activity/list.js') }}"></script>
@endsection
