@extends('layouts.app')
@section('title', 'Purchase Numbering Settings')
@section('content')
<section class="content-header"><h1>Purchase Numbering Settings</h1></section>
<section class="content">
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Configured Purchase Numbering</h3></div><div class="box-body">
        @include('purchase::settings.partials.key-values', ['data' => $numbering ?? []])
    </div></div>
    <div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Invoice Schemes</h3></div><div class="box-body">
        @include('purchase::settings.partials.data-table', ['rows' => $invoice_schemes ?? []])
    </div></div>
</section>
@endsection
