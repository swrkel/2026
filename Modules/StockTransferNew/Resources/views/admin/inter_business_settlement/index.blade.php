@extends('layouts.app')
@section('title', __('stocktransfernew::inter_business_settlement.title'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-inter-business-settlement.css') }}">
<section class="content-header stn-ibs-header">
    <h1>{{ __('stocktransfernew::inter_business_settlement.title') }}</h1>
    <p>{{ __('stocktransfernew::inter_business_settlement.subtitle') }}</p>
</section>
<section class="content stn-ibs-page">
    <div class="box box-solid stn-ibs-toolbar">
        <form method="GET" action="{{ route('stock-transfer-new.inter-business-settlement.index') }}" class="stn-ibs-filter-form">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
            <input type="text" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="Status" class="form-control">
            <button class="btn btn-primary">{{ __('stocktransfernew::inter_business_settlement.filter') }}</button>
            <a href="{{ route('stock-transfer-new.inter-business-settlement.create') }}" class="btn btn-success">{{ __('stocktransfernew::inter_business_settlement.create') }}</a>
            <a href="{{ route('stock-transfer-new.inter-business-settlement.export', request()->query()) }}" class="btn btn-info">CSV</a>
        </form>
    </div>
    <div class="row stn-ibs-cards">
        <div class="col-md-3"><div class="stn-card"><span>Settlements</span><strong>{{ number_format($summary['settlements']) }}</strong></div></div>
        <div class="col-md-3"><div class="stn-card"><span>Transfers</span><strong>{{ number_format($summary['transfers']) }}</strong></div></div>
        <div class="col-md-3"><div class="stn-card"><span>Transfer Value</span><strong>{{ number_format($summary['transfer_value'], 4) }}</strong></div></div>
        <div class="col-md-3"><div class="stn-card warning"><span>Variance Value</span><strong>{{ number_format($summary['variance_value'], 4) }}</strong></div></div>
    </div>
    <div class="box box-solid stn-ibs-table-box"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped stn-ibs-table">
            <thead><tr><th>No</th><th>Date</th><th>From Business</th><th>To Business</th><th>Transfers</th><th>Transfer Value</th><th>Variance</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->settlement_no }}</td><td>{{ $row->settlement_date }}</td><td>{{ $row->from_business_name }}</td><td>{{ $row->to_business_name }}</td>
                    <td class="text-right">{{ number_format($row->transfer_count) }}</td><td class="text-right">{{ number_format($row->transfer_value, 4) }}</td><td class="text-right">{{ number_format($row->variance_value, 4) }}</td>
                    <td><span class="label label-default">{{ ucfirst($row->status) }}</span></td><td><a class="btn btn-xs btn-primary" href="{{ route('stock-transfer-new.inter-business-settlement.show', $row->id) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">No inter-business settlement records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</section>
@endsection
