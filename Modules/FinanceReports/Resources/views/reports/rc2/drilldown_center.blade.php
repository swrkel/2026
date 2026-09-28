@extends('layouts.app')
@section('title', 'Finance Reports - Drilldown Center')
@section('content')
<section class="content-header"><h1>Drilldown Center - New</h1></section>
<section class="content">
    <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">Read-only drill-down path</h3></div>
        <div class="box-body">
            <ol class="breadcrumb">
                <li>Financial Statement</li><li>Account Group</li><li>Ledger</li><li>Voucher</li><li>Transaction</li>
            </ol>
            <pre>{{ json_encode($trail, JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
</section>
@endsection
