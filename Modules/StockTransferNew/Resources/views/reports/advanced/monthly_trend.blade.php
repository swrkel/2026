@extends('stocktransfernew::layouts.app')
@section('content')
@php($exportType = 'monthly-trend')
<div class="stnew-page">
    <div class="stnew-page-header"><h1>{{ $title }}</h1></div>
    @include('stocktransfernew::reports.advanced._filters')
    <div class="stnew-card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped stnew-datatable">
                <thead><tr><th>Month</th><th>Transfers</th><th>Requested</th><th>Dispatched</th><th>Received</th><th>Short</th><th>Excess</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr><td>{{ data_get($row, 'transfer_month') }}</td><td>{{ data_get($row, 'transfer_count') }}</td><td>{{ data_get($row, 'requested_qty') }}</td><td>{{ data_get($row, 'dispatched_qty') }}</td><td>{{ data_get($row, 'received_qty') }}</td><td>{{ data_get($row, 'short_qty') }}</td><td>{{ data_get($row, 'excess_qty') }}</td></tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('stocktransfernew::lang.no_records_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ method_exists($rows, 'links') ? $rows->appends(request()->query())->links() : '' }}
    </div>
</div>
@endsection
