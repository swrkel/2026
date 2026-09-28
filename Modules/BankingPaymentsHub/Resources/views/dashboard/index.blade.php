@extends('bankingpaymentshub::layouts.master')
@section('banking_payments_content')
<div class="row">
    @foreach($summary as $label => $value)
        <div class="col-md-2 col-sm-6">
            <div class="box box-solid bkg-card">
                <div class="box-body text-center">
                    <div class="bkg-number">{{ number_format($value) }}</div>
                    <div class="bkg-label">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Payments Hub</h3></div>
    <div class="box-body">
        <p>This release is part of BKG-RC-001 Treasury & Payments Hub and is ready for UI route testing.</p>
        <div class="row">
            <div class="col-md-3"><a class="btn btn-block btn-primary" href="{{ route('banking.payments.queue.index') }}">Queue</a></div>
            <div class="col-md-3"><a class="btn btn-block btn-primary" href="{{ route('banking.payments.batches.index') }}">Batches</a></div>
            <div class="col-md-3"><a class="btn btn-block btn-primary" href="{{ route('banking.payments.routing.index') }}">Routing</a></div>
            <div class="col-md-3"><a class="btn btn-block btn-primary" href="{{ route('banking.payments.reports.index') }}">Reports</a></div>
        </div>
    </div>
</div>
@endsection
