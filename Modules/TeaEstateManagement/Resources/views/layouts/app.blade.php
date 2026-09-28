@extends('layouts.app')
@section('title',$title??'Tea Estate Management')
@section('content')
@include('teaestate::partials.styles')
<section class="content tea-ui"><div class="tea-shell">
 <div class="tea-hero"><div><div class="tea-eyebrow">Tea Estate Management</div><h1>{{ $heading??($title??'Tea Estate Management') }}</h1><p>{{ $subheading??'Standalone plantation, buying, processing, inventory, selling and finance integration.' }}</p></div></div>
 @include('teaestate::partials.nav')
 @if(session('tea_success'))<div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ session('tea_success') }}</div>@endif
 @if(session('tea_error'))<div class="alert alert-danger">{{ session('tea_error') }}</div>@endif
 @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
 @if(isset($installed)&&!$installed) @include('teaestate::partials.install_notice') @endif
 @yield('tea_content')
</div></section>
@endsection
