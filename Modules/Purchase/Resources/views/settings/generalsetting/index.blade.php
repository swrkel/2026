@extends('layouts.app')
@section('title', 'Purchase General Settings')
@section('content')
<section class="content-header"><h1>Purchase General Settings</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Business Settings</h3></div><div class="box-body">
        @include('purchase::settings.partials.key-values', ['data' => $business ?? []])
    </div></div>
    <div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Purchase Settings</h3></div><div class="box-body">
        @include('purchase::settings.partials.key-values', ['data' => $settings ?? []])
    </div></div>
    <div class="box box-default"><div class="box-header with-border"><h3 class="box-title">Business Locations</h3></div><div class="box-body">
        @include('purchase::settings.partials.data-table', ['rows' => $locations ?? []])
    </div></div>
</section>
@endsection
