@extends('layouts.app')

@section('title', __('stocktransfernew::cost_reconciliation.title'))

@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-cost-reconciliation.css') }}">
<section class="content-header stn-cr-header">
    <h1>{{ __('stocktransfernew::cost_reconciliation.title') }}</h1>
    <p>{{ __('stocktransfernew::cost_reconciliation.subtitle') }}</p>
</section>

<section class="content stn-cr-page">
    <div class="stn-cr-toolbar box box-solid">
        <form method="GET" action="{{ route('stock-transfer-new.cost-reconciliation.index') }}" class="stn-cr-filter-form">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
            <input type="text" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="Status" class="form-control">
            <button type="submit" class="btn btn-primary">{{ __('stocktransfernew::cost_reconciliation.filter') }}</button>
            <a href="{{ route('stock-transfer-new.cost-reconciliation.export', request()->query()) }}" class="btn btn-success">{{ __('stocktransfernew::cost_reconciliation.export') }}</a>
        </form>
    </div>

    <div class="row stn-cr-cards">
        <div class="col-md-2 col-sm-6"><div class="stn-card"><span>Transfers</span><strong>{{ number_format($summary['transfers']) }}</strong></div></div>
        <div class="col-md-2 col-sm-6"><div class="stn-card"><span>Items</span><strong>{{ number_format($summary['items']) }}</strong></div></div>
        <div class="col-md-3 col-sm-6"><div class="stn-card"><span>Transfer Cost</span><strong>{{ number_format($summary['transfer_cost'], 4) }}</strong></div></div>
        <div class="col-md-3 col-sm-6"><div class="stn-card"><span>Received Cost</span><strong>{{ number_format($summary['received_cost'], 4) }}</strong></div></div>
        <div class="col-md-2 col-sm-6"><div class="stn-card warning"><span>Variance</span><strong>{{ number_format($summary['cost_variance'], 4) }}</strong></div></div>
    </div>

    <div class="box box-solid stn-cr-table-box">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-cr-table">
                <thead>
                    <tr>
                        <th>Date</th><th>Transfer No</th><th>From</th><th>To</th><th>Store</th><th>Items</th><th>Transfer Cost</th><th>Received Cost</th><th>Variance</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->transaction_date }}</td>
                            <td>{{ $row->transfer_no }}</td>
                            <td>{{ $row->from_location_name }}</td>
                            <td>{{ $row->to_location_name }}</td>
                            <td>{{ $row->store_name }}</td>
                            <td class="text-right">{{ number_format($row->item_count) }}</td>
                            <td class="text-right">{{ number_format($row->transfer_cost, 4) }}</td>
                            <td class="text-right">{{ number_format($row->received_cost, 4) }}</td>
                            <td class="text-right {{ $row->cost_variance != 0 ? 'text-red' : '' }}">{{ number_format($row->cost_variance, 4) }}</td>
                            <td><span class="label label-default">{{ ucfirst(str_replace('_', ' ', $row->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">No transfer cost records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
