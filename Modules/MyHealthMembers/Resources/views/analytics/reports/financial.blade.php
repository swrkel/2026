@extends('layouts.app')
@section('title', 'My Health Financial Analytics')
@section('content')
<section class="content-header"><h1>My Health <small>Financial Analytics</small></h1></section>
<section class="content">
    @include('myhealthmembers::analytics._filters')
    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Financial Summary</h3></div>
        <div class="box-body">
            <table class="table table-bordered table-striped">
                <tr><th>Invoice Total</th><td class="text-right">{{ number_format($analytics['financial']['invoice_total'] ?? 0, 2) }}</td></tr>
                <tr><th>Payment Total</th><td class="text-right">{{ number_format($analytics['financial']['payment_total'] ?? 0, 2) }}</td></tr>
                <tr><th>Claim Settlements</th><td class="text-right">{{ number_format($analytics['financial']['claim_total'] ?? 0, 2) }}</td></tr>
            </table>
        </div>
    </div>
</section>
@endsection
