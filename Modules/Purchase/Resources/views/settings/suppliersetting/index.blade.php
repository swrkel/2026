@extends('layouts.app')
@section('title', 'Purchase Supplier Settings')
@section('content')
<section class="content-header"><h1>Purchase Supplier Settings</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Supplier Configuration</h3></div><div class="box-body">
        @include('purchase::settings.partials.key-values', ['data' => $settings ?? []])
    </div></div>
    <div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Suppliers for this Business</h3></div><div class="box-body">
        @include('purchase::settings.partials.data-table', ['rows' => $suppliers ?? []])
    </div></div>
</section>
@endsection
