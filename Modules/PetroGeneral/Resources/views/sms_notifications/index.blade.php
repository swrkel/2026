@extends('layouts.app')
@section('title', __('petrogeneral::lang.petro_sms_notifications'))
@section('content')
<section class="content-header"><h1>@lang('petrogeneral::lang.petro_sms_notifications')</h1></section>
<section class="content main-content-inner">
    @include('petrogeneral::sms_notifications.tabs.navigation')
    <div class="tab-content pg-sms-tab-content">
        @include('petrogeneral::sms_notifications.tabs.sms_templates')
        @include('petrogeneral::sms_notifications.tabs.whatsapp_templates')
    </div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('Modules/PetroGeneral/Resources/assets/js/sms_notifications/templates.js') }}"></script>
@endsection
