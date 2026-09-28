@extends('layouts.app')

@section('title', __('stocktransfernew::messages.forecasting'))

@section('content')
<section class="content-header stn037-header">
    <h1>{{ __('stocktransfernew::messages.forecasting') }}</h1>
</section>

<section class="content stn037-page">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="stn037-kpis">
        <div class="stn037-card"><span>Open Forecasts</span><strong>{{ $data['open_forecasts'] }}</strong></div>
        <div class="stn037-card"><span>Critical Suggestions</span><strong>{{ $data['critical_suggestions'] }}</strong></div>
        <div class="stn037-card"><span>Total Suggested Qty</span><strong>{{ number_format($data['total_suggested_qty'], 3) }}</strong></div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">Replenishment Suggestions</h3>
            <div class="box-tools pull-right">
                <form method="POST" action="{{ route('stock-transfer-new.forecasting.generate') }}" style="display:inline-block;">
                    @csrf
                    <button class="btn btn-primary btn-sm">Generate Suggestions</button>
                </form>
                <a href="{{ route('stock-transfer-new.forecasting.export') }}" class="btn btn-success btn-sm">CSV</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn037-table">
                <thead>
                    <tr>
                        <th>Product</th><th>Location</th><th>Store</th><th>Current</th><th>Minimum</th><th>Suggested</th><th>Priority</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['suggestions'] as $row)
                        <tr>
                            <td>{{ $row->product_id }}</td>
                            <td>{{ $row->business_location_id }}</td>
                            <td>{{ $row->store_id }}</td>
                            <td>{{ number_format((float)$row->current_stock, 3) }}</td>
                            <td>{{ number_format((float)$row->minimum_stock, 3) }}</td>
                            <td>{{ number_format((float)$row->suggested_qty, 3) }}</td>
                            <td><span class="stn037-pill stn037-{{ $row->priority }}">{{ ucfirst($row->priority) }}</span></td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td>
                                @if($row->status === 'open')
                                    <form method="POST" action="{{ route('stock-transfer-new.forecasting.suggestions.approve', $row->id) }}" style="display:inline-block;">@csrf<button class="btn btn-xs btn-success">Approve</button></form>
                                    <form method="POST" action="{{ route('stock-transfer-new.forecasting.suggestions.close', $row->id) }}" style="display:inline-block;">@csrf<button class="btn btn-xs btn-default">Close</button></form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">No replenishment suggestions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stn_037.css') }}">
@endpush
@push('javascript')
<script src="{{ asset('modules/stocktransfernew/js/stn_037.js') }}"></script>
@endpush
