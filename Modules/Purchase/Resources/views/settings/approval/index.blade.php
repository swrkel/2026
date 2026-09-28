@extends('layouts.app')
@section('title', 'Purchase Approval Settings')
@section('content')
<section class="content-header"><h1>Purchase Approval Settings</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Approval Configuration</h3></div><div class="box-body">
        @include('purchase::settings.partials.data-table', ['rows' => $approvals ?? []])
    </div></div>
    <div class="box box-default"><div class="box-header with-border"><h3 class="box-title">Related Purchase Settings</h3></div><div class="box-body">
        @include('purchase::settings.partials.key-values', ['data' => $settings ?? []])
    </div></div>
</section>
@endsection
